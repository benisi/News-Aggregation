<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\SourceController;
use App\Http\Controllers\Api\UserController;

Route::get('/articles', [ArticleController::class, 'index'])->name('api.articles.index');
Route::get('/sources', [SourceController::class, 'index'])->name('api.sources.index');
Route::get('/categories', [CategoryController::class, 'index'])->name('api.categories.index');
Route::get('/authors', [AuthorController::class, 'index'])->name('api.authors.index');

Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');

    Route::get('/user/preferences', [UserController::class, 'getPreferences'])->name('api.user.preferences.show');
    Route::post('/user/preferences', [UserController::class, 'storePreferences'])->name('api.user.preferences.store');
    Route::get('/user/feed', [FeedController::class, 'index'])->name('api.user.feed.index');
});
