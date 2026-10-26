<?php

namespace App\Services;

use App\Models\Order;
use App\Models\VirtualNumberOrder;
use App\Models\VtuOrder;
use App\Models\LogOrder;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Class FinancialMetricsService
 *
 * Authoritative financial calculation service for Linkocial.com.
 * Centralizes the auditing and calculation of:
 *  1. Customer Total Spent
 *  2. Customer Total Deposits
 *  3. Admin Management Financial Performance
 *  4. Admin Management Total Profit & Growth Trends
 */
class FinancialMetricsService
{
    protected CurrencyConversionService $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Get the authoritative USD to NGN exchange rate for provider cost conversions.
     */
    public function getExchangeRate(): float
    {
        return $this->currencyService->getUsdToNgnRate();
    }

    /**
     * Calculate authoritative Total Spent (in NGN) for a specific user across all product categories.
     *
     * Only completed and delivered portions of customer-funded orders are included.
     * Excludes pending, processing, canceled, failed, and refunded transactions.
     *
     * @param int|string $userId
     * @return float
     */
    public function getUserTotalSpent($userId): float
    {
        $userId = (int) $userId;

        $smmSpent = $this->getSmmSpent($userId);
        $vnSpent = $this->getVirtualNumberSpent($userId);
        $vtuSpent = $this->getVtuSpent($userId);
        $logSpent = $this->getLogSpent($userId);

        return round($smmSpent + $vnSpent + $vtuSpent + $logSpent, 2);
    }

    /**
     * Calculate Total Spent (in NGN) for a user within a specific date range (e.g. weekly growth tracking).
     *
     * @param int|string $userId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return float
     */
    public function getUserSpentPeriod($userId, ?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $userId = (int) $userId;

        $smmSpent = $this->getSmmSpent($userId, $startDate, $endDate);
        $vnSpent = $this->getVirtualNumberSpent($userId, $startDate, $endDate);
        $vtuSpent = $this->getVtuSpent($userId, $startDate, $endDate);
        $logSpent = $this->getLogSpent($userId, $startDate, $endDate);

        return round($smmSpent + $vnSpent + $vtuSpent + $logSpent, 2);
    }

    /**
     * Calculate authoritative Total Deposits (in NGN) for a specific user.
     *
     * Counts qualifying successful external deposits and admin adjustments.
     * Excludes pending and failed payments.
     *
     * @param int|string $userId
     * @return float
     */
    public function getUserTotalDeposits($userId): float
    {
        $userId = (int) $userId;

        $total = Payment::where('user_id', $userId)
            ->where('status', 'success')
            ->sum('amount');

        return round((float) $total, 2);
    }

    /**
     * Calculate Admin Financial Performance summary metrics across the platform.
     *
     * All metrics denominated in Nigerian Naira (NGN).
     * Converts foreign provider wholesale costs (USD) using the authoritative exchange rate.
     *
     * @return array
     */
    public function getAdminFinancialPerformance(): array
    {
        $fxRate = $this->getExchangeRate();

        // 1. Lifetime Successful Deposits
        $totalDeposited = round((float) Payment::where('status', 'success')->sum('amount'), 2);

        // 2. SMM Spent & Provider Cost
        $smmMetrics = $this->getSmmPlatformMetrics();
        $smmSpent = $smmMetrics['spent'];
        $smmCostUsd = $smmMetrics['cost_usd'];
        $smmCostNgn = round($smmCostUsd * $fxRate, 2);

        // 3. Virtual Numbers Spent & Provider Cost (SMSPool / Grizzly wholesale in USD converted to NGN)
        $vnSpent = round((float) VirtualNumberOrder::whereIn('status', ['received', 'finished'])->sum('cost'), 2);
        $vnCostUsd = (float) VirtualNumberOrder::whereIn('status', ['received', 'finished'])->sum('provider_cost');
        $vnCostNgn = round($vnCostUsd * $fxRate, 2);

        // 4. VTU Spent & Provider Cost
        $vtuSpent = round((float) VtuOrder::where('status', 'completed')->sum('cost'), 2);
        $vtuCostNgn = 0.0; // Direct provider cost is unrecorded in existing schema

        // 5. Logs Marketplace Spent & Provider Cost
        $logSpent = round((float) LogOrder::where('status', 'completed')->sum('total_cost'), 2);
        $logCostNgn = round((float) (LogOrder::where('log_orders.status', 'completed')
            ->join('log_products', 'log_orders.log_product_id', '=', 'log_products.id')
            ->sum(DB::raw('log_orders.quantity * coalesce(log_products.base_api_price, 0)')) ?? 0.0), 2);

        // Aggregations
        $totalSpent = round($smmSpent + $vnSpent + $vtuSpent + $logSpent, 2);
        $totalCost = round($smmCostNgn + $vnCostNgn + $vtuCostNgn + $logCostNgn, 2);
        $totalProfit = round($totalSpent - $totalCost, 2);

        return [
            'totalDeposited' => $totalDeposited,
            'smmSpent'       => $smmSpent,
            'smmCost'        => $smmCostNgn,
            'vnSpent'        => $vnSpent,
            'vnCost'         => $vnCostNgn,
            'vtuSpent'       => $vtuSpent,
            'vtuCost'        => $vtuCostNgn,
            'logSpent'       => $logSpent,
            'logCost'        => $logCostNgn,
            'totalSpent'     => $totalSpent,
            'totalCost'      => $totalCost,
            'totalProfit'    => $totalProfit,
            'exchangeRate'   => $fxRate,
        ];
    }

