<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BoardInviteRequest extends FormRequest
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
        // ルートパラメータ {board} を取得
        $board = $this->route('board');

        return [
            'user_id' => [
                'required', 
                'integer', 
                'exists:users,id',
                // board_user テーブルで、このボードIDに対してuser_idが重複していないか
                Rule::unique('board_user')->where(function ($query) use ($board) {
                    return $query->where('board_id', $board->id);
                }),
            ],
            'role' => ['required', 'string', Rule::in(['member', 'admin', 'guest'])],
        ];
    }
}
