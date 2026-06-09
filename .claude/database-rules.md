# Database Rules — VA Điều Vận

## Schema Design Rules

### 1. Luôn có timestamps
```php
$table->timestamps();  // created_at, updated_at — bắt buộc trên mọi table
```

### 2. Soft delete cho dữ liệu nghiệp vụ quan trọng
```php
$table->softDeletes();  // dùng cho: dispatch_requests, trips, users, vehicles
// KHÔNG dùng cho: audit_logs, notifications, idempotent_requests
```

### 3. Foreign keys luôn có onDelete action rõ ràng
```php
$table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
// cascadeOnDelete() chỉ khi child records vô nghĩa nếu parent bị xóa
// restrictOnDelete() khi cần bảo vệ data integrity
```

### 4. JSON columns cho flexible data
```php
$table->json('settings')->nullable();       // config dạng key-value
$table->json('wizard_snapshot')->nullable(); // complex nested data
```

### 5. Optimistic lock version
```php
$table->unsignedInteger('lock_version')->default(0);
// Tăng mỗi lần update, check trước khi update
```

---

## Index Strategy

### Required indexes
1. Mọi FK column — tự động nếu dùng `foreignId()->constrained()`
2. Status columns — thường xuyên filter
3. Date columns — filter range
4. Compound indexes cho queries filter theo nhiều cột

### Composite index order
```php
// Cột selective (ít giá trị distinct) đặt TRƯỚC
// Cột range (date, amount) đặt CUỐI
$table->index(['status', 'driver_id', 'depart_at']);
// → query WHERE status='in_progress' AND driver_id=5 ORDER BY depart_at
```

### Khi nào dùng FULLTEXT
```php
// Chỉ khi cần full-text search (LIKE không đủ)
// Ví dụ: tìm kiếm trong origin/destination text
Schema::table('dispatch_requests', function (Blueprint $table) {
    $table->fullText(['origin', 'destination']);
});
```

---

## Query Rules

### KHÔNG dùng trực tiếp raw SQL trừ khi cần thiết
```php
// ĐÚNG — Query Builder có parameterized
DB::table('trips')
    ->where('driver_id', $driverId)
    ->whereDate('depart_at', today())
    ->get();

// Nếu phải dùng raw — bắt buộc parameterized bindings
DB::select('SELECT * FROM trips WHERE driver_id = ?', [$driverId]);

// SAI — SQL injection risk
DB::select("SELECT * FROM trips WHERE driver_id = {$driverId}");
```

### Pagination cho lists
```php
// LUÔN paginate cho endpoint trả danh sách
$result = Trip::query()->paginate($perPage);

// Giới hạn per_page tối đa
$perPage = min((int)($data['per_page'] ?? 20), 100);
```

### Chunk cho large datasets
```php
// Không load toàn bộ khi xử lý batch
Trip::where('status', 'pending')
    ->chunk(500, function (Collection $trips) use ($service) {
        foreach ($trips as $trip) {
            $service->process($trip);
        }
    });
```

---

## Migration Safety Rules

### Không break existing queries
```php
// ĐÚNG: thêm cột nullable
$table->string('new_field')->nullable()->after('existing_field');

// ĐÚNG: thêm cột với default
$table->boolean('is_active')->default(true)->after('name');

// CẨN THẬN: đổi tên cột → cần update code
// CẨN THẬN: xóa cột → check không còn code reference trước
```

### Production safe
```php
// ĐÚNG: tạo index concurrently (MySQL 8.0+)
// Không lock table khi thêm index vào table lớn
// → Dùng migration + chạy ngoài deploy window nếu table > 100k rows
```

### Không rollback khó
```php
// Luôn implement down() đúng
public function down(): void
{
    Schema::dropIfExists('new_table');
    // hoặc
    Schema::table('existing', function (Blueprint $table) {
        $table->dropColumn('new_column');
        $table->dropIndex('idx_name');
    });
}
```

---

## Seeder Rules

```php
// Dùng updateOrCreate để idempotent
FeatureToggle::updateOrCreate(
    ['name' => 'per_shift_attendance'],
    ['enabled' => false]
);

// Không hardcode IDs
// Không dùng seeder trong production (trừ FeatureToggleSeeder, RbacSeeder)
```

---

## Backup & Data Integrity

- Không xóa records từ `audit_logs` — append-only
- Soft delete cho mọi entity nghiệp vụ chính
- Financial records (payments, trip_costs) KHÔNG bao giờ hard delete
- `idempotent_requests` có thể cleanup sau 7 ngày
