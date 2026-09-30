<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function notice(Request $request)
    {
        $user = $request->user();

        // Farmers never verify email — they use CAO verification + PIN login
        if ($user->role === 'farmer') {
            return redirect()->route('farmer.dashboard');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect($user->redirectAfterVerification());
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request)
    {
        $user = $request->user();

        // Farmers should never reach here
        if ($user->role === 'farmer') {
            return redirect()->route('farmer.dashboard');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect($user->redirectAfterVerification())
                ->with('status', 'Your email is already verified.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            if (function_exists('log_activity')) {
                log_activity('email_verified', 'Email verified', $user, [
                    'email' => $user->email,
                    'role'  => $user->role,
                ]);
            }
        }

        return redirect($user->redirectAfterVerification())
            ->with('success', 'Your email has been verified! Welcome to CROPS.');
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        // Farmers shouldn't trigger resend
        if ($user->role === 'farmer') {
            return redirect()->route('farmer.dashboard');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect($user->redirectAfterVerification());
        }

        $user->sendEmailVerificationNotification();

        if (function_exists('log_activity')) {
            log_activity('verification_sent', 'Verification email resent', $user, [
                'email' => $user->email,
                'role'  => $user->role,
            ]);
        }

        return back()->with('status', 'verification-link-sent');
    }
}