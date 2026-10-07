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

    public static function add($userId, $amount, $type, $txType, $description = null, $ref = null, $createdAt = null)
    {
        $user = \App\Models\User::find($userId);
        $before = (float)$user->balance;
        
        $data = [
            'user_id' => $userId,
            'amount' => $amount,
            'type' => $type,
            'transaction_type' => $txType,
            'balance_before' => $before - ($type === 'credit' ? $amount : -$amount),
            'balance_after' => $before,
            'description' => $description,
            'reference' => $ref ?? strtoupper(Str::random(10)),
        ];

        if ($createdAt) {
            $data['created_at'] = $createdAt;
            $data['updated_at'] = $createdAt;
        }

        return self::create($data);
    }

    /**
     * Scope to order transactions so debits precede corresponding refunds,
     * while newest time periods always stay at the top.
     */
    public function scopeOrdered($query)
    {
        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        $minuteExpr = ($driver === 'sqlite') 
            ? "strftime('%Y-%m-%d %H:%M', created_at)" 
            : "DATE_FORMAT(created_at, '%Y-%m-%d %H:%i')";

        return $query->orderByRaw("{$minuteExpr} DESC")
                     ->orderByRaw("CASE WHEN type = 'debit' THEN 0 ELSE 1 END ASC")
                     ->orderBy('created_at', 'desc')
                     ->orderBy('id', 'asc');
    }
}
