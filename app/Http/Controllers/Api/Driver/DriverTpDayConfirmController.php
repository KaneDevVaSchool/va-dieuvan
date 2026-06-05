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

        if (! $tpProgramDay->confirmed_at) {
            $tpProgramDay->forceFill([
                'confirmed_at' => now(),
                'confirmed_by_driver_id' => $driver->id,
            ])->save();

            $this->audit->log($request->user()?->id, 'day.confirmed', $tpProgramDay, $tpProgramDay->program);
        }

        return $this->ok($this->presenter->programDay($tpProgramDay->fresh()));
    }

    public function unconfirm(Request $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $this->assertCanStartDay($request->user(), $tpProgramDay);

        abort_if($tpProgramDay->execution()->exists(), 422, 'Chuyến đã được bắt đầu, không thể bỏ xác nhận.');

        if ($tpProgramDay->confirmed_at) {
            $tpProgramDay->forceFill([
                'confirmed_at' => null,
                'confirmed_by_driver_id' => null,
            ])->save();

            $this->audit->log($request->user()?->id, 'day.unconfirmed', $tpProgramDay, $tpProgramDay->program);
        }

        return $this->ok($this->presenter->programDay($tpProgramDay->fresh()));
    }
}
