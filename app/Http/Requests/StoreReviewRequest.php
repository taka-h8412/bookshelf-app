<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => [
                'required',
                'integer',
                'between:1,5',
            ],
            'comment' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => '評価を選択してください。',
            'rating.integer' => '評価を正しく選択してください。',
            'rating.between' => '評価は★1から★5の間で選択してください。',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                // URLからレビュー対象の書籍を取得
                $book = $this->route('book');

                // ログインユーザーが同じ書籍へ投稿済みか確認
                $reviewExists = $book->reviews()
                    ->where('user_id', auth()->id())
                    ->exists();

                // 投稿済みの場合はバリデーションエラーを追加
                if ($reviewExists) {
                    $validator->errors()->add(
                        'rating',
                        'この書籍にはすでにレビューを投稿しています。'
                    );
                }
            },
        ];
    }
}
