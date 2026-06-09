# Laravel-Specific Rules — VA Điều Vận

## Models

### Fillable vs Guarded
```php
// Dùng $fillable (whitelist) — an toàn hơn $guarded = []
protected $fillable = [
    'specific_field_1',
    'specific_field_2',
];

// KHÔNG dùng $guarded = [] trừ khi có lý do cụ thể
```

### Relationships
```php
// Luôn đặt return type
public function driver(): BelongsTo
{
    return $this->belongsTo(Driver::class);
}

// FK column khi khác convention
public function creator(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}
```

### Casts
```php
protected $casts = [
    'settings' => 'array',         // JSON columns
    'depart_at' => 'datetime',     // Timestamp columns
    'is_active' => 'boolean',
    'amount' => 'decimal:2',
    'password' => 'hashed',        // PHP 8.1+
];
```

### Scopes
```php
// Local scope cho common queries
public function scopeActive(Builder $query): Builder
{
    return $query->where('status', 'active');
}

// Usage: Trip::active()->get()
```

---

## Form Requests

### Cấu trúc chuẩn
```php
class StoreDispatchRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Kiểm tra permission
        return $this->user()->hasPermission('dispatch_requests.create');
    }

    public function rules(): array
    {
        return [
            'origin' => ['required', 'string', 'max:500'],
            'destination' => ['required', 'string', 'max:500'],
            'depart_at' => ['required', 'date', 'after:now'],
            'passenger_count' => ['required', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'origin.required' => 'Vui lòng nhập điểm xuất phát.',
            'depart_at.after' => 'Thời gian khởi hành phải sau thời điểm hiện tại.',
        ];
    }
}
```

---

## Migrations

### Chuẩn đặt tên
```
YYYY_MM_DD_HHMMSS_create_{table_name}_table.php
YYYY_MM_DD_HHMMSS_add_{column}_to_{table}_table.php
YYYY_MM_DD_HHMMSS_add_{purpose}_indexes_to_{table}_table.php
```

### Template chuẩn
```php
public function up(): void
{
    Schema::create('table_name', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('status')->default('pending');
        $table->json('settings')->nullable();
        $table->softDeletes();        // nếu cần soft delete
        $table->timestamps();

        // Indexes
        $table->index('status');
        $table->index(['user_id', 'created_at']);
    });
}

public function down(): void
{
    Schema::dropIfExists('table_name');
}
```

### Foreign Key Convention
```php
// Dùng foreignId() cho bigint FK
$table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();

// Explicit khi column name khác
$table->unsignedBigInteger('created_by')->nullable();
$table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
```

---

## Notifications

### Template chuẩn
```php
class TripAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Trip $trip,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];  // + 'mail' nếu cần
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'trip_assigned',
            'trip_id' => $this->trip->id,
            'message' => "Chuyến #{$this->trip->id} đã được gán cho bạn.",
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
```

### ShouldQueue: Bắt buộc cho production notifications

---

## Commands

### Template chuẩn
```php
class SomeCommand extends Command
{
    protected $signature = 'app:some-command {--dry-run : Chỉ xem, không thực hiện}';
    protected $description = 'Mô tả command';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Bắt đầu...');

        // Logic
        $count = 0;

        if (!$dryRun) {
            // Thực hiện
        }

        $this->info("Hoàn thành. Đã xử lý: {$count}");
        return Command::SUCCESS;
    }
}
```

---

## Eloquent Query Tips

```php
// Tránh select(*) — chỉ lấy cột cần
Trip::select('id', 'status', 'depart_at', 'driver_id')->get();

// Chunk cho large datasets
Trip::where('status', 'completed')->chunk(500, function ($trips) {
    // process
});

// pluck cho key-value lookups
$nameById = User::pluck('name', 'id');

// exists() thay vì count() > 0
if (Trip::where('driver_id', $driverId)->where('status', 'in_progress')->exists()) {}

// Subquery thay vì N+1
Trip::addSelect(['driver_name' => Driver::select('full_name')
    ->whereColumn('id', 'trips.driver_id')
    ->limit(1)
])->get();
```

---

## Queue & Jobs

```php
class ProcessSomethingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;       // seconds between retries
    public int $timeout = 120;

    public function __construct(
        private readonly Model $model,
    ) {}

    public function handle(SomeService $service): void
    {
        $service->process($this->model);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Job failed', [
            'job' => static::class,
            'model_id' => $this->model->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```
