<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiOrder;
use App\Models\AiWebhookEvent;
use App\Models\Transaction;
use App\Services\AiPlugService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiPlugWebhookController extends Controller
{
    protected AiPlugService $aiPlugService;

    public function __construct(AiPlugService $aiPlugService)
    {
        $this->aiPlugService = $aiPlugService;
    }

    /**
     * Handle incoming signed webhook from AIPlug.
     */
    public function handle(Request $request): Response
    {
        $rawContent = $request->getContent();
        $signatureHeader = $request->header('X-AIPlug-Signature');

        // 1. Verify HMAC Signature
        if (!$this->aiPlugService->verifyWebhookSignature($rawContent, $signatureHeader)) {
            Log::warning('AIPlug webhook: rejected due to invalid signature or timestamp drift.');
            return response('Unauthorized: invalid signature', 401);
        }

        $payload = json_decode($rawContent, true);
        if (!$payload || !isset($payload['id'], $payload['type'])) {
            Log::warning('AIPlug webhook: invalid JSON payload received.');
            return response('Bad Request: invalid payload', 400);
        }

        $eventId = (string) $payload['id'];
        $eventType = (string) $payload['type'];

        // 2. Durable Deduplication: check if event was already recorded
        $existingEvent = AiWebhookEvent::where('event_id', $eventId)->first();
        if ($existingEvent) {
            // As per specification: duplicate events must still receive 200 OK immediately
            return response('OK (Duplicate event acknowledged)', 200);
        }

        // Record incoming event
        $webhookRecord = AiWebhookEvent::create([
            'event_id'   => $eventId,
            'event_type' => $eventType,
            'payload'    => $payload,
        ]);

        // 3. Process the event
        try {
            $this->processEvent($eventType, $payload);
            $webhookRecord->update(['processed_at' => now()]);
        } catch (\Throwable $e) {
            Log::error("AIPlug webhook processing exception for Event {$eventId}: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            // Still acknowledge with 200 so provider does not endlessly hammer endpoint for app-level logic errors
        }

        return response('OK', 200);
    }

    /**
     * Process specific AIPlug event types.
     */
    protected function processEvent(string $type, array $payload): void
    {
        if ($type === 'webhook.test') {
            Log::info('AIPlug webhook test event received successfully: ' . json_encode($payload));
            return;
        }

        $orderData = $payload['data']['order'] ?? [];
        $providerOrderId = $orderData['id'] ?? null;

        if (empty($providerOrderId)) {
            Log::info("AIPlug webhook: event type {$type} has no provider order ID.");
            return;
        }

        $order = AiOrder::where('provider_order_id', $providerOrderId)->first();

        // If not found by provider_order_id, attempt to match recent pending order by product provider ID
        if (!$order && !empty($payload['data']['orderUrl'])) {
            $order = AiOrder::where('status', 'processing')
                ->where('created_at', '>=', now()->subHours(2))
                ->latest()
                ->first();
        }

        if (!$order) {
            Log::warning("AIPlug webhook: order with provider ID {$providerOrderId} not found locally.");
            return;
        }

        switch ($type) {
            case 'order.fulfilled':
                // Per documentation: For fulfilled products, fetch GET /orders/:id for latest delivery credentials
                $deliveryPayload = $order->delivery_payload;
                try {
                    $details = $this->aiPlugService->getOrder($providerOrderId);
                    if ($details['success']) {
                        $remoteData = $details['data'];
                        $delivery = $remoteData['delivery'] ?? $remoteData['order']['delivery'] ?? null;
                        if ($delivery) {
                            $deliveryPayload = is_array($delivery) ? json_encode($delivery, JSON_PRETTY_PRINT) : (string) $delivery;
                        }
                    }
                } catch (\Throwable $pollErr) {
                    Log::warning("Could not fetch remote order delivery for {$providerOrderId}: " . $pollErr->getMessage());
                }

                $order->update([
                    'status'           => 'fulfilled',
                    'delivery_payload' => $deliveryPayload,
                    'metadata'         => array_merge($order->metadata ?? [], ['fulfilled_event' => $payload]),
                ]);
                break;

            case 'order.cancelled':
                if ($order->status !== 'refunded') {
                    DB::beginTransaction();
                    try {
                        $user = $order->user;
                        if ($user) {
                            $refundAmount = (float) $order->total_price;
                            $user->increment('balance', $refundAmount);

                            Transaction::add(
                                $user->id,
                                $refundAmount,
                                'credit',
                                'refund',
                                "Refund for cancelled AI Order #{$order->id} ({$order->product_name})"
                            );
                        }

                        $order->update([
                            'status'   => 'refunded',
                            'metadata' => array_merge($order->metadata ?? [], ['cancelled_event' => $payload]),
                        ]);
                        DB::commit();
                    } catch (\Throwable $refundEx) {
                        DB::rollBack();
                        Log::error("Failed to refund cancelled AI Order #{$order->id}: " . $refundEx->getMessage());
                    }
                }
                break;

            case 'order.processing':
            case 'order.awaiting_manual':
            case 'order.updated':
                $status = $orderData['status'] ?? str_replace('order.', '', $type);
                $order->update([
                    'status'   => $status,
                    'metadata' => array_merge($order->metadata ?? [], ['update_event' => $payload]),
                ]);
                break;

            default:
                Log::info("AIPlug webhook: unhandled event type '{$type}' for Order #{$order->id}");
                break;
        }
    }
}
