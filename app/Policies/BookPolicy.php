<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /**
     * 書籍を更新できるか判定
     */
    public function update(User $user, Book $book): bool
    {
        // ログインユーザーが書籍の登録者本人かを確認
        return $user->id === $book->user_id;
    }

    /**
     * 書籍を削除できるか判定
     */
    public function delete(User $user, Book $book): bool
    {
        // ログインユーザーが書籍の登録者本人かを確認
        return $user->id === $book->user_id;
    }
}