<?php

namespace Modules\Loans\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Settings\Services\SettingService;

class FundLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        /** @var SettingService $settings */
        $settings = app(SettingService::class);
        $maxAmount = (int) $settings->get('loans', 'max_amount', 500_000_000);

        return [
                'amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:1',
                "max:{$maxAmount}",
            ],
            'date' => [
                'sometimes',
                'nullable',
                'date',
                'before_or_equal:today', // تاریخ پرداخت نمی‌تواند در آینده باشد
            ],
            'reference' => [
               /* 'required', // برای واریز وجه، داشتن کد مرجع/پیگیری الزامی است*/
                'string',
                'max:255',
            ],
        ];
    }
}
