<?php

namespace Tests\Unit\Models;

use App\Models\Review;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    public function test_レビューは投稿ユーザーに属する(): void
    {
        $review = new Review();

        $this->assertInstanceOf(BelongsTo::class, $review->user());
    }

    public function test_レビューは書籍に属する(): void
    {
        $review = new Review();

        $this->assertInstanceOf(BelongsTo::class, $review->book());
    }

    public function test_レビューは複数のユーザーにいいねされる(): void
    {
        $review = new Review();

        $this->assertInstanceOf(BelongsToMany::class, $review->likedByUsers());
    }
}