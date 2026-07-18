<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;

class GenreController extends Controller
{
    public function index()
    {
        $genres = Genre::withCount('books')->get();

        return view('genres.index', compact('genres'));
    }

    public function create()
    {
        return view('genres.create');
    }

    public function store(StoreGenreRequest $request)
    {
        Genre::create($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを登録しました。');
    }

    public function show(Genre $genre)
    {
        $books = $genre->books()->with('genres')->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    public function edit(Genre $genre)
    {
        return view('genres.edit', compact('genre'));
    }

    public function update(UpdateGenreRequest $request, Genre $genre)
    {
        $genre->update($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを更新しました。');
    }

    public function destroy(Genre $genre)
    {
        // 書籍が紐づいている場合は削除しない
        if ($genre->books()->exists()) {
            return redirect()
                ->route('genres.index')
                ->with('error', '書籍が紐づいているジャンルは削除できません。');
        }

        // 書籍が紐づいていない場合は削除する
        $genre->delete();

        return redirect()->route('genres.index')->with('success', 'ジャンルを削除しました。');
    }
}
