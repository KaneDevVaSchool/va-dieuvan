# Architecture Rules — VA Điều Vận

## Quy tắc bất biến (KHÔNG được vi phạm)

### RULE-1: Controller không chứa business logic

Controller chỉ được:
- Nhận FormRequest (validate + authorize)
- Gọi Service/Action
- Trả response

Controller KHÔNG được:
- Trực tiếp query database
- Chứa business rules
- Thực hiện transformations phức tạp
- Gọi nhiều services rồi tự merge kết quả (dấu hiệu của God Controller)

**Ngoại lệ chấp nhận được**:
- `abort_unless()` cho visibility check đơn giản
- `$model->load()` để eager load relations trước khi trả response
- `$request->user()` để lấy actor

### RULE-2: Mọi mutation nghiệp vụ phải trong DB::transaction()

```php
// BẮT BUỘC khi:
// - Tạo/sửa nhiều records liên quan
// - Sau mutation có ghi audit log / gửi notification
// - Dùng optimistic locking

DB::transaction(function () use (...) {
    $model->update([...]);
    $this->audit->log(...);
    // notification có thể ra ngoài transaction nếu dùng Queue
});
```

### RULE-3: Phải có Audit Log cho mọi mutation nghiệp vụ

```php
app(AuditLogger::class)->log(
    actorId: $actorId,
    event: 'trip.assigned',   // domain.action format
    auditable: $trip,
    before: $before,          // state trước khi thay đổi
    after: $trip->toArray(),  // state sau khi thay đổi
);
```

### RULE-4: Optimistic locking cho concurrent mutations

Các entity bị nhiều user cùng thao tác phải dùng optimistic lock:
- `Trip`: `lock_version`
- `TpProgramDay`: `attendance_lock_version` (và per-shift variants)
- `DispatchRequest`: check status trước khi approve

### RULE-5: Financial data không được sửa khi đã paid

```php
// Luôn check trước khi sửa chi phí hoặc hành khách
FinancialDataLock::assertTripNotPaid($trip);
FinancialDataLock::assertTripAllowsPassengerAndCostEdits($trip);
```

### RULE-6: Permission check phải xảy ra ở tầng HTTP

- FormRequest: cho validation + authorization đơn giản
- Middleware (`EnsureHasPermission`): cho route-level
- `abort_unless(TripVisibility::...)`: cho object-level visibility
- Policy: cho complex authorization (prefer khi có)

### RULE-7: Eager loading bắt buộc — không lazy load trong loops

```php
// ĐÚNG
$days = TpProgramDay::with(['program', 'driver', 'execution.studentLogs'])->get();
foreach ($days as $day) {
    // sử dụng relations đã loaded
}

// SAI
$days = TpProgramDay::all();
foreach ($days as $day) {
    $program = $day->program; // N+1!
}
```

---

## Layer Responsibilities

```
HTTP Layer (Controllers, Middleware, FormRequests):
  - Parse và validate input
  - Authorize request
  - Delegate to Business Layer
  - Format và return response

Business Layer (Services, Actions):
  - Business rules
  - Orchestrate data operations
  - Emit domain events/notifications
  - Log audit trail

Data Layer (Models, Repositories):
  - Database access
  - Relationship definitions
  - Scopes và query builders
  - Cast/mutate attributes

Support (app/Support/*):
  - Cross-cutting concerns
  - Static utility helpers
  - Access control helpers (TripVisibility, FinancialDataLock)
```

---

## Dependency Direction

```
Controller → Service → Model/Repository
     ↓           ↓
FormRequest   AuditLogger
               ↓
          Notification/Event/Job
```

Services KHÔNG depend vào Controllers.
Models KHÔNG chứa business logic.
Controllers KHÔNG trực tiếp query Models (ngoại lệ: đơn giản có thể chấp nhận).

---

## Service Organization

```
app/Services/
  {Domain}/
    {Name}Service.php      — Business logic
    {Name}Presenter.php    — Presentation/formatting
    {Name}Query.php        — Complex read queries
    {Name}XlsxWriter.php   — Excel export
```

---

## Khi nào tạo Action vs Service

| Dùng Action | Dùng Service |
|-------------|-------------|
| Single-purpose operation | Related operations cùng domain |
| Có thể tái sử dụng từ nhiều Controllers | Internal service-to-service |
| Ví dụ: CreateTransportProgramAction | Ví dụ: AttendanceService |

---

## Frontend Architecture Rules

1. **Views** chỉ render và delegate logic vào composables
2. **Composables** chứa API calls và state management
3. **Pinia stores** cho global shared state (không dùng cho local component state)
4. **HTTP calls** chỉ qua `createHttpClient()` factory
5. **i18n** cho mọi text hiển thị cho người dùng
6. **TypeScript** cho interfaces và complex types; `.js` cho simple utilities có thể
