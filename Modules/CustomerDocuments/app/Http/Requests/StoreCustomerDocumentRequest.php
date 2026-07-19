<?php

namespace Modules\CustomerDocuments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreCustomerDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                'in:national_card_front,national_card_back,birth_certificate,selfie,residence_proof',
                Rule::unique('customer_documents', 'type')
                    ->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ];
    }
}
