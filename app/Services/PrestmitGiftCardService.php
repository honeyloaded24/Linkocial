<?php

namespace App\Services;

use App\Contracts\GiftCardProviderInterface;
use App\Exceptions\GiftCardProviderNotConfiguredException;
use Illuminate\Support\Facades\Log;

class PrestmitGiftCardService implements GiftCardProviderInterface
{
    protected ?string $apiKey;
    protected ?string $baseUrl;
    protected string $mode;
    protected ?string $webhookSecret;

    public function __construct()
    {
        $this->apiKey = config('services.prestmit.api_key');
        $this->baseUrl = config('services.prestmit.base_url');
        $this->mode = (string) config('services.prestmit.mode', 'sandbox');
        $this->webhookSecret = config('services.prestmit.webhook_secret');
    }

    /**
     * Provider unique identifier.
     */
    public function getProviderName(): string
    {
        return 'prestmit';
    }

    /**
     * Check if valid provider credentials are configured in the environment.
     * Prevents any external API calls when credentials are absent.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->baseUrl);
    }

    /**
     * Get the active environment mode ('sandbox' or 'production').
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Get the configured base URL without credentials.
     */
    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    /**
     * Ensure provider is configured before attempting operations.
     *
     * @throws GiftCardProviderNotConfiguredException
     */
    protected function ensureConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new GiftCardProviderNotConfiguredException('Prestmit');
        }
    }

    /**
     * Retrieve available products.
     * Guaranteed safe boundary: no mock purchases or guessing unverified contracts.
     *
     * @throws GiftCardProviderNotConfiguredException when not configured
     */
    public function getAvailableProducts(): array
    {
        $this->ensureConfigured();

        // Safe placeholder: pending approved API documentation & contract confirmation
        Log::info('Prestmit catalogue requested but live contract is awaiting approval.');
        return [];
    }

    /**
     * Retrieve specific brand details.
     *
     * @throws GiftCardProviderNotConfiguredException when not configured
     */
    public function getProductDetails(string $brandCode): ?array
    {
        $this->ensureConfigured();

        Log::info("Prestmit product details requested for {$brandCode} but live contract is awaiting approval.");
        return null;
    }

    /**
     * Query order status from provider.
     *
     * @throws GiftCardProviderNotConfiguredException when not configured
     */
    public function getOrderStatus(string $providerReference): array
    {
        $this->ensureConfigured();

        Log::info("Prestmit order status queried for {$providerReference} but live contract is awaiting approval.");
        return [
            'provider_reference' => $providerReference,
            'status'             => 'awaiting_provider_verification',
        ];
    }
}
