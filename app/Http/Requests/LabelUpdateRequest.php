<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LabelUpdateRequest extends FormRequest
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
        // ルートパラメータ {label} (Labelモデル) を取得
        $label = $this->route('label');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // 更新時は「自分自身のID」を除外して重複チェック
                Rule::unique('labels')->where(function ($query) use ($label) {
                    return $query->where('board_id', $label->board_id);
                })->ignore($label->id),
            ],
            'color' => 'required|string|max:50',
        ];
    }
}
