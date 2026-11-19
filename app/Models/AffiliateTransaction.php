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
        $amount = round((float) $amount, 2);
        $type = strtolower((string) $type);
        if ($amount <= 0 || !in_array($type, ['credit', 'debit'], true)) {
            throw new \\InvalidArgumentException('Affiliate transaction must have a positive amount and valid type.');
        }
        $user = \\App\\Models\\User::where('id', $userId)->lockForUpdate()->firstOrFail();
        $after = round((float) $user->commission_balance, 2);
        $before = round($after - ($type === 'credit' ? $amount : -$amount), 2);
        
        return self::create([
            'user_id' => $userId,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'balance_before' => $before - ($type === 'credit' ? $amount : -$amount),
            'balance_after' => $after,
        ]);
    }
}
