<?php

namespace App\Http\Controllers\Api\D2D;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\D2D\CreateRouteRequest;
use App\Http\Requests\Api\D2D\CreateRouteVersionRequest;
use App\Http\Requests\Api\D2D\EnrollRouteStudentsRequest;
use App\Http\Requests\Api\D2D\GenerateRouteTripRequest;
use App\Http\Requests\Api\D2D\ListRoutesRequest;
use App\Http\Requests\Api\D2D\RouteVersionDecisionRequest;
use App\Http\Requests\Api\D2D\ShowRouteRequest;
use App\Models\Route;
use App\Models\RouteRun;
use App\Models\RouteSchedule;
use App\Models\RouteStop;
use App\Models\RouteVersion;
use App\Models\Student;
use App\Models\Trip;
use App\Services\Auditing\AuditLogger;
use App\Support\Messages;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    use ApiResponses;

    public function index(ListRoutesRequest $request)
    {
        $data = $request->validated();

        $q = Route::query()
            ->withCount('versions')
            ->orderBy('name');

        $perPage = (int) ($data['per_page'] ?? 20);
        $results = $q->paginate($perPage);

        return $this->ok([
            'items' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    public function show(ShowRouteRequest $request, Route $route)
    {
        $route->load([
            'versions' => fn ($q) => $q->orderByDesc('version')->limit(20),
            'versions.stops',
            'versions.schedules',
        ]);

        return $this->ok($route);
    }

    public function store(CreateRouteRequest $request)
    {
        $data = $request->validated();

        $route = Route::create([
            'name' => $data['name'],
            'type' => 'door_to_door',
            'is_active' => true,
        ]);

        app(AuditLogger::class)->log($request->user()->id, 'route.create', $route, null, $route->toArray());

        return $this->created($route);
    }

    public function createVersion(CreateRouteVersionRequest $request, Route $route)
    {
        $data = $request->validated();

        $user = $request->user();

        return DB::transaction(function () use ($route, $data, $user) {
            $nextVersion = ((int) $route->versions()->max('version')) + 1;

            $version = RouteVersion::create([
                'route_id' => $route->id,
                'version' => $nextVersion,
                'status' => 'draft',
                'created_by' => $user->id,
            ]);

            foreach ($data['stops'] as $s) {
                RouteStop::create([
                    'route_version_id' => $version->id,
                    'stop_order' => $s['stop_order'],
                    'name' => $s['name'] ?? null,
                    'address' => $s['address'] ?? null,
                    'lat' => $s['lat'] ?? null,
                    'lng' => $s['lng'] ?? null,
                    'planned_time' => $s['planned_time'] ?? null,
                ]);
            }

            foreach (($data['schedules'] ?? []) as $sch) {
                RouteSchedule::create([
                    'route_version_id' => $version->id,
                    'day_of_week' => $sch['day_of_week'],
                    'depart_time' => $sch['depart_time'],
                    'arrive_time' => $sch['arrive_time'] ?? null,
                ]);
            }

            app(AuditLogger::class)->log(
                actorId: $user->id,
                event: 'route.version.create',
                auditable: $version,
                before: null,
                after: ['route_id' => $route->id, 'version' => $version->version],
            );

            return $this->created($version->load(['stops', 'schedules']));
        });
    }

    public function approveVersion(RouteVersionDecisionRequest $request, RouteVersion $routeVersion)
    {
        $data = $request->validated();

        $user = $request->user();
        $before = $routeVersion->toArray();

        if ($data['decision'] === 'approve') {
            $routeVersion->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
        } else {
            $routeVersion->update(['status' => 'archived']);
        }

        app(AuditLogger::class)->log($user->id, 'route.version.update', $routeVersion, $before, $routeVersion->toArray());

        return $this->ok($routeVersion);
    }

    public function generateTrip(GenerateRouteTripRequest $request, RouteVersion $routeVersion)
    {
        $data = $request->validated();

        if ($routeVersion->status !== 'approved') {
            abort(409, Messages::ROUTE_VERSION_MUST_BE_APPROVED);
        }

        $runDate = Carbon::parse($data['run_date'])->toDateString();

        return DB::transaction(function () use ($request, $routeVersion, $runDate, $data) {
            $departAt = Carbon::parse("{$runDate} {$data['schedule_depart_time']}:00");
            $arriveBy = isset($data['schedule_arrive_time'])
                ? Carbon::parse("{$runDate} {$data['schedule_arrive_time']}:00")
                : null;

            $trip = Trip::create([
                'dispatch_request_id' => null,
                'dispatcher_id' => $request->user()->id,
                'status' => 'pending',
                'depart_at' => $departAt,
                'arrive_by' => $arriveBy,
                'lock_version' => 0,
            ]);

            $run = RouteRun::updateOrCreate(
                ['route_version_id' => $routeVersion->id, 'run_date' => $runDate],
                ['trip_id' => $trip->id],
            );

            app(AuditLogger::class)->log(
                actorId: $request->user()->id,
                event: 'route.run.generate_trip',
                auditable: $routeVersion,
                before: null,
                after: ['route_run_id' => $run->id, 'trip_id' => $trip->id, 'run_date' => $runDate],
            );

            return $this->created(['route_run' => $run, 'trip' => $trip]);
        });
    }

    public function enrollStudents(EnrollRouteStudentsRequest $request, Route $route)
    {
        $data = $request->validated();

        $user = $request->user();

        return DB::transaction(function () use ($route, $data, $user) {
            foreach ($data['students'] as $s) {
                Student::findOrFail($s['student_id']);

                $route->students()->syncWithoutDetaching([
                    $s['student_id'] => [
                        'starts_on' => $s['starts_on'] ?? null,
                        'ends_on' => $s['ends_on'] ?? null,
                    ],
                ]);
            }

            app(AuditLogger::class)->log(
                actorId: $user->id,
                event: 'route.students.enroll',
                auditable: $route,
                before: null,
                after: ['count' => count($data['students'])],
            );

            return $this->ok(['route_id' => $route->id, 'enrolled' => count($data['students'])]);
        });
    }
}
