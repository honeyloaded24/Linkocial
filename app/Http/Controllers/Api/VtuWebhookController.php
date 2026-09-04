<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\VtuOrder;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class VtuWebhookController extends Controller
{
    /**
     * Handle incoming webhooks from the VTU provider.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('VTU Webhook Received: ', $payload);

        // Typical payload structure for refunds could include 'status' => 'failed' or 'refunded',
        // and a reference id such as 'id', 'ref', 'reference', or 'api_reference'.
        
        $status = $payload['status'] ?? null;
        if (!$status) {
            return response()->json(['message' => 'Status missing'], 200); // 200 so they don't retry unnecessarily if malformed
        }

        // Find the order by api_reference or id.
        // Some providers send 'id', some send 'ref', 'reference'
        $apiReference = $payload['id'] ?? $payload['ref'] ?? $payload['reference'] ?? null;

        if (!$apiReference) {
            Log::warning('VTU Webhook: No reference found in payload.');
            return response()->json(['message' => 'No reference found'], 200);
        }

        $order = VtuOrder::where('api_reference', $apiReference)->first();

        if (!$order) {
            // Also try matching by parsing the JSON api_response if api_reference wasn't saved properly
            $order = VtuOrder::where('api_response', 'like', '%"id":' . $apiReference . '%')
                             ->orWhere('api_response', 'like', '%"id":"' . $apiReference . '"%')
                             ->first();
        }

        if (!$order) {
            Log::warning("VTU Webhook: Order not found for reference {$apiReference}");
            return response()->json(['message' => 'Order not found'], 200);
        }

        if ($order->status === 'refunded') {
            Log::info("VTU Webhook: Order {$order->id} already refunded.");
            return response()->json(['message' => 'Already refunded'], 200);
        }

        $statusStr = strtolower($status);

        if ($statusStr === 'failed' || $statusStr === 'refunded' || $statusStr === 'reversed') {
            DB::beginTransaction();
            try {
                // Mark order as failed/refunded
                $order->status = 'refunded';
                
                // Append webhook response to api_response
                $existingResponse = json_decode($order->api_response, true) ?? [];
                $existingResponse['webhook'] = $payload;
                $order->api_response = json_encode($existingResponse);
                $order->save();

                // Refund the user
                $user = User::find($order->user_id);
                if ($user) {
                    $user->balance += $order->cost;
                    $user->save();
                    
                    // The cost was deducted in USD
                    $amountUsd = $order->cost;
                    $service = ucfirst($order->service_type);
                    Transaction::add($user->id, $amountUsd, 'credit', 'vtu_refund', "Refund for failed {$service} purchase (Ref: {$apiReference})");
                }

                DB::commit();
                Log::info("VTU Webhook: Successfully refunded order {$order->id}");
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("VTU Webhook Error during refund: " . $e->getMessage());
            }
        } elseif ($statusStr === 'successful' || $statusStr === 'success' || $statusStr === 'completed') {
            if ($order->status !== 'completed') {
                $order->status = 'completed';
                $existingResponse = json_decode($order->api_response, true) ?? [];
                $existingResponse['webhook'] = $payload;
                $order->api_response = json_encode($existingResponse);
                $order->save();
                Log::info("VTU Webhook: Marked order {$order->id} as completed.");
            }
        }

        return response()->json(['status' => 'success'], 200);
    }
}
