<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VtuOrder;
use App\Models\Transaction;
use App\Services\GongozconceptApi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VtuController extends Controller
{
    protected $vtuApi;

    public function __construct(GongozconceptApi $vtuApi)
    {
        $this->vtuApi = $vtuApi;
    }

    // ==========================================
    // AIRTIME
    // ==========================================
    public function airtime()
    {
        return view('vtu.airtime');
    }

    public function purchaseAirtime(Request $request)
    {
        $request->validate([
            'network' => 'required|integer',
            'phone' => 'required|string|min:10',
            'amount' => 'required|numeric|min:50',
        ]);

        $user = Auth::user();
        $baseAmountNgn = (float) $request->amount;
        $markupRate = (float) \App\Models\Setting::get('markup_vtu_airtime', 0);
        $chargedAmountNgn = $baseAmountNgn * (1 + ($markupRate / 100));
        
        $ngnRate = \App\Models\Currency::where('code', 'NGN')->value('exchange_rate') ?? 1420.00;
        $amountUsd = $chargedAmountNgn / $ngnRate;

        if ($user->balance < $amountUsd) {
            return back()->with('error', 'Insufficient balance. Please fund your account.');
        }

        DB::beginTransaction();

        try {
            // Deduct balance
            $user->balance -= $amountUsd;
            $user->save();

            // Log Transaction
            Transaction::add($user->id, $amountUsd, 'debit', 'vtu_airtime', "₦" . number_format($chargedAmountNgn, 2) . " Airtime top-up for {$request->phone}");

            // Create Order
            $order = VtuOrder::create([
                'user_id' => $user->id,
                'service_type' => 'airtime',
                'network' => $request->network,
                'phone_or_meter' => $request->phone,
                'plan_or_amount' => $baseAmountNgn, // Keep original NGN request
                'cost' => $amountUsd,
                'status' => 'processing',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('VTU Airtime error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while processing your request.');
        }

        // Call API
        $response = $this->vtuApi->buyAirtime($request->network, $baseAmountNgn, $request->phone);

        if (isset($response['id']) || (isset($response['status']) && $response['status'] == 'successful')) {
            $order->update([
                'status' => 'completed',
                'api_reference' => $response['id'] ?? null,
                'api_response' => json_encode($response),
            ]);
            return back()->with('success', 'Airtime purchased successfully!');
        } else {
            // Handle failure/refund
            $order->update([
                'status' => 'failed',
                'api_response' => json_encode($response),
            ]);

            // Refund User
            $user->balance += $amountUsd;
            $user->save();
            Transaction::add($user->id, $amountUsd, 'credit', 'vtu_refund', "Refund for failed ₦" . number_format($chargedAmountNgn, 2) . " Airtime top-up");

            $message = $response['error'][0] ?? ($response['message'] ?? 'Failed to purchase airtime.');
            return back()->with('error', $message);
        }
    }

    // ==========================================
    // DATA
    // ==========================================
    public function data()
    {
        $plansJson = \App\Services\VtuDataService::getPlansJsonWithCustomerPrices();
        return view('vtu.data', compact('plansJson'));
    }

    public function purchaseData(Request $request)
    {
        $request->validate([
            'network' => 'required|integer',
            'phone' => 'required|string|min:10',
            'plan' => 'required|integer',
            'amount' => 'required|numeric', // passed from frontend as customer price
        ]);

        $user = Auth::user();
        
        // Securely resolve the exact customer price on the server (wholesale price + admin markup)
        $verifiedPrice = \App\Services\VtuDataService::getCustomerPrice($request->network, $request->plan);
        $chargedAmountNgn = $verifiedPrice !== null ? $verifiedPrice : (float) $request->amount;

        $ngnRate = \App\Models\Currency::where('code', 'NGN')->value('exchange_rate') ?? 1420.00;
        $amountUsd = $chargedAmountNgn / $ngnRate;

        if ($user->balance < $amountUsd) {
            return back()->with('error', 'Insufficient balance. Please fund your account.');
        }

        DB::beginTransaction();

        try {
            $user->balance -= $amountUsd;
            $user->save();

            Transaction::add($user->id, $amountUsd, 'debit', 'vtu_data', "₦" . number_format($chargedAmountNgn, 2) . " Data purchase for {$request->phone}");

            $order = VtuOrder::create([
                'user_id' => $user->id,
                'service_type' => 'data',
                'network' => $request->network,
                'phone_or_meter' => $request->phone,
                'plan_or_amount' => $request->plan,
                'cost' => $amountUsd,
                'status' => 'processing',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('VTU Data error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while processing your request.');
        }

        $response = $this->vtuApi->buyData($request->network, $request->plan, $request->phone);

        if (isset($response['id']) || (isset($response['status']) && $response['status'] == 'successful')) {
            $order->update([
                'status' => 'completed',
                'api_reference' => $response['id'] ?? null,
                'api_response' => json_encode($response),
            ]);
            return back()->with('success', 'Data purchased successfully!');
        } else {
            $order->update([
                'status' => 'failed',
                'api_response' => json_encode($response),
            ]);

            $user->balance += $amountUsd;
            $user->save();
            Transaction::add($user->id, $amountUsd, 'credit', 'vtu_refund', "Refund for failed ₦" . number_format($chargedAmountNgn, 2) . " Data purchase");

            $message = $response['error'][0] ?? ($response['message'] ?? 'Failed to purchase data.');
            return back()->with('error', $message);
        }
    }

    // ==========================================
    // ELECTRICITY
    // ==========================================
    public function electricity()
    {
        return view('vtu.electricity');
    }

    public function validateMeter(Request $request)
    {
        $request->validate([
            'meter_number' => 'required',
            'disco' => 'required|string',
            'meter_type' => 'required|string',
        ]);

        $response = $this->vtuApi->validateMeter($request->meter_number, $request->disco, $request->meter_type);

        if (isset($response['name'])) {
            return response()->json([
                'success' => true,
                'name' => $response['name'],
                'address' => $response['address'] ?? null
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid meter number or could not validate.'
        ]);
    }

    public function purchaseElectricity(Request $request)
    {
        $request->validate([
            'disco' => 'required|string',
            'meter_number' => 'required|string',
            'meter_type' => 'required|string',
            'amount' => 'required|numeric|min:500',
            'phone' => 'required|string',
        ]);

        $user = Auth::user();
        $baseAmountNgn = (float) $request->amount;
        $markupRate = (float) \App\Models\Setting::get('markup_vtu_electricity', 0);
        $chargedAmountNgn = $baseAmountNgn * (1 + ($markupRate / 100));

        $ngnRate = \App\Models\Currency::where('code', 'NGN')->value('exchange_rate') ?? 1420.00;
        $amountUsd = $chargedAmountNgn / $ngnRate;

        if ($user->balance < $amountUsd) {
            return back()->with('error', 'Insufficient balance. Please fund your account.');
        }

        DB::beginTransaction();

        try {
            $user->balance -= $amountUsd;
            $user->save();

            Transaction::add($user->id, $amountUsd, 'debit', 'vtu_electricity', "₦" . number_format($chargedAmountNgn, 2) . " Electricity payment for meter {$request->meter_number}");

            $order = VtuOrder::create([
                'user_id' => $user->id,
                'service_type' => 'electricity',
                'network' => $request->disco,
                'phone_or_meter' => $request->meter_number,
                'plan_or_amount' => $baseAmountNgn,
                'cost' => $amountUsd,
                'status' => 'processing',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('VTU Electricity error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while processing your request.');
        }
        // 3. Purchase Electricity via API
        $response = $this->vtuApi->buyElectricity($request->disco, $baseAmountNgn, $request->meter_number, $request->meter_type, $request->phone);

        if (isset($response['id']) || (isset($response['status']) && $response['status'] == 'successful')) {
            $token = $response['token'] ?? 'Check history or SMS';
            
            $order->update([
                'status' => 'completed',
                'api_reference' => $response['id'] ?? null,
                'api_response' => json_encode($response),
            ]);
            
            return back()->with('success', "Electricity payment successful! Token: $token");
        } else {
            $order->update([
                'status' => 'failed',
                'api_response' => json_encode($response),
            ]);
            $user->balance += $amountUsd;
            $user->save();
            Transaction::add($user->id, $amountUsd, 'credit', 'vtu_refund', "Refund for failed ₦" . number_format($chargedAmountNgn, 2) . " Electricity payment");

            $message = $response['error'][0] ?? ($response['message'] ?? 'Failed to process electricity payment.');
            return back()->with('error', $message);
        }
    }


    // ==========================================
    // CABLE TV
    // ==========================================
    public function cable()
    {
        return view('vtu.cable');
    }

    public function validateIUC(Request $request)
    {
        $request->validate([
            'smart_card_number' => 'required',
            'cablename' => 'required',
        ]);

        $response = $this->vtuApi->validateIUC($request->smart_card_number, $request->cablename);

        if (isset($response['name'])) {
            return response()->json([
                'success' => true,
                'name' => $response['name'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid Smart Card number or could not validate.'
        ]);
    }

    public function purchaseCable(Request $request)
    {
        $request->validate([
            'cablename' => 'required',
            'cableplan' => 'required',
            'smart_card_number' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        $user = Auth::user();
        $baseAmountNgn = (float) $request->amount;
        $markupRate = (float) \App\Models\Setting::get('markup_vtu_cable', 0);
        $chargedAmountNgn = $baseAmountNgn * (1 + ($markupRate / 100));

        $ngnRate = \App\Models\Currency::where('code', 'NGN')->value('exchange_rate') ?? 1420.00;
        $amountUsd = $chargedAmountNgn / $ngnRate;

        if ($user->balance < $amountUsd) {
            return back()->with('error', 'Insufficient balance. Please fund your account.');
        }

        DB::beginTransaction();

        try {
            $user->balance -= $amountUsd;
            $user->save();

            Transaction::add($user->id, $amountUsd, 'debit', 'vtu_cable', "₦" . number_format($chargedAmountNgn, 2) . " Cable TV subscription for {$request->smart_card_number}");

            $order = VtuOrder::create([
                'user_id' => $user->id,
                'service_type' => 'cable',
                'network' => $request->cablename,
                'phone_or_meter' => $request->smart_card_number,
                'plan_or_amount' => $baseAmountNgn,
                'cost' => $amountUsd,
                'status' => 'processing',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('VTU Cable error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while processing your request.');
        }

        $response = $this->vtuApi->buyCable($request->cablename, $request->cableplan, $request->smart_card_number);

        if (isset($response['id']) || (isset($response['status']) && $response['status'] == 'successful')) {
            $order->update([
                'status' => 'completed',
                'api_reference' => $response['id'] ?? null,
                'api_response' => json_encode($response),
            ]);
            
            return back()->with('success', "Cable subscription successful!");
        } else {
            $order->update([
                'status' => 'failed',
                'api_response' => json_encode($response),
            ]);
            
            $user->balance += $amountUsd;
            $user->save();
            Transaction::add($user->id, $amountUsd, 'credit', 'vtu_refund', "Refund for failed ₦" . number_format($chargedAmountNgn, 2) . " Cable subscription");

            $message = $response['error'][0] ?? ($response['message'] ?? 'Failed to process cable subscription.');
            return back()->with('error', $message);
        }
    }
}
