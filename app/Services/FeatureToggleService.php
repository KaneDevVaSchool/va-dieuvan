<?php

namespace App\Services;

use App\Models\FeatureToggle;
use App\Models\User;
use App\Repositories\FeatureToggleRepository;
use Illuminate\Support\Facades\Cache;

class FeatureToggleService
{
    public function __construct(
        protected FeatureToggleRepository $repository
    ) {}

    public function allCached(): array
    {
        return Cache::remember(
            config('feature.cache_key'),
            now()->addHours(24),
            fn () => $this->repository->allOrdered()->map(fn (FeatureToggle $t) => [
                'id' => $t->id,
                'key' => $t->key,
                'name' => $t->name,
                'is_enabled' => $t->is_enabled,
                'module' => $t->module,
            ])->values()->all()
        );
    }

    public function clearCache(): void
    {
        Cache::forget(config('feature.cache_key'));
    }

    public function isEnabled(string $key): bool
    {
        $row = collect($this->allCached())->firstWhere('key', $key);
        if ($row === null) {
            return true;
        }

        return (bool) ($row['is_enabled'] ?? false);
    }

    /**
     * @return array<string, bool>
     */
    public function mapForUser(User $user): array
    {
        $out = [];
        foreach ($this->allCached() as $row) {
            $k = $row['key'];
            $out[$k] = $user->isSuperAdmin() ? true : (bool) $row['is_enabled'];
        }

        return $out;
    }

    public function create(array $data): FeatureToggle
    {
        $t = $this->repository->create($data);
        $this->clearCache();

        return $t;
    }

    public function update(FeatureToggle $toggle, array $data): FeatureToggle
    {
        $t = $this->repository->update($toggle, $data);
        $this->clearCache();

        return $t;
    }

    public function delete(FeatureToggle $toggle): void
    {
        $this->repository->delete($toggle);
        $this->clearCache();
    }
}
