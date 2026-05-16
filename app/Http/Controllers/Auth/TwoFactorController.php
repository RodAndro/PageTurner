<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    /**
     * Enable two-factor authentication for the user.
     */
    public function enable(Request $request)
    {
        $user = $request->user();

        // Generate a secret (6-digit code for email-based 2FA)
        $secret = $this->generateSecret();
        
        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => now(),
        ]);

        // Send notification
        try {
            $user->notify(new \App\Notifications\TwoFactorEnabledNotification());
        } catch (\Exception $e) {
            \Log::error('Failed to send 2FA enabled notification: ' . $e->getMessage());
        }

        return redirect()->route('profile.edit')
            ->with('status', '2fa-enabled')
            ->with('recovery_codes', $recoveryCodes);
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        // Send notification
        try {
            $user->notify(new \App\Notifications\TwoFactorDisabledNotification());
        } catch (\Exception $e) {
            \Log::error('Failed to send 2FA disabled notification: ' . $e->getMessage());
        }

        return redirect()->route('profile.edit')
            ->with('status', '2fa-disabled');
    }

    /**
     * Show the two-factor challenge view.
     */
    public function challenge(Request $request)
    {
        // Check if 2FA session exists
        if (!$request->session()->has('2fa:user:id')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Session expired. Please login again.'
            ]);
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Verify the two-factor authentication code.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $userId = $request->session()->get('2fa:user:id');

        if (!$userId) {
            return redirect()->route('login')->withErrors([
                'code' => 'Session expired. Please login again.',
            ]);
        }

        // Fetch fresh user from database
        $user = \App\Models\User::find($userId);

        if (!$user) {
            return redirect()->route('login')->withErrors([
                'code' => 'User not found. Please login again.',
            ]);
        }

        $code = $request->input('code');

        // Check if it's a recovery code
        if ($this->isValidRecoveryCode($user, $code)) {
            $this->removeUsedRecoveryCode($user, $code);
            
            Auth::login($user, $request->session()->get('2fa:remember'));
            $request->session()->forget(['2fa:user:id', '2fa:remember', '2fa:email_sent']);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Check if it's the current 2FA code
        $validCode = decrypt($user->two_factor_secret);
        
        if ($code === $validCode) {
            Auth::login($user, $request->session()->get('2fa:remember'));
            $request->session()->forget(['2fa:user:id', '2fa:remember', '2fa:email_sent']);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        }

        return back()->withErrors([
            'code' => 'The provided code is invalid.',
        ])->onlyInput('code');
    }

    /**
     * Resend the 2FA code via email.
     */
    public function resend(Request $request)
    {
        $userId = $request->session()->get('2fa:user:id');

        if (!$userId) {
            return redirect()->route('login')->withErrors([
                'code' => 'Session expired. Please login again.',
            ]);
        }

        // Fetch fresh user from database
        $user = \App\Models\User::find($userId);

        if (!$user) {
            return redirect()->route('login')->withErrors([
                'code' => 'User not found. Please login again.',
            ]);
        }

        // Generate new code
        $code = $this->generateSecret();
        $user->update([
            'two_factor_secret' => encrypt($code),
        ]);

        // Send email with code
        try {
            $user->notify(new \App\Notifications\TwoFactorCodeNotification($code));
            $request->session()->put('2fa:email_sent', true);
            return back()->with('status', 'A new verification code has been sent to your email.');
        } catch (\Exception $e) {
            \Log::error('Failed to send 2FA code: ' . $e->getMessage());
            $request->session()->put('2fa:email_sent', false);
            return back()->withErrors(['code' => 'Failed to send email. Please check your email configuration or try again later.']);
        }
    }

    /**
     * Generate recovery codes.
     */
    public function showRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (!$user->two_factor_enabled) {
            return redirect()->route('profile.edit');
        }

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        return view('auth.recovery-codes', compact('recoveryCodes'));
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $recoveryCodes = $this->generateRecoveryCodes();
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return redirect()->route('profile.edit')
            ->with('status', '2fa-recovery-regenerated')
            ->with('recovery_codes', $recoveryCodes);
    }

    /**
     * Generate a 6-digit secret for email-based 2FA.
     */
    protected function generateSecret(): string
    {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate recovery codes.
     */
    protected function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::upper(Str::random(10));
        }
        return $codes;
    }

    /**
     * Check if the code is a valid recovery code.
     */
    protected function isValidRecoveryCode($user, string $code): bool
    {
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        return in_array(Str::upper($code), $recoveryCodes);
    }

    /**
     * Remove a used recovery code.
     */
    protected function removeUsedRecoveryCode($user, string $code): void
    {
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        $recoveryCodes = array_diff($recoveryCodes, [Str::upper($code)]);
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode(array_values($recoveryCodes))),
        ]);
    }
}
