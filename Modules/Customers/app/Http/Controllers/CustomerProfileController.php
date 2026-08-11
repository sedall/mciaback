<?php

namespace Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Customers\Http\Requests\UpdateCustomerProfileRequest;
use Modules\Customers\Http\Resources\CustomerProfileResource;
use Modules\Customers\Models\CustomerProfile;

class CustomerProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $user = auth()->user();

        $profile = CustomerProfile::firstOrCreate([
            'user_id' => $user->id,
        ]);

        return response()->json([
            'data' => new CustomerProfileResource($profile),
        ]);
    }

    public function upsert(UpdateCustomerProfileRequest $request): JsonResponse
    {
        $user = auth()->user();

        $profile = CustomerProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );

        return response()->json([
            'data' => new CustomerProfileResource($profile),
            'message' => 'پروفایل با موفقیت به‌روزرسانی شد.',
        ]);
    }
}
