<?php

namespace Modules\Tickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateTicketStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:open,answered,closed'],
        ];
    }
}
