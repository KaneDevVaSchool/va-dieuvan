# Coding Standards — VA Điều Vận

## PHP / Laravel

### Namespace & Class Names
- Controllers: `App\Http\Controllers\Api\{Domain}\{Name}Controller`
- Services: `App\Services\{Domain}\{Name}Service`
- Actions: `App\Actions\{Name}Action`
- DTOs: `App\DTOs\{Domain}\{Name}DTO`
- Models: `App\Models\{Name}`
- Form Requests: `App\Http\Requests\Api\{Domain}\{Name}Request`
- Policies: `App\Policies\{Name}Policy`

### Controller Rules
```php
// ĐÚNG: Controller mỏng
public function store(StoreRequest $request, SomeService $service): JsonResponse
{
    $data = $request->validated();
    $result = $service->create($data, $request->user());
    return $this->ok($result);
}

// SAI: Business logic trong controller
public function store(Request $request): JsonResponse
{
    $data = $request->validate([...]); // Dùng FormRequest
    $model = new Model();
    $model->field = $this->processField($data['field']); // SAI: vào service
    $model->save();
    return response()->json($model);
}
```

### Service Rules
```php
// ĐÚNG
class SomeService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SomeDependency $dep,
    ) {}

    public function create(array $data, User $actor): Model
    {
        return DB::transaction(function () use ($data, $actor) {
            $model = Model::create($data);
            $this->audit->log($actor->id, 'model.created', $model, null, $model->toArray());
            return $model;
        });
    }
}
```

### Database Queries
```php
// ĐÚNG: Eager loading
$trips = Trip::with(['driver:id,full_name', 'vehicle:id,license_plate'])->get();

// SAI: Lazy loading trong loop
foreach ($trips as $trip) {
    echo $trip->driver->full_name; // N+1!
}

// ĐÚNG: Select only needed columns
Trip::select('id', 'status', 'depart_at')->where(...)->get();

// ĐÚNG: Transaction cho multi-step operations
DB::transaction(function () {
    $a->save();
    $b->save();
    event(new SomethingHappened());
});
```

### Naming Conventions
```php
// Methods: camelCase
public function assignDriverToTrip(Trip $trip, Driver $driver): void {}

// Properties: camelCase
private string $driverName;

// Constants: UPPER_SNAKE_CASE
const STATUS_IN_PROGRESS = 'in_progress';

// Config keys: dot.notation
config('dispatch.default_timeout');
```

### Type Hints
```php
// ĐÚNG: Full type hints
public function getAttendance(TpProgramDay $day, ?string $shift = null): array

// ĐÚNG: Return types
public function create(array $data): Model

// Dùng PHP 8.1 readonly properties
public function __construct(
    private readonly Service $service
) {}
```

### Error Handling
```php
// Dùng abort() cho HTTP errors
abort(404, 'Không tìm thấy chuyến đi.');
abort(403, 'Bạn không có quyền thực hiện thao tác này.');
abort(409, 'Dữ liệu đã được cập nhật bởi người khác. Vui lòng tải lại.');
abort(422, 'Dữ liệu không hợp lệ.');

// abort_unless / abort_if cho conditions
abort_unless($user->hasPermission('trips.assign'), 403);
abort_if($trip->isPaid(), 422, 'Chuyến đã thanh toán.');
```

---

## Vue 3 / Frontend

### Component Structure (Composition API)
```vue
<script setup lang="ts">
// 1. Imports
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

// 2. Props
const props = defineProps<{
  tripId: number
  readonly?: boolean
}>()

// 3. Emits
const emit = defineEmits<{
  updated: [trip: Trip]
}>()

// 4. Composables
const router = useRouter()
const { trip, loading, refresh } = useTripDetail(props.tripId)

// 5. State
const isEditing = ref(false)

// 6. Computed
const canEdit = computed(() => !props.readonly && trip.value?.status !== 'completed')

// 7. Methods
function handleSubmit() { ... }

// 8. Lifecycle (nếu cần)
</script>

<template>
  <!-- Template content -->
</template>
```

### Naming Conventions (Frontend)
```
Views:     {Feature}View.vue       (TripsListView.vue)
Components: {Prefix}{Name}.vue     (TripCard.vue, DriverHeader.vue)
Composables: use{Name}.ts/.js      (useTripDetail.ts)
Stores:    {domain}.ts/.js         (dispatch.ts)
```

### API Calls (dùng HTTP client)
```js
// ĐÚNG: Dùng createHttpClient
import { createHttpClient } from '../createHttpClient'
const http = createHttpClient()
const { data } = await http.get('/api/trips')

// SAI: Raw axios
import axios from 'axios'
const data = await axios.get('/api/trips')
```

### i18n
```vue
<!-- ĐÚNG -->
<span>{{ $t('trip.status.completed') }}</span>

<!-- SAI: Hardcoded text -->
<span>Hoàn thành</span>
```

---

## Git Commit Convention

```
feat: thêm tính năng điểm danh theo ca
fix: sửa lỗi optimistic lock khi xác nhận điểm danh
refactor: extract PassengerListNormalizerService
perf: tối ưu trip stats query với conditional aggregation
test: thêm test cho TP attendance bulk absence
docs: cập nhật API endpoints documentation
chore: cập nhật dependencies
```

---

## Linting & Formatting

- PHP: Laravel Pint (`vendor/bin/pint`)
- JS/TS: ESLint + Prettier
- Không commit code chưa qua linting

---

## Test Naming

```php
// Feature test
/** @test */
public function dispatcher_can_assign_driver_to_pending_trip(): void {}

// Unit test
/** @test */
public function passenger_row_filled_returns_false_for_empty_row(): void {}
```
