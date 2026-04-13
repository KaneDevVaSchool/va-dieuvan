<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class PermissionPlainVi
{
    private static ?array $cache = null;

    /**
     * @return array<string, string>
     */
    public static function map(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $path = base_path('resources/js/src/data/permission_plain_vi.json');
        if (! File::exists($path)) {
            return self::$cache = [];
        }

        $decoded = json_decode(File::get($path), true);

        return self::$cache = is_array($decoded) ? $decoded : [];
    }

    public static function text(?string $permissionName): ?string
    {
        if (! $permissionName) {
            return null;
        }

        return self::map()[$permissionName] ?? null;
    }
}
