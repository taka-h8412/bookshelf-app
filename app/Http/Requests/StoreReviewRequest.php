<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
}