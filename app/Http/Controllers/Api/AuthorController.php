<?php

namespace App\Http\Controllers\Api;

use App\Actions\Articles\SearchAuthorsAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    const DEFAULT_PER_PAGE = 50;
    const MAX_PER_PAGE = 100;

    public function index(Request $request, SearchAuthorsAction $searchAuthors)
    {
        $searchTerm = $request->query('search');
        $requestPerPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);
        $perPage = self::DEFAULT_PER_PAGE;

        if ($requestPerPage > 0) {
            $perPage = max(1, min(self::MAX_PER_PAGE, $requestPerPage));
        }

        return $searchAuthors->execute($searchTerm, $perPage);
    }
}
