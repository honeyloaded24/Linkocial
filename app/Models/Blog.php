<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Blog extends Model
{
    protected $fillable = [
        'title', 'slug', 'category', 'image', 'excerpt', 
        'content', 'status', 'meta_title', 'meta_description', 'views'
    ];

    protected static function booted()
    {
        static::creating(function ($blog) {
            if (empty($blog->slug)) {
                $blog->slug = Str::slug($blog->title);
            }
        });
    }

    public function getUrlAttribute()
    {
        return route('info.blog.show', $this->slug);
    }

    public function getHtmlContentAttribute()
    {
        if (class_exists(\Parsedown::class)) {
            $parsedown = new \Parsedown();
            $parsedown->setSafeMode(false);
            return $parsedown->text($this->content);
        }
        return nl2br($this->content);
    }

    public function getImageUrlAttribute()
    {
        if ($this->image && str_starts_with($this->image, 'http')) {
            return $this->image;
        }
        return $this->image ? asset('storage/' . $this->image) : asset('assets/img/blog-placeholder.jpg');
    }

    public function getSeoTitleAttribute()
    {
        return $this->meta_title ?: $this->title;
    }

    public function getSeoDescriptionAttribute()
    {
        if ($this->meta_description) {
            return $this->meta_description;
        }
        
        // Strip markdown/html and truncate
        $plainText = strip_tags($this->html_content);
        return Str::limit($plainText, 160);
    }

    public function getSafeExcerptAttribute()
    {
        return $this->excerpt ?: Str::limit(strip_tags($this->html_content), 120);
    }
}
