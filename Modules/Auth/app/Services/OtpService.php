<?php

namespace Modules\Auth\Services;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\OtpCode;

class OtpService
{
    public function request(string $mobile, string $purpose = 'login'): array
    {
        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(2);

        OtpCode::query()
            ->where('mobile', $mobile)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->delete();

        $otp = OtpCode::query()->create([
            'mobile' => $mobile,
            'purpose' => $purpose,
            'code' => $code,
            'expires_at' => $expiresAt,
            'attempts' => 0,
        ]);

        return [
            'otp_id' => $otp->id,
            'code' => $code,
            'expires_at' => $expiresAt,
        ];
    }

    public function verify(string $mobile, string $code, string $purpose = 'login'): OtpCode
    {
        $otp = OtpCode::query()
            ->where('mobile', $mobile)
            ->where('code', $code)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'code' => 'کد تایید نامعتبر است.',
            ]);
        }

        if ($otp->verified_at) {
            throw ValidationException::withMessages([
                'code' => 'این کد قبلاً تایید شده است.',
            ]);
        }

        if (Carbon::parse($otp->expires_at)->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'کد تایید منقضی شده است.',
            ]);
        }

        $otp->increment('attempts');

        $otp->forceFill([
            'verified_at' => now(),
            'used_at' => now(),
        ])->save();

        return $otp->fresh();
    }
}
