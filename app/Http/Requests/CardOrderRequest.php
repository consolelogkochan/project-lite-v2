<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CardOrderRequest extends FormRequest
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
        return [
            'lists' => 'required|array',
            'lists.*.id' => 'required|integer|exists:lists,id',
            // ★ "present" は「データとして送信されていれば、中身が空([])でもOK」という意味です
            'lists.*.cards' => 'present|array',
            'lists.*.cards.*' => 'required|integer|exists:cards,id',
        ];
    }
}
