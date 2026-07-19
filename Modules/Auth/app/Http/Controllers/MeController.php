<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customers\Services\KycStatusService;

class MeController extends Controller
{
    public function __construct(
        private KycStatusService $kycStatusService
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return $this->show($request);
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'roles',
            'customerProfile',
            'customerDocuments',
        ]);

        return response()->json([
            'data' => $this->kycStatusService->getAccountSnapshot($user),
        ]);
    }
}
