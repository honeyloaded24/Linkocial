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

    protected $appends = ['customer_description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get customer-safe description without internal provider or pricing metadata.
     */
    public function getCustomerDescriptionAttribute(): ?string
    {
        $desc = $this->attributes['description'] ?? null;
        if ($desc === null) {
            return null;
        }
        return self::sanitizeDescriptionForCustomer($desc);
    }

    /**
     * Sanitize transaction description for customer-facing views.
     * Strips provider names, server routing identifiers, wholesale provider costs,
     * internal exchange rates, and internal markup amounts.
     */
    public static function sanitizeDescriptionForCustomer(?string $description): ?string
    {
        if ($description === null || trim($description) === '') {
            return $description;
        }

        $sanitized = $description;

        // 1. Remove bracketed rate & markup blocks: e.g. [Rate: $1.13 @ ₦1,350.00/$, Markup: ₦1,000.00]
        $sanitized = preg_replace('/\s*\[Rate:[^\]]*\]?/i', '', $sanitized);
        $sanitized = preg_replace('/\s*\[Markup:[^\]]*\]?/i', '', $sanitized);

        // 2. Remove provider/server bracketed tags: e.g. [Server 1], [Server 2 (Grizzly)], [SMSPool], [Grizzly SMS], [Server 2]
        $sanitized = preg_replace('/\s*\[(?:Server\s*\d*(?:\s*\([^)]*\))?|SMSPool|Grizzly(?:\s*SMS)?)[^\]]*\]/i', '', $sanitized);

        // 3. Remove any trailing unclosed rate/markup fragments
        $sanitized = preg_replace('/\s*\[Rate:.*$/i', '', $sanitized);
        $sanitized = preg_replace('/\s*\[Markup:.*$/i', '', $sanitized);

        // 4. Normalize spacing around punctuation and parentheses
        $sanitized = preg_replace('/\s+/', ' ', $sanitized);
        $sanitized = preg_replace('/\s*\)/', ')', $sanitized);
        $sanitized = preg_replace('/\(\s*/', '(', $sanitized);

        // 5. Ensure balanced parentheses if trailing closing bracket was stripped or missing
        $openCount = substr_count($sanitized, '(');
        $closeCount = substr_count($sanitized, ')');
        if ($openCount > $closeCount) {
            $sanitized .= str_repeat(')', $openCount - $closeCount);
        }

        return trim($sanitized);
    }

    public static function add($userId, $amount, $type, $txType, $description = null, $ref = null, $createdAt = null)
    {
        $amount = round((float) $amount, 4);
        $type = strtolower((string) $type);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transaction amount must be greater than zero.');
        }

        if (!in_array($type, ['credit', 'debit'], true)) {
            throw new \InvalidArgumentException('Transaction type must be credit or debit.');
        }

        $user = \App\Models\User::findOrFail($userId);
        // Wallet mutations occur before this call in existing flows. Snapshot the
        // post-mutation balance and derive the prior balance from the ledger amount.
        $after = round((float) $user->balance, 4);

        $reference = $ref ?? strtoupper(Str::uuid()->toString());
        if ($ref !== null && self::where('reference', $ref)->exists()) {
            throw new \RuntimeException('Transaction reference already exists: ' . $ref);
        }

        $data = [
            'user_id' => $userId,
            'amount' => $amount,
            'type' => $type,
            'transaction_type' => $txType,
            'balance_before' => round($after - ($type === 'credit' ? $amount : -$amount), 4),
            'balance_after' => $after,
            'description' => $description,
            'reference' => $reference,
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
