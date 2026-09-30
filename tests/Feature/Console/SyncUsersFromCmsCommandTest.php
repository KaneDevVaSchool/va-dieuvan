<?php

namespace Tests\Feature\Console;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SyncUsersFromCmsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.cms' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        DB::purge('cms');

        Schema::connection('cms')->create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password')->nullable();
            $t->string('google_id')->nullable();
            $t->softDeletes();
        });
        Schema::connection('cms')->create('user_info', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('code')->nullable();
            $t->string('phone')->nullable();
        });
    }

    private function cmsUser(string $email, ?string $code = null, ?string $phone = null, string $name = 'CMS Name'): int
    {
        $id = DB::connection('cms')->table('users')->insertGetId([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('x'),
        ]);
        DB::connection('cms')->table('user_info')->insert(['user_id' => $id, 'code' => $code, 'phone' => $phone]);

        return $id;
    }

    public function test_manual_user_is_linked_by_email_keeping_id_and_role(): void
    {
        $this->seed(RbacSeeder::class);
        $manual = User::factory()->create([
            'email' => 'a@vaschools.edu.vn',
            'employee_code' => 'VA012345',
            'phone' => '0909123456',
            'source' => User::SOURCE_MANUAL,
        ]);
        $manual->assignRole('dispatcher');

        $this->cmsUser('a@vaschools.edu.vn', code: 'VA012345', phone: '0911000000', name: 'Nguyễn Văn A');

        $this->artisan('cms:sync-users')->assertSuccessful();

        $user = User::query()->findOrFail($manual->id);
        $this->assertSame(User::SOURCE_CMS, $user->source);
        $this->assertSame('Nguyễn Văn A', $user->name);
        $this->assertSame('0911000000', $user->phone);
        $this->assertTrue($user->hasRole('dispatcher'));
        $this->assertSame(1, User::query()->where('email', 'a@vaschools.edu.vn')->count());
        $this->assertTrue(AuditLog::query()->where('event', 'user.linked_to_cms')->where('auditable_id', $manual->id)->exists());
    }

    public function test_blank_cms_fields_do_not_erase_local_values(): void
    {
        $local = User::factory()->create([
            'email' => 'b@vaschools.edu.vn',
            'employee_code' => 'VA000777',
            'phone' => '0909999999',
            'source' => User::SOURCE_MANUAL,
        ]);

        $this->cmsUser('b@vaschools.edu.vn', code: null, phone: '  ');

        $this->artisan('cms:sync-users')->assertSuccessful();

        $user = $local->fresh();
        $this->assertSame('VA000777', $user->employee_code);
        $this->assertSame('0909999999', $user->phone);
    }

    public function test_cms_values_still_override_when_present(): void
    {
        $local = User::factory()->create(['email' => 'c@vaschools.edu.vn', 'employee_code' => 'OLD']);

        $this->cmsUser('c@vaschools.edu.vn', code: 'NEW');

        $this->artisan('cms:sync-users')->assertSuccessful();

        $this->assertSame('NEW', $local->fresh()->employee_code);
    }

    public function test_new_cms_user_is_created_with_cms_source(): void
    {
        $this->cmsUser('moi@vaschools.edu.vn', code: 'VA1');

        $this->artisan('cms:sync-users')->assertSuccessful();

        $this->assertSame(User::SOURCE_CMS, User::query()->where('email', 'moi@vaschools.edu.vn')->value('source'));
    }

    public function test_same_employee_code_different_email_is_warned_not_merged(): void
    {
        $manual = User::factory()->create([
            'email' => 'dung.ngoai@gmail.com',
            'employee_code' => 'va 011384',
            'source' => User::SOURCE_MANUAL,
        ]);

        $this->cmsUser('dungnh@vaschools.edu.vn', code: 'VA011384');

        $this->artisan('cms:sync-users')
            ->expectsOutputToContain('Possible duplicate')
            ->assertSuccessful();

        $this->assertSame(User::SOURCE_MANUAL, $manual->fresh()->source);
        $this->assertTrue(User::query()->where('email', 'dungnh@vaschools.edu.vn')->exists());
    }

    public function test_dry_run_writes_nothing(): void
    {
        $manual = User::factory()->create(['email' => 'd@vaschools.edu.vn', 'source' => User::SOURCE_MANUAL]);
        $this->cmsUser('d@vaschools.edu.vn', code: 'X');

        $this->artisan('cms:sync-users --dry-run')->assertSuccessful();

        $this->assertSame(User::SOURCE_MANUAL, $manual->fresh()->source);
        $this->assertNull($manual->fresh()->employee_code);
    }
}
