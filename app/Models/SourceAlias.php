<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceAlias extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'source_id',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
