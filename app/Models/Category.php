<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    use HasFactory;

    const CACHE_KEY = "all_categories_list";
    const CACHE_TTL = 3600;

    protected $fillable = ['name', 'slug'];

    protected static function booted()
    {
        static::saved(fn() => Cache::forget(static::CACHE_KEY));
        static::deleted(fn() => Cache::forget(static::CACHE_KEY));
    }
}
