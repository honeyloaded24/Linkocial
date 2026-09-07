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
}
