<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index()
    {
        $currencies = Currency::orderBy('is_default', 'desc')->get();
        return view('hq.currencies.index', compact('currencies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:3|unique:currencies,code',
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
        ]);

        $data = $request->all();
        if ($request->has('is_default')) {
            Currency::where('is_default', true)->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        Currency::create($data);

        return back()->with('success', 'Currency added successfully!');
    }

    public function update(Request $request, Currency $currency)
    {
        $request->validate([
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
        ]);

        $data = $request->only(['symbol', 'exchange_rate', 'is_active']);
        $data['is_active'] = $request->has('is_active');

        if ($request->has('is_default') && !$currency->is_default) {
            Currency::where('is_default', true)->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        $currency->update($data);

        \Illuminate\Support\Facades\Cache::forget("currency_data_{$currency->code}");

        return back()->with('success', 'Currency updated successfully!');
    }

    public function destroy(Currency $currency)
    {
        if ($currency->is_default) {
            return back()->with('error', 'Cannot delete default currency!');
        }

        \Illuminate\Support\Facades\Cache::forget("currency_data_{$currency->code}");
        $currency->delete();
        return back()->with('success', 'Currency deleted!');
    }

    public function switch(Request $request)
    {
        $request->validate([
            'currency_code' => 'required|string|size:3|exists:currencies,code',
        ]);

        $code = $request->currency_code;
        session(['currency_code' => $code]);

        if (auth()->check()) {
            auth()->user()->update(['currency_code' => $code]);
        }

        return back();
    }
}
