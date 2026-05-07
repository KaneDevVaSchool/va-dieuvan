<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\CreateMaintenanceReminderRequest;
use App\Http\Requests\Api\Driver\UpdateMaintenanceItemRequest;
use App\Models\MaintenanceReminder;
use App\Models\VehicleMaintenanceItem;
use App\Services\Maintenance\MaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverMaintenanceController extends Controller
{
    use ApiResponses;

    public function __construct(
        private MaintenanceService $service,
    ) {}

    /** GET /driver/maintenance */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $this->service->listForUser($user);

        return $this->ok($data);
    }

    /** GET /driver/maintenance/{item} */
    public function show(Request $request, VehicleMaintenanceItem $item): JsonResponse
    {
        $this->authorizeItem($request, $item);

        $data = $this->service->detailForUser($request->user(), $item);

        return $this->ok($data);
    }

    /** PUT /driver/maintenance/{item} */
    public function update(UpdateMaintenanceItemRequest $request, VehicleMaintenanceItem $item): JsonResponse
    {
        $this->authorizeItem($request, $item);

        $updated = $this->service->updateItem($request->user(), $item, $request->validated());

        return $this->ok($this->service->serializeItem($updated));
    }

    /** POST /driver/maintenance/{item}/images */
    public function uploadImage(Request $request, VehicleMaintenanceItem $item): JsonResponse
    {
        $this->authorizeItem($request, $item);

        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $attachment = $this->service->uploadImage(
            $request->user(),
            $item,
            $request->file('image'),
        );

        return $this->created([
            'id' => $attachment->id,
            'url' => $attachment->url,
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
        ]);
    }

    /** POST /driver/maintenance/reminders */
    public function storeReminder(CreateMaintenanceReminderRequest $request): JsonResponse
    {
        $user = $request->user();
        ['driver' => $driver, 'vehicle' => $vehicle] = $this->service->resolveDriverAndVehicle($user);

        abort_if(! $vehicle, 422, 'Không tìm thấy xe.');

        $reminder = $this->service->createReminder($user, $vehicle, $driver, $request->validated());

        return $this->created([
            'id' => $reminder->id,
            'title' => $reminder->title,
            'repeat_type' => $reminder->repeat_type,
            'remind_at' => $reminder->remind_at?->format('Y-m-d H:i'),
        ]);
    }

    /** DELETE /driver/maintenance/reminders/{reminder} */
    public function destroyReminder(Request $request, MaintenanceReminder $reminder): JsonResponse
    {
        $user = $request->user();
        ['vehicle' => $vehicle] = $this->service->resolveDriverAndVehicle($user);

        abort_if(! $vehicle, 422, 'Không tìm thấy xe.');

        $this->service->deleteReminder($vehicle, $reminder);

        return $this->ok(['deleted' => true]);
    }

    /** Ensure the item belongs to a vehicle accessible by the current driver. */
    private function authorizeItem(Request $request, VehicleMaintenanceItem $item): void
    {
        $user = $request->user();
        ['vehicle' => $vehicle] = $this->service->resolveDriverAndVehicle($user);

        abort_if(! $vehicle || $item->vehicle_id !== $vehicle->id, 403);
    }
}
