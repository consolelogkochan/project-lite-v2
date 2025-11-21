<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CardUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // ルートパラメータから現在のカードモデルを取得
        // (例: /cards/{card} の {card} 部分)
        $card = $this->route('card');

        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'start_date' => 'sometimes|nullable|date',
            
            'end_date' => [
                'sometimes', 'nullable', 'date',
                // start_date も end_date も入力がある場合のみ比較
                Rule::when($this->filled('start_date') && $this->filled('end_date'), [
                    'after_or_equal:start_date'
                ]),
            ],
            
            'reminder_at' => [
                'sometimes', 'nullable', 'date',
                // end_date も reminder_at も入力がある場合のみ比較
                Rule::when($this->filled('end_date') && $this->filled('reminder_at'), [
                    'before_or_equal:end_date'
                ]),
            ],

            'cover_image_id' => [
                'sometimes',
                'nullable',
                'integer',
                // attachments テーブルに存在し、かつ card_id が一致するか確認
                Rule::exists('attachments', 'id')->where(function ($query) use ($card) {
                    return $query->where('card_id', $card->id);
                }),
            ],

            'is_completed' => 'sometimes|required|boolean',
            'board_list_id' => 'sometimes|required|integer|exists:lists,id',
        ];
    }
}
