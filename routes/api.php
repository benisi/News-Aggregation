<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthorController;

Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/authors', [AuthorController::class, 'index']);
