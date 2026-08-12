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
        $min = config('loans.rules.min_amount', 10000000);
        $max = config('loans.rules.max_amount', 500000000);
        $tenures = implode(',', config('loans.rules.allowed_tenures', [3, 6, 12]));

        return [
            'amount' => ['required', 'integer', "min:{$min}", "max:{$max}"],
            'tenure_months' => ['required', 'integer', "in:{$tenures}"],
        ];
    }
}
