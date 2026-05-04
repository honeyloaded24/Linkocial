<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (!$user->referral_code) {
                $user->referral_code = strtoupper(\Illuminate\Support\Str::random(8));
            }
            if (!$user->api_key) {
                $user->api_key = \Illuminate\Support\Str::random(40);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'balance',
        'is_admin',
        'role', // user, reseller, vip, admin
        'referral_code',
        'referred_by',
        'commission_balance',
        'total_earned_commission',
        'total_withdrawn_commission',
        'two_factor_enabled',
        'two_factor_code',
        'two_factor_expires_at',
    ];

    public function isReseller()
    {
        return $this->role === 'reseller';
    }

    public function isVip()
    {
        return $this->role === 'vip';
    }

    public function isAdmin()
    {
        return $this->role === 'admin' || $this->is_admin;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'balance' => 'decimal:4',
            'is_admin' => 'boolean',
            'commission_balance' => 'decimal:4',
            'total_earned_commission' => 'decimal:4',
            'total_withdrawn_commission' => 'decimal:4',
        ];
    }

    /**
     * Get the orders for the user.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
