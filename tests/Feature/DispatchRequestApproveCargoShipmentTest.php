<?php

namespace Tests\Feature;

use App\Models\CargoShipment;
use App\Models\DispatchRequest;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchRequestApproveCargoShipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_cargo_request_creates_cargo_shipment(): void
    {
        $this->seed(RbacSeeder::class);

        $dispatcher = User::factory()->create();
        $dispatcher->assignRole('dispatcher');

        $departmentHead = User::factory()->create();
        $departmentHead->assignRole('department_head');

        $requester = User::factory()->create();
        $requester->assignRole('internal_user');

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'cargo',
            'origin' => 'Tân Bình',
            'destination' => 'Vũng Tàu',
            'depart_at' => now()->addDays(3),
            'arrive_by' => now()->addDays(4),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => [
                'form' => ['requester_name' => 'Nguyễn A', 'trip_type' => 'cargo'],
                'cargoRows' => [
                    [
                        'name' => 'Hộp',
                        'pickup_place' => 'Kho A',
                        'delivery_place' => 'Kho B',
                        'pickup_contact' => 'An',
                        'delivery_contact' => 'Bình',
                        'qty' => 2,
                    ],
                ],
            ],
        ]);

        $this->actingAs($dispatcher);

        $this->patchJson("/api/dispatch-requests/{$dr->id}/fill-price", [
            'service_price' => 250000,
        ])->assertSuccessful();

        $this->actingAs($departmentHead);

        $res = $this->postJson("/api/dispatch-requests/{$dr->id}/decision", [
            'decision' => 'approve',
        ]);

        $res->assertSuccessful();

        $this->assertSame(1, CargoShipment::query()->where('dispatch_request_id', $dr->id)->count());

        $s = CargoShipment::query()->where('dispatch_request_id', $dr->id)->first();
        $this->assertNotNull($s);
        $this->assertSame('Kho A', $s->pickup_address);
        $this->assertSame('Kho B', $s->delivery_address);
        $this->assertSame('An', $s->sender_name);
        $this->assertSame('Bình', $s->receiver_name);
        $this->assertSame(2, $s->quantity);
        $this->assertNotNull($s->tracking_code);
        $this->assertStringStartsWith('CGO-', (string) $s->tracking_code);
    }

    public function test_approve_non_cargo_does_not_create_cargo_shipment(): void
    {
        $this->seed(RbacSeeder::class);

        $dispatcher = User::factory()->create();
        $dispatcher->assignRole('dispatcher');

        $requester = User::factory()->create();
        $requester->assignRole('internal_user');

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'door_to_door',
            'depart_at' => now()->addDays(3),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
        ]);

        $this->actingAs($dispatcher);

        $this->postJson("/api/dispatch-requests/{$dr->id}/decision", [
            'decision' => 'approve',
        ])->assertSuccessful();

        $this->assertSame(0, CargoShipment::count());
    }
}
