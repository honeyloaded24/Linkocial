<?php

namespace Tests\Unit;

use App\Services\SmsBowerService;
use App\Http\Controllers\VirtualNumberController;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\Setting;
use ReflectionClass;

class SmsBowerMiddlePricingTest extends TestCase
{
    use RefreshDatabase;

    private function filterMiddlePrices(array $offers): array
    {
        $service = (new ReflectionClass(SmsBowerService::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(SmsBowerService::class, 'middlePricedOffers');
        $method->setAccessible(true);

        return $method->invoke($service, $offers);
    }

    public function test_it_excludes_the_lowest_and_highest_gold_price_bands(): void
    {
        $offers = [
            ['id' => 'low', 'price' => 0.10, 'stock' => 10],
            ['id' => 'middle-a', 'price' => 0.20, 'stock' => 5],
            ['id' => 'middle-b', 'price' => 0.20, 'stock' => 7],
            ['id' => 'high', 'price' => 0.30, 'stock' => 2],
        ];

        $result = $this->filterMiddlePrices($offers);

        $this->assertSame(['middle-a', 'middle-b'], array_column($result, 'id'));
    }

    public function test_it_returns_no_supplier_when_there_are_fewer_than_three_distinct_price_bands(): void
    {
        $offers = [
            ['id' => 'low', 'price' => 0.10, 'stock' => 10],
            ['id' => 'high', 'price' => 0.20, 'stock' => 5],
        ];

        $this->assertSame([], $this->filterMiddlePrices($offers));
    }

    public function test_it_excludes_all_suppliers_sharing_an_extreme_price(): void
    {
        $offers = [
            ['id' => 'lowest-a', 'price' => 0.10, 'stock' => 4],
            ['id' => 'lowest-b', 'price' => 0.10, 'stock' => 6],
            ['id' => 'middle', 'price' => 0.20, 'stock' => 3],
            ['id' => 'highest-a', 'price' => 0.30, 'stock' => 2],
            ['id' => 'highest-b', 'price' => 0.30, 'stock' => 1],
        ];

        $result = $this->filterMiddlePrices($offers);

        $this->assertSame(['middle'], array_column($result, 'id'));
    }

    public function test_gold_catalogue_parser_uses_partner_ids_and_stock_from_documented_response(): void
    {
        $service = (new ReflectionClass(SmsBowerService::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(SmsBowerService::class, 'goldPartnersFromResponse');
        $method->setAccessible(true);

        $response = [
            'usa' => [
                'lowest' => ['price' => 0.10, 'count' => 20],
                'middle-a' => ['price' => 0.20, 'count' => 12],
                'middle-b' => ['price' => 0.20, 'count' => 8],
                'highest' => ['price' => 0.30, 'count' => 5],
                'empty-stock' => ['price' => 0.25, 'count' => 0],
            ],
        ];

        $goldOffers = $method->invoke($service, $response, 'United States');
        $inStockOffers = array_values(array_filter($goldOffers, fn ($offer) => (int) ($offer['count'] ?? 0) > 0));
        $middleOffers = $this->filterMiddlePrices($inStockOffers);

        $this->assertSame(['middle-a', 'middle-b'], array_column($middleOffers, 'id'));
        $this->assertSame([0.20, 0.20], array_column($middleOffers, 'price'));
    }


    public function test_customer_quote_uses_cheapest_retained_middle_supplier_not_highest_fallback(): void
    {
        $service = (new ReflectionClass(SmsBowerService::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(SmsBowerService::class, 'customerQuotePrice');
        $method->setAccessible(true);

        $offers = [
            ['id' => 'first-attempt', 'price' => 1.067, 'stock' => 10],
            ['id' => 'fallback', 'price' => 4.13, 'stock' => 5],
        ];

        $this->assertSame(1.067, $method->invoke($service, $offers));
        $this->assertSame(0.0, $method->invoke($service, []));
    }


    public function test_united_states_catalogue_uses_country_specific_prices_when_missing_from_top_ten(): void
    {
        Setting::set('smsbower_api_key', 'test-smsbower-key');
        Cache::flush();

        Http::fake(function ($request) {
            $params = $request->data();
            return match ($params['action'] ?? '') {
                'getCountries' => Http::response([
                    '187' => ['id' => 187, 'eng' => 'United States', 'prefix' => '1'],
                ], 200),
                'getServicesList' => Http::response([
                    'status' => 'success',
                    'services' => [['code' => 'wa', 'name' => 'WhatsApp']],
                ], 200),
                'getTopCountriesByService' => Http::response([
                    'uk' => [
                        'uk-gold-a' => ['price' => 0.10, 'count' => 20],
                        'uk-gold-b' => ['price' => 0.20, 'count' => 15],
                        'uk-gold-c' => ['price' => 0.30, 'count' => 10],
                    ],
                ], 200),
                'getPricesV3' => Http::response([
                    '187' => [
                        'wa' => [
                            'us-a' => ['provider_id' => 'us-a', 'price' => 0.10, 'count' => 20],
                            'us-b' => ['provider_id' => 'us-b', 'price' => 0.20, 'count' => 15],
                            'us-c' => ['provider_id' => 'us-c', 'price' => 0.30, 'count' => 10],
                        ],
                    ],
                ], 200),
                default => Http::response('BAD_ACTION', 400),
            };
        });

        $rows = app(SmsBowerService::class)->getPricesForCountry('187');

        $this->assertCount(1, $rows);
        $this->assertSame('wa', $rows[0]['service']);
        $this->assertSame('WhatsApp', $rows[0]['service_name']);
        $this->assertSame(0.20, $rows[0]['price']);
        $this->assertSame(15, $rows[0]['count']);

        Http::assertSent(fn ($request) =>
            ($request->data()['action'] ?? null) === 'getPricesV3'
            && (string) ($request->data()['country'] ?? '') === '187'
            && ($request->data()['service'] ?? null) === 'wa'
        );
    }

    public function test_server3_country_list_always_includes_documented_uk_and_us_ids(): void
    {
        $controller = (new ReflectionClass(VirtualNumberController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(VirtualNumberController::class, 'ensureSmsBowerCoreCountries');
        $method->setAccessible(true);

        $countries = $method->invoke($controller, [
            ['ID' => '74', 'name' => 'Afghanistan', 'cc' => '93'],
        ]);

        $this->assertContains(['ID' => '16', 'name' => 'United Kingdom', 'cc' => '44'], $countries);
        $this->assertContains(['ID' => '187', 'name' => 'United States', 'cc' => '1'], $countries);
    }

    public function test_supplier_max_price_never_exceeds_the_selected_supplier_quote(): void
    {
        $service = (new ReflectionClass(SmsBowerService::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(SmsBowerService::class, 'supplierMaxPrice');
        $method->setAccessible(true);

        $this->assertSame(0.20, $method->invoke($service, 0.20, 0.50));
        $this->assertSame(0.15, $method->invoke($service, 0.20, 0.15));
        $this->assertSame(0.20, $method->invoke($service, 0.20, null));
    }

}
