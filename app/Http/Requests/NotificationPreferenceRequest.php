<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationPreferenceRequest extends FormRequest
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
            'notify_on_comment'      => 'required|boolean',
            'notify_on_attachment'   => 'required|boolean',
            'notify_on_due_date'     => 'required|boolean',
            'notify_on_card_move'    => 'required|boolean',
            'notify_on_card_created' => 'required|boolean',
            'notify_on_card_deleted' => 'required|boolean',
        ];
    }
}
