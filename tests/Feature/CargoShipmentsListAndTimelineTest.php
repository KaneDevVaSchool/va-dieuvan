<?php

namespace Tests\Feature;

use App\Models\CargoShipment;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CargoShipmentsListAndTimelineTest extends TestCase
{
    use RefreshDatabase;

    private function seedDispatcher(): User
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('dispatcher');

        return $user->refresh();
    }

    public function test_list_filters_by_created_at_range_and_search(): void
    {
        $user = $this->seedDispatcher();
        $this->actingAs($user);

        Carbon::setTestNow(Carbon::parse('2026-01-10 12:00:00'));
        CargoShipment::create([
            'pickup_address' => 'CS QTB',
            'delivery_address' => 'CS Q11',
            'tracking_code' => 'CGO-TEST-01',
            'status' => 'pending',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-15 12:00:00'));
        CargoShipment::create([
            'pickup_address' => 'HCM',
            'delivery_address' => 'BD',
            'tracking_code' => 'CGO-TEST-02',
            'status' => 'delivered',
        ]);
        Carbon::setTestNow();

        $jan = $this->getJson('/api/cargo-shipments?from=2026-01-01&to=2026-01-31');
        $jan->assertOk();
        $this->assertSame(1, $jan->json('data.meta.total'));

        $q = $this->getJson('/api/cargo-shipments?q=CGO-TEST-02');
        $q->assertOk();
        $this->assertSame(1, $q->json('data.meta.total'));
        $this->assertSame('CGO-TEST-02', $q->json('data.items.0.tracking_code'));
    }

    public function test_timeline_includes_milestones(): void
    {
        $user = $this->seedDispatcher();
        $this->actingAs($user);

        Carbon::setTestNow(Carbon::parse('2026-04-10 09:00:00'));
        $s = CargoShipment::create([
            'pickup_address' => 'A',
            'delivery_address' => 'B',
            'status' => 'delivered',
            'sla_due_at' => Carbon::parse('2026-04-10 12:00:00'),
            'picked_up_at' => Carbon::parse('2026-04-10 10:00:00'),
            'delivered_at' => Carbon::parse('2026-04-10 11:00:00'),
        ]);
        Carbon::setTestNow();

        $res = $this->getJson("/api/cargo-shipments/{$s->id}/timeline");
        $res->assertOk();
        $kinds = collect($res->json('data.items'))->pluck('kind')->all();
        $this->assertContains('milestone', $kinds);
        $codes = collect($res->json('data.items'))->where('kind', 'milestone')->pluck('code')->all();
        $this->assertContains('created', $codes);
        $this->assertContains('sla_due', $codes);
        $this->assertContains('picked_up', $codes);
        $this->assertContains('delivered', $codes);
    }
}
