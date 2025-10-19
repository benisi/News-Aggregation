<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Cache::remember(Category::CACHE_KEY, Category::CACHE_TTL, function () {
            return Category::orderBy('name')->get();
        });

        return CategoryResource::collection($categories)->response()
            ->header('Cache-Control', 'public, max-age=' . Category::CACHE_TTL);
    }
}
