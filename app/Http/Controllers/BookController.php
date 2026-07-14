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
        // 書籍の登録者本人だけ編集を許可
        $this->authorize('update', $book);

        // 編集画面で現在のジャンル選択状態を表示するため、紐づくジャンルを取得
        $book->load('genres');

        // 編集画面のジャンル選択肢を取得
        $genres = Genre::all();

        // 書籍情報とジャンル一覧を編集画面へ渡す
        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        // 書籍の登録者本人だけ更新を許可
        $this->authorize('update', $book);

        // booksテーブルで更新する項目だけをリクエストから取得
        $bookData = $request->only([
            'title',
            'author',
            'isbn',
            'published_date',
            'description',
            'image_url',
        ]);

        // 任意項目が送信されていない場合はnullを設定
        $bookData['description'] = $bookData['description'] ?? null;
        $bookData['image_url'] = $bookData['image_url'] ?? null;

        // booksテーブルの書籍情報を更新
        $book->update($bookData);

        // 選択されたジャンルIDだけをリクエストから取得
        $genres = $request->only('genres');

        // 中間テーブルのジャンル紐づきを現在の選択内容に更新
        $book->genres()->sync($genres['genres']);

        // 更新した書籍の詳細画面へリダイレクト
        return redirect()->route('books.show', $book)->with('success', '書籍を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        // 書籍の登録者本人だけ削除を許可
        $this->authorize('delete', $book);

        // booksテーブルから書籍を物理削除
        // 関連するジャンル紐づき・レビュー・お気に入りは
        // 外部キーのcascadeOnDeleteによりDB側で自動削除される
        $book->delete();

        // 書籍一覧画面へリダイレクト
        return redirect()->route('books.index')->with('success', '書籍を削除しました。');
    }
}
