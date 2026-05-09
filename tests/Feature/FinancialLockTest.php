<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\TripCost;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinancialLockTest extends TestCase
{
    use RefreshDatabase;

    private function seedAndMakeUser(string $roleName): User
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user->refresh();
    }

    private function makeTrip(User $dispatcher, array $overrides = []): Trip
    {
        $req = DispatchRequest::create([
            'requester_id' => $dispatcher->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => Carbon::parse('2026-04-10 10:00:00'),
            'status' => 'approved',
            'approved_by' => $dispatcher->id,
            'source_channel' => 'portal',
            'paper_status' => 'pending',
        ]);

        return Trip::create(array_merge([
            'dispatch_request_id' => $req->id,
            'dispatcher_id' => $dispatcher->id,
            'status' => 'assigned',
            'depart_at' => $req->depart_at,
            'arrive_by' => Carbon::parse('2026-04-10 14:00:00'),
            'lock_version' => 0,
            'payment_status' => 'unpaid',
        ], $overrides));
    }

    public function test_cannot_submit_cost_when_trip_is_paid(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 08:00:00'));

        $dispatcher = $this->seedAndMakeUser('dispatcher');

        $trip = $this->makeTrip($dispatcher, ['payment_status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($dispatcher);
        $res = $this->postJson("/api/trips/{$trip->id}/costs", [
            'type' => 'fuel',
            'amount' => 100000,
        ]);

        $res->assertStatus(409);
    }

    public function test_cannot_confirm_cost_when_trip_is_paid(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 08:00:00'));

        $dispatcher = $this->seedAndMakeUser('dispatcher');

        $trip = $this->makeTrip($dispatcher, ['payment_status' => 'unpaid']);

        $cost = TripCost::create([
            'trip_id' => $trip->id,
            'created_by' => $dispatcher->id,
            'type' => 'fuel',
            'amount' => 50000,
            'currency' => 'VND',
            'status' => 'submitted',
        ]);

        $trip->update(['payment_status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($dispatcher);
        $res = $this->postJson("/api/trip-costs/{$cost->id}/decision", [
            'decision' => 'confirm',
        ]);

        $res->assertStatus(409);
    }

    public function test_can_confirm_submitted_cost_when_trip_completed_and_unpaid(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 08:00:00'));

        $dispatcher = $this->seedAndMakeUser('dispatcher');

        $trip = $this->makeTrip($dispatcher, ['status' => 'completed']);

        $cost = TripCost::create([
            'trip_id' => $trip->id,
            'created_by' => $dispatcher->id,
            'type' => 'fuel',
            'amount' => 50000,
            'currency' => 'VND',
            'status' => 'submitted',
        ]);

        $this->actingAs($dispatcher);
        $res = $this->postJson("/api/trip-costs/{$cost->id}/decision", [
            'decision' => 'confirm',
        ]);

        $res->assertOk();
        $cost->refresh();
        $this->assertSame('confirmed', $cost->status);
    }

    public function test_cannot_assign_when_trip_is_paid(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 08:00:00'));

        $dispatcher = $this->seedAndMakeUser('dispatcher');
        $trip = $this->makeTrip($dispatcher, ['payment_status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($dispatcher);
        $res = $this->postJson("/api/trips/{$trip->id}/assign", [
            'lock_version' => 0,
        ]);

        $res->assertStatus(409);
    }

    public function test_cannot_upload_attachment_to_confirmed_trip_cost(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 08:00:00'));

        $dispatcher = $this->seedAndMakeUser('dispatcher');
        $trip = $this->makeTrip($dispatcher);

        $cost = TripCost::create([
            'trip_id' => $trip->id,
            'created_by' => $dispatcher->id,
            'type' => 'fuel',
            'amount' => 50000,
            'currency' => 'VND',
            'status' => 'confirmed',
            'confirmed_by' => $dispatcher->id,
            'confirmed_at' => now(),
        ]);

        Storage::fake('public');

        $this->actingAs($dispatcher);
        $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

        $res = $this->post('/api/attachments', [
            'attachable_type' => 'trip_cost',
            'attachable_id' => $cost->id,
            'kind' => 'receipt',
            'file' => $file,
        ]);

        $res->assertStatus(409);
    }

    public function test_cargo_store_generates_tracking_code(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 08:00:00'));

        $user = $this->seedAndMakeUser('dispatcher');
        $this->actingAs($user);

        $res = $this->postJson('/api/cargo-shipments', [
            'pickup_address' => 'P1',
            'delivery_address' => 'D1',
        ]);

        $res->assertCreated();
        $this->assertMatchesRegularExpression('/^CGO-\d{8}$/', $res->json('data.tracking_code'));
    }
}
