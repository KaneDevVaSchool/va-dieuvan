<?php

namespace App\Support;

/**
 * Đọc số tiền VND thành chữ, ví dụ 160000 → "Một trăm sáu mươi nghìn đồng."
 * (thay cho công thức Google Sheets trong mẫu Bảng kê / Đề nghị thanh toán).
 */
final class VietnameseMoneyWords
{
    private const DIGITS = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];

    public static function format(float|int|string|null $amount): string
    {
        $n = (int) round((float) ($amount ?? 0));

        if ($n === 0) {
            return 'Không đồng.';
        }

        $words = self::readNumber(abs($n));
        if ($n < 0) {
            $words = 'âm '.$words;
        }

        return self::ucfirst($words).' đồng.';
    }

    private static function readNumber(int $n): string
    {
        $parts = [];
        $billions = intdiv($n, 1_000_000_000);
        $rest = $n % 1_000_000_000;

        $hasHigher = false;
        if ($billions > 0) {
            $parts[] = self::readNumber($billions).' tỷ';
            $hasHigher = true;
        }

        $groups = [
            [intdiv($rest, 1_000_000), 'triệu'],
            [intdiv($rest % 1_000_000, 1000), 'nghìn'],
            [$rest % 1000, ''],
        ];

        foreach ($groups as [$value, $label]) {
            if ($value === 0) {
                continue;
            }
            $parts[] = trim(self::readTriple($value, $hasHigher).' '.$label);
            $hasHigher = true;
        }

        return implode(' ', $parts);
    }

    /**
     * @param  bool  $full  true khi đã có nhóm lớn hơn phía trước → đọc đủ "không trăm", "lẻ".
     */
    private static function readTriple(int $g, bool $full): string
    {
        $h = intdiv($g, 100);
        $t = intdiv($g % 100, 10);
        $u = $g % 10;

        $words = [];
        if ($h > 0 || $full) {
            $words[] = self::DIGITS[$h].' trăm';
        }

        if ($t === 0) {
            if ($u > 0 && ($h > 0 || $full)) {
                $words[] = 'lẻ';
            }
        } elseif ($t === 1) {
            $words[] = 'mười';
        } else {
            $words[] = self::DIGITS[$t].' mươi';
        }

        if ($u > 0) {
            $words[] = match (true) {
                $u === 1 && $t >= 2 => 'mốt',
                $u === 5 && $t >= 1 => 'lăm',
                default => self::DIGITS[$u],
            };
        }

        return implode(' ', $words);
    }

    private static function ucfirst(string $s): string
    {
        return mb_strtoupper(mb_substr($s, 0, 1)).mb_substr($s, 1);
    }
}
