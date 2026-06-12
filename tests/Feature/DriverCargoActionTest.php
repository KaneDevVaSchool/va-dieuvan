<?php

namespace Tests\Feature;

use App\Models\CargoShipment;
use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriverCargoActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_marks_pickup_then_delivered_and_uploads_pod(): void
    {
        Storage::fake('public');
        $this->seed(RbacSeeder::class);

        [$driver, , $shipment] = $this->makeCargoTrip();

        $this->actingAs($driver->user);

        // Đã nhận hàng
        $this->postJson("/api/driver/cargo-shipments/{$shipment->id}/status", ['status' => 'picked_up'])
            ->assertOk()
            ->assertJsonPath('data.shipment.status', 'picked_up');
        $shipment->refresh();
        $this->assertNotNull($shipment->picked_up_at);

        // Đã giao hàng
        $this->postJson("/api/driver/cargo-shipments/{$shipment->id}/status", ['status' => 'delivered'])
            ->assertOk()
            ->assertJsonPath('data.shipment.status', 'delivered');
        $shipment->refresh();
        $this->assertNotNull($shipment->delivered_at);

        // Ảnh minh chứng
        $this->postJson("/api/driver/cargo-shipments/{$shipment->id}/pod", [
            'file' => UploadedFile::fake()->image('pod.jpg'),
        ])->assertCreated();

        $this->assertSame(1, $shipment->attachments()->where('kind', 'pod')->count());
    }

    public function test_trip_show_serializes_cargo_shipment_for_driver(): void
    {
        $this->seed(RbacSeeder::class);
        [$driver, $trip, $shipment] = $this->makeCargoTrip();

        $this->actingAs($driver->user);

        $this->getJson("/api/trips/{$trip->id}")
            ->assertOk()
            ->assertJsonPath('data.cargo_shipment.id', $shipment->id)
            ->assertJsonPath('data.cargo_shipment.sender_name', 'Người gửi A')
            ->assertJsonPath('data.cargo_shipment.receiver_name', 'Người nhận B');
    }

    public function test_driver_cannot_set_arbitrary_status(): void
    {
        $this->seed(RbacSeeder::class);
        [$driver, , $shipment] = $this->makeCargoTrip();
        $this->actingAs($driver->user);

        $this->postJson("/api/driver/cargo-shipments/{$shipment->id}/status", ['status' => 'in_transit'])
            ->assertStatus(422);
    }

    public function test_other_driver_cannot_act_on_shipment(): void
    {
        $this->seed(RbacSeeder::class);
        [, , $shipment] = $this->makeCargoTrip();

        $otherUser = User::factory()->create(['is_active' => true]);
        $otherUser->assignRole('driver');
        Driver::query()->create([
            'user_id' => $otherUser->id,
            'full_name' => 'TX Khác',
            'phone' => '0900000222',
            'status' => 'active',
        ]);

        $this->actingAs($otherUser);
        $this->postJson("/api/driver/cargo-shipments/{$shipment->id}/status", ['status' => 'picked_up'])
            ->assertStatus(403);
    }

    /** @return array{0: Driver, 1: Trip, 2: CargoShipment} */
    private function makeCargoTrip(): array
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('driver');
        $driver = Driver::query()->create([
            'user_id' => $user->id,
            'full_name' => 'TX Hàng',
            'phone' => '0900000111',
            'status' => 'active',
        ]);

        $dr = DispatchRequest::create([
            'requester_id' => User::factory()->create()->id,
            'trip_type' => 'cargo',
            'origin' => '806 Âu Cơ',
            'destination' => '252 Lạc Long Quân',
            'depart_at' => now()->addDay(),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => [
                'cargoRows' => [
                    ['name' => 'Kỷ niệm chương', 'pickup_place' => '806 Âu Cơ', 'delivery_place' => '252 Lạc Long Quân'],
                ],
            ],
        ]);

        $trip = Trip::create([
            'dispatch_request_id' => $dr->id,
            'status' => 'in_progress',
            'depart_at' => now()->addDay(),
            'driver_id' => $driver->id,
            'lock_version' => 0,
        ]);

        $shipment = CargoShipment::create([
            'dispatch_request_id' => $dr->id,
            'trip_id' => $trip->id,
            'sender_name' => 'Người gửi A',
            'receiver_name' => 'Người nhận B',
            'pickup_address' => '806 Âu Cơ',
            'delivery_address' => '252 Lạc Long Quân',
            'status' => 'pending',
        ]);

        return [$driver, $trip, $shipment];
    }
}
