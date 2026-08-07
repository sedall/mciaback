<?php

namespace Modules\Loans\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'approved_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:1',
            ],
            'approved_term_months' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],
            'approved_installment_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:1',
            ],
            'admin_note' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
