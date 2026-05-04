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
        // Mocking a request for OrderController
        $orderRequest = new Request();
        $orderRequest->replace([
            'service_id' => $request->service,
            'link' => $request->link,
            'quantity' => $request->quantity,
            'runs' => $request->runs ?? null,
            'interval' => $request->interval ?? null,
        ]);

        // Manually authenticate user for the request
        \Illuminate\Support\Facades\Auth::login($user);

        $orderController = app(OrderController::class);
        $response = $orderController->store($orderRequest);
        $data = $response->getData();

        if (isset($data->error)) {
            return response()->json(['error' => $data->error], 400);
        }

        return response()->json([
            'order' => $data->order->id,
        ]);
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
            'charge' => $order->total_price,
            'start_count' => $order->start_count,
            'status' => ucfirst($order->status),
            'remains' => $order->remains,
            'currency' => 'NGN',
        ]);
    }
}
