<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'amount', 'type', 'transaction_type', 
        'balance_before', 'balance_after', 'description', 'reference'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function add($userId, $amount, $type, $txType, $description = null, $ref = null)
    {
        $user = \App\Models\User::find($userId);
        $before = (float)$user->balance;
        
        // This helper assumes balance has already been updated or will be updated.
        // Usually, we call this RIGHT AFTER saving the user balance.
        return self::create([
            'user_id' => $userId,
            'amount' => $amount,
            'type' => $type,
            'transaction_type' => $txType,
            'balance_before' => $before - ($type === 'credit' ? $amount : -$amount),
            'balance_after' => $before,
            'description' => $description,
            'reference' => $ref ?? strtoupper(Str::random(10)),
        ]);
    }
}
