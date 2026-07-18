<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログインユーザーはレビューを投稿できる(): void
    {
        // レビューを投稿するログインユーザーを作成
        $user = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // ログイン状態でレビュー投稿処理を実行
        $response = $this->actingAs($user)->post(
            route('reviews.store', $book),
            [
                'rating' => 5,
                'comment' => 'とても参考になる書籍でした。',
            ]
        );

        // 投稿後に対象書籍の詳細画面へリダイレクトされることを確認
        $response->assertRedirect(route('books.show', $book));

        // 投稿成功のフラッシュメッセージが設定されることを確認
        $response->assertSessionHas(
            'success',
            'レビューを投稿しました。'
        );

        // 投稿したレビューがDBに保存されていることを確認
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても参考になる書籍でした。',
        ]);
    }

    public function test_コメントが未入力でもレビューを投稿できる(): void
    {
        // レビューを投稿するログインユーザーを作成
        $user = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // コメントを送信せずにレビュー投稿処理を実行
        $response = $this->actingAs($user)->post(
            route('reviews.store', $book),
            [
                'rating' => 4,
            ]
        );

        // 書籍詳細画面へリダイレクトされることを確認
        $response->assertRedirect(route('books.show', $book));

        // レビュー投稿完了メッセージを確認
        $response->assertSessionHas(
            'success',
            'レビューを投稿しました。'
        );

        // コメントがnullのレビューが登録されたことを確認
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => null,
        ]);
    }

    public function test_評価が未選択の場合はレビューを投稿できない(): void
    {
        // レビューを投稿するログインユーザーを作成
        $user = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // 評価を送信せずにレビュー投稿処理を実行
        $response = $this->actingAs($user)->post(
            route('reviews.store', $book),
            [
                'comment' => '評価を未選択にしたレビューです。',
            ]
        );

        // 評価にバリデーションエラーがあることを確認
        $response->assertSessionHasErrors([
            'rating' => '評価を選択してください。',
        ]);

        // レビューがDBに登録されていないことを確認
        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_評価が1から5の範囲外の場合はレビューを投稿できない(): void
    {
        // レビューを投稿するログインユーザーを作成
        $user = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // 範囲外の評価でレビュー投稿処理を実行
        $response = $this->actingAs($user)->post(
            route('reviews.store', $book),
            [
                'rating' => 6,
                'comment' => '範囲外の評価を送信します。',
            ]
        );

        // 評価に指定したバリデーションエラーがあることを確認
        $response->assertSessionHasErrors([
            'rating' => '評価は★1から★5の間で選択してください。',
        ]);

        // レビューがDBに登録されていないことを確認
        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_評価が整数でない場合はレビューを投稿できない(): void
    {
        // レビューを投稿するログインユーザーを作成
        $user = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // 整数ではない評価でレビュー投稿処理を実行
        $response = $this->actingAs($user)->post(
            route('reviews.store', $book),
            [
                'rating' => '不正な評価',
                'comment' => '整数ではない評価を送信します。',
            ]
        );

        // 評価に指定したバリデーションエラーがあることを確認
        $response->assertSessionHasErrors([
            'rating' => '評価を正しく選択してください。',
        ]);

        // レビューがDBに登録されていないことを確認
        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_同じユーザーは同じ書籍に複数のレビューを投稿できない(): void
    {
        // レビューを投稿するログインユーザーを作成
        $user = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // 同じユーザーによる1件目のレビューを登録
        $book->reviews()->create([
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => '1件目のレビューです。',
        ]);

        // 同じユーザーが同じ書籍へ2件目のレビュー投稿処理を実行
        $response = $this->actingAs($user)->post(
            route('reviews.store', $book),
            [
                'rating' => 4,
                'comment' => '2件目のレビューです。',
            ]
        );

        // 重複投稿のバリデーションエラーがあることを確認
        $response->assertSessionHasErrors([
            'rating' => 'この書籍にはすでにレビューを投稿しています。',
        ]);

        // 同じユーザーと書籍のレビューが1件だけであることを確認
        $this->assertDatabaseCount('reviews', 1);

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '2件目のレビューです。',
        ]);
    }

    public function test_レビュー投稿者はレビュー編集画面を表示できる(): void
    {
        // レビューを投稿したユーザーを作成
        $user = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // ログインユーザーのレビューを作成
        $review = $book->reviews()->create([
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => '編集前のレビューです。',
        ]);

        // レビュー編集画面へアクセス
        $response = $this->actingAs($user)->get(
            route('reviews.edit', $review)
        );

        // 編集画面が正常に表示されることを確認
        $response->assertStatus(200);

        // 現在のレビュー内容が表示されることを確認
        $response->assertSee('レビューの編集');
        $response->assertSee('編集前のレビューです。');
        $response->assertSee('5');
    }

    public function test_レビュー投稿者以外はレビュー編集画面を表示できない(): void
    {
        // レビューを投稿したユーザーを作成
        $reviewAuthor = User::factory()->create();

        // レビュー投稿者とは別のログインユーザーを作成
        $otherUser = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // レビュー投稿者のレビューを作成
        $review = $book->reviews()->create([
            'user_id' => $reviewAuthor->id,
            'rating' => 5,
            'comment' => '投稿者本人だけが編集できるレビューです。',
        ]);

        // 別のユーザーとしてレビュー編集画面へアクセス
        $response = $this->actingAs($otherUser)->get(
            route('reviews.edit', $review)
        );

        // 認可されず403になることを確認
        $response->assertForbidden();
    }

    public function test_レビュー投稿者はレビュー内容を更新できる(): void
    {
        // レビューを投稿したユーザーを作成
        $user = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // 更新前のレビューを作成
        $review = $book->reviews()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => '更新前のレビューです。',
        ]);

        // レビュー更新処理を実行
        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'rating' => 5,
                'comment' => '更新後のレビューです。',
            ]
        );

        // 対象書籍の詳細画面へリダイレクトされることを確認
        $response->assertRedirect(route('books.show', $book));

        // 更新完了メッセージを確認
        $response->assertSessionHas(
            'success',
            'レビューを更新しました。'
        );

        // レビュー内容が更新されたことを確認
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '更新後のレビューです。',
        ]);

        // 更新前の内容が残っていないことを確認
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新前のレビューです。',
        ]);
    }

    public function test_レビュー投稿者以外はレビュー内容を更新できない(): void
    {
        // レビューを投稿したユーザーを作成
        $reviewAuthor = User::factory()->create();

        // レビュー投稿者とは別のログインユーザーを作成
        $otherUser = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // レビュー投稿者のレビューを作成
        $review = $book->reviews()->create([
            'user_id' => $reviewAuthor->id,
            'rating' => 3,
            'comment' => '更新前のレビューです。',
        ]);

        // 別のユーザーとしてレビュー更新処理を実行
        $response = $this->actingAs($otherUser)->put(
            route('reviews.update', $review),
            [
                'rating' => 5,
                'comment' => '他のユーザーが更新しようとした内容です。',
            ]
        );

        // 認可されず403になることを確認
        $response->assertForbidden();

        // レビュー内容が更新されていないことを確認
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $reviewAuthor->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '更新前のレビューです。',
        ]);

        // 不正な更新内容が保存されていないことを確認
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => '他のユーザーが更新しようとした内容です。',
        ]);
    }

    public function test_更新時に評価が未選択の場合はレビューを更新できない(): void
    {
        // レビューを投稿したユーザーを作成
        $user = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // 更新前のレビューを作成
        $review = $book->reviews()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => '更新前のレビューです。',
        ]);

        // 評価を送信せずにレビュー更新処理を実行
        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'comment' => '評価を未選択にした更新内容です。',
            ]
        );

        // 評価に指定したバリデーションエラーがあることを確認
        $response->assertSessionHasErrors([
            'rating' => '評価を選択してください。',
        ]);

        // 元のレビュー内容が残っていることを確認
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '更新前のレビューです。',
        ]);

        // 不正な更新内容が保存されていないことを確認
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'comment' => '評価を未選択にした更新内容です。',
        ]);
    }

    public function test_更新時に評価が1から5の範囲外の場合はレビューを更新できない(): void
    {
        // レビューを投稿したユーザーを作成
        $user = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // 更新前のレビューを作成
        $review = $book->reviews()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => '更新前のレビューです。',
        ]);

        // 範囲外の評価でレビュー更新処理を実行
        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'rating' => 6,
                'comment' => '範囲外の評価を指定した更新内容です。',
            ]
        );

        // 評価に指定したバリデーションエラーがあることを確認
        $response->assertSessionHasErrors([
            'rating' => '評価は★1から★5の間で選択してください。',
        ]);

        // 元のレビュー内容が残っていることを確認
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '更新前のレビューです。',
        ]);

        // 不正な更新内容が保存されていないことを確認
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'rating' => 6,
            'comment' => '範囲外の評価を指定した更新内容です。',
        ]);
    }

    public function test_更新時に評価が整数でない場合はレビューを更新できない(): void
    {
        // レビューを投稿したユーザーを作成
        $user = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // 更新前のレビューを作成
        $review = $book->reviews()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => '更新前のレビューです。',
        ]);

        // 整数ではない評価でレビュー更新処理を実行
        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'rating' => '不正な評価',
                'comment' => '整数ではない評価を指定した更新内容です。',
            ]
        );

        // 評価に指定したバリデーションエラーがあることを確認
        $response->assertSessionHasErrors([
            'rating' => '評価を正しく選択してください。',
        ]);

        // 元のレビュー内容が残っていることを確認
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '更新前のレビューです。',
        ]);

        // 不正な更新内容が保存されていないことを確認
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'comment' => '整数ではない評価を指定した更新内容です。',
        ]);
    }

    public function test_コメントが未入力でもレビューを更新できる(): void
    {
        // レビューを投稿したユーザーを作成
        $user = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // 更新前のレビューを作成
        $review = $book->reviews()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => '更新前のコメントです。',
        ]);

        // コメントを送信せずにレビュー更新処理を実行
        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'rating' => 4,
            ]
        );

        // 対象書籍の詳細画面へリダイレクトされることを確認
        $response->assertRedirect(route('books.show', $book));

        // 更新完了メッセージを確認
        $response->assertSessionHas(
            'success',
            'レビューを更新しました。'
        );

        // 評価が更新され、コメントがnullになったことを確認
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => null,
        ]);

        // 更新前のコメントが残っていないことを確認
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'comment' => '更新前のコメントです。',
        ]);
    }

    public function test_レビュー投稿者はレビューと紐づくいいねを削除できる(): void
    {
        // レビューを投稿するユーザーを作成
        $reviewAuthor = User::factory()->create();

        // レビューにいいねするユーザーを作成
        $likeUser = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // 削除対象のレビューを作成
        $review = $book->reviews()->create([
            'user_id' => $reviewAuthor->id,
            'rating' => 5,
            'comment' => '削除対象のレビューです。',
        ]);

        // レビューに紐づくいいねを作成
        $review->likedByUsers()->attach($likeUser->id);

        // レビュー投稿者として削除処理を実行
        $response = $this->actingAs($reviewAuthor)->delete(
            route('reviews.destroy', $review)
        );

        // 対象書籍の詳細画面へリダイレクトされることを確認
        $response->assertRedirect(route('books.show', $book));

        // 削除完了メッセージを確認
        $response->assertSessionHas(
            'success',
            'レビューを削除しました。'
        );

        // レビューが削除されたことを確認
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        // レビューに紐づくいいねも削除されたことを確認
        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);
    }

    public function test_レビュー投稿者以外はレビューを削除できない(): void
    {
        // レビューを投稿したユーザーを作成
        $reviewAuthor = User::factory()->create();

        // レビュー投稿者とは別のログインユーザーを作成
        $otherUser = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // 削除対象のレビューを作成
        $review = $book->reviews()->create([
            'user_id' => $reviewAuthor->id,
            'rating' => 5,
            'comment' => '投稿者本人だけが削除できるレビューです。',
        ]);

        // レビューにいいねするユーザーを作成
        $likeUser = User::factory()->create();

        // レビューに紐づくいいねを作成
        $review->likedByUsers()->attach($likeUser->id);

        // 別のユーザーとしてレビュー削除処理を実行
        $response = $this->actingAs($otherUser)->delete(
            route('reviews.destroy', $review)
        );

        // 認可されず403になることを確認
        $response->assertForbidden();

        // レビューが削除されていないことを確認
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $reviewAuthor->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '投稿者本人だけが削除できるレビューです。',
        ]);

        // レビューに紐づくいいねも削除されていないことを確認
        $this->assertDatabaseHas('review_likes', [
            'review_id' => $review->id,
            'user_id' => $likeUser->id,
        ]);
    }

    public function test_書籍詳細画面ではレビューが新しい順に表示される(): void
    {
        // レビュー対象の書籍を作成
        $book = Book::factory()->create();

        // 古いレビューを投稿するユーザーを作成
        $oldReviewUser = User::factory()->create();

        // 新しいレビューを投稿するユーザーを作成
        $newReviewUser = User::factory()->create();

        // 古いレビューを作成
        $oldReview = $book->reviews()->create([
            'user_id' => $oldReviewUser->id,
            'rating' => 3,
            'comment' => '先に投稿されたレビューです。',
        ]);

        // 古いレビューの投稿日時を設定
        $oldReview->created_at = '2026-07-16 10:00:00';
        $oldReview->updated_at = '2026-07-16 10:00:00';
        $oldReview->save();

        // 新しいレビューを作成
        $newReview = $book->reviews()->create([
            'user_id' => $newReviewUser->id,
            'rating' => 5,
            'comment' => '後から投稿されたレビューです。',
        ]);

        // 新しいレビューの投稿日時を設定
        $newReview->created_at = '2026-07-17 10:00:00';
        $newReview->updated_at = '2026-07-17 10:00:00';
        $newReview->save();

        // ゲストユーザーとして書籍詳細画面へアクセス
        $response = $this->get(
            route('books.show', $book)
        );

        // 書籍詳細画面が正常に表示されることを確認
        $response->assertStatus(200);

        // 新しいレビューが古いレビューより先に表示されることを確認
        $response->assertSeeInOrder([
            '後から投稿されたレビューです。',
            '先に投稿されたレビューです。',
        ]);
    }

    public function test_ログインユーザーはレビューにいいねできる(): void
    {
        // ログインユーザーを作成
        $user = User::factory()->create();

        // 書籍を作成
        $book = Book::factory()->create();

        // レビュー投稿者を作成
        $reviewUser = User::factory()->create();

        // レビューを作成
        $review = $book->reviews()->create([
            'user_id' => $reviewUser->id,
            'rating' => 5,
            'comment' => 'いいレビューです。',
        ]);

        // ログインユーザーとしていいねボタンを押す
        $response = $this->actingAs($user)->post(
            route('reviews.like', $review)
        );

        // review_likesテーブルにいいねが登録されることを確認
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // 書籍詳細画面へリダイレクトされることを確認
        $response->assertRedirect(
            route('books.show', $book)
        );
    }

    public function test_いいね済みのユーザーはレビューのいいねを解除できる(): void
    {
        // ログインユーザーを作成
        $user = User::factory()->create();

        // 書籍を作成
        $book = Book::factory()->create();

        // レビュー投稿者を作成
        $reviewUser = User::factory()->create();

        // レビューを作成
        $review = $book->reviews()->create([
            'user_id' => $reviewUser->id,
            'rating' => 5,
            'comment' => 'いいレビューです。',
        ]);

        // あらかじめログインユーザーのいいねを登録
        $review->likedByUsers()->attach($user->id);

        // ログインユーザーとしてもう一度いいねボタンを押す
        $response = $this->actingAs($user)->post(
            route('reviews.like', $review)
        );

        // いいねが削除されることを確認
        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // 書籍詳細画面へリダイレクトされることを確認
        $response->assertRedirect(
            route('books.show', $book)
        );
    }
}