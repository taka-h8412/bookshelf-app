<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::orderBy('id')->get();
        $reviews = Review::orderBy('id')->get();

        foreach ($reviews as $index => $review) {
            // Collection($index)上の順番を使って、いいね数を0〜3件に分散させる
            $likeCount = $index % 4;

            // 自分のレビューにはいいねできないため、レビュー投稿者を候補から除外する
            $likeUserIds = $users->where('id', '!=', $review->user_id)->take($likeCount)->pluck('id')->toArray();

            // 既存のいいねを外さず、未登録のいいねだけを追加する
            $review->likedByUsers()->syncWithoutDetaching($likeUserIds);
        }
    }
}