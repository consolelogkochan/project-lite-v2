<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

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
     * バリデーション前にデータを準備・整形する
     */
    protected function prepareForValidation()
    {
        // フロントエンドから 'null' 文字列が送られてくる場合の対策
        // (必要に応じてキャスト)
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

        // ★ 重要: 比較用の日付データを準備
        // リクエストにあればそれを使い、なければ現在のDBの値を使う
        $startDate = $this->has('start_date') ? $this->input('start_date') : $card->start_date;
        $endDate   = $this->has('end_date')   ? $this->input('end_date')   : $card->end_date;

        // 日付比較のために一時的にCarbonインスタンス化 (nullならnull)
        // (文字列比較でもISO形式ならある程度動くが、Carbonが確実)
        // ただしバリデーションルールには「値」を渡す必要があるため、ここでは変数準備のみ

        return [
            'title' => 'sometimes|required|string|max:255',
            // max:5000 を追加 (長文対策)
            'description' => 'sometimes|nullable|string|max:5000',

            'start_date' => [
                'sometimes', 'nullable', 'date',
            ],
            
            'end_date' => [
                'sometimes', 'nullable', 'date',
                // カスタムバリデーション: 終了日は開始日より後
                function ($attribute, $value, $fail) use ($startDate) {
                    if ($value && $startDate) {
                        // Carbonでパースして比較
                        $start = Carbon::parse($startDate);
                        $end = Carbon::parse($value);
                        if ($end->lt($start)) {
                            $fail('The due date must be after or equal to the start date.');
                        }
                    }
                },
            ],
            
            'reminder_at' => [
                'sometimes', 'nullable', 'date',
                // ルールC: リマインダーがあるなら、期限(end_date)も必須
                function ($attribute, $value, $fail) use ($endDate) {
                    if ($value && !$endDate) {
                         $fail('A due date is required to set a reminder.');
                    }
                },
                // ルールB: リマインダーは期限より前
                function ($attribute, $value, $fail) use ($endDate) {
                    if ($value && $endDate) {
                        $remind = Carbon::parse($value);
                        $end = Carbon::parse($endDate);
                        if ($remind->gt($end)) {
                            $fail('The reminder cannot be set after the due date.');
                        }
                    }
                },
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
