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
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($request->user()->redirectAfterVerification());
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($request->user()->redirectAfterVerification())
                ->with('status', 'Your email is already verified.');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));

            if (function_exists('log_activity')) {
                log_activity('email_verified', 'Email verified', $request->user());
            }
        }

        return redirect($request->user()->redirectAfterVerification())
            ->with('success', 'Your email has been verified! Welcome to CROPS.');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($request->user()->redirectAfterVerification());
        }

        $request->user()->sendEmailVerificationNotification();

        if (function_exists('log_activity')) {
            log_activity('verification_sent', 'Verification email resent', $request->user());
        }

        return back()->with('status', 'verification-link-sent');
    }
}