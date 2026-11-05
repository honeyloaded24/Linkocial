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
     * Get count of active products in this category dynamically
     */
    public function getActiveProductsCountAttribute(): int
    {
        return (int) ($this->products_count ?? $this->products()->where('is_active', true)->count());
    }

    /**
     * Render category icon matching Linkocial's visual branding and auto-detecting platform brands.
     */
    public function getIconHtml(int $size = 44, string $extraClasses = ''): string
    {
        $iconSize = max(14, round($size * 0.45));
        $classSuffix = $extraClasses ? ' ' . trim($extraClasses) : '';

        // Check if category has an icon attribute or product icon
        if (!empty($this->icon)) {
            if (str_starts_with($this->icon, '/') || str_starts_with($this->icon, 'http') || str_starts_with($this->icon, 'storage')) {
                $src = asset($this->icon);
                return '<div class="category-icon-wrapper flex-shrink-0 d-flex align-items-center justify-content-center overflow-hidden shadow-xs border'.$classSuffix.'" style="width: '.$size.'px; height: '.$size.'px; min-width: '.$size.'px; border-radius: 12px; background: var(--bg-surface);"><img src="'.$src.'" alt="'.e($this->name).'" class="w-100 h-100 object-fit-cover"></div>';
            } elseif (str_contains($this->icon, 'fa-')) {
                return '<div class="category-icon-wrapper flex-shrink-0 d-flex align-items-center justify-content-center shadow-xs text-white bg-primary'.$classSuffix.'" style="width: '.$size.'px; height: '.$size.'px; min-width: '.$size.'px; border-radius: 12px; font-size: '.$iconSize.'px;"><i class="'.$this->icon.'"></i></div>';
            }
        }

        $name = strtolower($this->name ?? '');

        $iconClass = 'fa-solid fa-layer-group';
        $bg = 'linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%)';

        if (str_contains($name, 'instagram') || str_contains($name, 'ig')) {
            $iconClass = 'fa-brands fa-instagram';
            $bg = 'linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%)';
        } elseif (str_contains($name, 'tiktok')) {
            $iconClass = 'fa-brands fa-tiktok';
            $bg = '#010101';
        } elseif (str_contains($name, 'facebook') || str_contains($name, 'fb')) {
            $iconClass = 'fa-brands fa-facebook-f';
            $bg = '#1877f2';
        } elseif (str_contains($name, 'twitter') || str_contains($name, ' x ') || str_ends_with($name, ' x') || str_starts_with($name, 'x ') || $name === 'x') {
            $iconClass = 'fa-brands fa-x-twitter';
            $bg = '#000000';
        } elseif (str_contains($name, 'telegram') || str_contains($name, 'tg')) {
            $iconClass = 'fa-brands fa-telegram';
            $bg = '#229ed9';
        } elseif (str_contains($name, 'whatsapp') || str_contains($name, 'wa')) {
            $iconClass = 'fa-brands fa-whatsapp';
            $bg = '#25d366';
        } elseif (str_contains($name, 'snapchat') || str_contains($name, 'snap')) {
            $iconClass = 'fa-brands fa-snapchat';
            $bg = '#fffc00';
            return '<div class="category-icon-wrapper flex-shrink-0 d-flex align-items-center justify-content-center shadow-xs text-dark'.$classSuffix.'" style="width: '.$size.'px; height: '.$size.'px; min-width: '.$size.'px; background: '.$bg.'; font-size: '.$iconSize.'px; border-radius: 12px;"><i class="'.$iconClass.'"></i></div>';
        } elseif (str_contains($name, 'gmail') || str_contains($name, 'google')) {
            $iconClass = 'fa-brands fa-google';
            $bg = 'linear-gradient(135deg, #ea4335 0%, #fbbc05 50%, #34a853 100%)';
        } elseif (str_contains($name, 'youtube') || str_contains($name, 'yt')) {
            $iconClass = 'fa-brands fa-youtube';
            $bg = '#ff0000';
        } elseif (str_contains($name, 'linkedin')) {
            $iconClass = 'fa-brands fa-linkedin-in';
            $bg = '#0a66c2';
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

        return '<div class="category-icon-wrapper flex-shrink-0 d-flex align-items-center justify-content-center shadow-xs text-white'.$classSuffix.'" style="width: '.$size.'px; height: '.$size.'px; min-width: '.$size.'px; background: '.$bg.'; font-size: '.$iconSize.'px; border-radius: 12px;"><i class="'.$iconClass.'"></i></div>';
    }
}
