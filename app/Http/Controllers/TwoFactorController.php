<?php

namespace App\Http\Controllers;

use App\Services\AuditLoggerService;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    /**
     * Show 2FA Setup page (renders QR code & secret key).
     */
    public function showSetup()
    {
        $user = Auth::user();

        // If secret key doesn't exist yet, generate new secret and recovery codes
        if (!$user->two_factor_secret) {
            $secret = TwoFactorService::generateSecretKey(16);
            $recoveryCodes = TwoFactorService::generateRecoveryCodes(8);

            $user->update([
                'two_factor_secret' => $secret,
                'two_factor_recovery_codes' => $recoveryCodes,
                'two_factor_confirmed_at' => null,
            ]);
        }

        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode(
            "otpauth://totp/Forensic%20Toolkit:" . rawurlencode($user->email) . "?secret=" . $user->two_factor_secret . "&issuer=Forensic%20Toolkit"
        );

        return view('auth.two-factor-setup', [
            'user' => $user,
            'qrCodeUrl' => $qrCodeUrl,
            'secretKey' => $user->two_factor_secret,
            'recoveryCodes' => $user->two_factor_recovery_codes ?: [],
            'isConfirmed' => !is_null($user->two_factor_confirmed_at),
        ]);
    }

    /**
     * Confirm & Enable 2FA after entering valid 6-digit TOTP code.
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = Auth::user();

        if (TwoFactorService::verifyCode($user->two_factor_secret, $request->code)) {
            $user->update([
                'two_factor_confirmed_at' => now(),
            ]);

            AuditLoggerService::log('enable_two_factor_auth', get_class($user), $user->id, [
                'user_email' => $user->email,
            ]);

            return redirect()->route('profile.edit')->with('status', 'two-factor-authentication-enabled');
        }

        return back()->withErrors(['code' => 'The provided 6-digit TOTP code is invalid or has expired. Please check your authenticator app.']);
    }

    /**
     * Disable 2FA for account.
     */
    public function disable(Request $request)
    {
        $user = Auth::user();

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        AuditLoggerService::log('disable_two_factor_auth', get_class($user), $user->id, [
            'user_email' => $user->email,
        ]);

        return redirect()->route('profile.edit')->with('status', 'two-factor-authentication-disabled');
    }

    /**
     * Show 2FA Challenge page (Secondary Login prompt).
     */
    public function showChallenge()
    {
        if (!session()->has('2fa:user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Verify secondary login TOTP code or Recovery Code.
     */
    public function verifyChallenge(Request $request)
    {
        $userId = session()->get('2fa:user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::findOrFail($userId);

        $code = $request->input('code');
        $recoveryCode = $request->input('recovery_code');

        // Check 6-digit TOTP code
        if ($code && TwoFactorService::verifyCode($user->two_factor_secret, $code)) {
            Auth::login($user, session()->get('2fa:remember', false));
            session()->forget(['2fa:user_id', '2fa:remember']);

            AuditLoggerService::log('two_factor_login_success', get_class($user), $user->id, [
                'auth_method' => 'totp',
            ]);

            return redirect()->intended(route('cases.index'));
        }

        // Check Emergency Recovery Code
        if ($recoveryCode) {
            $recoveryCodes = $user->two_factor_recovery_codes ?: [];
            $key = array_search(trim($recoveryCode), $recoveryCodes);

            if ($key !== false) {
                // Consume recovery code
                unset($recoveryCodes[$key]);
                $user->update(['two_factor_recovery_codes' => array_values($recoveryCodes)]);

                Auth::login($user, session()->get('2fa:remember', false));
                session()->forget(['2fa:user_id', '2fa:remember']);

                AuditLoggerService::log('two_factor_login_recovery_code_used', get_class($user), $user->id, [
                    'auth_method' => 'recovery_code',
                ]);

                return redirect()->intended(route('cases.index'));
            }
        }

        return back()->withErrors(['code' => 'The authentication code or recovery key provided was invalid.']);
    }
}
