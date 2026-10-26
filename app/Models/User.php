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
        'referral_code',
        'referred_by',
        'currency_code',
        'legacy_balance_recovered_at',
        'legacy_balance_usd',
        'legacy_recovery_rate',
        'currency_reconciliation_status',
        'reconciliation_notes',
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

    public function getNameAttribute(): string
    {
        $full = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
        return $full ?: ($this->username ?? 'User');
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
            'legacy_balance_recovered_at' => 'datetime',
            'legacy_balance_usd' => 'decimal:4',
            'legacy_recovery_rate' => 'decimal:4',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_expires_at' => 'datetime',
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

    /**
     * Get the support tickets for the user.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the transactions for the user.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Atomically deduct balance ensuring it never drops below 0.
     * Uses a conditional WHERE clause directly in SQL to prevent race conditions.
     *
     * @param float|int $amount
     * @return bool True if deduction succeeded, false if insufficient balance.
     */
    public function deductBalance(float|int $amount): bool
    {
        $amount = round((float) $amount, 4);
        if ($amount <= 0) {
            return true;
        }

        // Atomic database update with condition: balance >= amount
        $affected = \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $this->id)
            ->where('balance', '>=', $amount)
            ->decrement('balance', $amount);

        if ($affected > 0) {
            $this->refresh();
            return true;
        }

        return false;
    }

    /**
     * Atomically add to balance.
     *
     * @param float|int $amount
     * @return void
     */
    public function addBalance(float|int $amount): void
    {
        $amount = round((float) $amount, 4);
        if ($amount > 0) {
            \Illuminate\Support\Facades\DB::table('users')
                ->where('id', $this->id)
                ->increment('balance', $amount);
            $this->refresh();
        }
    }

    public function virtualAccounts()
    {
        return $this->hasMany(UserVirtualAccount::class);
    }

    public function virtualAccount(string $bankId = '9PSB')
    {
        return $this->virtualAccounts()->where('bank_id', strtoupper($bankId))->first();
    }
}
