<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateTransaction extends Model
{
    protected $fillable = ['user_id', 'amount', 'type', 'description', 'balance_before', 'balance_after'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log($userId, $amount, $type, $description)
    {
        $user = \App\Models\User::find($userId);
        $before = (float)$user->commission_balance;
        
        return self::create([
            'user_id' => $userId,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'balance_before' => $before - ($type === 'credit' ? $amount : -$amount),
            'balance_after' => $before,
        ]);
    }
}
