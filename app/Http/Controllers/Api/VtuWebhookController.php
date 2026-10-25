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

        // Verify webhook secret - strictly fail-closed for security
        $configuredSecret = \App\Models\Setting::get('vtu_webhook_secret');
        $receivedSecret = $request->header('X-Webhook-Secret') ?? $request->query('secret') ?? $request->bearerToken();
        if (empty($configuredSecret) || empty($receivedSecret) || !hash_equals((string) $configuredSecret, (string) $receivedSecret)) {
            Log::warning('VTU Webhook: Unauthorized signature or secret mismatch.');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Typical payload structure for refunds could include 'status' => 'failed' or 'refunded',
        // and a reference id such as 'id', 'ref', 'reference', or 'api_reference'.
        $status = $payload['status'] ?? null;
        if (!$status) {
            return response()->json(['message' => 'Status missing'], 200);
        }

        $apiReference = $payload['id'] ?? $payload['ref'] ?? $payload['reference'] ?? null;
        if (!$apiReference) {
            Log::warning('VTU Webhook: No reference found in payload.');
            return response()->json(['message' => 'No reference found'], 200);
        }

        $statusStr = strtolower((string) $status);

        return DB::transaction(function () use ($apiReference, $payload, $statusStr) {
            $order = VtuOrder::where('api_reference', $apiReference)->lockForUpdate()->first();

            if (!$order) {
                // Also try matching by parsing the JSON api_response if api_reference wasn't saved properly
                $order = VtuOrder::where('api_response', 'like', '%"id":' . $apiReference . '%')
                                 ->orWhere('api_response', 'like', '%"id":"' . $apiReference . '"%')
                                 ->lockForUpdate()
                                 ->first();
            }

            if (!$order) {
                Log::warning("VTU Webhook: Order not found for reference {$apiReference}");
                return response()->json(['message' => 'Order not found'], 200);
            }

            // Guard against duplicate refunds (both 'refunded' and already 'failed' orders)
            if ($order->status === 'refunded' || $order->status === 'failed') {
                Log::info("VTU Webhook: Order {$order->id} already marked as {$order->status}. Skipping refund.");
                return response()->json(['message' => 'Already refunded or failed'], 200);
            }

            if (in_array($statusStr, ['failed', 'refunded', 'reversed'])) {
                // Mark order as refunded
                $order->status = 'refunded';
                $existingResponse = json_decode($order->api_response, true) ?? [];
                $existingResponse['webhook'] = $payload;
                $order->api_response = json_encode($existingResponse);
                $order->save();

                // Refund the user atomically
                $user = User::where('id', $order->user_id)->lockForUpdate()->first();
                if ($user) {
                    $user->addBalance($order->cost);
                    
                    // The cost was charged in NGN
                    $refundAmount = $order->cost;
                    $service = ucfirst($order->service_type);
                    Transaction::add($user->id, $refundAmount, 'credit', 'vtu_refund', "Refund for failed {$service} purchase (Ref: {$apiReference})");
                }

                Log::info("VTU Webhook: Successfully refunded order {$order->id}");
            } elseif (in_array($statusStr, ['successful', 'success', 'completed'])) {
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
        });
    }
}
