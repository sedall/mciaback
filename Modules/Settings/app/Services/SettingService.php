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
                return [$item->key => $this->castFromStorage($item->value, $item->type)];
            })->toArray();
        });
    }

    /**
     * Get a specific setting value.
     */
    public function get(string $key, $default = null)
    {
        return Cache::rememberForever($this->cacheKey($key), function () use ($key, $default) {
            $setting = Setting::query()->where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return $this->castFromStorage($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting with cache invalidation (including public group cache).
     */
    public function set(
        string $key,
        mixed $value,
        string $group = 'general',
        ?string $type = null,
        bool $isPublic = false
    ): void {
        $resolvedType = $type ?? $this->detectType($value);

        $existing = Setting::query()->where('key', $key)->first();
        $oldGroup = $existing?->group;

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $this->castToStorage($value, $resolvedType),
                'group' => $group,
                'type' => $resolvedType,
                'is_public' => $isPublic,
            ]
        );

        Cache::forget($this->cacheKey($key));
        Cache::forget(self::CACHE_KEY);

        // Invalidate public group cache (both old and new group to prevent stale cache)
        Cache::forget($this->publicGroupCacheKey($group));
        if ($oldGroup && $oldGroup !== $group) {
            Cache::forget($this->publicGroupCacheKey($oldGroup));
        }
    }

    /**
     * Get public settings by group.
     */
    public function getPublicByGroup(string $group): array
    {
        return Cache::rememberForever($this->publicGroupCacheKey($group), function () use ($group) {
            return Setting::query()
                ->where('group', $group)
                ->where('is_public', true)
                ->get()
                ->mapWithKeys(function (Setting $item) {
                    return [$item->key => $this->castFromStorage($item->value, $item->type)];
                })
                ->toArray();
        });
    }

    /**
     * Bulk update settings from key-value pairs.
     */
    public function updateBulk(array $settingsData): void
    {
        $affectedGroups = [];

        foreach ($settingsData as $key => $value) {
            $setting = Setting::where('key', $key)->first();

            if ($setting) {
                $type = $setting->type ?: $this->detectType($value);
                $setting->update(['value' => $this->castToStorage($value, $type)]);

                Cache::forget($this->cacheKey($key));
                if (!empty($setting->group)) {
                    $affectedGroups[] = $setting->group;
                }
            }
        }

        $this->clearCache();

        foreach (array_unique($affectedGroups) as $group) {
            Cache::forget($this->publicGroupCacheKey($group));
        }
    }

    /**
     * Clear the global settings cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function cacheKey(string $key): string
    {
        return "setting.{$key}";
    }

    private function publicGroupCacheKey(string $group): string
    {
        return "settings.public.group.{$group}";
    }

    private function castFromStorage(?string $value, ?string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    private function castToStorage(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            'json' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            default => (string) $value,
        };
    }

    private function detectType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_array($value), is_object($value) => 'json',
            default => 'string',
        };
    }
}
