<?php

use App\Models\FeatureToggle;
use App\Services\FeatureToggleService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        FeatureToggle::updateOrCreate(
            ['key' => 'module.p2p_policy'],
            [
                'name' => 'P2P Policy (/p2p-policy)',
                'module' => 'operations',
                'is_enabled' => true,
                'maintenance_mode' => false,
                'upgrade_notice' => false,
            ],
        );

        app(FeatureToggleService::class)->clearCache();
    }

    public function down(): void
    {
        FeatureToggle::query()->where('key', 'module.p2p_policy')->delete();
        app(FeatureToggleService::class)->clearCache();
    }
};
