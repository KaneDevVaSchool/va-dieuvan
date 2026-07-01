<?php

namespace App\Services\LegacyImport;

final class LegacyStatusResolver
{
    /**
     * @param  array<string, array{request:string,trip:?string}>  $exactMap
     * @return array{request:string,trip:?string,warning:?string}|null
     */
    public static function resolve(?string $raw, array $exactMap): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $normalized = self::normalizeToken($raw);
        if ($normalized === '') {
            return null;
        }

        if (isset($exactMap[$normalized])) {
            return array_merge($exactMap[$normalized], ['warning' => null]);
        }

        $fuzzy = self::fuzzyMatch($normalized);
        if ($fuzzy !== null && isset($exactMap[$fuzzy])) {
            return array_merge($exactMap[$fuzzy], [
                'warning' => 'Trạng thái «'.$raw.'» được hiểu là «'.$fuzzy.'».',
            ]);
        }

        return [
            'request' => 'pending',
            'trip' => null,
            'warning' => 'Trạng thái «'.$raw.'» không khớp danh sách chuẩn — phiếu ở trạng thái chờ xử lý, chưa tạo chuyến.',
        ];
    }

    public static function normalizeToken(string $raw): string
    {
        $s = mb_strtolower(trim($raw));
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        return trim($s);
    }

    private static function fuzzyMatch(string $normalized): ?string
    {
        $rules = [
            'done' => ['done', 'xong', 'đã xong', 'ok', 'hoàn thành', 'complete', 'completed'],
            'hủy' => ['hủy', 'huỷ', 'huy', 'cancel', 'cancelled', 'canceled', 'đã hủy'],
            'báo xe ok' => ['báo xe ok', 'bao xe ok', 'báo xe', 'xe ok'],
            'in process' => ['in process', 'inprocess', 'đang chạy', 'đang đi', 'running'],
            'chờ thêm thông tin' => ['chờ thêm thông tin', 'cho them thong tin', 'chờ tt', 'pending info'],
            'taxi' => ['taxi'],
            'pending' => ['pending', 'chờ', 'cho'],
        ];

        foreach ($rules as $canonical => $needles) {
            foreach ($needles as $needle) {
                if ($normalized === $needle || str_contains($normalized, $needle)) {
                    return $canonical;
                }
            }
        }

        if (str_contains($normalized, 'done') || str_contains($normalized, 'xong')) {
            return 'done';
        }
        if (str_contains($normalized, 'hủy') || str_contains($normalized, 'huỷ') || str_contains($normalized, 'cancel')) {
            return 'hủy';
        }

        return null;
    }
}
