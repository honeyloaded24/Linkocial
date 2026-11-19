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
        // Avoid logging customer identifiers, phone numbers, and provider payloads.

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
                // Provider webhook can arrive before api_reference is persisted.
                // Search candidate responses, then compare decoded IDs strictly to
                // avoid substring matches (e.g. reference 123 matching 1234).
                $candidates = VtuOrder::where('api_response', 'like', '%"id"%')
                    ->whereIn('status', ['processing', 'completed'])
                    ->orderByDesc('id')
                    ->limit(100)
                    ->get();

                foreach ($candidates as $candidate) {
                    $decoded = json_decode($candidate->api_response, true);
                    if (is_array($decoded) && isset($decoded['id'])
                        && (string) $decoded['id'] === (string) $apiReference) {
                        $order = VtuOrder::whereKey($candidate->id)->lockForUpdate()->first();
                        break;
                    }
                }
            }

            if (!$order) {
                Log::warning("VTU Webhook: Order not found for reference {$apiReference}");
                return response()->json(['message' => 'Order not found'], 200);
            }

            // Terminal states must not be reversed by a delayed or conflicting webhook.
            if (in_array($order->status, ['refunded', 'failed'], true)) {
                return response()->json(['message' => 'Already refunded or failed'], 200);
            }

            if (in_array($statusStr, ['failed', 'refunded', 'reversed'])) {
                if ($order->status === 'completed') {
                    Log::warning("VTU Webhook: Refusing failure/refund event for completed order {$order->id}.");
                    return response()->json(['message' => 'Completed order protected from refund'], 200);
                }
                // Only refund when the owning wallet exists; state and ledger must commit together.
                $user = User::where('id', $order->user_id)->lockForUpdate()->first();
                if (!$user) {
                    Log::error("VTU Webhook: Refund blocked; user missing for order {$order->id}.");
                    return response()->json(['message' => 'User not found; refund not applied'], 500);
                }

                // Mark order as refunded
                $order->status = 'refunded';
                $existingResponse = json_decode($order->api_response, true) ?? [];
                $existingResponse['webhook'] = $payload;
                $order->api_response = json_encode($existingResponse);
                $order->save();

                // Refund the user atomically with a stable order-based ledger reference.
                $user->addBalance($order->cost);
                $refundAmount = $order->cost;
                $service = ucfirst($order->service_type);
                Transaction::add(
                    $user->id,
                    $refundAmount,
                    'credit',
                    'vtu_refund',
                    "Refund for failed {$service} purchase (Ref: {$apiReference})",
                    'VTU-WEBHOOK-REFUND-' . $order->id
                );

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
