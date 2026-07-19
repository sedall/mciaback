<?php

namespace Modules\CustomerDocuments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectCustomerDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'دلیل رد مدرک الزامی است.',
            'rejection_reason.string' => 'دلیل رد مدرک باید متن باشد.',
            'rejection_reason.max' => 'دلیل رد مدرک نباید بیشتر از 1000 کاراکتر باشد.',
        ];
    }
}
