<?php

namespace Modules\Loans\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:10000000', 'max:500000000'],
            'tenure_months' => ['required', 'integer', 'in:3,6,12'],
        ];
    }
}
