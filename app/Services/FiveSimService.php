<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FiveSimService
{
    protected $baseUrl = 'https://5sim.net';
    protected $token;

    public function __construct()
    {
        $this->token = env('FIVESIM_API_KEY');
    }

    /**
     * Get the base Http client pre-configured with headers.
     */
    protected function client()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ]);
    }

    /**
     * Get user profile / balance.
     */
    public function getProfile()
    {
        $response = $this->client()->get("{$this->baseUrl}/v1/user/profile");
        return $response->json();
    }

    /**
     * Get available prices and countries dynamically.
     * Uses /v1/guest/prices which returns: { country: { product: { operator: { cost, count, rate } } } }
     */
    public function getPrices($country = null, $product = null)
    {
        $query = [];
        if ($country && $country !== 'any') $query['country'] = $country;
        if ($product && $product !== 'any') $query['product'] = $product;

        $response = $this->client()->get("{$this->baseUrl}/v1/guest/prices", $query);
        return $response->json();
    }

    /**
     * Get list of available countries from 5sim.
     */
    public function getCountries()
    {
        $response = $this->client()->get("{$this->baseUrl}/v1/guest/countries");
        return $response->json();
    }
    
    /**
     * Buy an activation number
     */
    public function buyNumber($country, $product, $operator = 'any')
    {
        $response = $this->client()->get("{$this->baseUrl}/v1/user/buy/activation/{$country}/{$operator}/{$product}");
        
        if ($response->failed()) {
            Log::error('5sim buyNumber failed', ['response' => $response->body()]);
            throw new \Exception($response->body() ?: 'Failed to buy number from 5sim');
        }

        return $response->json();
    }

    /**
     * Check order status / poll for SMS
     */
    public function checkOrder($id)
    {
        $response = $this->client()->get("{$this->baseUrl}/v1/user/check/{$id}");
        return $response->json();
    }

    /**
     * Finish an order after receiving SMS successfully
     */
    public function finishOrder($id)
    {
        $response = $this->client()->get("{$this->baseUrl}/v1/user/finish/{$id}");
        return $response->json();
    }

    /**
     * Cancel an order (if SMS not received)
     */
    public function cancelOrder($id)
    {
        $response = $this->client()->get("{$this->baseUrl}/v1/user/cancel/{$id}");
        return $response->json();
    }
    
    /**
     * Ban an order (if number is invalid)
     */
    public function banOrder($id)
    {
        $response = $this->client()->get("{$this->baseUrl}/v1/user/ban/{$id}");
        return $response->json();
    }
}
