<?php

namespace Tests\Unit\Models;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\TestCase;

class GenreTest extends TestCase
{
    public function test_ジャンルは複数の書籍に紐づく(): void
    {
        $genre = new Genre();

        $this->assertInstanceOf(BelongsToMany::class, $genre->books());
    }
}