<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'source_id',
        'category_id',
        'title',
        'author',
        'description',
        'content',
        'url',
        'image_url',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}
