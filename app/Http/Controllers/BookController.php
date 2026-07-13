<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $books = Book::with('genres')->withAvg('reviews', 'rating')->paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request)
    {
        // 書籍テーブルへ保存する項目だけをリクエストから取得
        $bookData = $request->only([
            'title',
            'author',
            'isbn',
            'published_date',
            'description',
            'image_url',
        ]);

        // 登録者はリクエスト値ではなく、ログインユーザーのIDを設定
        $bookData['user_id'] = auth()->id();

        // 任意項目が送信されていない場合はnullを設定
        $bookData['description'] = $bookData['description'] ?? null;
        $bookData['image_url'] = $bookData['image_url'] ?? null;

        // booksテーブルへ書籍情報を登録
        $book = Book::create($bookData);

        // 選択されたジャンルIDだけをリクエストから取得
        $genres = $request->only('genres');

        // 中間テーブルbook_genreへ書籍とジャンルの紐づきを登録
        $book->genres()->sync($genres['genres']);

        return redirect()->route('books.show', $book)->with('success', '書籍を登録しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        // 詳細画面で使用する関連データをまとめて取得
        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        // 書籍詳細画面へ書籍情報を渡す
        return view('books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Book $book)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        //
    }
}
