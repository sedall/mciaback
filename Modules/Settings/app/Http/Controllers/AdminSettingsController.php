<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Settings\Models\Setting;
use Modules\Settings\Services\SettingService;

class AdminSettingsController extends Controller
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * List all settings for admin view.
     */
    public function index(): JsonResponse
    {
        $settings = Setting::all()->map(function ($setting) {
            return [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $setting->cast_value,
                'group' => $setting->group,
                'type' => $setting->type,
                'is_public' => (bool)$setting->is_public
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }

    /**
     * Bulk update settings by Admin.
     */
    public function updateBulk(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        $this->settingService->updateBulk($request->input('settings'));

        return response()->json([
            'status' => 'success',
            'message' => 'Settings updated successfully.'
        ]);
    }
}
