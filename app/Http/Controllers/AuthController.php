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
            if ($user->two_factor_enabled) {
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'referral_code' => 'nullable|string',
            'terms' => 'required|accepted',
        ];

        $secret = env('TURNSTILE_SECRET_KEY');
        $isConfigured = !empty($secret) && $secret !== 'your_secret_key_here';

        if ($isConfigured) {
            $rules['cf-turnstile-response'] = 'required';
        }

        $validated = $request->validate($rules, [
            'terms.required' => 'You must agree to our Terms & Conditions to join.',
            'terms.accepted' => 'You must agree to our Terms & Conditions to join.',
            'cf-turnstile-response.required' => 'Security verification is required.',
        ]);

        if ($isConfigured && !$this->verifyTurnstile($request->input('cf-turnstile-response'))) {
            return back()->withErrors(['email' => 'Security verification failed. Please try again.']);
        }

        $referrer = null;
        if ($request->filled('referral_code')) {
            $referrer = User::where('username', $request->referral_code)->first();
        }

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'balance' => 0.00,
            'referred_by' => $referrer ? $referrer->id : null,
        ]);

        event(new Registered($user));

        Auth::login($user, $request->has('remember'));
        
        // Send Welcome Email (Queued for speed)
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->queue(new \App\Mail\WelcomeEmail($user->first_name));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Welcome email queuing failed: " . $e->getMessage());
        }

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

    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();
        return redirect()->intended('dashboard?verified=1');
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
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
                    ? back()->with(['success' => __($status)])
                    : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60))->save();

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
        $request->validate(['code' => 'required|string|size:6']);

        if (!session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        $user = User::findOrFail(session('2fa_user_id'));

        if ($user->two_factor_code === $request->code && now()->lt($user->two_factor_expires_at)) {
            // Success! Reset 2FA fields and log them in
            $user->update([
                'two_factor_code' => null,
                'two_factor_expires_at' => null
            ]);

            Auth::login($user, session('2fa_remember', false));
            $this->recordLogin($user, $request);
            
            session()->forget(['2fa_user_id', '2fa_remember']);
            
            return redirect()->intended($user->isAdmin() ? 'hq/dashboard' : 'dashboard');
        }

        return back()->with('error', 'Invalid or expired verification code.');
    }

    public function resendTwoFactor()
    {
        if (!session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        $user = User::findOrFail(session('2fa_user_id'));
        $this->generateAndSend2FA($user);

        return back()->with('success', 'A new code has been sent to your email.');
    }

    private function generateAndSend2FA($user)
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $user->update([
            'two_factor_code' => $code,
            'two_factor_expires_at' => now()->addMinutes(10)
        ]);

        try {
            \Illuminate\Support\Facades\Mail::send('emails.auth.otp', ['code' => $code, 'name' => $user->first_name], function($message) use ($user) {
                $message->to($user->email)->subject('Security Verification Code 🔒');
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("2FA Email failed: " . $e->getMessage());
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
