<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Controller;
use App\Models\TpProgramDay;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SSE realtime cho dashboard điều vận theo dõi 1 ngày vận hành (Phase 8).
 * Stream thay đổi của execution + student logs (board/alight/absent/start/complete).
 */
class TpDayLiveUpdatesController extends Controller
{
    public function __invoke(Request $request, TpProgramDay $tpProgramDay): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_program.view') || $user->can('tp_attendance.manage')), 403);

        $tick = 3;
        $maxSeconds = 60;
        $maxIterations = max(1, (int) ceil($maxSeconds / max(1, $tick)));

        return response()->stream(function () use ($tpProgramDay, $tick, $maxIterations) {
            $this->emit('ready', ['day_id' => $tpProgramDay->id]);
            $lastSeen = Carbon::now()->subDecade();

            for ($i = 0; $i < $maxIterations && ! connection_aborted(); $i++) {
                $execution = $tpProgramDay->execution()->first();

                if ($execution) {
                    $logs = $execution->studentLogs()
                        ->where('updated_at', '>', $lastSeen)
                        ->get();

                    if ($logs->isNotEmpty()) {
                        $this->emit('attendance_changed', [
                            'execution_id' => $execution->id,
                            'status' => $execution->status,
                            'totals' => [
                                'expected' => $execution->total_expected,
                                'boarded' => $execution->total_boarded,
                                'alighted' => $execution->total_alighted,
                                'absent' => $execution->total_absent,
                            ],
                            'changed' => $logs->map(fn ($l) => [
                                'student_id' => $l->student_id,
                                'final_status' => $l->final_status,
                            ])->values()->all(),
                        ]);
                        $lastSeen = Carbon::parse($logs->max('updated_at'));
                    } else {
                        echo ": heartbeat\n\n";
                    }
                } else {
                    echo ": heartbeat\n\n";
                }

                $this->flushBuffers();
                if ($i < $maxIterations - 1) {
                    sleep($tick);
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function emit(string $event, array $payload): void
    {
        echo 'event: '.$event."\n";
        echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE)."\n\n";
        $this->flushBuffers();
    }

    private function flushBuffers(): void
    {
        if (ob_get_level() > 0) {
            @ob_flush();
        }
        @flush();
    }
}
