<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderServiceMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'provider_id',
        'provider_service_id',
        'original_price',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'original_price' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /**
     * Get the service that owns the mapping.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the provider that owns the mapping.
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
