<?php

namespace App\Http\Controllers;

use App\Models\Book;

class FavoriteController extends Controller
{
    public function index()
    {
        // ログインユーザーがお気に入り登録した書籍を取得
        $books = auth()->user()->favoriteBooks()->with('genres')->paginate(10);

        // お気に入り一覧画面へ書籍情報を渡す
        return view('favorites.index', compact('books'));
    }

    public function toggle(Book $book)
    {
        // 対象書籍にログインユーザーのお気に入りを追加または解除
        $book->favoritedByUsers()->toggle(auth()->id());

        return redirect()->route('books.show', $book);
    }
}