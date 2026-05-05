<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'description',
        'selling_price',
        'type',
        'min',
        'max',
        'is_active',
        'is_hot',
        'is_trending',
        'average_time',
    ];

    protected $casts = [
        'selling_price' => 'decimal:4',
        'is_active' => 'boolean',
        'is_hot' => 'boolean',
        'is_trending' => 'boolean',
    ];

    /**
     * Get the orders for the service.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Scope a query to only include services that have at least one active provider mapping.
     */
    public function scopeWithActiveProvider($query)
    {
        return $query->where('is_active', true)
            ->whereHas('mappings', function($q) {
                $q->where('is_active', true)
                  ->whereHas('provider', function($p) {
                      $p->where('is_active', true);
                  });
            });
    }

    /**
     * Get the provider mappings for the service.
     */
    public function mappings()
    {
        return $this->hasMany(ProviderServiceMapping::class);
    }

    /**
     * Get the social media channels and their service counts.
     */
    public static function getChannels()
    {
        $channels = [
            ['name' => 'Instagram', 'icon' => 'fa-brands fa-instagram', 'color' => '#E1306C', 'description' => 'Buy Followers, Likes, Comments, Shares, Views, and more to scale your profile.'],
            ['name' => 'TikTok', 'icon' => 'fa-brands fa-tiktok', 'color' => '#000000', 'description' => 'Buy Followers, Views, Likes, Shares, and tailored engagement for viral growth.'],
            ['name' => 'YouTube', 'icon' => 'fa-brands fa-youtube', 'color' => '#FF0000', 'description' => 'Buy Subscribers, Watchtime, Views, and Likes to monetize your channel faster.'],
            ['name' => 'X', 'icon' => 'fa-brands fa-x-twitter', 'color' => '#000000', 'description' => 'Buy Followers, Likes, Reposts, and Views to build authority on the world\'s square.'],
            ['name' => 'Facebook', 'icon' => 'fa-brands fa-facebook', 'color' => '#1877F2', 'description' => 'Buy Followers, Likes, Shares, and Post Engagement to reach a global audience.'],
            ['name' => 'Telegram', 'icon' => 'fa-brands fa-telegram', 'color' => '#0088cc', 'description' => 'Buy Members, Views, Reactions, and Votes to grow your community and influence.'],
            ['name' => 'Spotify', 'icon' => 'fa-brands fa-spotify', 'color' => '#1DB954', 'description' => 'Buy Plays, Followers, Saves, and Playlist Placements to get your music heard.'],
            ['name' => 'Audiomack', 'icon' => 'fa-solid fa-music', 'color' => '#FFA200', 'description' => 'Buy Plays, Followers, and Re-ups to boost your music\'s visibility and climb the Audiomack charts instantly.'],
        ];

        foreach ($channels as &$channel) {
            $channel['count'] = self::withActiveProvider()
                ->where('name', 'like', '%' . $channel['name'] . '%')
                ->count();
        }

        return $channels;
    }

    /**
     * Identify the channel name from the service name using configured platforms.
     */
    public function getChannelName()
    {
        $rawInstructions = \App\Models\Setting::get('platform_instructions_json', '[]');
        $instructions = json_decode($rawInstructions, true) ?: [];
        $configuredPlatforms = array_keys($instructions);
        
        // Default list to fall back on if JSON is empty or for common names
        $platforms = array_unique(array_merge($configuredPlatforms, ['Instagram', 'TikTok', 'YouTube', 'X', 'Twitter', 'Facebook', 'Telegram', 'Spotify', 'Audiomack']));

        foreach ($platforms as $channel) {
            if (stripos($this->name, $channel) !== false) {
                return $channel;
            }
        }
        return 'Default';
    }
}
