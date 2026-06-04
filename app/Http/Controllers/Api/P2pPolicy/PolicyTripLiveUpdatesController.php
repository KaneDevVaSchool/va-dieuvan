<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\PolicyTripLiveUpdatesRequest;
use App\Models\PolicyTrip;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PolicyTripLiveUpdatesController extends Controller
{
    public function __invoke(PolicyTripLiveUpdatesRequest $request): StreamedResponse
    {
        $data = $request->validated();
        $date = $data['date'];
        $timeSlot = $data['time_slot'] ?? null;

        return response()->stream(function () use ($date, $timeSlot) {
            $lastSeen = Carbon::now()->subSecond();
            $iterations = 0;
            $maxIterations = 36;

            while ($iterations < $maxIterations && ! connection_aborted()) {
                $q = PolicyTrip::query()
                    ->whereDate('trip_date', $date)
                    ->where('updated_at', '>', $lastSeen);

                if ($timeSlot) {
                    $q->where('time_slot', $timeSlot);
                }

                $trips = $q->orderBy('id')->get();

                foreach ($trips as $trip) {
                    $payload = json_encode([
                        'id' => $trip->id,
                        'boarded_count' => $trip->boarded_count,
                        'absent_count' => $trip->absent_count,
                        'status' => $trip->status,
                    ], JSON_THROW_ON_ERROR);

                    echo "data: {$payload}\n\n";
                }

                if ($trips->isNotEmpty()) {
                    $maxUpdated = $trips->max('updated_at');
                    $lastSeen = $maxUpdated ? Carbon::parse($maxUpdated) : Carbon::now();
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                $iterations++;
                sleep(5);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
