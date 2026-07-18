<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * レビューを更新できるか判定
     */
    public function update(User $user, Review $review): bool
    {
        // ログインユーザーがレビューの登録者本人かを確認
        return $user->id === $review->user_id;
    }

    /**
     * レビューを削除できるか判定
     */
    public function delete(User $user, Review $review): bool
    {
        // ログインユーザーがレビューの登録者本人かを確認
        return $user->id === $review->user_id;
    }
}
