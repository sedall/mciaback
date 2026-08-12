<?php

namespace Modules\Settings\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Settings\Models\Setting;

class SettingService
{
    const CACHE_KEY = 'system_settings';

    /**
     * Get all cached settings.
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::all()->mapWithKeys(function ($item) {
                return [$item->key => $item->cast_value];
            })->toArray();
        });
    }

    /**
     * Get a specific setting value.
     */
    public function get(string $key, $default = null)
    {
        $settings = $this->all();
        return $settings[$key] ?? $default;
    }

    /**
     * Bulk update settings from key-value pairs.
     */
    public function updateBulk(array $settingsData): void
    {
        foreach ($settingsData as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if ($setting) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $setting->update(['value' => $value]);
            }
        }
        $this->clearCache();
    }

    /**
     * Clear cached settings.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
