<?php

namespace Tests\Unit\Admin;

use App\DTOs\Admin\BulkUpdateUserRoleDTO;
use App\Http\Requests\Api\Admin\BulkUpdateUserRolesRequest;
use App\Models\User;
use App\Services\Admin\UserRoleService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UserRoleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_assign_invokes_spatie_roles(): void
    {
        $this->seed(RbacSeeder::class);

        $u = User::factory()->create();
        $this->assertFalse($u->hasRole('accountant'));

        $dto = new BulkUpdateUserRoleDTO(
            userIds: [$u->id],
            roles: ['accountant'],
            action: 'assign',
        );

        app(UserRoleService::class)->bulkUpdateRoles($dto);

        $u->refresh();
        $this->assertTrue($u->hasRole('accountant'));
    }

    public function test_bulk_update_request_rules_accept_payload(): void
    {
        $this->seed(RbacSeeder::class);
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $v = Validator::make(
            [
                'user_ids' => [$u1->id, $u2->id],
                'roles' => ['driver'],
                'action' => 'assign',
            ],
            (new BulkUpdateUserRolesRequest())->rules()
        );

        $this->assertFalse($v->fails());
    }
}
