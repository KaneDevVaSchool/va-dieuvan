<?php

namespace App\Console\Commands;

use App\Models\Trip;
use App\Notifications\TripDepartureReminderNotification;
use App\Services\Trips\TripDriverNotifyService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RemindUpcomingTripDriversCommand extends Command
{
    protected $signature = 'trips:remind-drivers-upcoming
        {--window-min=15 : Nhắc trước tối thiểu N phút}
        {--window-max=35 : Nhắc trước tối đa N phút}';

    protected $description = 'Nhắc tài xế về chuyến sắp khởi hành trong vòng 15–35 phút tới';

    public function handle(TripDriverNotifyService $notifyService): int
    {
        $windowMin = (int) $this->option('window-min');
        $windowMax = (int) $this->option('window-max');

        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $from = $now->copy()->addMinutes($windowMin);
        $to = $now->copy()->addMinutes($windowMax);

        $trips = Trip::query()
            ->whereIn('status', ['assigned', 'driver_confirmed'])
            ->whereBetween('depart_at', [$from, $to])
            ->with(['dispatchRequest'])
            ->get();

        $sent = 0;

        foreach ($trips as $trip) {
            $driverUsers = $notifyService->resolveDriverUsers($trip);
            if ($driverUsers === []) {
                continue;
            }

            $dr = $trip->dispatchRequest;
            $tripType = is_string($dr?->trip_type) && $dr->trip_type !== '' ? $dr->trip_type : 'unspecified';
            $origin = (string) ($dr?->origin ?? '');
            $destination = (string) ($dr?->destination ?? '');
            $departAt = $trip->depart_at instanceof Carbon
                ? $trip->depart_at->toIso8601String()
                : (string) ($trip->depart_at ?? '');

            foreach ($driverUsers as $user) {
                if ($this->alreadyReminded((int) $user->getKey(), (int) $trip->id)) {
                    continue;
                }

                $user->notify(new TripDepartureReminderNotification(
                    tripId: (int) $trip->id,
                    tripType: $tripType,
                    origin: $origin,
                    destination: $destination,
                    departAt: $departAt,
                ));
                $sent++;
            }
        }

        $this->info("Đã xếp {$sent} nhắc nhở khởi hành (cửa sổ +{$windowMin}–{$windowMax} phút).");

        return self::SUCCESS;
    }

    private function alreadyReminded(int $userId, int $tripId): bool
    {
        return DB::table('notifications')
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $userId)
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.event')) = 'trip.departure_reminder'")
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.trip_id')) = ?", [$tripId])
            ->exists();
    }
}
