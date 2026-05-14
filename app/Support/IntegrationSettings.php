<?php

namespace App\Support;

use App\Models\IntegrationSetting;

class IntegrationSettings
{
    public static function get(string $key, $default = null)
    {
        return IntegrationSetting::query()
            ->where('key', $key)
            ->value('value') ?? $default;
    }

    public static function set(string $key, $value): void
    {
        IntegrationSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}

