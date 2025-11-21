<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LabelStoreRequest extends FormRequest
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
        $board = $this->route('board');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // そのボード内で名前が重複しないように
                Rule::unique('labels')->where(function ($query) use ($board) {
                    return $query->where('board_id', $board->id);
                }),
            ],
            'color' => 'required|string|max:50',
        ];
    }
}
