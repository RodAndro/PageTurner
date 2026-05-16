<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // Check if 2FA is enabled for this user
        if ($user && $user->two_factor_enabled) {
            // Logout the user temporarily
            Auth::logout();

            // Generate a new 2FA code
            $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->update([
                'two_factor_secret' => encrypt($code),
            ]);

            // Send the code via email
            try {
                $user->notify(new \App\Notifications\TwoFactorCodeNotification($code));
                $emailSent = true;
            } catch (\Exception $e) {
                Log::error('Failed to send 2FA code: ' . $e->getMessage());
                $emailSent = false;
            }

            // Store user ID (not object) in session for 2FA challenge
            $request->session()->put('2fa:user:id', $user->id);
            $request->session()->put('2fa:remember', $request->boolean('remember'));
            $request->session()->put('2fa:email_sent', $emailSent);

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
