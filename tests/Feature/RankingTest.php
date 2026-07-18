<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ランキング画面ではレビュー平均評価が高い書籍から順番に表示される(): void
    {
        // レビュー投稿者を作成
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $thirdUser = User::factory()->create();

        // ランキング対象の書籍を作成
        $highRatedBook = Book::factory()->create([
            'title' => '平均評価が高い書籍',
        ]);

        $middleRatedBook = Book::factory()->create([
            'title' => '平均評価が中間の書籍',
        ]);

        $lowRatedBook = Book::factory()->create([
            'title' => '平均評価が低い書籍',
        ]);

        // 平均評価5.0のレビューを作成
        $highRatedBook->reviews()->create([
            'user_id' => $firstUser->id,
            'rating' => 5,
            'comment' => '高評価レビューです。',
        ]);

        // 平均評価4.0のレビューを作成
        $middleRatedBook->reviews()->create([
            'user_id' => $secondUser->id,
            'rating' => 4,
            'comment' => '中間評価レビューです。',
        ]);

        // 平均評価3.0のレビューを作成
        $lowRatedBook->reviews()->create([
            'user_id' => $thirdUser->id,
            'rating' => 3,
            'comment' => '低評価レビューです。',
        ]);

        // ランキング画面へアクセス
        $response = $this->get(
            route('ranking.index')
        );

        // ランキング画面が正常に表示されることを確認
        $response->assertStatus(200);

        // レビュー平均評価が高い書籍から順番に表示されることを確認
        $response->assertSeeInOrder([
            '平均評価が高い書籍',
            '平均評価が中間の書籍',
            '平均評価が低い書籍',
        ]);
    }

    public function test_ランキング画面にはレビュー平均評価の上位10冊だけが表示される(): void
    {
        // レビュー投稿者を作成
        $user = User::factory()->create();

        // 平均評価5の書籍を10冊作成
        $topBooks = Book::factory()->count(10)->create();

        foreach ($topBooks as $index => $book) {
            $book->update([
                'title' => 'ランキング上位書籍'.($index + 1),
            ]);

            $book->reviews()->create([
                'user_id' => $user->id,
                'rating' => 5,
                'comment' => '高評価レビューです。',
            ]);
        }

        // 平均評価1の11位となる書籍を作成
        $eleventhBook = Book::factory()->create([
            'title' => 'ランキング11位の書籍',
        ]);

        $eleventhBook->reviews()->create([
            'user_id' => $user->id,
            'rating' => 1,
            'comment' => '低評価レビューです。',
        ]);

        // ランキング画面へアクセス
        $response = $this->get(
            route('ranking.index')
        );

        // ランキング画面が正常に表示されることを確認
        $response->assertStatus(200);

        // ランキングに10冊だけ渡されていることを確認
        $response->assertViewHas('rankedBooks', function ($rankedBooks) {
            return $rankedBooks->count() === 10;
        });

        // 上位10冊が表示されることを確認
        foreach ($topBooks as $book) {
            $response->assertSee($book->title);
        }

        // 11位の書籍が表示されないことを確認
        $response->assertDontSee('ランキング11位の書籍');
    }

    public function test_レビューが1件もない書籍はランキングに表示されない(): void
    {
        // レビュー投稿者を作成
        $user = User::factory()->create();

        // レビューがある書籍を作成
        $reviewedBook = Book::factory()->create([
            'title' => 'レビューがある書籍',
        ]);

        // レビューがない書籍を作成
        $unreviewedBook = Book::factory()->create([
            'title' => 'レビューがない書籍',
        ]);

        // レビューがある書籍にレビューを投稿
        $reviewedBook->reviews()->create([
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => '高評価レビューです。',
        ]);

        // ランキング画面へアクセス
        $response = $this->get(
            route('ranking.index')
        );

        // ランキング画面が正常に表示されることを確認
        $response->assertStatus(200);

        // レビューがある書籍が表示されることを確認
        $response->assertSee('レビューがある書籍');

        // レビューがない書籍が表示されないことを確認
        $response->assertDontSee('レビューがない書籍');
    }

    public function test_ランキング画面の書籍リンクから詳細画面へ遷移できる(): void
    {
        // レビュー投稿者を作成
        $user = User::factory()->create();

        // ランキングに表示する書籍を作成
        $book = Book::factory()->create([
            'title' => '詳細画面へ遷移する書籍',
        ]);

        // 書籍にレビューを投稿
        $book->reviews()->create([
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => '高評価レビューです。',
        ]);

        // ランキング画面へアクセス
        $response = $this->get(
            route('ranking.index')
        );

        // ランキング画面が正常に表示されることを確認
        $response->assertStatus(200);

        // 書籍詳細画面へのリンクが表示されることを確認
        $response->assertSee(
            route('books.show', $book)
        );

        // 書籍タイトルが表示されることを確認
        $response->assertSee('詳細画面へ遷移する書籍');
    }
}