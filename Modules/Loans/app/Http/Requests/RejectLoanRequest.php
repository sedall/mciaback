<?php

namespace Modules\Loans\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'admin_note' => ['required_without:reason', 'string', 'max:2000'],
            'reason' => ['required_without:admin_note', 'string', 'max:2000'],

        ];
    }
}
