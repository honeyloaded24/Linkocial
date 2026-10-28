<?php

namespace App\Contracts;

interface GiftCardProviderInterface
{
    /**
     * Get the unique provider identifier (e.g. 'prestmit').
     */
    public function getProviderName(): string;

    /**
     * Determine whether the provider has valid credentials and configuration available.
     */
    public function isConfigured(): bool;

    /**
     * Retrieve available gift card brands/products.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableProducts(): array;

    /**
     * Retrieve details for a specific gift card brand/product.
     */
    public function getProductDetails(string $brandCode): ?array;

    /**
     * Check status of an existing order with the provider.
     */
    public function getOrderStatus(string $providerReference): array;
}
