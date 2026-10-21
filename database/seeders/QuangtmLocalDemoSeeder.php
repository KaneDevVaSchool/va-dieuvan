<?php

namespace Database\Seeders;

use App\Models\ChecklistSubmission;
use App\Models\ChecklistSubmissionItem;
use App\Models\ChecklistTemplate;
use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Checklist\ChecklistPrefillService;
use App\Support\ChecklistPeriod;
use App\Support\DocumentCodeGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Local only; rerunning preserves existing demo records and edits made while testing. */
class QuangtmLocalDemoSeeder extends Seeder
{
    private const MARKER = '[LOCAL DEMO QUANGTM]';

    public function run(): void
    {
        $connection = config('database.default');
        $host = config('database.connections.'.$connection.'.host');
        if (! app()->environment(['local', 'dev', 'development'])
            || $connection !== 'mysql'
            || ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || config('database.connections.'.$connection.'.url')) {
            throw new RuntimeException('This seeder requires a local development MySQL database.');
        }

        $user = User::where('email', 'quangtm@hcm.vaschools.edu.vn')->firstOrFail();
        if (! $user->hasRole('driver') || ! $user->is_active) {
            throw new RuntimeException('quangtm must be an active driver account.');
        }

        Model::withoutEvents(function () use ($user) {
            DB::transaction(function () use ($user) {
                $this->call(ChecklistTemplateSeeder::class);
                $driver = Driver::firstOrCreate(['user_id' => $user->id], [
                    'full_name' => $user->name,
                    'email' => $user->email,
                    'license_class' => 'D',
                    'license_expires_at' => now()->addYears(2)->toDateString(),
                    'employment_status' => 'active',
                    'availability_status' => 'available',
                ]);
                $vehicle = Vehicle::firstOrCreate(['license_plate' => '51B-999.01'], [
                    'type' => 'minibus', 'seat_count' => 16, 'status' => 'ready',
                    'default_driver_id' => $driver->id,
                    'inspection_expires_at' => now()->addYear()->toDateString(),
                    'insurance_expires_at' => now()->addYear()->toDateString(),
                    'notes' => self::MARKER.' Xe mẫu để test local.',
                ]);
                if (! str_contains((string) $vehicle->notes, self::MARKER)) {
                    throw new RuntimeException('Demo license plate is already used by another vehicle.');
                }

                $dispatcher = User::where('email', 'phongcongnghe@vaschools.edu.vn')->first() ?? $user;
                $local = Carbon::now('Asia/Ho_Chi_Minh');
                $today = $local->toDateString();
                $scenarios = [
                    ['completed', 'business', $local->copy()->subHours(4), 'Cơ sở HCM', 'Văn phòng VAS'],
                    ['in_progress', 'point_to_point', $local->copy()->subMinutes(30), 'Văn phòng VAS', 'Cơ sở Bình Thạnh'],
                    ['assigned', 'door_to_door', $local->copy()->addHour(), 'Cơ sở HCM', 'Điểm đón mẫu Quận 7'],
                    ['driver_confirmed', 'business', $local->copy()->addHours(3), 'Cơ sở HCM', 'Cơ sở Gò Vấp'],
                    ['assigned', 'point_to_point', $local->copy()->addDay()->setTime(8, 0), 'Cơ sở Bình Thạnh', 'Văn phòng VAS'],
                    ['assigned', 'door_to_door', $local->copy()->addDays(3)->setTime(7, 0), 'Điểm đón mẫu Quận 7', 'Cơ sở HCM'],
                    ['pending', 'business', $local->copy()->addDay()->setTime(13, 0), 'Văn phòng VAS', 'Cơ sở Gò Vấp'],
                    ['draft', 'point_to_point', $local->copy()->addDays(2)->setTime(9, 0), 'Cơ sở HCM', 'Cơ sở Bình Thạnh'],
                ];

                $trips = [];
                foreach ($scenarios as $index => [$status, $type, $depart, $origin, $destination]) {
                    $assigned = ! in_array($status, ['draft', 'pending'], true);
                    $request = DispatchRequest::firstOrCreate([
                        'notes' => self::MARKER." {$today} #".($index + 1),
                        'requester_id' => $user->id,
                    ], [
                        'trip_type' => $type, 'origin' => $origin, 'destination' => $destination,
                        'depart_at' => $depart->copy()->utc(),
                        'arrive_by' => $depart->copy()->addHour()->utc(),
                        'status' => $assigned ? 'approved' : $status,
                        'approved_by' => $assigned ? $dispatcher->id : null,
                        'source_channel' => 'portal', 'passenger_count' => 4,
                        'service_price' => $assigned ? 350000 : null,
                    ]);
                    if ($assigned) {
                        $trip = Trip::firstOrCreate(['dispatch_request_id' => $request->id], [
                            'dispatcher_id' => $dispatcher->id, 'driver_id' => $driver->id,
                            'vehicle_id' => $vehicle->id, 'status' => $status,
                            'depart_at' => $depart->copy()->utc(),
                            'arrive_by' => $depart->copy()->addHour()->utc(),
                            'started_at' => in_array($status, ['in_progress', 'completed'], true) ? $depart->copy()->utc() : null,
                            'completed_at' => $status === 'completed' ? $depart->copy()->addHour()->utc() : null,
                            'lock_version' => 0,
                        ]);
                        $trips[] = $trip;
                    }
                    $this->command?->line("Phiếu #{$request->id}: {$status} / {$origin} → {$destination}");
                }

                $daily = ChecklistTemplate::where('code', ChecklistTemplate::CODE_DAILY)->firstOrFail();
                $weekly = ChecklistTemplate::where('code', ChecklistTemplate::CODE_WEEKLY)->firstOrFail();
                foreach ([
                    [$daily, $local, 'draft', $trips[2]],
                    [$weekly, $local, 'draft', null],
                    [$daily, $local->copy()->subDay(), 'submitted', null],
                    [$daily, $local->copy()->subDays(2), 'reviewed', null],
                    [$weekly, $local->copy()->subWeek(), 'reviewed', null],
                ] as [$template, $at, $status, $trip]) {
                    $marker = self::MARKER.' '.$template->frequency.' '.ChecklistPeriod::key($template->frequency, $at);
                    $submission = ChecklistSubmission::where('user_id', $user->id)->where('note', $marker)->first();
                    if ($submission) {
                        continue;
                    }
                    $draft = $status === 'draft';
                    $items = $template->activeItems()->get();
                    $prefill = app(ChecklistPrefillService::class);
                    $hints = $prefill->hints($items, $driver, $vehicle);
                    $tripInfo = $prefill->tripInfo($driver, $vehicle, $trip, null);
                    $tripInfo['date'] = $at->format('d/m/Y');
                    $submission = ChecklistSubmission::create([
                        'code' => DocumentCodeGenerator::next(DocumentCodeGenerator::PREFIX_CHECKLIST, $at),
                        'template_id' => $template->id, 'template_version' => $template->version,
                        'driver_id' => $driver->id, 'user_id' => $user->id, 'vehicle_id' => $vehicle->id,
                        'trip_id' => $trip?->id, 'period_type' => $template->frequency,
                        'period_key' => ChecklistPeriod::key($template->frequency, $at),
                        'started_at' => $at->copy()->subMinutes(20)->utc(),
                        'submitted_at' => $draft ? null : $at->copy()->utc(),
                        'confirmed_statement_at' => $draft ? null : $at->copy()->utc(),
                        'location_text' => 'Bãi xe mẫu HCM', 'trip_info' => $tripInfo,
                        'result' => $draft ? null : 'pass', 'status' => $status, 'note' => $marker,
                        'inspector_name' => $draft ? null : $user->name,
                        'reviewed_by' => $status === 'reviewed' ? $dispatcher->id : null,
                        'reviewed_at' => $status === 'reviewed' ? $at->copy()->addMinutes(30)->utc() : null,
                        'review_note' => $status === 'reviewed' ? 'Dữ liệu mẫu local: đã kiểm tra.' : null,
                        'lock_version' => 0,
                    ]);
                    foreach ($items as $index => $item) {
                        $checked = ! $draft || ($template->frequency === 'daily' && $index < 5);
                        $submission->items()->create([
                            'template_item_id' => $item->id, 'group_name' => $item->group_name,
                            'title' => $item->title, 'requirement' => $item->requirement,
                            'severity' => $item->severity, 'ng_action' => $item->ng_action,
                            'allow_na' => $item->allow_na, 'requires_photo_on_ng' => false,
                            'sort_order' => $item->sort_order, 'prefill_hint' => $hints[$item->id] ?? null,
                            'result' => $checked ? ChecklistSubmissionItem::RESULT_OK : null,
                            'checked_at' => $checked ? $at->copy()->subMinutes(5)->utc() : null,
                        ]);
                    }
                    $this->command?->line("Checklist {$submission->code}: {$template->frequency} / {$status}");
                }
                $this->command?->info("User #{$user->id}, driver #{$driver->id}, xe {$vehicle->license_plate}");
            });
        });
    }
}
