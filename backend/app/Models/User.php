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
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'barangay',
    'rsbsa_number',
    'phone',
    'email_verified_at',
    'verified_by_cao_at',
    'verified_by_cao_id',
    'pin_encrypted',       // ← added
    'pin_generated_at',    // ← added
])]
#[Hidden(['password', 'remember_token', 'pin_encrypted'])]
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'verified_by_cao_at' => 'datetime',
            'pin_generated_at'   => 'datetime',
            'password'           => 'hashed',
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════

    public function farms()
    {
        return $this->hasMany(Farm::class, 'user_id');
    }

    /**
     * Activity log entries for this user.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    // ═══════════════════════════════════════════════════════════
    // EMAIL VERIFICATION
    // ═══════════════════════════════════════════════════════════

    public function redirectAfterVerification(): string
    {
        return match ($this->role) {
            'admin'  => '/admin/dashboard',
            'staff'  => '/staff/dashboard',
            'farmer' => '/farmer/dashboard',
            default  => '/',
        };
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    // ═══════════════════════════════════════════════════════════
    // ROLE HELPERS
    // ═══════════════════════════════════════════════════════════

    public function isVerifiedByCao(): bool
    {
        return $this->role === 'farmer' && $this->verified_by_cao_at !== null;
    }

    public function isFarmer(): bool
    {
        return $this->role === 'farmer';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // ═══════════════════════════════════════════════════════════
    // PIN MANAGEMENT
    // ═══════════════════════════════════════════════════════════

    /**
     * Decrypted PIN (returns null if none stored or decryption fails).
     * Access as $user->pin
     */
    public function getPinAttribute(): ?string
    {
        if (!$this->pin_encrypted) {
            return null;
        }
        try {
            return Crypt::decryptString($this->pin_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set + persist a new PIN (encrypted).
     */
    public function setPin(string $plainPin): void
    {
        $this->pin_encrypted    = Crypt::encryptString($plainPin);
        $this->pin_generated_at = now();
        $this->save();
    }

    /**
     * Clear the stored PIN (called when a farmer sets their own password).
     */
    public function clearPin(): void
    {
        $this->pin_encrypted    = null;
        $this->pin_generated_at = null;
        $this->save();
    }
}