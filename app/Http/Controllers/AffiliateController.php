<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $minPayout = (float) \App\Models\Setting::get('affiliate_min_payout', 5000.00);

        $request->validate([
            'amount' => "required|numeric|min:{$minPayout}",
            'bank_details' => 'required|string|min:15',
        ]);

        $created = DB::transaction(function () use ($user, $request) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            if ((float) $lockedUser->commission_balance < (float) $request->amount) {
                return 'insufficient';
            }

            // Serialize withdrawal requests for this affiliate so concurrent requests
            // cannot both pass the pending-request check.
            $pending = WithdrawalRequest::where('user_id', $lockedUser->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->exists();

            if ($pending) {
                return 'pending';
            }

            WithdrawalRequest::create([
                'user_id' => $lockedUser->id,
                'amount' => round((float) $request->amount, 2),
                'bank_details' => $request->bank_details,
                'status' => 'pending',
            ]);

            return 'created';
        });

        if ($created === 'insufficient') {
            return back()->with('error', 'Insufficient commission balance.');
        }

        if ($created === 'pending') {
            return back()->with('error', 'You already have a pending withdrawal request.');
        }
        return back()->with('success', 'Your withdrawal request has been submitted for approval.');
    }
}
