<?php

namespace Modules\Loans\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RepayInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required','integer','min:1'],
            'reference' => ['nullable','string','max:100'],
        ];
    }
}
