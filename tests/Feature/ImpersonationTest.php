<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use App\Services\CmsUserInfoService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->mock(CmsUserInfoService::class, function ($mock) {
            $mock->shouldReceive('findCmsUserIdByEmail')->andReturn(null);
        });
    }

    private function account(array $roles): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($roles);

        return $user;
    }

    private function impersonator(): User
    {
        return User::factory()->create([
            'email' => 'phongcongnghe@vaschools.edu.vn',
            'is_active' => true,
        ]);
    }

    private function asToken(string $token): self
    {
        Auth::forgetGuards();

        return $this->withToken($token);
    }

    private function start(User $actor, User $target, ?string $role = null): string
    {
        return $this->asToken($actor->createToken('test')->plainTextToken)
            ->postJson('/api/impersonate/'.$target->id, $role ? ['role' => $role] : [])
            ->assertOk()->json('data.token');
    }

    public function test_allowed_email_can_experience_target_role_without_changing_assignments(): void
    {
        $actor = $this->impersonator();
        $target = $this->account(['driver', 'admin']);
        $target->givePermissionTo('report.export');
        $token = $this->start($actor, $target, 'driver');

        $response = $this->asToken($token)->getJson('/api/user')->assertOk()
            ->assertJsonPath('id', $target->id)
            ->assertJsonPath('is_superadmin', false)
            ->assertJsonPath('can_impersonate', false)
            ->assertJsonPath('impersonation.impersonator.id', $actor->id)
            ->assertJsonPath('impersonation.role.name', 'driver')
            ->assertJsonCount(1, 'roles')->assertJsonPath('roles.0.name', 'driver');
        $this->assertContains('trip.view_own', $response->json('permissions'));
        $this->assertContains('report.export', $response->json('permissions'));
        $this->assertNotContains('resource.driver.manage', $response->json('permissions'));
        $this->assertTrue($target->fresh()->hasAllRoles(['driver', 'admin']));
        $this->asToken($token)->postJson('/api/drivers', ['full_name' => 'Forbidden'])->assertForbidden();
        $this->assertDatabaseMissing('drivers', ['full_name' => 'Forbidden']);
        $this->assertEqualsWithDelta(now()->addHours(2)->timestamp, PersonalAccessToken::findToken($token)->expires_at->timestamp, 3);
    }

    public function test_other_accounts_including_super_admin_cannot_start(): void
    {
        $target = $this->account(['driver']);
        foreach (['superadmin', 'admin', 'dispatcher', 'driver'] as $role) {
            $actor = $this->account([$role]);
            $this->asToken($actor->createToken('test')->plainTextToken)
                ->postJson('/api/impersonate/'.$target->id)->assertForbidden();
        }
    }

    public function test_profile_exposes_impersonation_access_only_for_allowed_email(): void
    {
        $allowed = $this->impersonator();
        $this->asToken($allowed->createToken('test')->plainTextToken)->getJson('/api/user')
            ->assertOk()->assertJsonPath('can_impersonate', true);

        $other = $this->account(['superadmin']);
        $this->asToken($other->createToken('test')->plainTextToken)->getJson('/api/user')
            ->assertOk()->assertJsonPath('is_superadmin', true)->assertJsonPath('can_impersonate', false);

        $other->update(['email' => 'hiennn@vaschools.edu.vn']);
        $this->asToken($other->createToken('test')->plainTextToken)
            ->postJson('/api/impersonate/'.$this->account(['driver'])->id)->assertForbidden();
    }

    public function test_inactive_allowed_account_cannot_start(): void
    {
        $actor = $this->impersonator();
        $actor->update(['is_active' => false]);
        $this->asToken($actor->createToken('test')->plainTextToken)
            ->postJson('/api/impersonate/'.$this->account(['driver'])->id)->assertUnauthorized();
    }

    public function test_self_super_admin_and_inactive_targets_are_rejected(): void
    {
        $actor = $this->impersonator();
        $original = $actor->createToken('test')->plainTextToken;
        $this->asToken($original)->postJson('/api/impersonate/'.$actor->id)->assertForbidden();
        $other = $this->account(['superadmin']);
        $this->asToken($original)->postJson('/api/impersonate/'.$other->id)->assertForbidden();
        $bootstrap = $this->account(['driver']);
        $bootstrap->update(['email' => 'hiennn@vaschools.edu.vn']);
        $this->asToken($original)->postJson('/api/impersonate/'.$bootstrap->id)->assertForbidden();
        $inactive = $this->account(['driver']);
        $inactive->update(['is_active' => false]);
        $this->asToken($original)->postJson('/api/impersonate/'.$inactive->id)->assertUnprocessable();
    }

    public function test_role_must_exist_on_target_and_multirole_requires_selection(): void
    {
        $actor = $this->impersonator();
        $original = $actor->createToken('test')->plainTextToken;
        $target = $this->account([]);
        $this->asToken($original)->postJson('/api/impersonate/'.$target->id, ['role' => 'driver'])->assertUnprocessable();
        $target->assignRole(['driver', 'admin']);
        $this->asToken($original)->postJson('/api/impersonate/'.$target->id)->assertUnprocessable();
        $this->asToken($original)->postJson('/api/impersonate/'.$target->id, ['role' => 'dispatcher'])->assertUnprocessable();
        $this->assertTrue($target->fresh()->hasAllRoles(['driver', 'admin']));
    }

    public function test_user_without_role_is_impersonated_as_portal_requester(): void
    {
        $actor = $this->impersonator();
        $target = $this->account([]);
        $token = $this->start($actor, $target);

        $this->asToken($token)->getJson('/api/user')->assertOk()
            ->assertJsonPath('id', $target->id)
            ->assertJsonPath('is_superadmin', false)
            ->assertJsonPath('can_impersonate', false)
            ->assertJsonPath('impersonation.portal', true)
            ->assertJsonPath('impersonation.role.label', 'Portal người yêu cầu')
            ->assertJsonCount(0, 'roles');
        $this->asToken($token)->getJson('/api/portal/dispatch-requests')->assertOk();
        $this->asToken($token)->getJson('/api/vehicles')->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['event' => 'impersonation.started', 'actor_id' => $actor->id, 'auditable_id' => $target->id]);
    }

    public function test_staff_can_be_impersonated_as_portal_without_staff_access(): void
    {
        $actor = $this->impersonator();
        $target = $this->account(['dispatcher']);
        $token = $this->asToken($actor->createToken('test')->plainTextToken)
            ->postJson('/api/impersonate/'.$target->id, ['portal' => true])->assertOk()->json('data.token');

        $this->asToken($token)->getJson('/api/user')->assertOk()
            ->assertJsonPath('impersonation.portal', true)->assertJsonCount(0, 'roles');
        $this->asToken($token)->getJson('/api/portal/dispatch-requests')->assertOk();
        $this->asToken($token)->getJson('/api/vehicles')->assertForbidden();
        $this->assertTrue($target->fresh()->hasRole('dispatcher'));
    }

    public function test_nested_impersonation_is_forbidden(): void
    {
        $actor = $this->impersonator();
        $token = $this->start($actor, $this->account(['admin']));
        $target = $this->account(['driver']);
        $this->asToken($token)->postJson('/api/impersonate/'.$target->id)->assertForbidden();
    }

    public function test_leave_revokes_only_impersonation_token_and_original_still_works(): void
    {
        $actor = $this->impersonator();
        $original = $actor->createToken('original')->plainTextToken;
        $target = $this->account(['driver']);
        $token = $this->asToken($original)->postJson('/api/impersonate/'.$target->id)->assertOk()->json('data.token');
        $this->asToken($token)->postJson('/api/impersonate/leave')->assertOk();
        $this->assertNull(PersonalAccessToken::findToken($token));
        $this->asToken($token)->getJson('/api/user')->assertUnauthorized();
        $this->asToken($original)->getJson('/api/user')->assertOk()
            ->assertJsonPath('id', $actor->id)->assertJsonPath('impersonation', null);
        $this->assertDatabaseHas('audit_logs', ['event' => 'impersonation.started', 'actor_id' => $actor->id, 'auditable_id' => $target->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'impersonation.ended', 'actor_id' => $actor->id, 'auditable_id' => $target->id]);
    }

    public function test_expired_token_and_revoked_actor_access_are_rejected(): void
    {
        $actor = $this->impersonator();
        $target = $this->account(['driver']);
        $token = $this->start($actor, $target);
        PersonalAccessToken::findToken($token)->update(['expires_at' => now()->subMinute()]);
        $this->asToken($token)->getJson('/api/user')->assertUnauthorized();
        $token = $this->start($actor, $target);
        $actor->assignRole('superadmin');
        $actor->update(['email' => 'former-impersonator@example.test']);
        $this->asToken($token)->getJson('/api/user')->assertUnauthorized();
        $this->asToken($token)->postJson('/api/impersonate/leave')->assertOk();
    }

    public function test_target_role_removal_invalidates_impersonation(): void
    {
        $actor = $this->impersonator();
        $target = $this->account(['driver']);
        $token = $this->start($actor, $target);
        $target->removeRole('driver');
        $this->asToken($token)->getJson('/api/driver/summary')->assertUnauthorized();
    }

    public function test_driver_sees_only_their_trips_and_writes_log_the_impersonator(): void
    {
        $actor = $this->impersonator();
        $target = $this->account(['driver']);
        $driver = Driver::create(['user_id' => $target->id, 'full_name' => $target->name]);
        $request = DispatchRequest::create([
            'requester_id' => $actor->id, 'trip_type' => 'cargo', 'origin' => 'A', 'destination' => 'B',
            'depart_at' => now(), 'status' => 'approved', 'source_channel' => 'portal',
        ]);
        $trip = Trip::create(['dispatch_request_id' => $request->id, 'driver_id' => $driver->id, 'depart_at' => now(), 'status' => 'assigned']);
        $otherRequest = $request->replicate();
        $otherRequest->save();
        Trip::create(['dispatch_request_id' => $otherRequest->id, 'depart_at' => now(), 'status' => 'assigned']);
        $token = $this->start($actor, $target);
        $date = now()->toDateString();
        $this->asToken($token)->getJson('/api/driver/trips?date_from='.$date.'&date_to='.$date)
            ->assertOk()->assertJsonPath('data.meta.total', 1)->assertJsonPath('data.items.0.id', $trip->id);
        $this->asToken($token)->postJson('/api/trips/'.$trip->id.'/status', ['status' => 'driver_confirmed', 'lock_version' => 0])->assertOk();
        $log = AuditLog::where('event', 'api.request')->latest('id')->firstOrFail();
        $this->assertSame($target->id, $log->actor_id);
        $this->assertSame($actor->id, $log->metadata['impersonation']['impersonator']['id']);
    }

    public function test_ordinary_session_cannot_leave_and_cannot_spoof_role_header(): void
    {
        $user = $this->account(['driver']);
        $token = $user->createToken('test')->plainTextToken;
        $this->asToken($token)->postJson('/api/impersonate/leave')->assertUnprocessable();
        $this->asToken($token)->withHeader('X-Simulated-Role', 'superadmin')
            ->postJson('/api/drivers', ['full_name' => 'Forbidden'])->assertForbidden();
    }
}
