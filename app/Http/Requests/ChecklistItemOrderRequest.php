<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChecklistItemOrderRequest extends FormRequest
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
            'checklist_id'       => 'required|integer|exists:checklists,id',
            'ordered_item_ids'   => 'required|array',
            'ordered_item_ids.*' => 'required|integer|exists:checklist_items,id',
        ];
    }
}
