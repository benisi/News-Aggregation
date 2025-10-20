<?php

namespace App\Http\Controllers\Api;

use App\Actions\Articles\ListArticlesAction;
use App\Actions\Users\GetPreferencesAction;
use App\DTOs\ArticleFilterDTO;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class FeedController extends Controller
{
    public function index(Request $request, ListArticlesAction $listArticles, GetPreferencesAction $getPreferences)
    {
        $savedPreferences = $getPreferences->execute($request->user());
        $sourcePrefs = Arr::get($savedPreferences, 'preferred_sources', '');
        $categoryPrefs = Arr::get($savedPreferences, 'preferred_categories', '');
        $authorPrefs = Arr::get($savedPreferences, 'preferred_authors', '');

        $hasPreferences = !empty($sourcePrefs) || !empty($categoryPrefs) || !empty($authorPrefs);

        if (!$hasPreferences) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'status' => 'UNCONFIGURED_FEED',
                    'message' => __('Your personalized feed is unconfigured. Please select preferred sources, categories, or authors.'),
                ]
            ]);
        }

        $requestData = [
            'source_id' =>  $sourcePrefs,
            'category_id' => $categoryPrefs,
            'author_id' => $authorPrefs,
            'search' => $request->input('search'),
            'date_from' => $request->input('date_from'),
            'per_page' => $request->input('per_page'),
        ];

        $filters = ArticleFilterDTO::fromArray($requestData);

        return $listArticles->execute($filters);
    }
}
