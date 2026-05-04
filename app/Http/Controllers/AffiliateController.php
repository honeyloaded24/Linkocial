<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AffiliateController extends Controller
{
    /**
     * User's Affiliate Dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $referralsCount = User::where('referred_by', $user->id)->count();
        $transactions = \App\Models\AffiliateTransaction::where('user_id', $user->id)->latest()->get();

        return view('affiliate.index', compact('user', 'referralsCount', 'transactions'));
    }

    /**
     * Request a withdrawal of commissions
     */
    public function requestWithdrawal(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'amount' => 'required|numeric|min:1000', // Min withdrawal e.g. 1000 NGN
            'bank_details' => 'required|string|min:20',
        ]);

        if ($user->commission_balance < $request->amount) {
            return back()->with('error', 'Insufficient commission balance.');
        }

        // Check if there's already a pending request
        $pending = WithdrawalRequest::where('user_id', $user->id)->where('status', 'pending')->exists();
        if ($pending) {
            return back()->with('error', 'You already have a pending withdrawal request.');
        }

        WithdrawalRequest::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'bank_details' => $request->bank_details,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Your withdrawal request has been submitted for approval.');
    }
}
