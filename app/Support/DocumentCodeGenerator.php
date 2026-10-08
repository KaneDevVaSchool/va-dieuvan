<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sinh mã chứng từ theo ngày: {PREFIX}-{YYMMDD}-{n}, ví dụ DX-260930-1.
 * n bắt đầu lại từ 1 mỗi ngày (giờ Việt Nam) và đếm riêng theo tiền tố.
 */
final class DocumentCodeGenerator
{
    public const PREFIX_CHECKLIST = 'CL';

    public const PREFIX_PROPOSAL = 'DX';

    public const PREFIX_PAYMENT = 'TT';

    public const PREFIX_ADVANCE = 'TU';

    public const PREFIX_REIMBURSEMENT = 'HU';

    public const PREFIX_NO_INVOICE = 'BK';

    public static function next(string $prefix, ?CarbonInterface $at = null): string
    {
        $prefix = strtoupper(trim($prefix));
        $local = ($at ? Carbon::instance($at) : Carbon::now())->copy()->setTimezone(TripScheduleInstant::DISPLAY_TZ);
        $date = $local->toDateString();

        $number = DB::transaction(function () use ($prefix, $date): int {
            DB::table('document_sequences')->insertOrIgnore([
                'prefix' => $prefix,
                'seq_date' => $date,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('document_sequences')
                ->where('prefix', $prefix)
                ->where('seq_date', $date)
                ->lockForUpdate()
                ->first(['id', 'last_number']);

            $next = (int) $row->last_number + 1;

            DB::table('document_sequences')
                ->where('id', $row->id)
                ->update(['last_number' => $next, 'updated_at' => now()]);

            return $next;
        });

        return sprintf('%s-%s-%d', $prefix, $local->format('ymd'), $number);
    }
}
