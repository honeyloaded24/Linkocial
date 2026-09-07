<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogProduct extends Model
{
    protected $fillable = [
        'type', 'log_category_id', 'api_product_id', 'api_product_key', 
        'name', 'description', 'icon', 'base_api_price', 'unit_price', 
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

    /**
     * Clean up third-party promotional spam specifically during external API sync
     */
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
                if (preg_match('/^(?:join|channel|telegram|support|updates|contact|powered by|visit|group|official|\*|•|-|—).*?(?:t\.me|telegram|fadded)/i', $trimmed) ||
                    preg_match('/(?:t\.me|telegram|fadded).*?(?:channel|group|support|updates|link|bot)?$/i', $trimmed)) {
                    continue;
                }

                $trimmed = preg_replace('/https?:\/\/(?:www\.)?(?:t\.me|telegram\.me|telegram\.dog)\/[a-zA-Z0-9_+\-\/]+/i', '', $trimmed);
                $trimmed = preg_replace('/(?:^|\s)(?:t\.me|telegram\.me)\/[a-zA-Z0-9_+\-\/]+/i', '', $trimmed);
                $trimmed = preg_replace('/@(?:[a-zA-Z0-9_]+)/i', '', $trimmed);
                $trimmed = preg_replace('/(?i)\bfadded(?:\.net|\.com|\.org|\.io)?\b/i', '', $trimmed);
            }

            // Strip leftover artifacts
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

    /**
     * Render rich description safely converting newlines to paragraphs and URLs to clickable links
     */
    public function renderDescriptionHtml(): string
    {
        $desc = $this->description ?? '';
        if (empty(trim($desc))) {
            return '<span class="text-muted fst-italic">No description provided.</span>';
        }

        // 1. Escape HTML to prevent XSS
        $escaped = e($desc);

        // 2. Auto-convert URLs to clickable links
        $pattern = '/(?i)\b((?:https?:\/\/|www\.)[^\s<>"\'\)]+)/';
        $linked = preg_replace_callback($pattern, function ($matches) {
            $url = $matches[1];
            $href = str_starts_with(strtolower($url), 'http') ? $url : 'https://' . $url;
            return '<a href="' . $href . '" target="_blank" rel="noopener noreferrer" class="text-primary text-decoration-underline fw-bold">' . $url . '</a>';
        }, $escaped);

        // 3. Preserve paragraphs and line breaks
        return nl2br($linked);
    }

    /**
     * Render product icon:
     * 1. If custom icon uploaded, display the uploaded image.
     * 2. Otherwise auto-detect based on platform name (Instagram, FB, X, TikTok, Gmail, VPN, etc.).
     * 3. Fallback to default key icon.
     */
    public function getIconHtml(int $size = 45, string $extraClasses = ''): string
    {
        $iconSize = max(14, round($size * 0.45));
        $classSuffix = $extraClasses ? ' ' . trim($extraClasses) : '';

        // 1. Check if custom icon is uploaded
        if (!empty($this->icon)) {
            if (str_starts_with($this->icon, '/') || str_starts_with($this->icon, 'http') || str_starts_with($this->icon, 'storage')) {
                $src = asset($this->icon);
                return '<div class="product-icon flex-shrink-0 d-flex align-items-center justify-content-center overflow-hidden rounded-circle shadow-sm border'.$classSuffix.'" style="width: '.$size.'px; height: '.$size.'px; min-width: '.$size.'px; background: var(--bg-surface);"><img src="'.$src.'" alt="'.e($this->name).'" class="w-100 h-100 object-fit-cover"></div>';
            } elseif (str_contains($this->icon, 'fa-')) {
                return '<div class="product-icon flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle shadow-sm text-white bg-primary'.$classSuffix.'" style="width: '.$size.'px; height: '.$size.'px; min-width: '.$size.'px; font-size: '.$iconSize.'px;"><i class="'.$this->icon.'"></i></div>';
            }
        }

        // 2. Auto-detect from product name & category
        $name = strtolower($this->name . ' ' . ($this->category->name ?? ''));

        $iconClass = 'fa-solid fa-key';
        $bg = 'linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%)';

        if (str_contains($name, 'instagram') || str_contains($name, 'ig')) {
            $iconClass = 'fa-brands fa-instagram';
            $bg = 'linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%)';
        } elseif (str_contains($name, 'facebook') || str_contains($name, 'fb')) {
            $iconClass = 'fa-brands fa-facebook-f';
            $bg = '#1877f2';
        } elseif (str_contains($name, 'twitter') || str_contains($name, ' x ') || str_ends_with($name, ' x') || str_starts_with($name, 'x ')) {
            $iconClass = 'fa-brands fa-x-twitter';
            $bg = '#000000';
        } elseif (str_contains($name, 'tiktok')) {
            $iconClass = 'fa-brands fa-tiktok';
            $bg = '#010101';
        } elseif (str_contains($name, 'linkedin')) {
            $iconClass = 'fa-brands fa-linkedin-in';
            $bg = '#0a66c2';
        } elseif (str_contains($name, 'snapchat') || str_contains($name, 'snap')) {
            $iconClass = 'fa-brands fa-snapchat';
            $bg = '#fffc00';
            // yellow background needs dark icon
            return '<div class="product-icon flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle shadow-sm text-dark'.$classSuffix.'" style="width: '.$size.'px; height: '.$size.'px; min-width: '.$size.'px; background: '.$bg.'; font-size: '.$iconSize.'px;"><i class="'.$iconClass.'"></i></div>';
        } elseif (str_contains($name, 'telegram') || str_contains($name, 'tg')) {
            $iconClass = 'fa-brands fa-telegram';
            $bg = '#229ed9';
        } elseif (str_contains($name, 'whatsapp') || str_contains($name, 'wa')) {
            $iconClass = 'fa-brands fa-whatsapp';
            $bg = '#25d366';
        } elseif (str_contains($name, 'gmail') || str_contains($name, 'google')) {
            $iconClass = 'fa-brands fa-google';
            $bg = 'linear-gradient(135deg, #ea4335 0%, #fbbc05 50%, #34a853 100%)';
        } elseif (str_contains($name, 'youtube') || str_contains($name, 'yt')) {
            $iconClass = 'fa-brands fa-youtube';
            $bg = '#ff0000';
        } elseif (str_contains($name, 'discord')) {
            $iconClass = 'fa-brands fa-discord';
            $bg = '#5865f2';
        } elseif (str_contains($name, 'reddit')) {
            $iconClass = 'fa-brands fa-reddit-alien';
            $bg = '#ff4500';
        } elseif (str_contains($name, 'netflix')) {
            $iconClass = 'fa-solid fa-play';
            $bg = '#e50914';
        } elseif (str_contains($name, 'apple') || str_contains($name, 'icloud')) {
            $iconClass = 'fa-brands fa-apple';
            $bg = '#000000';
        } elseif (str_contains($name, 'microsoft') || str_contains($name, 'outlook') || str_contains($name, 'hotmail')) {
            $iconClass = 'fa-brands fa-microsoft';
            $bg = '#00a4ef';
        } elseif (str_contains($name, 'yahoo')) {
            $iconClass = 'fa-brands fa-yahoo';
            $bg = '#6001d2';
        } elseif (str_contains($name, 'spotify')) {
            $iconClass = 'fa-brands fa-spotify';
            $bg = '#1db954';
        } elseif (str_contains($name, 'vpn')) {
            $iconClass = 'fa-solid fa-shield-halved';
            $bg = 'linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%)';
        } elseif (str_contains($name, 'proxy') || str_contains($name, 'proxies')) {
            $iconClass = 'fa-solid fa-server';
            $bg = 'linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%)';
        }

        return '<div class="product-icon flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle shadow-sm text-white'.$classSuffix.'" style="width: '.$size.'px; height: '.$size.'px; min-width: '.$size.'px; background: '.$bg.'; font-size: '.$iconSize.'px;"><i class="'.$iconClass.'"></i></div>';
    }
}
