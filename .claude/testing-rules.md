# Testing Rules — VA Điều Vận

## Test Strategy

```
Unit Tests     → Isolated logic (Services, DTOs, calculations)
Feature Tests  → HTTP endpoints (with DB, no mocks)
E2E Tests      → User flows (Playwright)
```

**Không mock database** — Feature tests dùng RefreshDatabase với real DB.

---

## PHPUnit Feature Tests

### Template chuẩn
```php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $dispatcher;
    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->dispatcher = User::factory()->create();
        $this->dispatcher->assignRole('dispatcher');
        
        $this->driver = User::factory()->create();
        $this->driver->assignRole('driver');
    }

    public function test_dispatcher_can_assign_driver_to_pending_trip(): void
    {
        $trip = Trip::factory()->pending()->create();

        $response = $this
            ->actingAs($this->dispatcher, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/assign", [
                'driver_id' => $this->driver->id,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'driver_id' => $this->driver->id,
            'status' => 'assigned',
        ]);
    }

    public function test_driver_cannot_assign_another_trip(): void
    {
        $trip = Trip::factory()->pending()->create();

        $response = $this
            ->actingAs($this->driver, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/assign", [
                'driver_id' => $this->driver->id,
            ]);

        $response->assertForbidden();
    }
}
```

### Test naming pattern
```php
// {subject}_{can/cannot/should/returns}_{action_or_result}_{context}
public function test_dispatcher_can_assign_driver_to_pending_trip(): void
public function test_attendance_confirm_fails_when_version_mismatch(): void
public function test_financial_lock_prevents_cost_edit_on_paid_trip(): void
```

### Assertions bắt buộc
```php
// 1. HTTP status
$response->assertOk();         // 200
$response->assertCreated();    // 201
$response->assertForbidden();  // 403
$response->assertNotFound();   // 404
$response->assertUnprocessable(); // 422
$response->assertStatus(409);  // conflict

// 2. Database state
$this->assertDatabaseHas('trips', ['id' => $trip->id, 'status' => 'completed']);
$this->assertDatabaseMissing('trips', ['id' => $trip->id, 'driver_id' => null]);

// 3. Response structure (khi cần)
$response->assertJsonPath('data.status', 'completed');
$response->assertJsonStructure(['items' => [['id', 'status', 'depart_at']]]);
```

---

## Factories

### Cấu trúc
```php
// database/factories/TripFactory.php
class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        return [
            'status' => 'pending',
            'depart_at' => now()->addDay(),
            'arrive_by' => now()->addDay()->addHours(2),
            'lock_version' => 0,
        ];
    }

    // States cho common scenarios
    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function inProgress(): static
    {
        return $this->state([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'started_at' => now()->subHours(2),
            'completed_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->completed()->state([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}
```

---

## Test Coverage Priorities

### PHẢI có tests (P0)
- Authentication (login, logout, Google OAuth callback)
- Authorization (role-based access control)
- Optimistic lock conflicts
- Financial lock enforcement
- Concurrent operations (dispatch request approval)
- Import validation (TP student import)
- Idempotency behavior

### NÊN có tests (P1)
- CRUD cho mọi resource chính
- Attendance workflow (mark absent, confirm, reopen)
- Trip lifecycle (pending → assigned → in_progress → completed)
- Notification dispatch
- Report generation

### Không cần test (P3)
- Framework internals
- Third-party packages
- Simple getters/setters

---

## Playwright E2E Tests

### Cấu trúc
```
tests/e2e/
  auth/
    login.spec.ts
    google-auth.spec.ts
  dispatch/
    create-request.spec.ts
    assign-trip.spec.ts
  driver/
    start-trip.spec.ts
    tp-attendance.spec.ts
  admin/
    manage-users.spec.ts
    feature-toggles.spec.ts
```

### Template
```ts
// tests/e2e/dispatch/create-request.spec.ts
import { test, expect } from '@playwright/test'
import { loginAs } from '../helpers/auth'

test.describe('Tạo yêu cầu điều vận', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'dispatcher')
  })

  test('tạo yêu cầu point-to-point thành công', async ({ page }) => {
    await page.goto('/requests/create')
    
    await page.selectOption('[data-testid="trip-type"]', 'point_to_point')
    await page.fill('[data-testid="origin"]', 'VP Chính - 123 Nguyễn Du')
    await page.fill('[data-testid="destination"]', 'Sân bay Tân Sơn Nhất')
    
    await page.click('[data-testid="submit-btn"]')
    
    await expect(page.locator('[data-testid="success-message"]')).toBeVisible()
    await expect(page).toHaveURL(/\/requests\/\d+/)
  })
})
```

### data-testid attributes
Mọi interactive element trong Vue components phải có `data-testid` cho E2E tests:
```vue
<button data-testid="assign-driver-btn" @click="handleAssign">
  Gán tài xế
</button>
```
