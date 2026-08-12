<?php

namespace Modules\Tickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:10000'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file'],
        ];
    }
}
