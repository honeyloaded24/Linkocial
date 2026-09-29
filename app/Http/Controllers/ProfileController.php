<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function index()
    {
        return view('profile.index', ['user' => Auth::user()]);
    }

    public function generateApiKey()
    {
        $user = Auth::user();
        $newKey = Str::random(40);
        $user->api_key = $newKey;
        $user->save();

        \App\Models\ActivityLog::log("Generated new API key", $user);

        return back()->with('success', 'New API key generated successfully.')->with('new_api_key', $newKey);
    }

    public function deleteApiKey()
    {
        $user = Auth::user();
        $user->api_key = null;
        $user->save();

        \App\Models\ActivityLog::log("Deleted API key", $user);

        return back()->with('success', 'API key deleted.');
    }

    public function toggle2fa(Request $request)
    {
        $user = Auth::user();
        $user->two_factor_enabled = !$user->two_factor_enabled;
        if (!$user->two_factor_enabled) {
            $user->two_factor_code = null;
            $user->two_factor_expires_at = null;
        }
        $user->save();

        $status = $user->two_factor_enabled ? 'enabled' : 'disabled';
        \App\Models\ActivityLog::log("Two-Factor Authentication $status", $user);

        return back()->with('success', "Two-Factor Authentication has been $status.");
    }
}
