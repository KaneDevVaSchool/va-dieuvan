<?php

namespace App\Services;

use App\Models\FeatureToggle;
use App\Models\User;
use App\Repositories\FeatureToggleRepository;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;

class FeatureToggleService
{
    public function __construct(
        protected FeatureToggleRepository $repository
    ) {}

    /**
     * Danh sách cho màn admin — luôn đọc DB (tránh cache cũ làm danh sách trống sau khi seed).
     */
    public function allFresh(): array
    {
        return $this->mapRows($this->repository->allOrdered());
    }

    /**
     * Danh sách cache 24h cho runtime (menu, mapForUser, isEnabled).
     */
    public function allCached(): array
    {
        return Cache::remember(
            config('feature.cache_key'),
            now()->addHours(24),
            fn () => $this->mapRows($this->repository->allOrdered())
        );
    }

    /**
     * @param EloquentCollection<int, FeatureToggle> $rows
     * @return list<array{id:int,key:string,name:string,is_enabled:bool,maintenance_mode:bool,upgrade_notice:bool,module:?string}>
     */
    protected function mapRows(EloquentCollection $rows): array
    {
        return $rows->map(fn (FeatureToggle $t) => [
            'id' => $t->id,
            'key' => $t->key,
            'name' => $t->name,
            'is_enabled' => $t->is_enabled,
            'maintenance_mode' => (bool) $t->maintenance_mode,
            'upgrade_notice' => (bool) $t->upgrade_notice,
            'module' => $t->module,
        ])->values()->all();
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
            $out[$row['key']] = (bool) $row['is_enabled'];
        }

        return $out;
    }

    /**
     * Trạng thái đầy đủ từ cache (bật menu, bảo trì, nâng cấp) — dùng banner runtime, không phụ thuộc superadmin.
     *
     * @return array<string, array{is_enabled: bool, maintenance_mode: bool, upgrade_notice: bool, name: string}>
     */
    public function mapStatesForRuntime(): array
    {
        $out = [];
        foreach ($this->allCached() as $row) {
            $k = $row['key'];
            $out[$k] = [
                'is_enabled' => (bool) ($row['is_enabled'] ?? false),
                'maintenance_mode' => (bool) ($row['maintenance_mode'] ?? false),
                'upgrade_notice' => (bool) ($row['upgrade_notice'] ?? false),
                'name' => (string) ($row['name'] ?? ''),
            ];
        }

        return $out;
    }

    public function create(array $data): FeatureToggle
    {
        $toggle = $this->repository->create($data);
        $this->clearCache();

        return $toggle;
    }

    public function update(FeatureToggle $toggle, array $data): FeatureToggle
    {
        $result = $this->repository->update($toggle, $data);
        $this->clearCache();

        return $result;
    }

    public function delete(FeatureToggle $toggle): void
    {
        $this->repository->delete($toggle);
        $this->clearCache();
    }
}
