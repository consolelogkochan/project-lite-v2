<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListOrderRequest extends FormRequest
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
            'orderedListIds' => 'required|array',
            // lists テーブルの id であることを確認
            'orderedListIds.*' => 'required|integer|exists:lists,id',
        ];
    }
}
