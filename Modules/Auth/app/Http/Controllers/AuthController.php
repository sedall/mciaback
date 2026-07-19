<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Access\Services\RoleAssignmentService;
use Modules\Auth\Http\Requests\RequestOtpRequest;
use Modules\Auth\Http\Requests\VerifyOtpRequest;
use Modules\Auth\Http\Resources\PanelUserResource;
use Modules\Auth\Services\OtpService;

class AuthController extends Controller
{
    public function __construct(
        protected OtpService $otpService,
        protected RoleAssignmentService $roleAssignmentService,
    )
    {}

    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->otpService->request(
            mobile: $validated['mobile'],
            purpose: 'login',
        );

        $response = [
            'message' => 'OTP sent successfully.',
            'data' => [
                'mobile' => $validated['mobile'],
                'expires_at' => $result['expires_at'] ?? null,
            ],
        ];

        if (app()->environment(['local', 'testing'])) {
            $response['data']['code'] = $result['code'] ?? null;
        }

        return response()->json($response);
    }


    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $otpCode = $this->otpService->verify(
            mobile: $validated['mobile'],
            code: $validated['code'],
            purpose: 'login',
        );

        $entryPoint = $this->resolveEntryPointFromRequest($request);

        $user = $this->resolveUserByEntryPoint(
            mobile: $otpCode->mobile,
            entryPoint: $entryPoint,
        );

        if (! $user->mobile_verified_at) {
            $user->forceFill([
                'mobile_verified_at' => now(),
            ])->save();
        }

        $token = $user->createToken($entryPoint . '-auth-token')->plainTextToken;

        return response()->json([
            'message' => 'OTP verified successfully.',
            'token' => $token,
            'user' => new PanelUserResource($user->loadMissing('roles')),
        ]);
    }

    protected function resolveEntryPointFromRequest(Request $request): string
    {
        $routeName = (string) $request->route()?->getName();

        return match (true) {
            str_contains($routeName, 'customer') => 'customer',
            str_contains($routeName, 'admin') => 'admin',
            str_contains($routeName, 'clinic') => 'clinic',
            default => 'customer',
        };
    }

    protected function resolveUserByEntryPoint(string $mobile, string $entryPoint): User
    {
        if ($entryPoint === 'customer') {
            $user = User::query()->firstOrCreate(
                ['mobile' => $mobile],
                [
                    'password' => bcrypt(Str::random(16)),
                    'mobile_verified_at' => now(),
                ]
            );

            $this->roleAssignmentService->assignDefaultCustomerRole($user);

            return $user->fresh();
        }

        $user = User::query()
            ->where('mobile', $mobile)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'mobile' => 'User not found for this mobile number.',
            ]);
        }

        return $user;
    }
}
