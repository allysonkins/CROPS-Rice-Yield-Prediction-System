<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'barangay'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the farms owned by this user (farmer).
     */
    public function farms()
    {
        return $this->hasMany(Farm::class, 'user_id');
    }

    /**
     * Redirect URL after email verification (role-aware).
     */
    public function redirectAfterVerification(): string
    {
        return match ($this->role) {
            'admin'  => '/admin/dashboard',
            'staff'  => '/staff/dashboard',
            'farmer' => '/farmer/dashboard',
            default  => '/',
        };
    }

    /**
     * Use our custom branded verification email.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }
}