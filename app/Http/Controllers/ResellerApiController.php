<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Service;
use App\Models\Order;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ResellerApiController extends Controller
{
    /**
     * Handle incoming SMM Reseller API requests (Standard SMM protocol v1/v2).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Support CORS preflight OPTIONS requests immediately
        if ($request->isMethod('options')) {
            return $this->corsResponse(response()->json(['status' => 'ok']));
        }

        try {
            // 2. Extract and sanitize API Key & Action
            $apiKey = trim((string) (
                $request->input('key') ??
                $request->input('api_key') ??
                $request->bearerToken() ??
                $request->query('key') ??
                $request->query('api_key')
            ));

            $action = strtolower(trim((string) (
                $request->input('action') ??
                $request->query('action')
            )));

            // 3. Validate presence of Action
            if (empty($action)) {
                return $this->corsResponse(response()->json([
                    'error' => 'Action parameter is required (e.g. services, add, status, orders, balance).'
                ], 400));
            }

            // 4. Validate API Key & Authenticate User
            if (empty($apiKey)) {
                return $this->corsResponse(response()->json([
                    'error' => 'API key is required.'
                ], 401));
            }

            $user = User::where('api_key', $apiKey)->first();

            if (!$user) {
                return $this->corsResponse(response()->json([
                    'error' => 'Invalid API key.'
                ], 401));
            }

            if (!$user->is_active) {
                return $this->corsResponse(response()->json([
                    'error' => 'Account is suspended or inactive.'
                ], 403));
            }

            if (!$user->hasVerifiedEmail()) {
                return $this->corsResponse(response()->json([
                    'error' => 'Email verification required before accessing API services.'
                ], 403));
            }

            // 5. Route to appropriate SMM protocol action
            switch ($action) {
                case 'services':
                    return $this->services();

                case 'balance':
                    return $this->balance($user);

                case 'add':
                    return $this->addOrder($request, $user);

                case 'status':
                    // If multiple orders parameter is present, delegate to multi-order status
                    if ($request->filled('orders') || is_array($request->input('orders'))) {
                        return $this->multiStatus($request, $user);
                    }
                    return $this->singleStatus($request, $user);

                case 'orders':
                    return $this->multiStatus($request, $user);

                case 'refill':
                case 'refill_status':
                    return $this->corsResponse(response()->json([
                        'error' => 'Refill is not supported for this service.'
                    ], 400));

                case 'cancel':
                    return $this->corsResponse(response()->json([
                        'error' => 'Cancellation is not supported via API for this order.'
                    ], 400));

                default:
                    return $this->corsResponse(response()->json([
                        'error' => "Unsupported action '{$action}'. Supported actions: services, add, status, orders, balance."
                    ], 400));
            }
        } catch (\Throwable $e) {
            Log::error('Reseller API Unhandled Exception: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->corsResponse(response()->json([
                'error' => 'An internal server error occurred while processing your API request. Please contact support if this persists.'
            ], 500));
        }
    }

    /**
     * Action: services
     * Retrieve full list of active SMM services in standard SMM API format.
     */
    private function services(): JsonResponse
    {
        $services = Service::where('is_active', true)
            ->get()
            ->map(function ($s) {
                return [
                    'service'     => (int) $s->id,
                    'name'        => (string) $s->name,
                    'type'        => (string) ($s->type ?: 'Default'),
                    'category'    => (string) $s->category,
                    'rate'        => (string) number_format((float) $s->selling_price, 2, '.', ''),
                    'min'         => (string) ($s->min ?? 1),
                    'max'         => (string) ($s->max ?? 100000),
                    'dripfeed'    => false,
                    'refill'      => false,
                    'cancel'      => false,
                    'description' => (string) ($s->description ?? ''),
                ];
            });

        return $this->corsResponse(response()->json($services));
    }

    /**
     * Action: balance
     * Return user account balance in NGN.
     */
    private function balance(User $user): JsonResponse
    {
        return $this->corsResponse(response()->json([
            'balance'  => (string) number_format((float) $user->balance, 2, '.', ''),
            'currency' => 'NGN',
        ]));
    }

    /**
     * Action: add
     * Place a new order on behalf of the authenticated API user.
     */
    private function addOrder(Request $request, User $user): JsonResponse
    {
        $serviceId = $request->input('service') ?? $request->input('service_id');
        $link = trim((string) $request->input('link'));
        $quantity = $request->input('quantity');

        // Validation for order payload
        if (empty($serviceId)) {
            return $this->corsResponse(response()->json(['error' => 'Service ID is required.'], 400));
        }

        if (empty($link)) {
            return $this->corsResponse(response()->json(['error' => 'Target link or URL is required.'], 400));
        }

        if (!is_numeric($quantity) || (int) $quantity < 1) {
            return $this->corsResponse(response()->json(['error' => 'Quantity must be a positive integer.'], 400));
        }

        // Mock a JSON request to reuse OrderController core logic
        $orderRequest = new Request();
        $orderRequest->headers->set('Accept', 'application/json');
        $orderRequest->setUserResolver(fn() => $user);
        $orderRequest->replace([
            'service_id' => (int) $serviceId,
            'link'       => $link,
            'quantity'   => (int) $quantity,
            'runs'       => $request->input('runs'),
            'interval'   => $request->input('interval'),
        ]);

        Auth::login($user);

        try {
            $orderController = app(OrderController::class);
            $response = $orderController->store($orderRequest);

            if ($response instanceof JsonResponse) {
                $data = $response->getData(true);

                if (isset($data['error'])) {
                    $status = $response->status() >= 400 ? $response->status() : 400;
                    return $this->corsResponse(response()->json(['error' => $data['error']], $status));
                }

                if (isset($data['order']['id'])) {
                    return $this->corsResponse(response()->json(['order' => (int) $data['order']['id']]));
                }

                return $this->corsResponse($response);
            }

            // Fallback for redirected responses
            $content = json_decode($response->getContent(), true);
            if (isset($content['error'])) {
                return $this->corsResponse(response()->json(['error' => $content['error']], 400));
            }
            if (isset($content['order']['id'])) {
                return $this->corsResponse(response()->json(['order' => (int) $content['order']['id']]));
            }

            return $this->corsResponse(response()->json(['error' => 'Unable to process order.'], 400));
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?? 'Validation failed.';
            return $this->corsResponse(response()->json(['error' => $firstError], 422));
        } catch (\Throwable $e) {
            Log::error('Reseller API addOrder error: ' . $e->getMessage());
            return $this->corsResponse(response()->json(['error' => 'Order placement failed: ' . $e->getMessage()], 400));
        }
    }

    /**
     * Action: status (Single Order)
     */
    private function singleStatus(Request $request, User $user): JsonResponse
    {
        $orderId = trim((string) (
            $request->input('order') ??
            $request->query('order') ??
            $request->input('order_id')
        ));

        if (empty($orderId)) {
            return $this->corsResponse(response()->json(['error' => 'Order ID required.'], 400));
        }

        $order = Order::where('user_id', $user->id)->find($orderId);

        if (!$order) {
            return $this->corsResponse(response()->json(['error' => 'Incorrect order ID'], 404));
        }

        return $this->corsResponse(response()->json([
            'charge'      => (string) number_format((float) $order->charge, 4, '.', ''),
            'start_count' => (string) ($order->start_count ?? 0),
            'status'      => ucfirst($order->status),
            'remains'     => (string) ($order->remains ?? 0),
            'currency'    => 'NGN',
        ]));
    }

    /**
     * Action: status / orders (Multiple Orders Batch)
     * Standard SMM format: returns a key-value object of order_id => status array
     */
    private function multiStatus(Request $request, User $user): JsonResponse
    {
        $ordersRaw = $request->input('orders') ?? $request->query('orders');

        if (empty($ordersRaw)) {
            return $this->corsResponse(response()->json(['error' => 'Orders parameter required.'], 400));
        }

        $orderIds = is_array($ordersRaw)
            ? $ordersRaw
            : array_filter(array_map('trim', explode(',', (string) $ordersRaw)));

        if (empty($orderIds)) {
            return $this->corsResponse(response()->json(['error' => 'No valid order IDs provided.'], 400));
        }

        // Limit batch size to 100 orders per request to prevent overload
        $orderIds = array_slice($orderIds, 0, 100);

        $orders = Order::where('user_id', $user->id)
            ->whereIn('id', $orderIds)
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($orderIds as $id) {
            if ($orders->has($id)) {
                $order = $orders->get($id);
                $result[$id] = [
                    'charge'      => (string) number_format((float) $order->charge, 4, '.', ''),
                    'start_count' => (string) ($order->start_count ?? 0),
                    'status'      => ucfirst($order->status),
                    'remains'     => (string) ($order->remains ?? 0),
                    'currency'    => 'NGN',
                ];
            } else {
                $result[$id] = [
                    'error' => 'Incorrect order ID',
                ];
            }
        }

        return $this->corsResponse(response()->json($result));
    }

    /**
     * Attach standard CORS and caching headers to API responses.
     */
    private function corsResponse(JsonResponse $response): JsonResponse
    {
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
            ->header('Cache-Control', 'no-cache, private');
    }
}
