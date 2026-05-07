<?php

namespace App\Services\Maintenance;

use App\Models\Attachment;
use App\Models\Driver;
use App\Models\MaintenanceReminder;
use App\Models\MaintenanceRenewalLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleMaintenanceItem;
use App\Support\TripVisibility;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class MaintenanceService
{
    /**
     * Resolve the driver and primary vehicle for the authenticated user.
     *
     * @return array{driver: Driver|null, vehicle: Vehicle|null}
     */
    public function resolveDriverAndVehicle(User $user): array
    {
        $driver = Driver::query()->where('user_id', $user->id)->first();

        $vehicle = null;
        if ($driver) {
            $vehicle = Vehicle::query()
                ->where('default_driver_id', $driver->id)
                ->orderByDesc('id')
                ->first();
        }

        if (! $vehicle) {
            $tripWithVehicle = TripVisibility::visibleTripsQuery($user)
                ->whereHas('dispatchRequest')
                ->whereNotNull('vehicle_id')
                ->orderByDesc('trips.depart_at')
                ->with('vehicle')
                ->first();
            $vehicle = $tripWithVehicle?->vehicle;
        }

        return ['driver' => $driver, 'vehicle' => $vehicle];
    }

    /**
     * Return serialized list payload for /driver/maintenance.
     *
     * @return array<string, mixed>
     */
    public function listForUser(User $user): array
    {
        ['driver' => $driver, 'vehicle' => $vehicle] = $this->resolveDriverAndVehicle($user);

        if (! $vehicle) {
            return [
                'vehicle' => null,
                'items' => [],
                'reminders' => [],
                'counts' => ['urgent' => 0, 'upcoming' => 0, 'safe' => 0],
            ];
        }

        $this->seedDefaultItemsForVehicle($vehicle, $driver);

        $items = VehicleMaintenanceItem::query()
            ->where('vehicle_id', $vehicle->id)
            ->orderByRaw('COALESCE(expiry_date, next_service_date) ASC')
            ->get();

        $reminders = MaintenanceReminder::query()
            ->where('vehicle_id', $vehicle->id)
            ->orderBy('remind_at')
            ->get();

        $counts = [
            'urgent' => $items->filter(fn ($i) => $i->status === 'urgent')->count(),
            'upcoming' => $items->filter(fn ($i) => $i->status === 'warning')->count(),
            'safe' => $items->filter(fn ($i) => $i->status === 'safe')->count(),
        ];

        return [
            'vehicle' => $this->serializeVehicle($vehicle),
            'items' => $items->map(fn (VehicleMaintenanceItem $item) => $this->serializeItem($item))->values()->all(),
            'reminders' => $reminders->map(fn (MaintenanceReminder $r) => $this->serializeReminder($r))->values()->all(),
            'counts' => $counts,
        ];
    }

    /**
     * Single item detail with renewal history and attachments.
     *
     * @return array<string, mixed>
     */
    public function detailForUser(User $user, VehicleMaintenanceItem $item): array
    {
        $item->load(['renewalLogs.updatedBy', 'attachments', 'vehicle']);

        return [
            ...$this->serializeItem($item),
            'issued_by' => $item->issued_by,
            'estimated_renewal_cost' => $item->estimated_renewal_cost,
            'next_service_km' => $item->next_service_km,
            'last_service_km' => $item->last_service_km,
            'last_service_date' => $item->last_service_date?->format('Y-m-d'),
            'reminder_enabled' => $item->reminder_enabled,
            'reminder_days_before' => $item->reminder_days_before,
            'images' => $item->attachments->map(fn (Attachment $a) => [
                'id' => $a->id,
                'url' => $a->url,
                'original_name' => $a->original_name,
                'mime_type' => $a->mime_type,
            ])->values()->all(),
            'history' => $item->renewalLogs->map(fn (MaintenanceRenewalLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'amount_paid' => $log->amount_paid,
                'notes' => $log->notes,
                'updated_by' => $log->updatedBy?->name,
                'created_at' => $log->created_at?->format('d/m/Y'),
            ])->values()->all(),
        ];
    }

    /**
     * Update expiry/notes and append a renewal log entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateItem(User $user, VehicleMaintenanceItem $item, array $data): VehicleMaintenanceItem
    {
        $before = $item->only(['expiry_date', 'next_service_date', 'next_service_km', 'notes', 'reminder_enabled', 'reminder_days_before', 'issued_by', 'estimated_renewal_cost']);

        $item->fill([
            'expiry_date' => $data['expiry_date'] ?? $item->expiry_date,
            'next_service_date' => $data['next_service_date'] ?? $item->next_service_date,
            'next_service_km' => $data['next_service_km'] ?? $item->next_service_km,
            'notes' => $data['notes'] ?? $item->notes,
            'reminder_enabled' => $data['reminder_enabled'] ?? $item->reminder_enabled,
            'reminder_days_before' => $data['reminder_days_before'] ?? $item->reminder_days_before,
            'issued_by' => $data['issued_by'] ?? $item->issued_by,
            'estimated_renewal_cost' => $data['estimated_renewal_cost'] ?? $item->estimated_renewal_cost,
            'last_updated_by_id' => $user->id,
        ]);
        $item->save();

        // Log renewal entry when expiry date changed
        $newExpiry = $data['expiry_date'] ?? null;
        if ($newExpiry && $newExpiry !== $before['expiry_date']) {
            MaintenanceRenewalLog::create([
                'maintenance_item_id' => $item->id,
                'action' => 'renewed',
                'amount_paid' => $data['amount_paid'] ?? null,
                'notes' => $data['renewal_notes'] ?? null,
                'updated_by_user_id' => $user->id,
            ]);
        } elseif ($data['notes'] ?? null) {
            MaintenanceRenewalLog::create([
                'maintenance_item_id' => $item->id,
                'action' => 'noted',
                'amount_paid' => null,
                'notes' => $data['notes'],
                'updated_by_user_id' => $user->id,
            ]);
        }

        return $item->fresh();
    }

    /** Upload an image attachment for the given maintenance item. */
    public function uploadImage(User $user, VehicleMaintenanceItem $item, UploadedFile $file): Attachment
    {
        $disk = 'public';
        $path = Storage::putFileAs(
            "attachments/maintenance/{$item->id}",
            $file,
            $file->hashName(),
            ['disk' => $disk],
        );

        return Attachment::create([
            'uploaded_by' => $user->id,
            'attachable_type' => VehicleMaintenanceItem::class,
            'attachable_id' => $item->id,
            'kind' => 'maintenance_photo',
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'file_binary' => Attachment::bytesFromUpload($file),
        ]);
    }

    /**
     * Create a custom reminder.
     *
     * @param  array<string, mixed>  $data
     */
    public function createReminder(User $user, Vehicle $vehicle, ?Driver $driver, array $data): MaintenanceReminder
    {
        return MaintenanceReminder::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver?->id,
            'title' => $data['title'],
            'repeat_type' => $data['repeat_type'] ?? 'once',
            'remind_at' => Carbon::parse($data['remind_at']),
            'created_by_user_id' => $user->id,
        ]);
    }

    /** Delete a custom reminder that belongs to the vehicle. */
    public function deleteReminder(Vehicle $vehicle, MaintenanceReminder $reminder): void
    {
        abort_if($reminder->vehicle_id !== $vehicle->id, 403);
        $reminder->delete();
    }

    /**
     * Seed 7 default items for a vehicle on first load (lazy initialization).
     */
    public function seedDefaultItemsForVehicle(Vehicle $vehicle, ?Driver $driver): void
    {
        if (VehicleMaintenanceItem::query()->where('vehicle_id', $vehicle->id)->exists()) {
            return;
        }

        $defaults = [
            // Legal documents — use vehicle expiry dates if available
            [
                'type' => 'registration',
                'name' => 'Đăng kiểm xe',
                'expiry_date' => $vehicle->inspection_expires_at?->format('Y-m-d'),
                'issued_by' => 'Cục Đăng kiểm Việt Nam',
            ],
            [
                'type' => 'insurance_mandatory',
                'name' => 'Bảo hiểm bắt buộc (TNDS)',
                'expiry_date' => $vehicle->insurance_expires_at?->format('Y-m-d'),
                'issued_by' => null,
            ],
            [
                'type' => 'insurance_hull',
                'name' => 'Bảo hiểm thân vỏ',
                'expiry_date' => $vehicle->insurance_expires_at?->format('Y-m-d'),
                'issued_by' => null,
            ],
            // Periodic maintenance
            [
                'type' => 'oil',
                'name' => 'Thay dầu máy',
                'next_service_km' => $vehicle->odometer_km ? $vehicle->odometer_km + 5000 : null,
                'last_service_km' => $vehicle->odometer_km,
                'last_service_date' => $vehicle->last_maintenance_at?->format('Y-m-d'),
            ],
            [
                'type' => 'tire',
                'name' => 'Kiểm tra lốp xe',
                'next_service_km' => $vehicle->odometer_km ? $vehicle->odometer_km + 10000 : null,
                'last_service_km' => $vehicle->odometer_km,
            ],
            [
                'type' => 'air_filter',
                'name' => 'Thay lọc gió',
                'next_service_date' => now()->addMonths(6)->format('Y-m-d'),
            ],
            [
                'type' => 'brake',
                'name' => 'Phanh & má phanh',
                'next_service_km' => $vehicle->odometer_km ? $vehicle->odometer_km + 20000 : null,
                'last_service_km' => $vehicle->odometer_km,
            ],
        ];

        foreach ($defaults as $row) {
            VehicleMaintenanceItem::create(array_merge($row, [
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver?->id,
            ]));
        }
    }

    // -----------------------------------------------------------------
    // Serialization helpers
    // -----------------------------------------------------------------

    /** @return array<string, mixed> */
    private function serializeVehicle(Vehicle $v): array
    {
        return [
            'id' => $v->id,
            'license_plate' => $v->license_plate,
            'type' => $v->type,
            'seat_count' => (int) ($v->seat_count ?? 0),
            'color' => null,
            'odometer_km' => $v->odometer_km,
        ];
    }

    /** @return array<string, mixed> */
    public function serializeItem(VehicleMaintenanceItem $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type,
            'name' => $item->name,
            'expiry_date' => $item->expiry_date?->format('Y-m-d'),
            'next_service_date' => $item->next_service_date?->format('Y-m-d'),
            'days_remaining' => $item->days_remaining,
            'status' => $item->status,
            'icon' => $item->icon,
            'notes' => $item->notes,
            'last_updated' => $item->updated_at?->format('Y-m-d'),
            'next_service_km' => $item->next_service_km,
        ];
    }

    /** @return array<string, mixed> */
    private function serializeReminder(MaintenanceReminder $r): array
    {
        return [
            'id' => $r->id,
            'title' => $r->title,
            'repeat_type' => $r->repeat_type,
            'remind_at' => $r->remind_at?->format('Y-m-d H:i'),
        ];
    }
}
