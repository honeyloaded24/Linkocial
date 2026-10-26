<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Show the login view
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Show the registration view
     */
    public function showRegister(Request $request)
    {
        $ref = $request->query('ref');
        return view('auth.register', compact('ref'));
    }

    /**
     * Handle authentication attempt
     */
    public function login(Request $request)
    {
        $rules = [
            'login' => ['required', 'string'],
            'password' => ['required'],
        ];

        $secret = env('TURNSTILE_SECRET_KEY');
        $isConfigured = !empty($secret) && $secret !== 'your_secret_key_here';

        if ($isConfigured) {
            $rules['cf-turnstile-response'] = ['required'];
        }

        $request->validate($rules, [
            'cf-turnstile-response.required' => 'Security verification is required.',
        ]);

        if ($isConfigured && !$this->verifyTurnstile($request->input('cf-turnstile-response'))) {
            return back()->withErrors(['login' => 'Security verification failed. Please try again.']);
        }

        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $loginType => $request->login,
            'password' => $request->password,
        ];

        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // 1. Prevent banned users from accessing the site
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account has been suspended. Please contact support.',
                ]);
            }

            // 2. Handle Two-Factor Authentication
            $is2faGloballyEnabled = (bool) \App\Models\Setting::get('two_factor_system_enabled', true);
            if ($user->two_factor_enabled && $is2faGloballyEnabled) {
                Auth::logout();
                $request->session()->put('2fa_user_id', $user->id);
                $request->session()->put('2fa_remember', $remember);
                
                $this->generateAndSend2FA($user);
                
                return redirect()->route('two-factor.index');
            }

            // 3. Record Login History
            $this->recordLogin($user, $request);

            $request->session()->regenerate();
            
            // Redirect admins to HQ dashboard, users to regular dashboard
            if ($user->isAdmin()) {
                return redirect()->intended('hq/dashboard');
            }

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->onlyInput('login');
    }
    /**
     * Handle user registration (API or Web)
     */
    public function register(Request $request)
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\s\-\'\.]+$/u'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\s\-\'\.]+$/u'],
            'username' => ['required', 'string', 'alpha_dash', 'max:30', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'referral_code' => ['nullable', 'string', 'alpha_dash', 'max:30'],
            'terms' => ['required', 'accepted'],
        ];

        // Explicit check against URLs, domains, and promotional spam content in names & username
        $spamPattern = '/(https?:\/\/|www\.|\.com|\.org|\.net|\.io|\.xyz|\.ru|\.top|\.online|t\.me\/|telegram|crypto|bitcoin|usdt|ethereum|wallet|giveaway|investment)/i';
        if (
            preg_match($spamPattern, (string)$request->input('first_name')) ||
            preg_match($spamPattern, (string)$request->input('last_name')) ||
            preg_match($spamPattern, (string)$request->input('username'))
        ) {
            return back()->withErrors(['first_name' => 'Name or username contains prohibited promotional, crypto, or link content.'])->withInput();
        }

        $secret = env('TURNSTILE_SECRET_KEY');
        $isConfigured = !empty($secret) && $secret !== 'your_secret_key_here';

        if ($isConfigured) {
            $rules['cf-turnstile-response'] = 'required';
        }

        $validated = $request->validate($rules, [
            'terms.required' => 'You must agree to our Terms & Conditions to join.',
            'terms.accepted' => 'You must agree to our Terms & Conditions to join.',
            'cf-turnstile-response.required' => 'Security verification is required.',
            'first_name.regex' => 'The first name may only contain letters, spaces, hyphens, and apostrophes.',
            'last_name.regex' => 'The last name may only contain letters, spaces, hyphens, and apostrophes.',
            'first_name.max' => 'The first name may not be greater than 50 characters.',
            'last_name.max' => 'The last name may not be greater than 50 characters.',
            'username.alpha_dash' => 'The username may only contain letters, numbers, dashes, and underscores.',
            'username.max' => 'The username may not be greater than 30 characters.',
        ]);

        if ($isConfigured && !$this->verifyTurnstile($request->input('cf-turnstile-response'))) {
            return back()->withErrors(['email' => 'Security verification failed. Please try again.']);
        }

        $referrer = null;
        if ($request->filled('referral_code')) {
            $referrer = User::where('username', $request->referral_code)->first();
        }

        $user = User::create([
            'first_name' => trim(strip_tags($validated['first_name'])),
            'last_name' => trim(strip_tags($validated['last_name'])),
            'username' => strtolower(trim(strip_tags($validated['username']))),
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
            'balance' => 0.00,
            'referred_by' => $referrer ? $referrer->id : null,
        ]);

        event(new Registered($user));

        Auth::login($user, $request->has('remember'));

        return redirect()->route('dashboard');
    }

    /**
     * Email Verification Methods
     */
    public function showVerifyEmail(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended('dashboard')
                    : view('auth.verify-email');
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        // Guard against browser speculative link prefetching
        if (
            $request->header('Purpose') === 'prefetch' ||
            $request->header('Sec-Purpose') === 'prefetch' ||
            $request->header('X-Purpose') === 'preview' ||
            $request->header('X-Moz') === 'prefetch'
        ) {
            return response()->noContent();
        }

        $user = User::find($id);

        if (!$user || !hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect()->route('login')->with('error', 'The verification link is invalid or has expired.');
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new \Illuminate\Auth\Events\Verified($user));
        }

        // Auto-login or switch to the verified user's session
        if (!Auth::check() || Auth::id() !== $user->id) {
            Auth::login($user, true);
        }

        return redirect()->route('dashboard', ['verified' => 1])->with('success', 'Email verified successfully! Welcome to Linkocial.');
    }

    public function resendVerificationEmail(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('success', 'Verification link sent!');
    }

    /**
     * Password Reset Methods
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->email))]);
        $request->validate(['email' => 'required|email']);

        try {
            $status = Password::sendResetLink($request->only('email'));

            return $status === Password::RESET_LINK_SENT
                        ? back()->with(['success' => __($status)])
                        : back()->withErrors(['email' => __($status)]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Password reset email failed: " . $e->getMessage());
            return back()->withErrors(['email' => 'Failed to send password reset email. Please check SMTP settings or try again later.']);
        }
    }

    public function showResetPassword(Request $request, $token)
    {
        $email = $request->email ? strtolower(trim((string) $request->email)) : null;
        return view('auth.reset-password', ['token' => $token, 'email' => $email]);
    }

    public function resetPassword(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->email))]);
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('success', __($status))
                    : back()->withErrors(['email' => [__($status)]]);
    }

    /**
     * 2FA Logic
     */
    public function showTwoFactor()
    {
        if (!session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }
        return view('auth.two-factor');
    }

    public function verifyTwoFactor(Request $request)
    {
        $rawCode = (string) $request->code;
        $cleanCode = preg_replace('/\D/', '', $rawCode);

        if (strlen($cleanCode) !== 6) {
            return back()->with('error', 'Please enter a valid 6-digit verification code.');
        }

        if (!session()->has('2fa_user_id')) {
            return redirect()->route('login')->with('error', 'Your verification session has expired. Please log in again.');
        }

        $user = User::find(session('2fa_user_id'));
        if (!$user) {
            session()->forget(['2fa_user_id', '2fa_remember']);
            return redirect()->route('login')->with('error', 'User not found. Please log in again.');
        }

        $now = now();
        $expiresAt = $user->two_factor_expires_at;
        $isNotExpired = false;
        if ($expiresAt) {
            $carbonExpires = $expiresAt instanceof \Carbon\Carbon ? $expiresAt : \Carbon\Carbon::parse($expiresAt);
            $isNotExpired = $now->lte($carbonExpires);
        }

        if ($user->two_factor_code && hash_equals((string) $user->two_factor_code, $cleanCode) && $isNotExpired) {
            // Success! Reset 2FA fields and log them in
            $user->forceFill([
                'two_factor_code' => null,
                'two_factor_expires_at' => null
            ])->save();

            $remember = session('2fa_remember', false);
            session()->forget(['2fa_user_id', '2fa_remember']);
            $request->session()->regenerate();

            Auth::login($user, $remember);
            $this->recordLogin($user, $request);
            
            return redirect()->intended($user->isAdmin() ? 'hq/dashboard' : 'dashboard');
        }

        return back()->with('error', 'Invalid or expired verification code. Please check your latest email or request a new code.');
    }

    public function resendTwoFactor()
    {
        if (!session()->has('2fa_user_id')) {
            return redirect()->route('login')->with('error', 'Your verification session has expired. Please log in again.');
        }

        $user = User::findOrFail(session('2fa_user_id'));
        $this->generateAndSend2FA($user, true); // Force new code on explicit resend click

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    private function generateAndSend2FA($user, $forceNew = false): bool
    {
        $existingCode = $user->two_factor_code;
        $expiresAt = $user->two_factor_expires_at;

        $reuseCode = false;
        if (!$forceNew && $existingCode && $expiresAt) {
            $carbonExpires = $expiresAt instanceof \Carbon\Carbon ? $expiresAt : \Carbon\Carbon::parse($expiresAt);
            // If code is still valid and has at least 5 minutes left
            if ($carbonExpires->isFuture() && $carbonExpires->diffInMinutes(now()) <= 15) {
                $code = $existingCode;
                $reuseCode = true;
            }
        }

        if (!$reuseCode) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->forceFill([
                'two_factor_code' => $code,
                'two_factor_expires_at' => now()->addMinutes(15)
            ])->save();
        }

        try {
            \Illuminate\Support\Facades\Mail::send('emails.auth.otp', ['code' => $code, 'name' => $user->first_name], function($message) use ($user) {
                $message->to($user->email)->subject('Security Verification Code 🔒');
            });
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("2FA Email failed for user #{$user->id} ({$user->email}): " . $e->getMessage());
            return false;
        }
    }

    private function recordLogin($user, $request)
    {
        \App\Models\LoginLog::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'location' => 'Unknown',
        ]);
    }

    /**
     * Log the user out of the application
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function verifyTurnstile($response)
    {
        $secret = env('TURNSTILE_SECRET_KEY');
        if ($secret === 'your_secret_key_here' || empty($secret)) {
            return true; // Bypass verification if keys aren't set yet to avoid blocking the site
        }

        try {
            $verifyResponse = \Illuminate\Support\Facades\Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $response,
                'remoteip' => request()->ip(),
            ]);

            return $verifyResponse->json()['success'] ?? false;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Turnstile verification error: " . $e->getMessage());
            return false;
        }
    }
}
