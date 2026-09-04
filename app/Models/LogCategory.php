<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogCategory extends Model
{
    protected $fillable = ['name', 'is_active'];

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
}
