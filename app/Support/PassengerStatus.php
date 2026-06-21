<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Trạng thái vận hành của hành khách trong một chuyến.
 *
 * Lưu trong cột JSON Trip::passenger_check_ins, key = passengerKey.
 * Mỗi entry: { status, checked_in_at?, dropped_off_at?, updated_at }.
 *
 * "pending" là mặc định và KHÔNG lưu entry (không có key trong map).
 * Tương thích ngược: entry cũ chỉ có { checked_in_at } được hiểu là "onboard".
 */
final class PassengerStatus
{
    public const PENDING = 'pending';

    public const CONFIRMED = 'confirmed';

    public const ONBOARD = 'onboard';

    public const DROPPED_OFF = 'dropped_off';

    public const ABSENT = 'absent';

    public const CANCELLED = 'cancelled';

    /** Mọi trạng thái hợp lệ nhận từ client. */
    public const ALL = [
        self::PENDING,
        self::CONFIRMED,
        self::ONBOARD,
        self::DROPPED_OFF,
        self::ABSENT,
        self::CANCELLED,
    ];

    /**
     * Áp trạng thái mới cho một hành khách vào bản đồ check-in.
     *
     * @param  array<string, mixed>  $map  bản đồ passenger_check_ins hiện tại
     * @param  string  $key  passengerKey
     * @param  string  $status  trạng thái đích (trong self::ALL)
     * @param  string|null  $at  thời điểm ISO áp dụng cho mốc onboard/dropoff (mặc định: now)
     * @return array<string, mixed> bản đồ đã cập nhật
     */
    public static function apply(array $map, string $key, string $status, ?string $at = null): array
    {
        // pending = trở về mặc định → xoá entry.
        if ($status === self::PENDING) {
            unset($map[$key]);

            return $map;
        }

        $now = Carbon::now()->toIso8601String();
        $stamp = $at ? Carbon::parse($at)->toIso8601String() : $now;

        $prev = is_array($map[$key] ?? null) ? $map[$key] : [];

        $entry = [
            'status' => $status,
            'updated_at' => $now,
        ];

        // Giữ lại mốc thời gian đã có để không mất lịch sử lên/xuống xe.
        if (! empty($prev['checked_in_at'])) {
            $entry['checked_in_at'] = $prev['checked_in_at'];
        }
        if (! empty($prev['dropped_off_at'])) {
            $entry['dropped_off_at'] = $prev['dropped_off_at'];
        }

        if ($status === self::ONBOARD) {
            $entry['checked_in_at'] = $stamp;
        } elseif ($status === self::DROPPED_OFF) {
            // Đã xuống xe ngụ ý đã từng lên xe.
            $entry['checked_in_at'] = $entry['checked_in_at'] ?? $stamp;
            $entry['dropped_off_at'] = $stamp;
        }

        $map[$key] = $entry;

        return $map;
    }

    /**
     * Đọc trạng thái chuẩn hoá từ một entry (tương thích entry cũ chỉ có checked_in_at).
     *
     * @param  mixed  $entry
     */
    public static function read($entry): string
    {
        if (! is_array($entry)) {
            return self::PENDING;
        }
        $status = $entry['status'] ?? null;
        if (is_string($status) && in_array($status, self::ALL, true)) {
            return $status;
        }
        // Entry cũ: có checked_in_at nhưng chưa có status.
        if (! empty($entry['checked_in_at'])) {
            return self::ONBOARD;
        }

        return self::PENDING;
    }
}
