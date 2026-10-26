<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Service;
use App\Models\Order;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResellerApiController extends Controller
{
    public function handle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|exists:users,api_key',
            'action' => 'required|in:services,add,status,balance',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid API key or action.'], 401);
        }

        $user = User::where('api_key', $request->key)->first();
        if (!$user || !$user->is_active) {
            return response()->json(['error' => 'Account is suspended or invalid API key.'], 403);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json(['error' => 'Email verification required before accessing API services.'], 403);
        }

        switch ($request->action) {
            case 'services':
                return $this->services();
            case 'add':
                return $this->addOrder($request, $user);
            case 'status':
                return $this->status($request, $user);
            case 'balance':
                return $this->balance($user);
            default:
                return response()->json(['error' => 'Unsupported action.'], 400);
        }
    }

    private function services()
    {
        $services = Service::where('is_active', true)->get()->map(function ($s) {
            return [
                'service' => $s->id,
                'name' => $s->name,
                'type' => 'Default',
                'category' => $s->category,
                'rate' => $s->selling_price,
                'min' => $s->min,
                'max' => $s->max,
                'description' => $s->description,
            ];
        });

        return response()->json($services);
    }

    private function balance($user)
    {
        return response()->json([
            'balance' => $user->balance,
            'currency' => \App\Models\Setting::get('currency_symbol', '₦'),
        ]);
    }

    private function addOrder($request, $user)
    {
        // Mocking a JSON request for OrderController
        $orderRequest = new Request();
        $orderRequest->headers->set('Accept', 'application/json');
        $orderRequest->setUserResolver(fn() => $user);
        $orderRequest->replace([
            'service_id' => $request->service,
            'link' => $request->link,
            'quantity' => $request->quantity,
            'runs' => $request->runs ?? null,
            'interval' => $request->interval ?? null,
        ]);

        // Manually authenticate user for the request
        \Illuminate\Support\Facades\Auth::login($user);

        try {
            $orderController = app(OrderController::class);
            $response = $orderController->store($orderRequest);

            if ($response instanceof \Illuminate\Http\JsonResponse) {
                $data = $response->getData();
                if (isset($data->error)) {
                    return response()->json(['error' => $data->error], $response->status() >= 400 ? $response->status() : 400);
                }
                if (isset($data->order)) {
                    return response()->json(['order' => $data->order->id]);
                }
                return $response;
            }

            // Fallback for non-JSON responses
            $content = json_decode($response->getContent(), true);
            if (isset($content['error'])) {
                return response()->json(['error' => $content['error']], 400);
            }
            if (isset($content['order']['id'])) {
                return response()->json(['order' => $content['order']['id']]);
            }

            return response()->json(['error' => 'Unable to process order.'], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Reseller API addOrder error: ' . $e->getMessage());
            return response()->json(['error' => 'Order placement failed: ' . $e->getMessage()], 500);
        }
    }

    private function status($request, $user)
    {
        if (!$request->filled('order')) {
            return response()->json(['error' => 'Order ID required.'], 400);
        }

        $order = Order::where('user_id', $user->id)->find($request->order);

        if (!$order) {
            return response()->json(['error' => 'Order not found.'], 404);
        }

        return response()->json([
            'charge' => (float) $order->charge,
            'start_count' => $order->start_count,
            'status' => ucfirst($order->status),
            'remains' => $order->remains,
            'currency' => 'NGN',
        ]);
    }
}
