<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class BookTest extends TestCase
{
    public function test_書籍は登録ユーザーに属する(): void
    {
        $book = new Book();

        $this->assertInstanceOf(BelongsTo::class, $book->user());
    }

    public function test_書籍は複数のジャンルに紐づく(): void
    {
        $book = new Book();

        $this->assertInstanceOf(BelongsToMany::class, $book->genres());
    }

    public function test_書籍は複数のレビューを持つ(): void
    {
        $book = new Book();

        $this->assertInstanceOf(HasMany::class, $book->reviews());
    }

    public function test_書籍は複数のユーザーにお気に入り登録される(): void
    {
        $book = new Book();

        $this->assertInstanceOf(BelongsToMany::class, $book->favoritedByUsers());
    }
}