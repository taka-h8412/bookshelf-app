<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 公開ルート
|--------------------------------------------------------------------------
*/

// トップページ・書籍一覧
Route::get('/', [BookController::class, 'index']);

Route::get('/books', [BookController::class, 'index'])
    ->name('books.index');

/*
|--------------------------------------------------------------------------
| 認証必須ルート
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | 書籍管理
    |--------------------------------------------------------------------------
    */

    Route::get('/books/create', [BookController::class, 'create'])
        ->name('books.create');

    Route::post('/books', [BookController::class, 'store'])
        ->name('books.store');

    Route::get('/books/{book}/edit', [BookController::class, 'edit'])
        ->name('books.edit');

    Route::put('/books/{book}', [BookController::class, 'update'])
        ->name('books.update');

    Route::delete('/books/{book}', [BookController::class, 'destroy'])
        ->name('books.destroy');

    /*
    |--------------------------------------------------------------------------
    | ジャンル管理
    |--------------------------------------------------------------------------
    */

    Route::resource('genres', GenreController::class);

    /*
    |--------------------------------------------------------------------------
    | レビュー管理
    |--------------------------------------------------------------------------
    */

    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');

    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])
        ->name('reviews.edit');

    Route::put('/reviews/{review}', [ReviewController::class, 'update'])
        ->name('reviews.update');

    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
        ->name('reviews.destroy');

    Route::post('/reviews/{review}/like', [ReviewController::class, 'like'])
        ->name('reviews.like');

    /*
    |--------------------------------------------------------------------------
    | 未実装機能の仮ルート
    |--------------------------------------------------------------------------
    */

    Route::get('/ranking', fn () => 'ランキングは実装中です。')
        ->name('ranking.index');

    Route::get('/favorites', fn () => 'お気に入り一覧は実装中です。')
        ->name('favorites.index');
});

/*
|--------------------------------------------------------------------------
| 書籍詳細
|--------------------------------------------------------------------------
*/

// 動的パラメータを持つため、/books/createより後ろに定義
Route::get('/books/{book}', [BookController::class, 'show'])
    ->name('books.show');