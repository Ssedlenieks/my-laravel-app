<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'user_id',
        'image',
    ];

    protected $appends = [
        'image_url',
        'created_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'post_category');
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    public function getImageUrlAttribute()
    {
        return $this->image
            ? Storage::url($this->image)
            : null;
    }

    public function getCreatedDateAttribute()
    {
        return $this->created_at->format('Y-m-d H:i');
    }
}
