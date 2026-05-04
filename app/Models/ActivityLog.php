<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'model_type', 'model_id', 'details', 'ip_address'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log($action, $model = null, $details = null)
    {
        return self::create([
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->id : null,
            'details' => is_array($details) ? json_encode($details) : $details,
            'ip_address' => request()->ip(),
        ]);
    }
}
