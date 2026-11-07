<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiWebhookEvent extends Model
{
    protected $table = 'ai_webhook_events';

    protected $fillable = [
        'event_id',
        'event_type',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
