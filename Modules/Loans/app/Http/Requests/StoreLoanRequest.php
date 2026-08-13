<?php

namespace Modules\Loans\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Settings\Services\SettingService;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $settings = app(SettingService::class);


        $min = (int)$settings->get('loans', 'min_amount', 10_000_000);
        $max = (int)$settings->get('loans', 'max_amount', 500000000);
        $tenures = $settings->get('loans', 'allowed_tenures', [3, 6, 12]);

        if (!is_array($tenures) || $tenures === []) {
            $tenures = [3, 6, 12];
        }

        $tenures = array_map('intval', $tenures);
        $tenuresCsv = implode(',', $tenures);

        $min = (int) config('loans.rules.min_amount', 10_000_000);
        $max = (int) config('loans.rules.max_amount', 500_000_000);
        $tenures = config('loans.rules.allowed_tenures', [3, 6, 12]);
        $tenuresCsv = $tenures;
        return [
            'amount' => ['required', 'integer', "min:{$min}","max:{$max}",],
            'tenure_months' => ['required', 'integer', "in:{$tenuresCsv}"],
        ];
    }


}
