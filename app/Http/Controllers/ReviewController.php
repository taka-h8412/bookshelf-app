<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Book $book)
    {
        // 同じユーザーが同じ書籍へレビューを投稿済みか確認
        $reviewExists = $book->reviews()
            ->where('user_id', auth()->id())
            ->exists();

        // 投稿済みの場合は入力画面へ戻してエラーを表示
        if ($reviewExists) {
            return back()
                ->withInput()
                ->withErrors([
                    'rating' => 'この書籍にはすでにレビューを投稿しています。',
                ]);
        }

        // reviewsテーブルへ保存する入力項目だけを取得
        $reviewData = $request->only([
            'rating',
            'comment',
        ]);

        // コメントが送信されていない場合はnullを設定
        $reviewData['comment'] = $reviewData['comment'] ?? null;

        // レビュー投稿者としてログインユーザーIDを設定
        $reviewData['user_id'] = auth()->id();

        // 対象書籍とのリレーションを利用してレビューを登録
        $book->reviews()->create($reviewData);

        return redirect()->route('books.show', $book)->with('success', 'レビューを投稿しました。');
    }

    public function edit(Review $review)
    {
        // レビュー投稿者本人だけに編集を許可
        $this->authorize('update', $review);

        // 現在のレビュー内容を編集画面へ渡す
        return view('reviews.edit', compact('review'));
    }

    public function update(UpdateReviewRequest $request, Review $review)
    {
        // レビュー投稿者本人だけに更新を許可
        $this->authorize('update', $review);

        // reviewsテーブルを更新する入力項目だけを取得
        $reviewData = $request->only([
            'rating',
            'comment',
        ]);

        // コメントが送信されていない場合はnullを設定
        $reviewData['comment'] = $reviewData['comment'] ?? null;

        // レビュー内容を更新
        $review->update($reviewData);

        return redirect()->route('books.show', $review->book)->with('success', 'レビューを更新しました。');
    }

    public function destroy(Review $review)
    {
        // レビュー投稿者本人だけに削除を許可
        $this->authorize('delete', $review);

        // 削除後の遷移先として対象書籍を保持
        $book = $review->book;

        // レビューを削除
        $review->delete();

        return redirect()->route('books.show', $book)->with('success', 'レビューを削除しました。');
    }

    public function like(Review $review)
    {
        // 対象レビューにログインユーザーのいいねを追加または解除
        $review->likedByUsers()->toggle(auth()->id());

        return redirect()->route('books.show', $review->book);
    }
}