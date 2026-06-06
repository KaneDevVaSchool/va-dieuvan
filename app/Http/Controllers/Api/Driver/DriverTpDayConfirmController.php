<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Driver\Concerns\ActsOnTpExecutions;
use App\Http\Controllers\Controller;
use App\Models\TpProgramDay;
use App\Services\TransportProgram\TpAuditLogger;
use App\Services\TransportProgram\TpProgramPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tài xế xác nhận sẽ chạy chuyến định kì (trước bước "Bắt đầu chuyến").
 *
 * Khi chương trình có cả buổi sáng lẫn buổi chiều (multi_slot), mỗi ca xác nhận
 * độc lập qua tham số `shift` (morning | afternoon).
 * Khi chỉ có 1 ca (single-slot), dùng confirmed_at chung như cũ.
 */
class DriverTpDayConfirmController extends Controller
{
    use ActsOnTpExecutions;
    use ApiResponses;

    public function __construct(
        private readonly TpProgramPresenter $presenter,
        private readonly TpAuditLogger $audit,
    ) {}

    public function confirm(Request $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $driver = $this->assertCanStartDay($request->user(), $tpProgramDay);

        abort_if($tpProgramDay->day_type === TpProgramDay::DAY_CANCELLED, 422, 'Ngày này đã bị hủy.');
        abort_if($tpProgramDay->execution()->exists(), 422, 'Chuyến đã được bắt đầu.');

        $shift = $this->resolveShift($request);

        if ($shift === 'morning') {
            if (! $tpProgramDay->morning_confirmed_at) {
                $tpProgramDay->forceFill([
                    'morning_confirmed_at' => now(),
                    'morning_confirmed_by_driver_id' => $driver->id,
                ])->save();
                $this->audit->log($request->user()?->id, 'day.confirmed', $tpProgramDay, $tpProgramDay->program);
            }
        } elseif ($shift === 'afternoon') {
            if (! $tpProgramDay->afternoon_confirmed_at) {
                $tpProgramDay->forceFill([
                    'afternoon_confirmed_at' => now(),
                    'afternoon_confirmed_by_driver_id' => $driver->id,
                ])->save();
                $this->audit->log($request->user()?->id, 'day.confirmed', $tpProgramDay, $tpProgramDay->program);
            }
        } else {
            // Single-slot backward-compat
            if (! $tpProgramDay->confirmed_at) {
                $tpProgramDay->forceFill([
                    'confirmed_at' => now(),
                    'confirmed_by_driver_id' => $driver->id,
                ])->save();
                $this->audit->log($request->user()?->id, 'day.confirmed', $tpProgramDay, $tpProgramDay->program);
            }
        }

        return $this->ok($this->buildPayload($tpProgramDay->fresh(), $shift));
    }

    public function unconfirm(Request $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $this->assertCanStartDay($request->user(), $tpProgramDay);

        abort_if($tpProgramDay->execution()->exists(), 422, 'Chuyến đã được bắt đầu, không thể bỏ xác nhận.');

        $shift = $this->resolveShift($request);

        if ($shift === 'morning') {
            if ($tpProgramDay->morning_confirmed_at) {
                $tpProgramDay->forceFill([
                    'morning_confirmed_at' => null,
                    'morning_confirmed_by_driver_id' => null,
                ])->save();
                $this->audit->log($request->user()?->id, 'day.unconfirmed', $tpProgramDay, $tpProgramDay->program);
            }
        } elseif ($shift === 'afternoon') {
            if ($tpProgramDay->afternoon_confirmed_at) {
                $tpProgramDay->forceFill([
                    'afternoon_confirmed_at' => null,
                    'afternoon_confirmed_by_driver_id' => null,
                ])->save();
                $this->audit->log($request->user()?->id, 'day.unconfirmed', $tpProgramDay, $tpProgramDay->program);
            }
        } else {
            if ($tpProgramDay->confirmed_at) {
                $tpProgramDay->forceFill([
                    'confirmed_at' => null,
                    'confirmed_by_driver_id' => null,
                ])->save();
                $this->audit->log($request->user()?->id, 'day.unconfirmed', $tpProgramDay, $tpProgramDay->program);
            }
        }

        return $this->ok($this->buildPayload($tpProgramDay->fresh(), $shift));
    }

    private function resolveShift(Request $request): ?string
    {
        $shift = $request->input('shift');
        if (in_array($shift, ['morning', 'afternoon'], true)) {
            return $shift;
        }

        return null;
    }

    private function buildPayload(TpProgramDay $day, ?string $shift): array
    {
        $payload = $this->presenter->programDay($day);
        // Thêm confirmed_at theo ca để frontend cập nhật đúng trạng thái card.
        $payload['slot_confirmed_at'] = $day->slotConfirmedAt($shift)?->toIso8601String();
        $payload['shift'] = $shift;

        return $payload;
    }
}
