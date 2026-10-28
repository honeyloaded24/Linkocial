<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VtuOrder;
use App\Models\Transaction;
use App\Services\GongozconceptApi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VtuController extends Controller
{
    protected $vtuApi;

    public function __construct(GongozconceptApi $vtuApi)
    {
        $this->vtuApi = $vtuApi;
    }

    // ==========================================
    // AIRTIME
    // ==========================================
    public function airtime()
    {
        return view('vtu.airtime');
    }

    public function purchaseAirtime(Request $request)
    {
        $request->validate([
            'network' => 'required|integer',
            'phone' => 'required|string|min:10',
            'amount' => 'required|numeric|min:50',
        ]);

        $user = Auth::user();
        $baseAmountNgn = (float) $request->amount;
        try {
            $calc = app(\App\Services\ProductPricingService::class)->calculateSellingPrice($baseAmountNgn, \App\Services\ProductPricingService::PRODUCT_VTU);
            $chargedAmountNgn = (float) $calc['selling_price'];
        } catch (\Throwable $e) {
            $markupRate = (float) \App\Models\Setting::get('markup_vtu_airtime', 0);
            $chargedAmountNgn = round($baseAmountNgn * (1 + ($markupRate / 100)), 2);
        }

        DB::beginTransaction();

        try {
            // Atomic conditional deduction directly in NGN
            if (!$user->deductBalance($chargedAmountNgn)) {
                DB::rollBack();
                return back()->with('error', 'Insufficient balance. Please fund your account.');
            }

            // Log Transaction in NGN
            $debitTx = Transaction::add($user->id, $chargedAmountNgn, 'debit', 'vtu_airtime', "₦" . number_format($chargedAmountNgn, 2) . " Airtime top-up for {$request->phone}");

            // Create Order with cost in NGN
            $order = VtuOrder::create([
                'user_id' => $user->id,
                'service_type' => 'airtime',
                'network' => $request->network,
                'phone_or_meter' => $request->phone,
                'plan_or_amount' => $baseAmountNgn,
                'cost' => $chargedAmountNgn,
                'status' => 'processing',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('VTU Airtime error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while processing your request.');
        }

        // Call API with try-catch protection
        try {
            $response = $this->vtuApi->buyAirtime($request->network, $baseAmountNgn, $request->phone);

            if (isset($response['id']) || (isset($response['status']) && $response['status'] == 'successful')) {
                $order->update([
                    'status' => 'completed',
                    'api_reference' => $response['id'] ?? null,
                    'api_response' => json_encode($response),
                ]);
                return back()->with('success', 'Airtime purchased successfully!');
            } else {
                // Handle failure/refund in NGN
                $order->update([
                    'status' => 'refunded',
                    'api_response' => json_encode($response),
                ]);

                // Refund User in NGN
                $user->addBalance($chargedAmountNgn);
                Transaction::add($user->id, $chargedAmountNgn, 'credit', 'vtu_refund', "Refund for failed ₦" . number_format($chargedAmountNgn, 2) . " Airtime top-up", null, $debitTx->created_at ?? null);

                $message = $response['error'][0] ?? ($response['message'] ?? 'Failed to purchase airtime.');
                return back()->with('error', $message);
            }
        } catch (\Throwable $e) {
            Log::error('VTU Airtime API Exception: ' . $e->getMessage());
            $order->update([
                'status' => 'refunded',
                'api_response' => json_encode(['error' => $e->getMessage()]),
            ]);
            $user->addBalance($chargedAmountNgn);
            Transaction::add($user->id, $chargedAmountNgn, 'credit', 'vtu_refund', "Refund for failed ₦" . number_format($chargedAmountNgn, 2) . " Airtime top-up (Connection error)", null, $debitTx->created_at ?? null);
            return back()->with('error', 'Airtime provider is currently unreachable. Your funds have been automatically refunded.');
        }
    }

    // ==========================================
    // DATA
    // ==========================================
    public function data()
    {
        $plansJson = \App\Services\VtuDataService::getPlansJsonWithCustomerPrices();
        return view('vtu.data', compact('plansJson'));
    }

    public function purchaseData(Request $request)
    {
        $request->validate([
            'network' => 'required|integer',
            'phone' => 'required|string|min:10',
            'plan' => 'required|integer',
            'amount' => 'required|numeric', // passed from frontend as customer price
        ]);

        $user = Auth::user();
        
        // Securely resolve the exact customer price on the server (wholesale price + admin markup)
        $verifiedPrice = \App\Services\VtuDataService::getCustomerPrice($request->network, $request->plan);
        $chargedAmountNgn = round($verifiedPrice !== null ? $verifiedPrice : (float) $request->amount, 2);

        DB::beginTransaction();

        try {
            if (!$user->deductBalance($chargedAmountNgn)) {
                DB::rollBack();
                return back()->with('error', 'Insufficient balance. Please fund your account.');
            }

            $debitTx = Transaction::add($user->id, $chargedAmountNgn, 'debit', 'vtu_data', "₦" . number_format($chargedAmountNgn, 2) . " Data purchase for {$request->phone}");

            $order = VtuOrder::create([
                'user_id' => $user->id,
                'service_type' => 'data',
                'network' => $request->network,
                'phone_or_meter' => $request->phone,
                'plan_or_amount' => $request->plan,
                'cost' => $chargedAmountNgn,
                'status' => 'processing',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('VTU Data error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while processing your request.');
        }

        try {
            $response = $this->vtuApi->buyData($request->network, $request->plan, $request->phone);

            if (isset($response['id']) || (isset($response['status']) && $response['status'] == 'successful')) {
                $order->update([
                    'status' => 'completed',
                    'api_reference' => $response['id'] ?? null,
                    'api_response' => json_encode($response),
                ]);
                return back()->with('success', 'Data purchased successfully!');
            } else {
                $order->update([
                    'status' => 'refunded',
                    'api_response' => json_encode($response),
                ]);

                $user->addBalance($chargedAmountNgn);
                Transaction::add($user->id, $chargedAmountNgn, 'credit', 'vtu_refund', "Refund for failed ₦" . number_format($chargedAmountNgn, 2) . " Data purchase", null, $debitTx->created_at ?? null);

                $message = $this->sanitizeDataErrorMessage($response);
                return back()->with('error', $message);
            }
        } catch (\Throwable $e) {
            Log::error('VTU Data API Exception: ' . $e->getMessage());
            $order->update([
                'status' => 'refunded',
                'api_response' => json_encode(['error' => $e->getMessage()]),
            ]);
            $user->addBalance($chargedAmountNgn);
            Transaction::add($user->id, $chargedAmountNgn, 'credit', 'vtu_refund', "Refund for failed ₦" . number_format($chargedAmountNgn, 2) . " Data purchase (Connection error)", null, $debitTx->created_at ?? null);
            return back()->with('error', "We couldn't process your request right now. Please try again shortly.");
        }
    }

    /**
     * Sanitize Gongoz / VTU provider error messages for data subscriptions into neutral customer-facing text.
     * Prevents provider identity (Gongoz), internal technical details, and raw endpoints from leaking.
     * Preserves raw response in protected application logs for developer debugging.
     */
    protected function sanitizeDataErrorMessage($response): string
    {
        $rawMessage = '';

        if (is_array($response)) {
            if (isset($response['error'])) {
                if (is_array($response['error'])) {
                    $first = reset($response['error']);
                    $rawMessage = is_string($first) ? $first : (is_array($first) ? json_encode($first) : (string) $first);
                } elseif (is_string($response['error'])) {
                    $rawMessage = $response['error'];
                }
            } elseif (isset($response['message']) && is_string($response['message'])) {
                $rawMessage = $response['message'];
            } elseif (isset($response['detail']) && is_string($response['detail'])) {
                $rawMessage = $response['detail'];
            } elseif (isset($response['msg']) && is_string($response['msg'])) {
                $rawMessage = $response['msg'];
            } elseif (isset($response['body']) && is_string($response['body'])) {
                $rawMessage = strip_tags($response['body']);
            }
        } elseif (is_string($response)) {
            $rawMessage = $response;
        }

        // Preserve detailed diagnostic information in protected application logs
        Log::warning('Gongoz Data Subscription Provider Error:', [
            'raw_message' => $rawMessage,
            'response' => $response,
            'user_id' => Auth::id(),
        ]);

        $lower = strtolower(trim((string) $rawMessage));

        $defaultUnavailable = 'This data plan is currently unavailable. Please select another plan or try again later.';
        $defaultTryAgain = "We couldn't process your request right now. Please try again shortly.";
        $defaultServiceUnavailable = 'This service is temporarily unavailable. Please try again later.';

        if (empty($lower)) {
            return $defaultUnavailable;
        }

        // 1. Authentication, token, config, provider credentials, or internal server errors
        if (
            str_contains($lower, 'token') ||
            str_contains($lower, 'api key') ||
            str_contains($lower, 'unauthorized') ||
            str_contains($lower, 'configuration') ||
            str_contains($lower, 'settings') ||
            str_contains($lower, 'endpoint') ||
            str_contains($lower, 'exception') ||
            str_contains($lower, 'http') ||
            str_contains($lower, '500') ||
            str_contains($lower, '502') ||
            str_contains($lower, '503')
        ) {
            return $defaultServiceUnavailable;
        }

        // 2. Connectivity, timeout, unreachable or processing errors
        if (
            str_contains($lower, 'connect') ||
            str_contains($lower, 'timeout') ||
            str_contains($lower, 'timed out') ||
            str_contains($lower, 'unreachable') ||
            str_contains($lower, 'temporarily unavailable') ||
            str_contains($lower, 'try again') ||
            str_contains($lower, 'gateway')
        ) {
            return $defaultTryAgain;
        }

        // 3. Provider branding (Gongoz / Gongozconcept), plan availability, SME, or network errors
        // e.g., "GONGOZ SME not available on this network currently"
        if (
            str_contains($lower, 'gongoz') ||
            str_contains($lower, 'not available') ||
            str_contains($lower, 'unavailable') ||
            str_contains($lower, 'out of stock') ||
            str_contains($lower, 'sme') ||
            str_contains($lower, 'plan') ||
            str_contains($lower, 'network')
        ) {
            return $defaultUnavailable;
        }

        // 4. Safe user validation error (e.g. "Invalid phone number", "Please enter a valid mobile number")
        // Ensure no provider names, URLs, brackets, or code symbols exist
        if (
            strlen($rawMessage) <= 120 &&
            !preg_match('/(https?:\/\/|www\.|\.com|\.php|\{|\}|\[|\]|<|>|\/|\\\)/i', $rawMessage) &&
            !preg_match('/\b(gongozconcept|gongoz|api|vtu|disco|smartcard)\b/i', $rawMessage)
        ) {
            return $rawMessage;
        }

        return $defaultUnavailable;
    }

    // ==========================================
    // ELECTRICITY
    // ==========================================
    public function electricity()
    {
        return view('vtu.electricity');
    }

    public function validateMeter(Request $request)
    {
        $request->validate([
            'meter_number' => 'required|string',
            'disco' => 'required|string',
            'meter_type' => 'required|string',
        ]);

        $cleanMeter = preg_replace('/\D/', '', (string) $request->meter_number);
        if (strlen($cleanMeter) < 6) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid meter number with at least 6 digits.'
            ]);
        }

        $response = $this->vtuApi->validateMeter($cleanMeter, $request->disco, $request->meter_type);

        $name = $response['extracted_name'] ?? \App\Services\GongozconceptApi::extractCustomerName($response);

        if ($name) {
            return response()->json([
                'success' => true,
                'name' => $name,
                'address' => $response['extracted_address'] ?? \App\Services\GongozconceptApi::extractAddress($response),
                'detectedMismatch' => $response['detectedMismatch'] ?? false,
                'suggestedMeterType' => $response['suggestedMeterType'] ?? null,
            ]);
        }

        $errMsg = $response['message'] ?? ($response['detail'] ?? ($response['error'] ?? null));
        if (is_array($errMsg)) {
            $errMsg = implode(', ', array_filter($errMsg, 'is_string'));
        }
        if (empty($errMsg)) {
            $errMsg = 'Could not verify meter. Please double-check your provider and meter number.';
        }

        return response()->json([
            'success' => false,
            'message' => $errMsg
        ]);
    }

    public function purchaseElectricity(Request $request)
    {
        $request->validate([
            'disco' => 'required|string',
            'meter_number' => 'required|string',
            'meter_type' => 'required|string',
            'amount' => 'required|numeric|min:500',
            'phone' => 'required|string',
        ]);

        $user = Auth::user();
        $baseAmountNgn = (float) $request->amount;
        try {
            $calc = app(\App\Services\ProductPricingService::class)->calculateSellingPrice($baseAmountNgn, \App\Services\ProductPricingService::PRODUCT_VTU);
            $chargedAmountNgn = (float) $calc['selling_price'];
        } catch (\Throwable $e) {
            $markupRate = (float) \App\Models\Setting::get('markup_vtu_electricity', 0);
            $chargedAmountNgn = round($baseAmountNgn * (1 + ($markupRate / 100)), 2);
        }

        DB::beginTransaction();

        try {
            if (!$user->deductBalance($chargedAmountNgn)) {
                DB::rollBack();
                return back()->with('error', 'Insufficient balance. Please fund your account.');
            }

            $debitTx = Transaction::add($user->id, $chargedAmountNgn, 'debit', 'vtu_electricity', "₦" . number_format($chargedAmountNgn, 2) . " Electricity payment for meter {$request->meter_number}");

            $order = VtuOrder::create([
                'user_id' => $user->id,
                'service_type' => 'electricity',
                'network' => $request->disco,
                'phone_or_meter' => $request->meter_number,
                'plan_or_amount' => $baseAmountNgn,
                'cost' => $chargedAmountNgn,
                'status' => 'processing',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('VTU Electricity error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while processing your request.');
        }
        // 3. Purchase Electricity via API with try-catch protection
        try {
            $response = $this->vtuApi->buyElectricity($request->disco, $baseAmountNgn, $request->meter_number, $request->meter_type, $request->phone);

            if (isset($response['id']) || (isset($response['status']) && $response['status'] == 'successful')) {
                $token = $response['token'] ?? 'Check history or SMS';
                
                $order->update([
                    'status' => 'completed',
                    'api_reference' => $response['id'] ?? null,
                    'api_response' => json_encode($response),
                ]);
                
                return back()->with('success', "Electricity payment successful! Token: $token");
            } else {
                $order->update([
                    'status' => 'refunded',
                    'api_response' => json_encode($response),
                ]);
                $user->addBalance($chargedAmountNgn);
                Transaction::add($user->id, $chargedAmountNgn, 'credit', 'vtu_refund', "Refund for failed ₦" . number_format($chargedAmountNgn, 2) . " Electricity payment", null, $debitTx->created_at ?? null);

                $message = $response['error'][0] ?? ($response['message'] ?? 'Failed to process electricity payment.');
                return back()->with('error', $message);
            }
        } catch (\Throwable $e) {
            Log::error('VTU Electricity API Exception: ' . $e->getMessage());
            $order->update([
                'status' => 'refunded',
                'api_response' => json_encode(['error' => $e->getMessage()]),
            ]);
            $user->addBalance($chargedAmountNgn);
            Transaction::add($user->id, $chargedAmountNgn, 'credit', 'vtu_refund', "Refund for failed ₦" . number_format($chargedAmountNgn, 2) . " Electricity payment (Connection error)", null, $debitTx->created_at ?? null);
            return back()->with('error', 'Electricity provider is currently unreachable. Your funds have been automatically refunded.');
        }
    }


    // ==========================================
    // CABLE TV
    // ==========================================
    public function cable()
    {
        return view('vtu.cable');
    }

    public function validateIUC(Request $request)
    {
        $request->validate([
            'smart_card_number' => 'required',
            'cablename' => 'required',
        ]);

        try {
            $response = $this->vtuApi->validateIUC($request->smart_card_number, $request->cablename);

            $name = $response['name'] ?? ($response['Customer_Name'] ?? ($response['customer_name'] ?? null));
            if (!empty($name) && strtolower($name) !== 'invalid' && strtolower($name) !== 'null') {
                return response()->json([
                    'success' => true,
                    'name' => $name,
                ]);
            }

            $message = $response['message'] ?? ($response['error'][0] ?? ($response['error'] ?? 'Invalid Smart Card number or could not validate.'));
            return response()->json([
                'success' => false,
                'message' => is_string($message) ? $message : 'Invalid Smart Card number or could not validate.'
            ]);
        } catch (\Exception $e) {
            Log::error('VTU Validate IUC error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to validate Smart Card right now. Please check the number and try again.'
            ]);
        }
    }

    public function purchaseCable(Request $request)
    {
        $request->validate([
            'cablename' => 'required',
            'cableplan' => 'required',
            'smart_card_number' => 'required|string',
        ]);

        $user = Auth::user();

        // Secure server-side pricing verification (prevents client-side price tampering)
        $verifiedPrice = \App\Services\VtuCableService::getCustomerPrice($request->cablename, $request->cableplan);
        if ($verifiedPrice === null || $verifiedPrice <= 0) {
            return back()->with('error', 'The selected cable package is invalid or currently unavailable. Please reselect your plan.');
        }

        $chargedAmountNgn = round((float) $verifiedPrice, 2);
        $baseAmountNgn = \App\Services\VtuCableService::getPlanBasePrice($request->cablename, $request->cableplan) ?? $verifiedPrice;

        DB::beginTransaction();

        try {
            if (!$user->deductBalance($chargedAmountNgn)) {
                DB::rollBack();
                return back()->with('error', 'Insufficient balance. Please fund your account.');
            }

            $debitTx = Transaction::add($user->id, $chargedAmountNgn, 'debit', 'vtu_cable', "₦" . number_format($chargedAmountNgn, 2) . " Cable TV subscription for {$request->smart_card_number}");

            $order = VtuOrder::create([
                'user_id' => $user->id,
                'service_type' => 'cable',
                'network' => $request->cablename,
                'phone_or_meter' => $request->smart_card_number,
                'plan_or_amount' => $baseAmountNgn,
                'cost' => $chargedAmountNgn,
                'status' => 'processing',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('VTU Cable error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while processing your request.');
        }

        try {
            $response = $this->vtuApi->buyCable($request->cablename, $request->cableplan, $request->smart_card_number);

            if (isset($response['id']) || (isset($response['status']) && $response['status'] == 'successful')) {
                $order->update([
                    'status' => 'completed',
                    'api_reference' => $response['id'] ?? null,
                    'api_response' => json_encode($response),
                ]);
                
                return back()->with('success', "Cable subscription successful!");
            } else {
                $order->update([
                    'status' => 'refunded',
                    'api_response' => json_encode($response),
                ]);
                
                $user->addBalance($chargedAmountNgn);
                Transaction::add($user->id, $chargedAmountNgn, 'credit', 'vtu_refund', "Refund for failed ₦" . number_format($chargedAmountNgn, 2) . " Cable subscription", null, $debitTx->created_at ?? null);

                $message = $response['error'][0] ?? ($response['message'] ?? 'Failed to process cable subscription.');
                return back()->with('error', $message);
            }
        } catch (\Throwable $e) {
            Log::error('VTU Cable API Exception: ' . $e->getMessage());
            $order->update([
                'status' => 'refunded',
                'api_response' => json_encode(['error' => $e->getMessage()]),
            ]);
            $user->addBalance($chargedAmountNgn);
            Transaction::add($user->id, $chargedAmountNgn, 'credit', 'vtu_refund', "Refund for failed ₦" . number_format($chargedAmountNgn, 2) . " Cable subscription (Connection error)", null, $debitTx->created_at ?? null);
            return back()->with('error', 'Cable provider is currently unreachable. Your funds have been automatically refunded.');
        }
    }

    // ==========================================
    // ORDER HISTORY
    // ==========================================
    public function history(Request $request)
    {
        $orders = VtuOrder::where('user_id', Auth::id())
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('vtu.history', compact('orders'));
    }
}

