<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SourceController;

Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/sources', [SourceController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/authors', [AuthorController::class, 'index']);
