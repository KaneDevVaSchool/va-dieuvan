# Kế hoạch Refactor — VA Điều Vận

**Ngày lập**: 2026-06-10  
**Mục tiêu**: Nâng kiến trúc lên Production Enterprise standard

---

## Sprint 1 — Critical Fixes (1-2 ngày)

### RF-1.1: Fix missing transaction trong TripController::updatePassengerList()

**File**: `app/Http/Controllers/Api/Trips/TripController.php`  
**Action**: Wrap business path (cargo/business/passenger_rows) trong DB::transaction()

```php
// Trước:
$dr->wizard_snapshot = $snap;
$dr->passenger_count = ...;
$dr->save();
app(AuditLogger::class)->log(...);

// Sau:
DB::transaction(function () use ($dr, $snap, $actorId, $trip, ...) {
    $dr->wizard_snapshot = $snap;
    $dr->passenger_count = ...;
    $dr->save();
    app(AuditLogger::class)->log(...);
});
```

### RF-1.2: Fix markAbsentBulk() atomic

**File**: `app/Services/TransportProgram/AttendanceService.php`  
**Action**: Wrap bulk operation trong single outer transaction

```php
public function markAbsentBulk(...): void
{
    DB::transaction(function () use (...) {
        foreach ($studentIds as $sid) {
            $this->markAbsentInTransaction($day, $sid, ...);
        }
    });
}
```

---

## Sprint 2 — Controller Cleanup (3-5 ngày)

### RF-2.1: Extract PassengerListNormalizerService

**Tạo mới**: `app/Services/Trips/PassengerListNormalizerService.php`

```php
class PassengerListNormalizerService
{
    public function normalizePassengerRows(array $rows): array { ... }
    public function normalizeBusinessRows(array $rows): array { ... }
    public function normalizeCargoRows(array $rows): array { ... }
    public function isRowFilled(string $type, array $row): bool { ... }
    public function sumPassengerCount(array $passengerRows, array $businessRows): int { ... }
    public function sumCargoQty(array $cargoRows): int { ... }
}
```

### RF-2.2: Extract loadTripRelations() helper

**File**: `app/Http/Controllers/Api/Trips/TripController.php`

```php
private function loadTripRelations(Trip $trip): Trip
{
    return $trip->load([
        'dispatcher:id,name,email,employee_code',
        'vehicle:id,license_plate,status,type,seat_count,odometer_km',
        'driver:id,full_name,phone,odometer_km,user_id',
        'transportProvider:id,name',
        'record',
        'dispatchRequest',
        'dispatchRequest.requester:id,name,phone,email,employee_code,avatar_url',
        'dispatchRequest.attachments' => fn($q) => $q->orderByDesc('id')->limit(50),
        'costs' => fn($q) => $q->orderByDesc('id')->limit(50),
        'events' => fn($q) => $q->orderByDesc('id')->limit(50)->with('creator:id,name'),
        'tripPassengers' => fn($q) => $q->orderBy('id'),
    ]);
}
```

### RF-2.3: Tạo TripResource

**Tạo mới**: `app/Http/Resources/TripResource.php`

```php
class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'depart_at' => $this->depart_at?->toIso8601String(),
            // ... explicit fields
        ];
    }
}
```

### RF-2.4: Tạo TripPolicy

**Tạo mới**: `app/Policies/TripPolicy.php`

```php
class TripPolicy
{
    public function view(User $user, Trip $trip): bool
    {
        return TripVisibility::userCanViewTrip($user, $trip);
    }

    public function assign(User $user, Trip $trip): bool
    {
        return $user->hasPermission('trips.assign');
    }
}
```

---

## Sprint 3 — Performance (2-3 ngày)

### RF-3.1: Tối ưu Trip stats query

**File**: `app/Http/Controllers/Api/Trips/TripController.php`  
**Action**: Gộp count queries thành single query với CASE WHEN

### RF-3.2: Add missing database indexes

**Tạo migration**: `2026_06_10_add_performance_indexes.php`

```php
public function up(): void
{
    Schema::table('trips', function (Blueprint $table) {
        $table->index(['driver_id', 'depart_at'], 'idx_trips_driver_depart');
    });

    Schema::table('tp_trip_student_logs', function (Blueprint $table) {
        $table->index(['execution_id', 'student_id'], 'idx_tp_student_logs_exec_student');
    });
}
```

### RF-3.3: Cache FeatureToggle

**File**: `app/Repositories/FeatureToggleRepository.php`

```php
public function findByName(string $name): ?FeatureToggle
{
    return Cache::remember("feature_toggle:{$name}", 300, fn() =>
        FeatureToggle::where('name', $name)->first()
    );
}
```

---

## Sprint 4 — Architecture (5-7 ngày)

### RF-4.1: Thêm TripRepository

```php
class TripRepository
{
    public function listWithFilters(User $user, array $filters, int $perPage): LengthAwarePaginator;
    public function getStatsAggregates(User $user, array $filters): array;
}
```

### RF-4.2: Implement ShouldQueue cho Notifications

```php
class TripAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    // ...
}
```

### RF-4.3: Role constants thay vì hardcoded strings

**Tạo mới**: `app/Support/Roles.php`

```php
class Roles
{
    const SUPERADMIN = 'superadmin';
    const ADMIN = 'admin';
    const DISPATCHER = 'dispatcher';
    const DEPARTMENT_HEAD = 'department_head';
    const INTERNAL_USER = 'internal_user';
    const DRIVER = 'driver';
    const ACCOUNTANT = 'accountant';
}
```

---

## Không Refactor (intentional design)

Các pattern sau trông như "vi phạm" nhưng là intentional:

1. **`app()` trong TripController::show()**  — Dùng để tránh constructor bloat cho rarely-used service
2. **Multiple service dependencies trong 1 controller** — Nhiều responsibility per controller là tradeoff để không quá nhiều controller files
3. **`abort_unless()` thay vì Policy** — Đơn giản hơn cho visibility checks không cần full Policy object
