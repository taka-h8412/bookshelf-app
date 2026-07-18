<?php

namespace App\Http\Controllers;

use App\Models\Book;

class RankingController extends Controller
{
    public function index()
    {
        // レビュー平均評価とレビュー件数を集計し、評価が高い順に上位10冊を取得
        $rankedBooks = Book::withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->having('reviews_count', '>', 0)
            ->orderByDesc('reviews_avg_rating')
            ->limit(10)
            ->get();

        // ランキング画面へ書籍情報を渡す
        return view('ranking.index', compact('rankedBooks'));
    }
}