<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'password',
        'role',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getNameAttribute(): string
    {
        $name = trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
        return $this->suffix ? "{$name} {$this->suffix}" : $name;
    }

    /**
     * Check if user is an administrator.
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->role === 'admin';
    }

 
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->first_name ??= 'Test';
            $user->last_name ??= 'User';
            $user->password ??= Hash::make('password');
        });
    }


    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the recovery codes for the user.
     */
    public function recoveryCodes(): array
    {
        return json_decode($this->two_factor_recovery_codes, true) ?? [];
    }

    /**
     * Replace the recovery codes for the user.
     */
    public function replaceRecoveryCodes(array $codes): void
    {
        $this->two_factor_recovery_codes = json_encode($codes);
        $this->save();
    }
}
