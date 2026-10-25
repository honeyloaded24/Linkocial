<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index()
    {
        return redirect()->route('hq.dashboard')->with('info', 'Multi-currency management is disabled. Platform operates exclusively in Nigerian Naira (NGN).');
    }

    public function store(Request $request)
    {
        return back()->with('error', 'Additional currencies cannot be added. The platform operates exclusively in NGN.');
    }

    public function update(Request $request, Currency $currency)
    {
        return back()->with('error', 'Currency updates are disabled. The platform operates exclusively in NGN.');
    }

    public function destroy(Currency $currency)
    {
        return back()->with('error', 'Currencies cannot be deleted or modified.');
    }

    public function switch(Request $request)
    {
        // Strictly force session and user currency to NGN
        session(['currency_code' => 'NGN']);

        if (auth()->check()) {
            auth()->user()->update(['currency_code' => 'NGN']);
        }

        return back()->with('info', 'The platform exclusively uses Nigerian Naira (NGN).');
    }
}
