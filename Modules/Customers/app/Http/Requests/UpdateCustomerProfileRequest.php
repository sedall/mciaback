<?php

namespace Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $profileId = optional($this->user()?->customerProfile)->id;

        return [
            'first_name'    => ['nullable', 'string', 'max:100'],
            'last_name'     => ['nullable', 'string', 'max:100'],
            'father_name'   => ['nullable', 'string', 'max:100'],
            'national_code' => [
                'nullable',
                'digits:10',
                Rule::unique('customer_profiles', 'national_code')->ignore($profileId),
            ],
            'postal_code' => ['nullable', 'digits:10'],
            'birth_date'    => ['nullable', 'date'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female'])],
            'province'      => ['nullable', 'string', 'max:100'],
            'city'          => ['nullable', 'string', 'max:100'],
            'address'       => ['nullable', 'string', 'max:1000'],
            'landline_phone' => ['nullable', 'string', 'max:12', 'min:11'],
        ];
    }
}
