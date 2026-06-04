<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\PolicyTripLiveUpdatesRequest;
use App\Models\PolicyTrip;
use App\Services\P2pPolicy\PolicyTripPresenter;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SSE realtime cho dashboard điều vận (§8.1, U5). Đẩy snapshot ban đầu rồi
 * stream các chuyến có `updated_at` thay đổi (boarded/absent/status). Client
 * tự reconnect; fallback polling khi trình duyệt không hỗ trợ EventSource.
 */
class PolicyTripLiveUpdatesController extends Controller
{
    public function __construct(
        private readonly PolicyTripPresenter $presenter,
    ) {}

    public function __invoke(PolicyTripLiveUpdatesRequest $request): StreamedResponse
    {
        $data = $request->validated();
        $date = $data['date'];
        $timeSlot = $data['time_slot'] ?? null;

        $tick = (int) config('p2p.sse.tick_seconds', 3);
        $maxSeconds = (int) config('p2p.sse.max_seconds', 60);
        $maxIterations = max(1, (int) ceil($maxSeconds / max(1, $tick)));

        return response()->stream(function () use ($date, $timeSlot, $tick, $maxIterations) {
            $this->emit('ready', ['date' => $date, 'time_slot' => $timeSlot]);

            $lastSeen = Carbon::now()->subDecade();

            for ($i = 0; $i < $maxIterations && ! connection_aborted(); $i++) {
                $trips = PolicyTrip::query()
                    ->forDate($date)
                    ->when($timeSlot, fn ($q) => $q->where('time_slot', $timeSlot))
                    ->where('updated_at', '>', $lastSeen)
                    ->orderBy('id')
                    ->get();

                foreach ($trips as $trip) {
                    $this->emit('trip', $this->presenter->tripLiveRow($trip));
                }

                if ($trips->isNotEmpty()) {
                    $lastSeen = Carbon::parse($trips->max('updated_at'));
                } else {
                    echo ": heartbeat\n\n"; // comment line giữ kết nối
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
