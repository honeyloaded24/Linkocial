<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogProduct extends Model
{
    protected $fillable = [
        'type', 'log_category_id', 'api_product_id', 'api_product_key', 
        'name', 'description', 'base_api_price', 'unit_price', 
        'our_price', 'in_stock', 'is_active'
    ];

    public function category()
    {
        return $this->belongsTo(LogCategory::class, 'log_category_id');
    }

    public function logOrders()
    {
        return $this->hasMany(LogOrder::class, 'log_product_id');
    }

    public function items()
    {
        return $this->hasMany(LogItem::class, 'log_product_id');
    }

    public static function sanitizeDescription(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        $lines = explode("\n", str_replace("\r\n", "\n", $text));
        $cleanLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) {
                continue;
            }

            // If line is primarily a Telegram / Fadded promotion or contact link
            $isPromoOrTelegram = preg_match('/(?:t\.me|telegram|fadded)/i', $trimmed);
            
            if ($isPromoOrTelegram) {
                // Drop the entire line if it's an invite, channel reference, support or promo
                if (preg_match('/^(?:join|channel|telegram|support|updates|contact|powered by|visit|group|official|\*|•|-|—).*?(?:t\.me|telegram|fadded)/i', $trimmed) ||
                    preg_match('/(?:t\.me|telegram|fadded).*?(?:channel|group|support|updates|link|bot)?$/i', $trimmed)) {
                    continue;
                }

                // Otherwise, strip out URLs, handles, and fadded occurrences
                $trimmed = preg_replace('/https?:\/\/(?:www\.)?(?:t\.me|telegram\.me|telegram\.dog)\/[a-zA-Z0-9_+\-\/]+/i', '', $trimmed);
                $trimmed = preg_replace('/(?:^|\s)(?:t\.me|telegram\.me)\/[a-zA-Z0-9_+\-\/]+/i', '', $trimmed);
                $trimmed = preg_replace('/@(?:[a-zA-Z0-9_]+)/i', '', $trimmed);
                $trimmed = preg_replace('/(?i)\bfadded(?:\.net|\.com|\.org|\.io)?\b/i', '', $trimmed);
            }

            // Strip any remaining general URLs (http://, https://, www.)
            $trimmed = preg_replace('/https?:\/\/[^\s<>"\'\)]+/i', '', $trimmed);
            $trimmed = preg_replace('/www\.[a-zA-Z0-9\-\.]+\.[a-z]{2,}[^\s<>"\'\)]*/i', '', $trimmed);

            // Strip leftover artifacts like dangling colons, empty brackets, multiple spaces
            $trimmed = preg_replace('/\s+/', ' ', $trimmed);
            $trimmed = trim($trimmed, " \t\r\n-—:;,.|/");

            if (!empty($trimmed) && !preg_match('/^(?:or|and|for updates|support|channel|visit)$/i', $trimmed)) {
                $cleanLines[] = $trimmed;
            }
        }

        return trim(implode("\n", $cleanLines));
    }

    public static function sanitizeTitle(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        $text = preg_replace('/(?i)\bfadded(?:\.net|\.com|\.org|\.io)?\b/i', '', $text);
        $text = preg_replace('/https?:\/\/[^\s]+/i', '', $text);
        $text = preg_replace('/@(?:[a-zA-Z0-9_]+)/i', '', $text);
        $text = preg_replace('/\[\s*\]|\(\s*\)/', '', $text);
        
        return trim(preg_replace('/\s+/', ' ', $text), " \t\r\n-—:;,.|/");
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = static::sanitizeDescription($value);
    }

    public function getDescriptionAttribute($value)
    {
        return static::sanitizeDescription($value ?? '');
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = static::sanitizeTitle($value);
    }

    public function getNameAttribute($value)
    {
        return static::sanitizeTitle($value ?? '');
    }
}
