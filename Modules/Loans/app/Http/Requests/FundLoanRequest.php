<?php

namespace Modules\Loans\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FundLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:1',
            ],
            'date' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'reference' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
