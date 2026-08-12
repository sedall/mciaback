<?php

namespace Modules\Tickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketMessageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file'],
        ];
    }
}
