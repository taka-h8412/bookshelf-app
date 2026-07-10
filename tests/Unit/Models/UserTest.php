<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_ユーザーは複数の書籍を登録できる(): void
    {
        $user = new User();

        $this->assertInstanceOf(HasMany::class, $user->books());
    }

    public function test_ユーザーは複数のレビューを投稿できる(): void
    {
        $user = new User();

        $this->assertInstanceOf(HasMany::class, $user->reviews());
    }

    public function test_ユーザーは複数の書籍をお気に入り登録できる(): void
    {
        $user = new User();

        $this->assertInstanceOf(BelongsToMany::class, $user->favoriteBooks());
    }

    public function test_ユーザーは複数のレビューにいいねできる(): void
    {
        $user = new User();

        $this->assertInstanceOf(BelongsToMany::class, $user->likedReviews());
    }
}