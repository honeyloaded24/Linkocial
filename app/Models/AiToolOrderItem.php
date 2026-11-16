<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiToolOrderItem extends Model
{
    use HasFactory;

    protected $table = 'ai_tool_order_items';

    protected $fillable = [
        'ai_tool_order_id',
        'item_index',
        'code',
        'details',
    ];

    protected $casts = [
        'item_index' => 'integer',
    ];

    /**
     * Never expose raw values in serialization unless explicitly requested.
     */
    protected $hidden = ['code', 'details'];

    /**
     * Safely decrypt the code attribute without crashing on key rotation or corrupt MAC.
     */
    public function getCodeAttribute($value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Throwable) {
            // If raw text or different encryption key was used, return safe string
            return $value;
        }
    }

    /**
     * Safely encrypt code attribute at rest.
     */
    public function setCodeAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['code'] = $value;
            return;
        }

        try {
            $this->attributes['code'] = \Illuminate\Support\Facades\Crypt::encryptString((string) $value);
        } catch (\Throwable) {
            $this->attributes['code'] = (string) $value;
        }
    }

    /**
     * Safely decrypt the details attribute without crashing on key rotation or corrupt MAC.
     */
    public function getDetailsAttribute($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    /**
     * Safely encrypt details attribute at rest.
     */
    public function setDetailsAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['details'] = null;
            return;
        }

        $stringVal = is_array($value) ? json_encode($value) : (string) $value;

        try {
            $this->attributes['details'] = \Illuminate\Support\Facades\Crypt::encryptString($stringVal);
        } catch (\Throwable) {
            $this->attributes['details'] = $stringVal;
        }
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(AiToolOrder::class, 'ai_tool_order_id');
    }
}
