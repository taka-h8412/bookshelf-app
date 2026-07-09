<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = [
            [
                'email' => 'suzuki@example.com',
                'isbn' => '9784101010014',
                'rating' => 4,
                'comment' => '猫の視点から人間社会を眺める構成が面白く、古典ですが想像以上に読みやすかったです。',
            ],
            [
                'email' => 'tanaka@example.com',
                'isbn' => '9784101010014',
                'rating' => 5,
                'comment' => 'ユーモアの中に鋭い風刺があり、夏目漱石の観察力を感じられる作品でした。',
            ],

            [
                'email' => 'yamada@example.com',
                'isbn' => '9784422100524',
                'rating' => 5,
                'comment' => '人間関係の基本が具体例とともに説明されていて、仕事でも日常でも活かせる内容でした。',
            ],
            [
                'email' => 'sato@example.com',
                'isbn' => '9784422100524',
                'rating' => 4,
                'comment' => '相手を尊重する姿勢の大切さがよく分かり、コミュニケーションを見直すきっかけになりました。',
            ],
            [
                'email' => 'takahashi@example.com',
                'isbn' => '9784422100524',
                'rating' => 5,
                'comment' => '古い本ですが内容は今でも通用し、定期的に読み返したいと思える一冊です。',
            ],

            [
                'email' => 'yamada@example.com',
                'isbn' => '9784873115658',
                'rating' => 5,
                'comment' => '変数名や関数分割など、すぐに実装で意識できる内容が多くて参考になりました。',
            ],
            [
                'email' => 'suzuki@example.com',
                'isbn' => '9784873115658',
                'rating' => 4,
                'comment' => 'コードを読む人の視点で考える重要性が分かり、チーム開発にも役立つと感じました。',
            ],
            [
                'email' => 'sato@example.com',
                'isbn' => '9784873115658',
                'rating' => 5,
                'comment' => '初心者にも分かりやすく、きれいなコードを書くための基礎を学べました。',
            ],

            [
                'email' => 'yamada@example.com',
                'isbn' => '9784863940246',
                'rating' => 4,
                'comment' => '主体的に行動することや優先順位を考えることの大切さを再確認できました。',
            ],
            [
                'email' => 'tanaka@example.com',
                'isbn' => '9784863940246',
                'rating' => 5,
                'comment' => '仕事だけでなく人生全体に活かせる考え方が多く、長く読み継がれる理由が分かりました。',
            ],
            [
                'email' => 'takahashi@example.com',
                'isbn' => '9784863940246',
                'rating' => 4,
                'comment' => '分量はありますが、章ごとに学びがあり実践したくなる内容でした。',
            ],

            [
                'email' => 'suzuki@example.com',
                'isbn' => '9784101010021',
                'rating' => 4,
                'comment' => '主人公のまっすぐな性格が印象的で、テンポよく読み進められました。',
            ],
            [
                'email' => 'sato@example.com',
                'isbn' => '9784101010021',
                'rating' => 3,
                'comment' => '時代背景は古いですが、人間関係の描写は今読んでも面白かったです。',
            ],
            [
                'email' => 'takahashi@example.com',
                'isbn' => '9784101010021',
                'rating' => 4,
                'comment' => '短めで読みやすく、夏目漱石の作品に初めて触れる人にも向いていると感じました。',
            ],

            [
                'email' => 'yamada@example.com',
                'isbn' => '9784309226712',
                'rating' => 5,
                'comment' => '人類史を大きな流れで捉えられ、歴史と科学のつながりがとても面白かったです。',
            ],
            [
                'email' => 'tanaka@example.com',
                'isbn' => '9784309226712',
                'rating' => 4,
                'comment' => '知的好奇心を刺激される内容で、普段のものの見方が少し変わりました。',
            ],
            [
                'email' => 'sato@example.com',
                'isbn' => '9784309226712',
                'rating' => 5,
                'comment' => '難しいテーマを分かりやすく説明していて、最後まで興味を持って読めました。',
            ],

            [
                'email' => 'yamada@example.com',
                'isbn' => '9784048930598',
                'rating' => 5,
                'comment' => '保守しやすいコードを書くための考え方が具体的で、実務でも意識したい内容でした。',
            ],
            [
                'email' => 'suzuki@example.com',
                'isbn' => '9784048930598',
                'rating' => 4,
                'comment' => 'コードの品質を高めるための基準が分かり、読み応えのある技術書でした。',
            ],
            [
                'email' => 'takahashi@example.com',
                'isbn' => '9784048930598',
                'rating' => 5,
                'comment' => '初心者には少し難しい部分もありますが、成長するほど価値が分かる本だと思います。',
            ],

            [
                'email' => 'suzuki@example.com',
                'isbn' => '9784478025819',
                'rating' => 4,
                'comment' => '対話形式なので読みやすく、考え方のクセを見直すきっかけになりました。',
            ],
            [
                'email' => 'sato@example.com',
                'isbn' => '9784478025819',
                'rating' => 5,
                'comment' => '他人の評価に振り回されない考え方が印象に残り、前向きな気持ちになれました。',
            ],
            [
                'email' => 'takahashi@example.com',
                'isbn' => '9784478025819',
                'rating' => 4,
                'comment' => '自己啓発書として読みやすく、アドラー心理学の入口として良い本だと思います。',
            ],

            [
                'email' => 'yamada@example.com',
                'isbn' => '9784163902302',
                'rating' => 4,
                'comment' => '芸人の世界の葛藤や孤独が丁寧に描かれていて、余韻の残る作品でした。',
            ],
            [
                'email' => 'suzuki@example.com',
                'isbn' => '9784163902302',
                'rating' => 3,
                'comment' => '静かな文章の中に熱量があり、登場人物の不器用さが印象的でした。',
            ],
            [
                'email' => 'tanaka@example.com',
                'isbn' => '9784163902302',
                'rating' => 4,
                'comment' => '派手さはありませんが、夢を追う人の痛みが伝わってくる小説でした。',
            ],

            [
                'email' => 'yamada@example.com',
                'isbn' => '9784822289607',
                'rating' => 5,
                'comment' => '思い込みではなくデータで世界を見る大切さを学べる、非常に実用的な本でした。',
            ],
            [
                'email' => 'sato@example.com',
                'isbn' => '9784822289607',
                'rating' => 5,
                'comment' => '世界に対する認識が変わり、ニュースの見方にも良い影響がありました。',
            ],
            [
                'email' => 'takahashi@example.com',
                'isbn' => '9784822289607',
                'rating' => 4,
                'comment' => 'グラフやデータをもとに説明されていて、納得感を持って読めました。',
            ],

            [
                'email' => 'suzuki@example.com',
                'isbn' => '9784822251468',
                'rating' => 4,
                'comment' => 'コンテナが物流や経済に与えた影響が分かり、ビジネス史として面白かったです。',
            ],
            [
                'email' => 'tanaka@example.com',
                'isbn' => '9784822251468',
                'rating' => 5,
                'comment' => '普段意識しない物流の仕組みが見えて、世界経済の見方が広がりました。',
            ],
            [
                'email' => 'sato@example.com',
                'isbn' => '9784822251468',
                'rating' => 4,
                'comment' => '歴史とビジネスの両面から読める本で、地味なテーマなのに引き込まれました。',
            ],
        ];

        foreach ($reviews as $reviewData) {
            $user = User::where('email', $reviewData['email'])->first();
            $book = Book::where('isbn', $reviewData['isbn'])->first();

            Review::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $reviewData['rating'],
                'comment' => $reviewData['comment'],
            ]);
        }
    }
}