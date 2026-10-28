<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\VtuOrder;
use App\Models\Transaction;
use App\Services\GongozconceptApi;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Mockery;

class GongozDataErrorSanitizationTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'balance' => 50000,
            'role' => 'user',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 1. Test that provider-branded message "GONGOZ SME not available on this network currently"
     * is sanitized into a neutral error and does NOT display "Gongoz".
     */
    public function test_gongoz_sme_unavailable_error_is_sanitized_and_hides_provider_name()
    {
        $mockApi = Mockery::mock(GongozconceptApi::class);
        $mockApi->shouldReceive('buyData')
            ->once()
            ->andReturn([
                'status' => 'fail',
                'error' => ['GONGOZ SME not available on this network currently'],
            ]);

        $this->app->instance(GongozconceptApi::class, $mockApi);

        $initialBalance = $this->user->balance;

        $response = $this->actingAs($this->user)->post(route('vtu.data.purchase'), [
            'network' => 1,
            'plan' => 280,
            'phone' => '08012345678',
            'amount' => 500,
        ]);

        $response->assertSessionHas('error', 'This data plan is currently unavailable. Please select another plan or try again later.');

        $errorMessage = session('error');
        $this->assertStringNotContainsStringIgnoringCase('gongoz', $errorMessage);
        $this->assertStringNotContainsStringIgnoringCase('gongozconcept', $errorMessage);

        // Verify accurate transaction outcome: balance was refunded and order marked refunded
        $this->user->refresh();
        $this->assertEquals($initialBalance, $this->user->balance);

        $order = VtuOrder::where('user_id', $this->user->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('refunded', $order->status);
        $this->assertStringContainsString('GONGOZ SME not available', $order->api_response);
    }

    /**
     * 2. Test that Gongoz error returned in string format instead of array is also sanitized.
     */
    public function test_gongoz_string_error_is_sanitized()
    {
        $mockApi = Mockery::mock(GongozconceptApi::class);
        $mockApi->shouldReceive('buyData')
            ->once()
            ->andReturn([
                'status' => false,
                'message' => 'GONGOZ CORPORATE GIFTING not available on this network currently',
            ]);

        $this->app->instance(GongozconceptApi::class, $mockApi);

        $response = $this->actingAs($this->user)->post(route('vtu.data.purchase'), [
            'network' => 1,
            'plan' => 280,
            'phone' => '08012345678',
            'amount' => 500,
        ]);

        $response->assertSessionHas('error', 'This data plan is currently unavailable. Please select another plan or try again later.');
        $errorMessage = session('error');
        $this->assertStringNotContainsStringIgnoringCase('gongoz', $errorMessage);
    }

    /**
     * 3. Test that connection timeouts and unreachable errors produce neutral "try again" messages.
     */
    public function test_connection_error_produces_neutral_try_again_message()
    {
        $mockApi = Mockery::mock(GongozconceptApi::class);
        $mockApi->shouldReceive('buyData')
            ->once()
            ->andReturn([
                'status' => false,
                'message' => 'Failed to connect to the VTU provider: Connection timed out after 15 seconds',
            ]);

        $this->app->instance(GongozconceptApi::class, $mockApi);

        $response = $this->actingAs($this->user)->post(route('vtu.data.purchase'), [
            'network' => 1,
            'plan' => 280,
            'phone' => '08012345678',
            'amount' => 500,
        ]);

        $response->assertSessionHas('error', "We couldn't process your request right now. Please try again shortly.");
        $errorMessage = session('error');
        $this->assertStringNotContainsStringIgnoringCase('gongoz', $errorMessage);
        $this->assertStringNotContainsStringIgnoringCase('vtu provider', $errorMessage);
    }

    /**
     * 4. Test that provider auth/token/configuration error produces neutral "temporarily unavailable" message.
     */
    public function test_provider_auth_or_key_error_produces_neutral_message()
    {
        $mockApi = Mockery::mock(GongozconceptApi::class);
        $mockApi->shouldReceive('buyData')
            ->once()
            ->andReturn([
                'status' => false,
                'message' => 'VTU Provider authentication error: Invalid or expired API token. Check HQ Settings.',
                'detail' => 'Invalid token.',
            ]);

        $this->app->instance(GongozconceptApi::class, $mockApi);

        $response = $this->actingAs($this->user)->post(route('vtu.data.purchase'), [
            'network' => 1,
            'plan' => 280,
            'phone' => '08012345678',
            'amount' => 500,
        ]);

        $response->assertSessionHas('error', 'This service is temporarily unavailable. Please try again later.');
        $errorMessage = session('error');
        $this->assertStringNotContainsStringIgnoringCase('gongoz', $errorMessage);
        $this->assertStringNotContainsStringIgnoringCase('token', $errorMessage);
        $this->assertStringNotContainsStringIgnoringCase('hq settings', $errorMessage);
    }

    /**
     * 5. Test that exceptions in buyData() produce neutral messages and preserve funds.
     */
    public function test_exception_in_buy_data_produces_neutral_message_and_refunds()
    {
        $mockApi = Mockery::mock(GongozconceptApi::class);
        $mockApi->shouldReceive('buyData')
            ->once()
            ->andThrow(new \RuntimeException('Gongozconcept upstream server 500 internal error at https://gongozconcept.com/api/data/'));

        $this->app->instance(GongozconceptApi::class, $mockApi);

        $initialBalance = $this->user->balance;

        $response = $this->actingAs($this->user)->post(route('vtu.data.purchase'), [
            'network' => 1,
            'plan' => 280,
            'phone' => '08012345678',
            'amount' => 500,
        ]);

        $response->assertSessionHas('error', "We couldn't process your request right now. Please try again shortly.");
        $errorMessage = session('error');
        $this->assertStringNotContainsStringIgnoringCase('gongoz', $errorMessage);
        $this->assertStringNotContainsStringIgnoringCase('upstream', $errorMessage);

        $this->user->refresh();
        $this->assertEquals($initialBalance, $this->user->balance);
    }

    /**
     * 6. Test that successful purchase outcomes remain completely unaffected.
     */
    public function test_successful_data_purchase_remains_completed()
    {
        $mockApi = Mockery::mock(GongozconceptApi::class);
        $mockApi->shouldReceive('buyData')
            ->once()
            ->andReturn([
                'status' => 'successful',
                'id' => 998877,
                'msg' => 'Data delivered successfully',
            ]);

        $this->app->instance(GongozconceptApi::class, $mockApi);

        $initialBalance = $this->user->balance;

        $response = $this->actingAs($this->user)->post(route('vtu.data.purchase'), [
            'network' => 1,
            'plan' => 280,
            'phone' => '08012345678',
            'amount' => 500,
        ]);

        $response->assertSessionHas('success', 'Data purchased successfully!');
        $this->user->refresh();
        $this->assertLessThan($initialBalance, $this->user->balance);

        $order = VtuOrder::where('user_id', $this->user->id)->latest()->first();
        $this->assertEquals('completed', $order->status);
        $this->assertEquals('998877', $order->api_reference);
    }
}
