<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends BaseVerifyEmail
{
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your CROPS Account')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Welcome to CROPS — the Rice Yield Prediction & Analysis System for Santiago City.')
            ->line('Please click the button below to verify your email address and activate your account.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('If you did not create this account, no further action is required.')
            ->salutation("Regards,\nCROPS — City Agriculture Office");
    }
}