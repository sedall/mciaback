<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'code' => ['required', 'string', 'size:6'],
            'purpose' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();

        $data['mobile'] = trim($data['mobile']);
        $data['code'] = trim($data['code']);
        $data['purpose'] = $data['purpose'] ?? 'login';

        return $data;
    }
}
