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
        'code'       => 'encrypted',
        'details'    => 'encrypted',
    ];

    /**
     * Never expose raw encrypted values in serialisation.
     */
    protected $hidden = ['code', 'details'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(AiToolOrder::class, 'ai_tool_order_id');
    }
}
