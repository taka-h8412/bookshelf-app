<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookManagementTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_書籍の登録者は編集画面を表示できる(): void
    {
        // 書籍を登録するユーザーを作成
        $user = User::factory()->create();

        // 編集画面に表示するジャンルを作成
        $selectedGenre = Genre::create([
            'name' => '技術書',
        ]);

        $unselectedGenre = Genre::create([
            'name' => '小説',
        ]);

        // ログインユーザーが登録した書籍を作成
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => 'Laravel実践ガイド',
            'author' => '山田太郎',
            'isbn' => '9784000000101',
            'published_date' => '2026-07-14',
            'description' => 'Laravelの実践的な内容を学べる書籍です。',
        ]);

        // 書籍に技術書ジャンルを紐づける
        $book->genres()->attach($selectedGenre->id);

        // 登録者本人として書籍編集画面へアクセス
        $response = $this->actingAs($user)->get(
            route('books.edit', $book)
        );

        // 編集画面が正常に表示されることを確認
        $response->assertStatus(200);

        // 現在の書籍情報が入力欄に表示されることを確認
        $response->assertSee('書籍の編集');
        $response->assertSee('value="Laravel実践ガイド"', false);
        $response->assertSee('value="山田太郎"', false);
        $response->assertSee('value="9784000000101"', false);
        $response->assertSee('value="2026-07-14"', false);
        $response->assertSee('Laravelの実践的な内容を学べる書籍です。');

        // 登録済みジャンルが選択状態で表示されることを確認
        $response->assertSee(
            'value="'.$selectedGenre->id.'"',
            false
        );

        $response->assertSee('checked', false);

        // 未選択のジャンルも選択肢として表示されることを確認
        $response->assertSee('小説');
    }

    public function test_書籍の登録者は書籍情報を更新できる(): void
    {
        // 書籍を登録するユーザーを作成
        $user = User::factory()->create();

        // 更新前後で使用するジャンルを作成
        $oldGenre = Genre::create([
            'name' => '技術書',
        ]);

        $newGenre = Genre::create([
            'name' => 'ビジネス',
        ]);

        // ログインユーザーが登録した書籍を作成
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '9784000000201',
            'published_date' => '2026-07-01',
            'description' => '更新前の説明です。',
            'image_url' => null,
        ]);

        // 更新前のジャンルを紐づける
        $book->genres()->attach($oldGenre->id);

        // 登録者本人として書籍更新処理を実行
        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            [
                'title' => '更新後タイトル',
                'author' => '更新後著者',
                'isbn' => '9784000000202',
                'published_date' => '2026-07-14',
                'description' => '更新後の説明です。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=update',
                'genres' => [$newGenre->id],
            ]
        );

        // 更新後に書籍詳細画面へリダイレクトされることを確認
        $response->assertRedirect(
            route('books.show', $book)
        );

        // 更新成功のフラッシュメッセージを確認
        $response->assertSessionHas(
            'success',
            '書籍を更新しました。'
        );

        // booksテーブルの書籍情報が更新されていることを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9784000000202',
            'published_date' => '2026-07-14',
            'description' => '更新後の説明です。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=update',
        ]);

        // 新しいジャンルとの紐づきが保存されていることを確認
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        // 更新前のジャンルとの紐づきが削除されていることを確認
        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);
    }

    public function test_更新時は現在の書籍のISBNを重複として扱わない(): void
    {
        // 書籍を登録するユーザーを作成
        $user = User::factory()->create();

        // 書籍に紐づけるジャンルを作成
        $genre = Genre::create([
            'name' => '技術書',
        ]);

        // ログインユーザーが登録した書籍を作成
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '9784000000301',
            'published_date' => '2026-07-01',
            'description' => '更新前の説明です。',
            'image_url' => null,
        ]);

        // 書籍とジャンルを紐づける
        $book->genres()->attach($genre->id);

        // ISBNは変更せず、その他の書籍情報を更新
        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            [
                'title' => '更新後タイトル',
                'author' => '更新後著者',
                'isbn' => '9784000000301',
                'published_date' => '2026-07-14',
                'description' => '更新後の説明です。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=update',
                'genres' => [$genre->id],
            ]
        );

        // 更新後に書籍詳細画面へリダイレクトされることを確認
        $response->assertRedirect(
            route('books.show', $book)
        );

        // 更新成功のフラッシュメッセージを確認
        $response->assertSessionHas(
            'success',
            '書籍を更新しました。'
        );

        // 同じISBNのまま書籍情報が更新されていることを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9784000000301',
            'published_date' => '2026-07-14',
            'description' => '更新後の説明です。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=update',
        ]);
    }

    public function test_書籍の登録者以外は編集画面を表示できない(): void
    {
        // 書籍を登録するユーザーを作成
        $owner = User::factory()->create();

        // 別のログインユーザーを作成
        $otherUser = User::factory()->create();

        // 登録者が作成した書籍を用意
        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        // 登録者以外のユーザーとして編集画面へアクセス
        $response = $this->actingAs($otherUser)->get(
            route('books.edit', $book)
        );

        // アクセスが拒否されることを確認
        $response->assertForbidden();
    }

    public function test_書籍の登録者以外は書籍情報を更新できない(): void
    {
        // 書籍を登録するユーザーを作成
        $owner = User::factory()->create();

        // 別のログインユーザーを作成
        $otherUser = User::factory()->create();

        // 書籍に紐づけるジャンルを作成
        $genre = Genre::create([
            'name' => '技術書',
        ]);

        // 登録者が作成した書籍を用意
        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '更新前タイトル',
            'isbn' => '9784000000401',
        ]);

        $book->genres()->attach($genre->id);

        // 登録者以外のユーザーとして更新処理を実行
        $response = $this->actingAs($otherUser)->put(
            route('books.update', $book),
            [
                'title' => '不正に更新されたタイトル',
                'author' => $book->author,
                'isbn' => $book->isbn,
                'published_date' => $book->published_date,
                'description' => $book->description,
                'image_url' => $book->image_url,
                'genres' => [$genre->id],
            ]
        );

        // アクセスが拒否されることを確認
        $response->assertForbidden();

        // 書籍情報が変更されていないことを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '更新前タイトル',
            'isbn' => '9784000000401',
        ]);
    }

    public function test_未ログインユーザーは書籍編集画面にアクセスできない(): void
    {
        // 編集対象の書籍を作成
        $book = Book::factory()->create();

        // 未ログイン状態で書籍編集画面へアクセス
        $response = $this->get(
            route('books.edit', $book)
        );

        // ログイン画面へリダイレクトされることを確認(authミドルウェア)
        $response->assertRedirect(route('login'));
    }

    public function test_書籍の登録者は書籍を削除できる(): void
    {
        // 書籍を登録するユーザーを作成
        $user = User::factory()->create();

        // 書籍に紐づけるジャンルを作成
        $genre = Genre::create([
            'name' => '技術書',
        ]);

        // ログインユーザーが登録した書籍を作成
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '削除対象の書籍',
        ]);

        // 書籍とジャンルを紐づける
        $book->genres()->attach($genre->id);

        // 登録者本人として書籍削除処理を実行
        $response = $this->actingAs($user)->delete(
            route('books.destroy', $book)
        );

        // 削除後に書籍一覧画面へリダイレクトされることを確認
        $response->assertRedirect(
            route('books.index')
        );

        // 削除成功のフラッシュメッセージを確認
        $response->assertSessionHas(
            'success',
            '書籍を削除しました。'
        );

        // booksテーブルから書籍が物理削除されていることを確認
        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '削除対象の書籍',
        ]);

        // 中間テーブルのジャンル紐づきも削除されていることを確認
        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

        public function test_書籍の登録者以外は書籍を削除できない(): void
    {
        // 書籍を登録するユーザーを作成
        $owner = User::factory()->create();

        // 別のログインユーザーを作成
        $otherUser = User::factory()->create();

        // 登録者が作成した書籍を用意
        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '削除してはいけない書籍',
        ]);

        // 登録者以外のユーザーとして削除処理を実行
        $response = $this->actingAs($otherUser)->delete(
            route('books.destroy', $book)
        );

        // アクセスが拒否されることを確認
        $response->assertForbidden();

        // 書籍が削除されずDBに残っていることを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '削除してはいけない書籍',
        ]);
    }

    public function test_未ログインユーザーは書籍を削除できない(): void
    {
        // 削除対象の書籍を作成
        $book = Book::factory()->create();

        // 未ログイン状態で削除処理を実行
        $response = $this->delete(
            route('books.destroy', $book)
        );

        // ログイン画面へリダイレクトされることを確認
        $response->assertRedirect(route('login'));

        // 書籍が削除されずDBに残っていることを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_出版日が有効な日付でない場合は書籍を登録できない(): void
    {
        // 書籍を登録するログインユーザーを作成
        $user = User::factory()->create();

        // 書籍に紐づけるジャンルを作成
        $genre = Genre::create([
            'name' => '技術書',
        ]);

        // 有効ではない出版日で書籍登録処理を実行
        $response = $this->actingAs($user)->post(
            route('books.store'),
            [
                'title' => '日付確認用書籍',
                'author' => 'テスト著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-13-40',
                'description' => '出版日のバリデーション確認用です。',
                'image_url' => 'https://placehold.co/200x300',
                'genres' => [$genre->id],
            ]
        );

        // 出版日にバリデーションエラーがあることを確認
        $response->assertSessionHasErrors('published_date');

        // 書籍がDBに登録されていないことを確認
        $this->assertDatabaseMissing('books', [
            'isbn' => '9781234567890',
        ]);
    }

    public function test_画像URLがURL形式でない場合は書籍を登録できない(): void
    {
        // 書籍を登録するログインユーザーを作成
        $user = User::factory()->create();

        // 書籍に紐づけるジャンルを作成
        $genre = Genre::create([
            'name' => '技術書',
        ]);

        // URL形式ではない画像URLで書籍登録処理を実行
        $response = $this->actingAs($user)->post(
            route('books.store'),
            [
                'title' => '画像URL確認用書籍',
                'author' => 'テスト著者',
                'isbn' => '9780987654321',
                'published_date' => '2026-07-17',
                'description' => '画像URLのバリデーション確認用です。',
                'image_url' => '画像URLではない文字列',
                'genres' => [$genre->id],
            ]
        );

        // 画像URLに指定したバリデーションメッセージがあることを確認
        $response->assertSessionHasErrors([
            'image_url' => '画像URLはURL形式で入力してください。',
        ]);

        // 書籍がDBに登録されていないことを確認
        $this->assertDatabaseMissing('books', [
            'isbn' => '9780987654321',
        ]);
    }
}