    /**
     * Compute 30-day Chart Data for Admin Dashboard.
     *
     * Accurately converts provider costs into NGN for daily profit trends.
     * Excludes pending/unfulfilled orders from profit recognition.
     *
     * @return array
     */
    public function getAdmin30DayChartData(): array
    {
        return Cache::remember('admin_dashboard_chart_v3', 300, function () {
            $startDate = now()->subDays(29)->startOfDay();
            $fxRate = $this->getExchangeRate();

            // SMM daily orders and profit in NGN (completed & partial non-refunded orders only)
            $smmStats = Order::where('created_at', '>=', $startDate)
                ->whereIn('status', ['completed', 'partial'])
                ->where('is_refunded', false)
                ->selectRaw("
                    DATE(created_at) as date, 
                    count(*) as count, 
                    sum(
                        CASE 
                            WHEN status = 'completed' THEN (charge - (coalesce(provider_cost, 0) * {$fxRate}))
                            WHEN status = 'partial' AND remains IS NOT NULL AND remains > 0 AND quantity > 0 AND remains < quantity 
                            THEN ((charge - (coalesce(provider_cost, 0) * {$fxRate})) * 1.0 * (quantity - remains) / quantity)
                            ELSE (charge - (coalesce(provider_cost, 0) * {$fxRate}))
                        END
                    ) as profit
                ")
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            // Virtual Numbers daily orders and profit in NGN (fulfilled received/finished only)
            $vnStats = VirtualNumberOrder::where('created_at', '>=', $startDate)
                ->whereIn('status', ['received', 'finished'])
                ->selectRaw("DATE(created_at) as date, count(*) as count, sum(cost - (coalesce(provider_cost, 0) * {$fxRate})) as profit")
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            // VTU orders counts (completed only)
            $vtuCounts = VtuOrder::where('created_at', '>=', $startDate)
                ->where('status', 'completed')
                ->selectRaw("DATE(created_at) as date, count(*) as count")
                ->groupBy('date')
                ->pluck('count', 'date');

            // Log orders counts (completed only)
            $logCounts = LogOrder::where('created_at', '>=', $startDate)
                ->where('status', 'completed')
                ->selectRaw("DATE(created_at) as date, count(*) as count")
                ->groupBy('date')
                ->pluck('count', 'date');

            // User registrations
            $userCounts = User::where('created_at', '>=', $startDate)
                ->selectRaw("DATE(created_at) as date, count(*) as count")
                ->groupBy('date')
                ->pluck('count', 'date');

            $days = [];
            $orderData = [];
            $profitData = [];
            $userData = [];

            for ($i = 29; $i >= 0; $i--) {
                $targetDate = now()->subDays($i);
                $dateKey = $targetDate->format('Y-m-d');
                $days[] = $targetDate->format('M d');

                $smmCount = (int) ($smmStats[$dateKey]->count ?? 0);
                $smmProfit = (float) ($smmStats[$dateKey]->profit ?? 0);
                $vnCount = (int) ($vnStats[$dateKey]->count ?? 0);
                $vnProfit = (float) ($vnStats[$dateKey]->profit ?? 0);
                $vtuCount = (int) ($vtuCounts[$dateKey] ?? 0);
                $logCount = (int) ($logCounts[$dateKey] ?? 0);

                $orderData[] = $smmCount + $vnCount + $vtuCount + $logCount;
                $profitData[] = round($smmProfit + $vnProfit, 2);
                $userData[] = (int) ($userCounts[$dateKey] ?? 0);
            }

            return [
                'labels'  => $days,
                'orders'  => $orderData,
                'profits' => $profitData,
                'users'   => $userData,
            ];
        });
    }

    // =========================================================================
    // INTERNAL QUERY HELPERS
    // =========================================================================

    /**
     * Compute completed customer spent on SMM orders.
     * Correctly handles completed orders and fulfilled portions of partial orders.
     */
    protected function getSmmSpent(?int $userId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = Order::query();

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($startDate !== null && $endDate !== null) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate !== null) {
            $query->where('created_at', '>=', $startDate);
        }

        // Single SQL expression handles completed orders and partial delivered portions
        $result = $query->selectRaw("
            COALESCE(SUM(
                CASE 
                    WHEN status = 'completed' AND is_refunded = 0 THEN charge
                    WHEN status = 'partial' THEN 
                        CASE 
                            WHEN remains IS NOT NULL AND remains > 0 AND quantity > 0 AND remains < quantity 
                            THEN (charge * 1.0 * (quantity - remains) / quantity)
                            WHEN is_refunded = 0 THEN charge
                            ELSE 0 
                        END
                    ELSE 0 
                END
            ), 0) as eligible_spent
        ")->value('eligible_spent');

        return round((float) $result, 2);
    }

    /**
     * Compute platform-wide SMM spent (in NGN) and provider cost (in USD).
     */
    protected function getSmmPlatformMetrics(): array
    {
        $result = Order::selectRaw("
            COALESCE(SUM(
                CASE 
                    WHEN status = 'completed' AND is_refunded = 0 THEN charge
                    WHEN status = 'partial' THEN 
                        CASE 
                            WHEN remains IS NOT NULL AND remains > 0 AND quantity > 0 AND remains < quantity 
                            THEN (charge * 1.0 * (quantity - remains) / quantity)
                            WHEN is_refunded = 0 THEN charge
                            ELSE 0 
                        END
                    ELSE 0 
                END
            ), 0) as eligible_spent,
            COALESCE(SUM(
                CASE 
                    WHEN status = 'completed' AND is_refunded = 0 AND provider_cost IS NOT NULL THEN provider_cost
                    WHEN status = 'partial' AND provider_cost IS NOT NULL THEN 
                        CASE 
                            WHEN remains IS NOT NULL AND remains > 0 AND quantity > 0 AND remains < quantity 
                            THEN (provider_cost * 1.0 * (quantity - remains) / quantity)
                            WHEN is_refunded = 0 THEN provider_cost
                            ELSE 0 
                        END
                    ELSE 0 
                END
            ), 0) as eligible_cost_usd
        ")->first();

        return [
            'spent'    => round((float) ($result->eligible_spent ?? 0.0), 2),
            'cost_usd' => (float) ($result->eligible_cost_usd ?? 0.0),
        ];
    }

    /**
     * Compute Virtual Number customer spent in NGN.
     */
    protected function getVirtualNumberSpent(?int $userId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = VirtualNumberOrder::whereIn('status', ['received', 'finished']);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($startDate !== null && $endDate !== null) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate !== null) {
            $query->where('created_at', '>=', $startDate);
        }

        return round((float) $query->sum('cost'), 2);
    }

    /**
     * Compute VTU customer spent in NGN.
     */
    protected function getVtuSpent(?int $userId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = VtuOrder::where('status', 'completed');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($startDate !== null && $endDate !== null) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate !== null) {
            $query->where('created_at', '>=', $startDate);
        }

        return round((float) $query->sum('cost'), 2);
    }

