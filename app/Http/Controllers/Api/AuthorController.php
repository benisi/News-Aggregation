<?php

namespace App\Http\Controllers\Api;

use App\Actions\Articles\SearchAuthorsAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function index(Request $request, SearchAuthorsAction $searchAuthors)
    {
        $searchTerm = $request->query('search');
        return $searchAuthors->execute($searchTerm);
    }
}
