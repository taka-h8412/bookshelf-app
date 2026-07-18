<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログインユーザーは書籍をお気に入り登録できる(): void
    {
        // ログインユーザーを作成
        $user = User::factory()->create();

        // お気に入り登録する書籍を作成
        $book = Book::factory()->create();

        // ログインユーザーとしてお気に入りボタンを押す
        $response = $this->actingAs($user)->post(
            route('favorites.toggle', $book)
        );

        // favoritesテーブルにお気に入りが登録されることを確認
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // 書籍詳細画面へリダイレクトされることを確認
        $response->assertRedirect(
            route('books.show', $book)
        );
    }

    public function test_お気に入り済みの書籍をもう一度押すと解除できる(): void
    {
        // ログインユーザーを作成
        $user = User::factory()->create();

        // お気に入り登録する書籍を作成
        $book = Book::factory()->create();

        // あらかじめログインユーザーのお気に入りを登録
        $book->favoritedByUsers()->attach($user->id);

        // ログインユーザーとしてもう一度お気に入りボタンを押す
        $response = $this->actingAs($user)->post(
            route('favorites.toggle', $book)
        );

        // お気に入りが削除されることを確認
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // 書籍詳細画面へリダイレクトされることを確認
        $response->assertRedirect(
            route('books.show', $book)
        );
    }

    public function test_ログインユーザーはお気に入り一覧画面を表示できる(): void
    {
        // ログインユーザーを作成
        $user = User::factory()->create();

        // ログインユーザーとしてお気に入り一覧画面へアクセス
        $response = $this->actingAs($user)->get(
            route('favorites.index')
        );

        // お気に入り一覧画面が正常に表示されることを確認
        $response->assertStatus(200);

        // お気に入り一覧画面の見出しが表示されることを確認
        $response->assertSee('お気に入り');
    }

    public function test_お気に入り一覧にはログインユーザーがお気に入り登録した書籍だけが表示される(): void
    {
        // ログインユーザーを作成
        $user = User::factory()->create();

        // お気に入り登録する書籍を作成
        $favoriteBook = Book::factory()->create([
            'title' => 'お気に入り登録した書籍',
        ]);

        // お気に入り登録しない書籍を作成
        $otherBook = Book::factory()->create([
            'title' => 'お気に入り登録していない書籍',
        ]);

        // ログインユーザーのお気に入りを登録
        $favoriteBook->favoritedByUsers()->attach($user->id);

        // お気に入り一覧画面へアクセス
        $response = $this->actingAs($user)->get(
            route('favorites.index')
        );

        // お気に入り一覧画面が正常に表示されることを確認
        $response->assertStatus(200);

        // お気に入り登録した書籍が表示されることを確認
        $response->assertSee('お気に入り登録した書籍');

        // お気に入り登録していない書籍が表示されないことを確認
        $response->assertDontSee('お気に入り登録していない書籍');
    }

    public function test_未ログインユーザーはお気に入り一覧画面へアクセスできない(): void
    {
        // 未ログイン状態でお気に入り一覧画面へアクセス
        $response = $this->get(
            route('favorites.index')
        );

        // ログイン画面へリダイレクトされることを確認
        $response->assertRedirect(
            route('login')
        );
    }

    public function test_お気に入り一覧画面では書籍が10件ずつ表示される(): void
    {
        // ログインユーザーを作成
        $user = User::factory()->create();

        // お気に入り登録する書籍を11件作成
        $books = Book::factory()->count(11)->create();

        // 11件すべてをログインユーザーのお気に入りに登録
        foreach ($books as $book) {
            $book->favoritedByUsers()->attach($user->id);
        }

        // お気に入り一覧の1ページ目へアクセス
        $response = $this->actingAs($user)->get(
            route('favorites.index')
        );

        // お気に入り一覧画面が正常に表示されることを確認
        $response->assertStatus(200);

        // 1ページ目には10件表示されることを確認
        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10
                && $books->total() === 11
                && $books->lastPage() === 2;
        });
    }
}