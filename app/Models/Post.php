<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'category_id', 'title', 'content', 'published_at', 'status'];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // Relations
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->where('published_at', '>', now());
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function isPublishable(): bool
    {
        return $this->status === 'published' && $this->published_at && $this->published_at->isPast();
    }
}
