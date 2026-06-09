<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Tiêu đề hiển thị quyền (tiếng Việt) — đồng bộ với businessCapabilities.js / permission_display_vi.json.
 */
class PermissionDisplayVi
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

        $path = base_path('resources/js/src/data/permission_display_vi.json');
        if (! File::exists($path)) {
            return self::$cache = [];
        }

        $decoded = json_decode(File::get($path), true);

        return self::$cache = is_array($decoded) ? $decoded : [];
    }

    public static function title(?string $permissionName): ?string
    {
        if (! $permissionName) {
            return null;
        }

        return self::map()[$permissionName] ?? null;
    }
}
