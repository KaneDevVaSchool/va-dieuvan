<?php

namespace App\Console\Commands;

use App\Services\P2pPolicy\PolicyNotificationService;
use App\Services\P2pPolicy\PolicyTripGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Sinh chuyến policy cho một ngày (§4.2).
 *  - PRIMARY 22:00 (T-1):   p2p:generate-trips --for=tomorrow
 *  - FALLBACK 05:00 (T):     p2p:generate-trips --for=today --alert-on-fail
 * Idempotent qua generator. `--alert-on-fail` (chỉ đặt ở lần fallback) gửi email
 * admin khi job vẫn fail — tránh spam khi lần primary đã fail (alert sau lần 2).
 */
class GeneratePolicyTripsCommand extends Command
{
    protected $signature = 'p2p:generate-trips
        {--date= : YYYY-MM-DD (ưu tiên hơn --for)}
        {--for=tomorrow : tomorrow|today khi không truyền --date}
        {--alert-on-fail : Gửi email admin nếu job fail (đặt ở lần chạy fallback)}';

    protected $description = 'Tự động sinh chuyến đưa đón học sinh chính sách cho một ngày (idempotent).';

    public function handle(
        PolicyTripGeneratorService $generator,
        PolicyNotificationService $notifier,
    ): int {
        $date = $this->resolveDate();
        $alertOnFail = (bool) $this->option('alert-on-fail');

        try {
            $result = $generator->generateForDate($date);
        } catch (Throwable $e) {
            $this->error("p2p:generate-trips date={$date} FAILED: {$e->getMessage()}");
            report($e);
            if ($alertOnFail) {
                $notifier->generationFailed($date, $e->getMessage());
            }

            return self::FAILURE;
        }

        switch ($result['result']) {
            case PolicyTripGeneratorService::RESULT_NOT_SERVICE_DAY:
                $this->info("p2p:generate-trips date={$date} skipped: không phải ngày học/bù.");

                return self::SUCCESS;

            case PolicyTripGeneratorService::RESULT_MISSING_SEMESTER:
                // [L1] Ngày học nhưng thiếu semester → cảnh báo admin để bổ sung.
                $this->warn("p2p:generate-trips date={$date} thiếu semester — alert admin.");
                $notifier->calendarMissingSemester($date);

                return self::SUCCESS;

            default:
                $this->info(sprintf(
                    'p2p:generate-trips date=%s created=%d skipped=%d',
                    $date,
                    $result['created'],
                    $result['skipped'],
                ));

                return self::SUCCESS;
        }
    }

    private function resolveDate(): string
    {
        $explicit = $this->option('date');
        if ($explicit) {
            return Carbon::parse((string) $explicit)->toDateString();
        }

        return $this->option('for') === 'today'
            ? Carbon::today()->toDateString()
            : Carbon::tomorrow()->toDateString();
    }
}
