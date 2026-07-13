<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BookManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 共通ナビゲーション(navigation.blade.php)で使用する未実装ルートを、テスト中だけ仮登録する
        if (! Route::has('ranking.index')) {
            Route::get('/test/ranking', fn () => '')->name('ranking.index');
        }

        if (! Route::has('favorites.index')) {
            Route::get('/test/favorites', fn () => '')->name('favorites.index');
        }

        // テスト中に追加した名前付きルートをLaravelへ再認識させる
        app('router')->getRoutes()->refreshNameLookups();
    }

    public function test_ゲストユーザーは書籍一覧画面を表示できる(): void
    {
        // 書籍に紐づけるジャンルを作成
        $genre = Genre::create([
            'name' => '小説',
        ]);

        // 一覧に表示する書籍を作成
        $book = Book::factory()->create([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
        ]);

        // 書籍とジャンルを紐づける
        $book->genres()->attach($genre->id);

        // ゲスト状態で書籍一覧画面へアクセス
        $response = $this->get(route('books.index'));

        // 画面が正常に表示されることを確認
        $response->assertStatus(200);

        // 書籍情報とジャンルが表示されることを確認
        $response->assertSee('吾輩は猫である');
        $response->assertSee('夏目漱石');
        $response->assertSee('小説');
    }

    public function test_書籍一覧画面では書籍が10件ずつ表示される(): void
    {
        // 書籍を11件作成
        Book::factory()->count(11)->create();

        // ゲスト状態で書籍一覧画面へアクセス
        $response = $this->get(route('books.index'));

        // 画面が正常に表示されることを確認
        $response->assertStatus(200);

        // 1ページ10件で、全11件・2ページになることを確認
        $response->assertViewHas('books', function ($books) {
            return $books->perPage() === 10
                && $books->total() === 11
                && $books->count() === 10
                && $books->lastPage() === 2;
        });
    }

    public function test_ログインユーザーは書籍登録画面を表示できる(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 登録画面に表示するジャンルを作成
        Genre::create([
            'name' => '小説',
        ]);

        Genre::create([
            'name' => '技術書',
        ]);

        // ログイン状態で書籍登録画面へアクセス
        $response = $this->actingAs($user)->get(
            route('books.create')
        );

        // 画面が正常に表示されることを確認
        $response->assertStatus(200);

        // 登録画面に必要な項目が表示されることを確認
        $response->assertSee('書籍の登録');
        $response->assertSee('タイトル');
        $response->assertSee('著者');
        $response->assertSee('ISBN-13');
        $response->assertSee('出版日');
        $response->assertSee('小説');
        $response->assertSee('技術書');
    }

    public function test_未ログインユーザーは書籍登録画面にアクセスできない(): void
    {
        // 未ログイン状態で書籍登録画面へアクセス
        $response = $this->get(
            route('books.create')
        );

        // ログイン画面へリダイレクトされることを確認
        $response->assertRedirect(route('login'));
    }

    public function test_ログインユーザーは書籍を登録できる(): void
    {
        $this->withoutExceptionHandling();
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 書籍に紐づけるジャンルを作成
        $genre = Genre::create([
            'name' => '技術書',
        ]);

        // ログイン状態で書籍登録処理を実行
        $response = $this->actingAs($user)->post(
            route('books.store'),
            [
                'title' => 'Laravel入門',
                'author' => '山田太郎',
                'isbn' => '9784000000001',
                'published_date' => '2026-07-13',
                'description' => 'Laravelの基礎を学べる書籍です。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
                'genres' => [$genre->id],
            ]
        );

        // 登録後に書籍詳細画面へリダイレクトされることを確認
        $book = Book::where('isbn', '9784000000001')->first();

        $response->assertRedirect(
            route('books.show', $book)
        );

        // 登録成功のフラッシュメッセージを確認
        $response->assertSessionHas(
            'success',
            '書籍を登録しました。'
        );

        // 書籍がDBに保存されていることを確認
        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '9784000000001',
        ]);

        // 書籍とジャンルの紐づきが保存されていることを確認
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_必須項目が未入力の場合は書籍を登録できない(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 必須項目を空にして書籍登録処理を実行
        $response = $this->actingAs($user)
            ->from(route('books.create'))
            ->post(route('books.store'), [
                'title' => '',
                'author' => '',
                'isbn' => '',
                'published_date' => '',
                'genres' => [],
            ]);

        // 書籍登録画面へ戻されることを確認
        $response->assertRedirect(route('books.create'));

        // 必須項目に日本語のバリデーションエラーがあることを確認
        $response->assertSessionHasErrors([
            'title' => 'タイトルを入力してください。',
            'author' => '著者を入力してください。',
            'isbn' => 'ISBNを入力してください。',
            'published_date' => '出版日を入力してください。',
            'genres' => 'ジャンルを選択してください。',
        ]);

        // 書籍がDBに登録されていないことを確認
        $this->assertDatabaseCount('books', 0);
    }

    public function test_ISBNが13桁でない場合は書籍を登録できない(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 書籍に紐づけるジャンルを作成
        $genre = Genre::create([
            'name' => '技術書',
        ]);

        // 13桁ではないISBNで書籍登録処理を実行
        $response = $this->actingAs($user)
            ->from(route('books.create'))
            ->post(route('books.store'), [
                'title' => 'Laravel入門',
                'author' => '山田太郎',
                'isbn' => '978400000001',
                'published_date' => '2026-07-13',
                'genres' => [$genre->id],
            ]);

        // 書籍登録画面へ戻されることを確認
        $response->assertRedirect(route('books.create'));

        // ISBNの桁数エラーを確認
        $response->assertSessionHasErrors([
            'isbn' => 'ISBNは13桁の数字で入力してください。',
        ]);

        // 書籍がDBに登録されていないことを確認
        $this->assertDatabaseCount('books', 0);
    }

    public function test_同じISBNの書籍は重複して登録できない(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 書籍に紐づけるジャンルを作成
        $genre = Genre::create([
            'name' => '技術書',
        ]);

        // 重複確認用の書籍を事前に登録
        Book::factory()->create([
            'isbn' => '9784000000001',
        ]);

        // 既に登録されているISBNで書籍登録処理を実行
        $response = $this->actingAs($user)
            ->from(route('books.create'))
            ->post(route('books.store'), [
                'title' => 'Laravel実践入門',
                'author' => '佐藤太郎',
                'isbn' => '9784000000001',
                'published_date' => '2026-07-13',
                'genres' => [$genre->id],
            ]);

        // 書籍登録画面へ戻されることを確認
        $response->assertRedirect(route('books.create'));

        // ISBNの重複エラーを確認
        $response->assertSessionHasErrors([
            'isbn' => 'このISBNは既に登録されています。',
        ]);

        // 同じISBNの書籍が1件だけであることを確認
        $this->assertDatabaseCount('books', 1);

        $this->assertDatabaseMissing('books', [
            'title' => 'Laravel実践入門',
            'isbn' => '9784000000001',
        ]);
    }

    public function test_存在しないジャンルIDでは書籍を登録できない(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 存在しないジャンルIDで書籍登録処理を実行
        $response = $this->actingAs($user)
            ->from(route('books.create'))
            ->post(route('books.store'), [
                'title' => 'Laravel入門',
                'author' => '山田太郎',
                'isbn' => '9784000000001',
                'published_date' => '2026-07-13',
                'genres' => [9999],
            ]);

        // 書籍登録画面へ戻されることを確認
        $response->assertRedirect(route('books.create'));

        // 存在しないジャンルのエラーを確認
        $response->assertSessionHasErrors([
            'genres.0' => '選択されたジャンルは存在しません。',
        ]);

        // 書籍がDBに登録されていないことを確認
        $this->assertDatabaseCount('books', 0);

        // 中間テーブルにも紐づきが登録されていないことを確認
        $this->assertDatabaseCount('book_genre', 0);
    }

    public function test_任意項目が未入力でも書籍を登録できる(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 書籍に紐づけるジャンルを作成
        $genre = Genre::create([
            'name' => '技術書',
        ]);

        // descriptionとimage_urlを送信せずに書籍登録処理を実行
        $response = $this->actingAs($user)->post(
            route('books.store'),
            [
                'title' => 'Laravel入門',
                'author' => '山田太郎',
                'isbn' => '9784000000002',
                'published_date' => '2026-07-13',
                'genres' => [$genre->id],
            ]
        );

        // 登録された書籍を取得
        $book = Book::where('isbn', '9784000000002')->first();

        // 登録後に書籍詳細画面へリダイレクトされることを確認
        $response->assertRedirect(
            route('books.show', $book)
        );

        // 登録成功のフラッシュメッセージを確認
        $response->assertSessionHas(
            'success',
            '書籍を登録しました。'
        );

        // 任意項目がnullで保存されることを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'description' => null,
            'image_url' => null,
        ]);

        // 書籍とジャンルの紐づきが保存されることを確認
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_書籍に複数のジャンルを登録できる(): void
    {
        // テスト用のログインユーザーを作成
        $user = User::factory()->create();

        // 書籍に紐づけるジャンルを2件作成
        $novelGenre = Genre::create([
            'name' => '小説',
        ]);

        $historyGenre = Genre::create([
            'name' => '歴史',
        ]);

        // 2件のジャンルを選択して書籍登録処理を実行
        $response = $this->actingAs($user)->post(
            route('books.store'),
            [
                'title' => '歴史小説入門',
                'author' => '山田太郎',
                'isbn' => '9784000000003',
                'published_date' => '2026-07-13',
                'genres' => [
                    $novelGenre->id,
                    $historyGenre->id,
                ],
            ]
        );

        // 登録された書籍を取得
        $book = Book::where('isbn', '9784000000003')->first();

        // 登録した書籍の詳細画面へリダイレクトされることを確認
        $response->assertRedirect(
            route('books.show', $book)
        );

        // 登録成功のフラッシュメッセージを確認
        $response->assertSessionHas(
            'success',
            '書籍を登録しました。'
        );

        // booksテーブルへ書籍が登録されていることを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'isbn' => '9784000000003',
        ]);

        // 小説ジャンルとの紐づきが登録されていることを確認
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $novelGenre->id,
        ]);

        // 歴史ジャンルとの紐づきが登録されていることを確認
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $historyGenre->id,
        ]);

        // 1冊の書籍に2件のジャンルが紐づいていることを確認
        $this->assertCount(2, $book->genres);
    }

    public function test_ゲストユーザーは書籍詳細画面を表示できる(): void
    {
        // 書籍に紐づけるジャンルを作成
        $genre = Genre::create([
            'name' => '技術書',
        ]);

        // 詳細画面に表示する書籍を作成
        $book = Book::factory()->create([
            'title' => 'Laravel実践ガイド',
            'author' => '山田太郎',
            'isbn' => '9784000000101',
            'published_date' => '2026-07-13',
            'description' => 'Laravelの実践的な内容を学べる書籍です。',
        ]);

        // 書籍とジャンルを紐づける
        $book->genres()->attach($genre->id);

        // ゲスト状態で書籍詳細画面へアクセス
        $response = $this->get(
            route('books.show', $book)
        );

        // 詳細画面が正常に表示されることを確認
        $response->assertStatus(200);

        // 書籍情報とジャンルが表示されることを確認
        $response->assertSee('Laravel実践ガイド');
        $response->assertSee('山田太郎');
        $response->assertSee('9784000000101');
        $response->assertSee('2026-07-13');
        $response->assertSee('Laravelの実践的な内容を学べる書籍です。');
        $response->assertSee('技術書');
    }

    public function test_ログインユーザーは書籍詳細画面を表示できる(): void
    {
        // 書籍を登録するログインユーザーを作成
        $user = User::factory()->create();

        // 書籍に紐づけるジャンルを作成
        $genre = Genre::create([
            'name' => '技術書',
        ]);

        // 詳細画面に表示する書籍を作成
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => 'Laravel実践ガイド',
            'author' => '山田太郎',
        ]);

        // 書籍とジャンルを紐づける
        $book->genres()->attach($genre->id);

        // ログイン状態で書籍詳細画面へアクセス
        $response = $this->actingAs($user)->get(
            route('books.show', $book)
        );

        // 詳細画面が正常に表示されることを確認
        $response->assertStatus(200);

        // 書籍情報が表示されることを確認
        $response->assertSee('Laravel実践ガイド');
        $response->assertSee('山田太郎');
        $response->assertSee('技術書');
    }
}