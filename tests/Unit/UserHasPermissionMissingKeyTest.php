<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserHasPermissionMissingKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_permission_is_false_when_key_missing_from_database(): void
    {
        $this->seed(RbacSeeder::class);
        Permission::query()->where('name', 'request.create')->delete();

        $user = User::factory()->create();
        $user->assignRole('dispatcher');

        $this->assertFalse($user->hasPermission('request.create'));
    }
}
