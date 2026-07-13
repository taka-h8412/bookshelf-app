<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class GenreManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 共通ナビゲーション(navigation.blade.php)で使用する未実装ルートを、ジャンル機能のテスト中だけ仮登録する
        if (! Route::has('books.index')) {
            Route::get('/test/books', fn () => '')->name('books.index'); //空文字を返す
        }

        if (! Route::has('books.create')) {
            Route::get('/test/books/create', fn () => '')->name('books.create'); //空文字を返す
        }

        if (! Route::has('books.show')) {
            Route::get('/test/books/{book}', fn () => '')->name('books.show'); //空文字を返す
        }

        if (! Route::has('ranking.index')) {
            Route::get('/test/ranking', fn () => '')->name('ranking.index'); //空文字を返す
        }

        if (! Route::has('favorites.index')) {
            Route::get('/test/favorites', fn () => '')->name('favorites.index'); //空文字を返す
        }

        // テスト中に追加した名前付きルートをLaravelへ再認識させる
        app('router')->getRoutes()->refreshNameLookups();
    }

    public function test_ログインユーザーはジャンル一覧画面を表示できる(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 一覧画面に表示するジャンルを作成
        Genre::create([
            'name' => '小説',
        ]);

        Genre::create([
            'name' => 'ビジネス',
        ]);

        // ログイン状態でジャンル一覧画面へアクセス
        $response = $this->actingAs($user)->get('/genres');

        // 画面が正常に表示されることを確認
        $response->assertStatus(200);

        // 見出しと登録済みジャンルが表示されることを確認
        $response->assertSee('ジャンル管理');
        $response->assertSee('小説');
        $response->assertSee('ビジネス');
    }

    public function test_ログインユーザーはジャンル登録画面を表示できる(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // ログイン状態でジャンル登録画面へアクセス
        $response = $this->actingAs($user)->get('/genres/create');

        // 画面が正常に表示されることを確認
        $response->assertStatus(200);

        // 登録画面に必要な文字が表示されることを確認
        $response->assertSee('ジャンル登録');
        $response->assertSee('ジャンル名');
    }

    public function test_ログインユーザーはジャンルを登録できる(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // ログイン状態でジャンル登録処理を実行
        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => '技術書',
        ]);

        // 登録後にジャンル一覧画面へリダイレクトされることを確認
        $response->assertRedirect(route('genres.index'));

        // 入力したジャンルがDBに保存されていることを確認
        $this->assertDatabaseHas('genres', [
            'name' => '技術書',
        ]);
    }

    public function test_ジャンル名が未入力の場合は登録できない(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // ジャンル名を空欄で登録処理を実行
        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => '',
        ]);

        // nameにバリデーションエラーがあることを確認
        $response->assertSessionHasErrors('name');

        // ジャンルがDBに保存されていないことを確認
        $this->assertDatabaseCount('genres', 0);
    }

    public function test_同じジャンル名は重複して登録できない(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 既存ジャンルを作成
        Genre::create([
            'name' => '小説',
        ]);

        // 同じジャンル名で登録処理を実行
        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => '小説',
        ]);

        // nameにバリデーションエラーがあることを確認
        $response->assertSessionHasErrors('name');

        // 重複登録されていないことを確認
        $this->assertDatabaseCount('genres', 1);
    }

    public function test_ログインユーザーはジャンル詳細画面を表示できる(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 詳細画面に表示するジャンルを作成
        $genre = Genre::create([
            'name' => '小説',
        ]);

        // ログイン状態でジャンル詳細画面へアクセス
        $response = $this->actingAs($user)->get(
            route('genres.show', $genre)
        );

        // 画面が正常に表示されることを確認
        $response->assertStatus(200);

        // 対象ジャンル名が表示されることを確認
        $response->assertSee('ジャンル: 小説');

        // 書籍がない場合のメッセージが表示されることを確認
        $response->assertSee('このジャンルの書籍はまだ登録されていません。');
    }

    public function test_ログインユーザーはジャンル編集画面を表示できる(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 編集対象のジャンルを作成
        $genre = Genre::create([
            'name' => '小説',
        ]);

        // ログイン状態でジャンル編集画面へアクセス
        $response = $this->actingAs($user)->get(
            route('genres.edit', $genre)
        );

        // 画面が正常に表示されることを確認
        $response->assertStatus(200);

        // 編集画面に必要な文字が表示されることを確認
        $response->assertSee('ジャンル編集');
        $response->assertSee('小説');
    }

    public function test_ログインユーザーはジャンルを更新できる(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 更新対象のジャンルを作成
        $genre = Genre::create([
            'name' => '小説',
        ]);

        // ログイン状態でジャンル更新処理を実行
        $response = $this->actingAs($user)->put(
            route('genres.update', $genre),
            [
                'name' => '文学',
            ]
        );

        // 更新後にジャンル一覧画面へリダイレクトされることを確認
        $response->assertRedirect(route('genres.index'));

        // 対象ジャンルの名前が更新されていることを確認
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '文学',
        ]);

        // 更新前の名前が残っていないことを確認
        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_ジャンル名が未入力の場合は更新できない(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 更新対象のジャンルを作成
        $genre = Genre::create([
            'name' => '小説',
        ]);

        // ジャンル名を空欄で更新処理を実行
        $response = $this->actingAs($user)->put(
            route('genres.update', $genre),
            [
                'name' => '',
            ]
        );

        // nameにバリデーションエラーがあることを確認
        $response->assertSessionHasErrors('name');

        // 元のジャンル名が保持されていることを確認
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_他のジャンルと同じ名前には更新できない(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 更新対象のジャンルを作成
        $genre = Genre::create([
            'name' => '小説',
        ]);

        // 既存の別ジャンルを作成
        Genre::create([
            'name' => 'ビジネス',
        ]);

        // 既存ジャンルと同じ名前へ更新処理を実行
        $response = $this->actingAs($user)->put(
            route('genres.update', $genre),
            [
                'name' => 'ビジネス',
            ]
        );

        // nameにバリデーションエラーがあることを確認
        $response->assertSessionHasErrors('name');

        // 元のジャンル名が保持されていることを確認
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_更新時は現在のジャンル名を重複として扱わない(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 更新対象のジャンルを作成
        $genre = Genre::create([
            'name' => '小説',
        ]);

        // 現在と同じジャンル名で更新処理を実行
        $response = $this->actingAs($user)->put(
            route('genres.update', $genre),
            [
                'name' => '小説',
            ]
        );

        // 更新後にジャンル一覧画面へリダイレクトされることを確認
        $response->assertRedirect(route('genres.index'));

        // ジャンル名が保持されていることを確認
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_書籍が紐づいていないジャンルは削除できる(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 削除対象のジャンルを作成
        $genre = Genre::create([
            'name' => '小説',
        ]);

        // ログイン状態でジャンル削除処理を実行
        $response = $this->actingAs($user)->delete(
            route('genres.destroy', $genre)
        );

        // 削除後にジャンル一覧画面へリダイレクトされることを確認
        $response->assertRedirect(route('genres.index'));

        // 対象ジャンルがDBから削除されていることを確認
        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_書籍が紐づいているジャンルは削除できない(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 削除対象のジャンルを作成
        $genre = Genre::create([
            'name' => '小説',
        ]);

        // ジャンルに紐づける書籍を作成
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        // 書籍とジャンルを紐づける
        $book->genres()->attach($genre->id);

        // ログイン状態でジャンル削除処理を実行
        $response = $this->actingAs($user)->delete(
            route('genres.destroy', $genre)
        );

        // 削除できず、ジャンル一覧画面へ戻ることを確認
        $response->assertRedirect(route('genres.index'));

        // エラーフラッシュメッセージが設定されていることを確認
        $response->assertSessionHas('error','書籍が紐づいているジャンルは削除できません。');

        // 対象ジャンルがDBに残っていることを確認
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_未ログインユーザーはジャンル一覧画面にアクセスできない(): void
    {
        $response = $this->get(route('genres.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_未ログインユーザーはジャンル詳細画面にアクセスできない(): void
    {
        $genre = Genre::create([
            'name' => '小説',
        ]);

        $response = $this->get(
            route('genres.show', $genre)
        );

        $response->assertRedirect(route('login'));
    }

    public function test_未ログインユーザーはジャンル登録画面にアクセスできない(): void
    {
        $response = $this->get(route('genres.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_未ログインユーザーはジャンル編集画面にアクセスできない(): void
    {
        $genre = Genre::create([
            'name' => '小説',
        ]);

        $response = $this->get(
            route('genres.edit', $genre)
        );

        $response->assertRedirect(route('login'));
    }

    public function test_ジャンル詳細画面では紐づく書籍が10件ずつ表示される(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 詳細画面に表示するジャンルを作成
        $genre = Genre::create([
            'name' => '小説',
        ]);

        // ジャンルに紐づける書籍を11件作成
        $books = Book::factory()->count(11)->create([
            'user_id' => $user->id,
        ]);

        // 作成した書籍とジャンルを紐づける
        foreach ($books as $book) {
            $book->genres()->attach($genre->id);
        }

        // ログイン状態でジャンル詳細画面へアクセス
        $response = $this->actingAs($user)->get(
            route('genres.show', $genre)
        );

        // 画面が正常に表示されることを確認
        $response->assertStatus(200);

        // 10件単位でページネーションされていることを確認
        $response->assertViewHas('books', function ($books) {
            return $books->perPage() === 10
                && $books->total() === 11
                && $books->count() === 10
                && $books->lastPage() === 2;
        });
    }
}
