<?php

namespace App\Http\Controllers\Api;

use App\Actions\Articles\ListArticlesAction;
use App\DTOs\ArticleFilterDTO;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request, ListArticlesAction $listArticles)
    {
        $filters = ArticleFilterDTO::fromRequest($request);

        $articles = $listArticles->execute($filters);

        return $articles;
    }
}
