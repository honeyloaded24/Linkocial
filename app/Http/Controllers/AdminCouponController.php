<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;

class AdminCouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::withCount('usages')->latest()->get();
        return view('hq.coupons.index', compact('coupons'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0.01',
            'min_spend' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'user_limit' => 'required|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);

        Coupon::create([
            'code' => strtoupper($request->code),
            'type' => $request->type,
            'value' => $request->value,
            'min_spend' => $request->min_spend ?? 0.00,
            'usage_limit' => $request->usage_limit,
            'user_limit' => $request->user_limit,
            'expires_at' => $request->expires_at,
            'status' => $request->has('status'),
        ]);

        return back()->with('success', 'Coupon created successfully!');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return back()->with('success', 'Coupon deleted successfully!');
    }

    public function toggle(Coupon $coupon)
    {
        $coupon->update([
            'status' => !$coupon->status
        ]);
        return back()->with('success', 'Coupon status updated!');
    }
}
