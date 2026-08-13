<?php

namespace Modules\Loans\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Settings\Services\SettingService;

class ApproveLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // دسترسی فقط برای ادمین یا کاربرانی که اجازه مدیریت وام دارند
        return auth()->check();
    }

    public function rules(): array
    {
        /** @var SettingService $settings */
        $settings = app(SettingService::class);

        $maxAmount = (int) $settings->get('loans', 'max_amount', 500_000_000);
        $allowedTenures = $settings->get('loans', 'allowed_tenures', [3, 6, 12]);
        $tenuresCsv = is_array($allowedTenures) ? implode(',', $allowedTenures) : '3,6,12';

        return [
            'approved_amount' => [
                'required', // در زمان تایید، مبلغ تایید شده الزامی است
                'numeric',
                'min:1',
                "max:{$maxAmount}",
            ],
            'approved_term_months' => [
                'required',
                'integer',
                "in:{$tenuresCsv}",
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
            'reference' => [
                /* 'required', // برای واریز وجه، داشتن کد مرجع/پیگیری الزامی است*/
                'string',
                'max:255',
            ],
        ];
    }
}
