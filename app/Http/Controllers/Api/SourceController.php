<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SourceResource;
use App\Models\Source;
use Illuminate\Support\Facades\Cache;

class SourceController extends Controller
{
    public function index()
    {
        $sources = Cache::remember(Source::CACHE_KEY, Source::CACHE_TTL, function () {
            return Source::orderBy('name')->get();
        });

        return SourceResource::collection($sources)->response()
            ->header('Cache-Control', 'public, max-age=' . Source::CACHE_TTL);
    }
}