    /**
     * Compute Log Marketplace customer spent in NGN.
     */
    protected function getLogSpent(?int $userId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = LogOrder::where('status', 'completed');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($startDate !== null && $endDate !== null) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate !== null) {
            $query->where('created_at', '>=', $startDate);
        }

        return round((float) $query->sum('total_cost'), 2);
    }

    // =========================================================================
    // PRODUCT-LEVEL FINANCIAL RECORD SUMMARIES
    // =========================================================================

    /**
     * Get SMM (Social Boosting) product metrics and status breakdown.
     */
    public function getSmmSummary(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $fxRate = $this->getExchangeRate();
        $baseQuery = Order::query();
        if ($startDate !== null && $endDate !== null) {
            $baseQuery->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate !== null) {
            $baseQuery->where('created_at', '>=', $startDate);
        }

        $counts = [
            'total'       => (clone $baseQuery)->count(),
            'completed'   => (clone $baseQuery)->where('status', 'completed')->where('is_refunded', false)->count(),
            'pending'     => (clone $baseQuery)->where('status', 'pending')->count(),
            'processing'  => (clone $baseQuery)->whereIn('status', ['processing', 'in_progress'])->count(),
            'partial'     => (clone $baseQuery)->where('status', 'partial')->count(),
            'failed'      => (clone $baseQuery)->whereIn('status', ['failed', 'canceled'])->count(),
        ];

        $financials = (clone $baseQuery)->selectRaw("
            COALESCE(SUM(
                CASE 
                    WHEN status = 'completed' AND is_refunded = 0 THEN charge
                    WHEN status = 'partial' THEN 
                        CASE 
                            WHEN remains IS NOT NULL AND remains > 0 AND quantity > 0 AND remains < quantity 
                            THEN (charge * 1.0 * (quantity - remains) / quantity)
                            WHEN is_refunded = 0 THEN charge
                            ELSE 0 
                        END
                    ELSE 0 
                END
            ), 0) as eligible_revenue,
            COALESCE(SUM(
                CASE 
                    WHEN status = 'completed' AND is_refunded = 0 AND provider_cost IS NOT NULL THEN provider_cost
                    WHEN status = 'partial' AND provider_cost IS NOT NULL THEN 
                        CASE 
                            WHEN remains IS NOT NULL AND remains > 0 AND quantity > 0 AND remains < quantity 
                            THEN (provider_cost * 1.0 * (quantity - remains) / quantity)
                            WHEN is_refunded = 0 THEN provider_cost
                            ELSE 0 
                        END
                    ELSE 0 
                END
            ), 0) as eligible_cost_usd
        ")->first();

        $revenue = round((float) ($financials->eligible_revenue ?? 0.0), 2);
        $costUsd = (float) ($financials->eligible_cost_usd ?? 0.0);
        $costNgn = round($costUsd * $fxRate, 2);
        $profit = round($revenue - $costNgn, 2);

        return array_merge($counts, [
            'revenue'          => $revenue,
            'provider_cost'    => $costNgn,
            'profit'           => $profit,
            'exchange_rate'    => $fxRate,
            'cost_is_recorded' => true,
        ]);
    }

    /**
     * Get Virtual Number product metrics and status breakdown.
     */
    public function getVirtualNumberSummary(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $fxRate = $this->getExchangeRate();
        $baseQuery = VirtualNumberOrder::query();
        if ($startDate !== null && $endDate !== null) {
            $baseQuery->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate !== null) {
            $baseQuery->where('created_at', '>=', $startDate);
        }

        $counts = [
            'total'     => (clone $baseQuery)->count(),
            'completed' => (clone $baseQuery)->whereIn('status', ['received', 'finished'])->count(),
            'pending'   => (clone $baseQuery)->where('status', 'pending')->count(),
            'failed'    => (clone $baseQuery)->where('status', 'canceled')->count(),
        ];

        $revenue = round((float) (clone $baseQuery)->whereIn('status', ['received', 'finished'])->sum('cost'), 2);
        $costUsd = (float) (clone $baseQuery)->whereIn('status', ['received', 'finished'])->sum('provider_cost');
        $costNgn = round($costUsd * $fxRate, 2);
        $profit = round($revenue - $costNgn, 2);

        return array_merge($counts, [
            'revenue'          => $revenue,
            'provider_cost'    => $costNgn,
            'profit'           => $profit,
            'exchange_rate'    => $fxRate,
            'cost_is_recorded' => true,
        ]);
    }

    /**
     * Get VTU product metrics and status breakdown.
     */
    public function getVtuSummary(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $baseQuery = VtuOrder::query();
        if ($startDate !== null && $endDate !== null) {
            $baseQuery->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate !== null) {
            $baseQuery->where('created_at', '>=', $startDate);
        }

        $counts = [
            'total'     => (clone $baseQuery)->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'pending'   => (clone $baseQuery)->whereIn('status', ['pending', 'processing'])->count(),
            'failed'    => (clone $baseQuery)->whereIn('status', ['failed', 'refunded'])->count(),
        ];

        $revenue = round((float) (clone $baseQuery)->where('status', 'completed')->sum('cost'), 2);
        $providerCost = null; // Unrecorded in existing schema
        $profit = $revenue; // Tracked customer revenue; wholesale provider cost is unrecorded

        return array_merge($counts, [
            'revenue'          => $revenue,
            'provider_cost'    => $providerCost,
            'profit'           => $profit,
            'cost_is_recorded' => false,
        ]);
    }

    /**
     * Get Logs Marketplace product metrics and status breakdown.
     */
    public function getLogSummary(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $baseQuery = LogOrder::query();
        if ($startDate !== null && $endDate !== null) {
            $baseQuery->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate !== null) {
            $baseQuery->where('created_at', '>=', $startDate);
        }

        $counts = [
            'total'     => (clone $baseQuery)->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'pending'   => (clone $baseQuery)->where('status', 'pending')->count(),
            'failed'    => (clone $baseQuery)->whereIn('status', ['failed', 'refunded'])->count(),
        ];

        $revenue = round((float) (clone $baseQuery)->where('status', 'completed')->sum('total_cost'), 2);
        
        $costQuery = (clone $baseQuery)->where('log_orders.status', 'completed')
            ->join('log_products', 'log_orders.log_product_id', '=', 'log_products.id');
        $cost = round((float) ($costQuery->sum(DB::raw('log_orders.quantity * coalesce(log_products.base_api_price, 0)')) ?? 0.0), 2);
        $profit = round($revenue - $cost, 2);

        return array_merge($counts, [
            'revenue'          => $revenue,
            'provider_cost'    => $cost,
            'profit'           => $profit,
            'cost_is_recorded' => true,
        ]);
    }
}
