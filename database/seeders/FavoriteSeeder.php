<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    public function run(): void
    {
        $favorites = [
            'yamada@example.com' => [
                '9784101010014',
                '9784422100524',
                '9784873115658',
                '9784309226712',
                '9784822289607',
            ],
            'suzuki@example.com' => [
                '9784101010021',
                '9784478025819',
                '9784163902302',
                '9784822251468',
            ],
            'tanaka@example.com' => [
                '9784863940246',
                '9784309226712',
                '9784822289607',
                '9784822251468',
            ],
            'sato@example.com' => [
                '9784422100524',
                '9784048930598',
                '9784478025819',
            ],
            'takahashi@example.com' => [
                '9784101010014',
                '9784873115658',
                '9784048930598',
                '9784163902302',
                '9784822289607',
            ],
        ];

        foreach ($favorites as $email => $isbns) {
            $user = User::where('email', $email)->first();

            // Seederではidを直接書かず、要件で一意なISBNから書籍IDを取得する
            $bookIds = Book::whereIn('isbn', $isbns)->pluck('id')->toArray();

            // 既存のお気に入りを外さず、未登録の組み合わせだけを追加する
            $user->favoriteBooks()->syncWithoutDetaching($bookIds);
        }
    }
}