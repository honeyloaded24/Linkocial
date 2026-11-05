<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogCategory extends Model
{
    protected $fillable = ['name', 'slug', 'is_active'];

    protected static function booted()
    {
        static::saving(function ($category) {
            if (empty($category->slug) && !empty($category->name)) {
                $category->slug = \Illuminate\Support\Str::slug($category->name);
            }
        });
    }

    public function products()
    {
        return $this->hasMany(LogProduct::class);
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = LogProduct::sanitizeTitle($value);
    }

    public function getNameAttribute($value)
    {
        return LogProduct::sanitizeTitle($value ?? '');
    }

    /**
     * Get count of active products in this category dynamically.
     * Guaranteed to count only products where is_active = true.
     */
    public function getActiveProductsCountAttribute(): int
    {
        // 1. If active_products_count was eager-loaded via withCount, use it directly (0 additional queries)
        if (array_key_exists('active_products_count', $this->attributes)) {
            return (int) $this->attributes['active_products_count'];
        }

        // 2. If products relationship is already loaded in memory, count only active records
        if ($this->relationLoaded('products')) {
            return (int) $this->products->where('is_active', true)->count();
        }

        // 3. Fallback: query active products count explicitly from the database
        return (int) $this->products()->where('is_active', true)->count();
    }

    /**
     * Validate and sanitize Font Awesome class names against a strict pattern.
     */
    public static function isValidFontAwesomeClass(?string $icon): bool
    {
        if (empty($icon)) {
            return false;
        }

        $trimmed = trim($icon);
        // Allows: fa-solid fa-users, fa-brands fa-instagram, fas fa-heart, etc.
        // Rejects any characters like quotes, angle brackets, semicolons, parentheses, or control characters.
        return (bool) preg_match('/^(?:fa[srbld]?|fa-(?:solid|regular|brands|sharp|light|thin|duotone))\s+fa-[a-z0-9-]+(?:\s+fa-[a-z0-9-]+)*$/i', $trimmed);
    }

    /**
     * Validate an image icon URL or local storage path, returning a safe URL or null.
     */
    public static function getSafeIconUrl(?string $icon): ?string
    {
        if (empty($icon)) {
            return null;
        }

        $trimmed = trim($icon);

        // Disallow whitespace, quotes, angle brackets, backticks, or control characters anywhere
        if (preg_match('/[\s"\'<>`\x00-\x1F\x7F]/', $trimmed)) {
            return null;
        }

        // 1. Safe remote HTTP/HTTPS URLs
        if (preg_match('#^https?://#i', $trimmed)) {
            if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
                return null;
            }
            $scheme = strtolower((string) parse_url($trimmed, PHP_URL_SCHEME));
            if (!in_array($scheme, ['http', 'https'], true)) {
                return null;
            }
            return $trimmed;
        }

        // 2. Safe local storage or asset paths (must not contain directory traversal)
        $normalized = ltrim($trimmed, '/');
        if (str_contains($normalized, '..')) {
            return null;
        }

        if (
            str_starts_with($normalized, 'storage/') ||
            str_starts_with($normalized, 'images/') ||
            str_starts_with($normalized, 'assets/') ||
            str_starts_with($normalized, 'uploads/')
        ) {
            if (preg_match('#^[a-zA-Z0-9_\-\./]+$#', $normalized)) {
                return asset($normalized);
            }
        }

        return null;
    }

    /**
     * Render category icon matching Linkocial's visual branding and auto-detecting platform brands.
     * All dynamic HTML attributes are strictly validated and escaped.
     */
    public function getIconHtml(int $size = 44, string $extraClasses = ''): string
    {
        $safeSize = max(16, min(256, (int) $size));
        $iconSize = max(14, (int) round($safeSize * 0.45));

        // Sanitize any extraClasses to prevent class attribute injection
        $sanitizedExtra = trim(preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $extraClasses));
        $classSuffix = $sanitizedExtra !== '' ? ' ' . e($sanitizedExtra) : '';

        // 1. Check if category has a valid image icon
        $safeImgUrl = static::getSafeIconUrl($this->icon ?? null);
        if ($safeImgUrl !== null) {
            return '<div class="category-icon-wrapper flex-shrink-0 d-flex align-items-center justify-content-center overflow-hidden shadow-xs border' . $classSuffix . '" style="width: ' . $safeSize . 'px; height: ' . $safeSize . 'px; min-width: ' . $safeSize . 'px; border-radius: 12px; background: var(--bg-surface);"><img src="' . e($safeImgUrl) . '" alt="' . e($this->name) . '" class="w-100 h-100 object-fit-cover"></div>';
        }

        // 2. Check if category has an approved Font Awesome class
        if (static::isValidFontAwesomeClass($this->icon ?? null)) {
            $safeFaClass = trim($this->icon);
            return '<div class="category-icon-wrapper flex-shrink-0 d-flex align-items-center justify-content-center shadow-xs text-white bg-primary' . $classSuffix . '" style="width: ' . $safeSize . 'px; height: ' . $safeSize . 'px; min-width: ' . $safeSize . 'px; border-radius: 12px; font-size: ' . $iconSize . 'px;"><i class="' . e($safeFaClass) . '"></i></div>';
        }

        // 3. Fallback: Auto-detect brand platform using reliable word boundaries
        $haystack = strtolower(trim(($this->name ?? '') . ' ' . ($this->slug ?? '')));

        $iconClass = 'fa-solid fa-layer-group';
        $bg = 'linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%)';

        if (preg_match('/\b(instagram|ig)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-instagram';
            $bg = 'linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%)';
        } elseif (preg_match('/\b(tiktok)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-tiktok';
            $bg = '#010101';
        } elseif (preg_match('/\b(facebook|fb)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-facebook-f';
            $bg = '#1877f2';
        } elseif (preg_match('/\b(twitter|x)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-x-twitter';
            $bg = '#000000';
        } elseif (preg_match('/\b(telegram|tg)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-telegram';
            $bg = '#229ed9';
        } elseif (preg_match('/\b(whatsapp|wa)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-whatsapp';
            $bg = '#25d366';
        } elseif (preg_match('/\b(snapchat|snap)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-snapchat';
            $bg = '#fffc00';
            return '<div class="category-icon-wrapper flex-shrink-0 d-flex align-items-center justify-content-center shadow-xs text-dark' . $classSuffix . '" style="width: ' . $safeSize . 'px; height: ' . $safeSize . 'px; min-width: ' . $safeSize . 'px; background: ' . $bg . '; font-size: ' . $iconSize . 'px; border-radius: 12px;"><i class="' . $iconClass . '"></i></div>';
        } elseif (preg_match('/\b(gmail|google)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-google';
            $bg = 'linear-gradient(135deg, #ea4335 0%, #fbbc05 50%, #34a853 100%)';
        } elseif (preg_match('/\b(youtube|yt)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-youtube';
            $bg = '#ff0000';
        } elseif (preg_match('/\b(linkedin)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-linkedin-in';
            $bg = '#0a66c2';
        } elseif (preg_match('/\b(discord)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-discord';
            $bg = '#5865f2';
        } elseif (preg_match('/\b(reddit)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-reddit-alien';
            $bg = '#ff4500';
        } elseif (preg_match('/\b(netflix)\b/i', $haystack)) {
            $iconClass = 'fa-solid fa-play';
            $bg = '#e50914';
        } elseif (preg_match('/\b(apple|icloud)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-apple';
            $bg = '#000000';
        } elseif (preg_match('/\b(microsoft|outlook|hotmail)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-microsoft';
            $bg = '#00a4ef';
        } elseif (preg_match('/\b(yahoo)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-yahoo';
            $bg = '#6001d2';
        } elseif (preg_match('/\b(spotify)\b/i', $haystack)) {
            $iconClass = 'fa-brands fa-spotify';
            $bg = '#1db954';
        } elseif (preg_match('/\b(vpn)\b/i', $haystack)) {
            $iconClass = 'fa-solid fa-shield-halved';
            $bg = 'linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%)';
        } elseif (preg_match('/\b(proxy|proxies)\b/i', $haystack)) {
            $iconClass = 'fa-solid fa-server';
            $bg = 'linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%)';
        }

        return '<div class="category-icon-wrapper flex-shrink-0 d-flex align-items-center justify-content-center shadow-xs text-white' . $classSuffix . '" style="width: ' . $safeSize . 'px; height: ' . $safeSize . 'px; min-width: ' . $safeSize . 'px; background: ' . $bg . '; font-size: ' . $iconSize . 'px; border-radius: 12px;"><i class="' . $iconClass . '"></i></div>';
    }
}
