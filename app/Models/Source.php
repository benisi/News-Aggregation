<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Source extends Model
{
    use HasFactory;

    const CACHE_KEY = 'all_sources_list';
    const CACHE_TTL = 3600;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'url',
        'category_id',
    ];

    protected static function booted()
    {
        static::saved(fn() => Cache::forget(static::CACHE_KEY));
        static::deleted(fn() => Cache::forget(static::CACHE_KEY));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(SourceAlias::class);
    }

    public function authors(): HasMany
    {
        return $this->hasMany(Author::class);
    }
}
