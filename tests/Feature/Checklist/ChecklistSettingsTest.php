<?php

namespace Tests\Feature\Checklist;

use App\Models\ChecklistTemplate;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_manages_items_and_reminder_slots(): void
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('dispatcher');
        $this->actingAs($user);

        $template = ChecklistTemplate::query()->where('code', ChecklistTemplate::CODE_DAILY)->firstOrFail();
        $version = $template->version;

        $list = $this->getJson('/api/checklist-settings')->assertOk();
        $this->assertCount(2, $list->json('data.templates'));

        $item = $this->postJson("/api/checklist-settings/templates/{$template->id}/items", [
            'group_name' => 'An toàn vận hành',
            'title' => 'Kính chắn gió',
            'severity' => 'critical',
            'ng_action' => 'Không khởi hành',
        ])->assertCreated();
        $this->assertSame($version + 1, $template->fresh()->version);

        $this->patchJson("/api/checklist-settings/templates/{$template->id}/items/{$item->json('data.id')}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $slot = $this->postJson('/api/checklist-settings/slots', [
            'template_id' => $template->id,
            'remind_at' => '13:15',
            'weekdays' => [5, 1, 1],
            'escalate_after_minutes' => 30,
        ])->assertCreated();
        $this->assertSame([1, 5], $slot->json('data.weekdays'));

        $this->postJson('/api/checklist-settings/slots', ['template_id' => $template->id, 'remind_at' => '25:00', 'weekdays' => [1]])
            ->assertStatus(422);

        $this->deleteJson('/api/checklist-settings/slots/'.$slot->json('data.id'))->assertOk();
    }

    public function test_driver_cannot_manage_settings(): void
    {
        $this->seed(RbacSeeder::class);
        $driver = User::factory()->create(['is_active' => true]);
        $driver->assignRole('driver');

        $this->actingAs($driver)->postJson('/api/checklist-settings/slots', [
            'template_id' => 1, 'remind_at' => '06:00', 'weekdays' => [1],
        ])->assertForbidden();
    }
}
