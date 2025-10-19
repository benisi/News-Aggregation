<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    use HasFactory;

    const FETCH_DATA_LIMIT = 100;

    protected $fillable = [
        'name',
        'source_id'
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }
}
