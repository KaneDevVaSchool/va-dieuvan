<?php

namespace App\Repositories;

use App\Models\FeatureToggle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class FeatureToggleRepository
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_KEY_ALL = 'feature_toggles:all';
    private const CACHE_KEY_PREFIX = 'feature_toggle:key:';

    public function allOrdered(): Collection
    {
        return FeatureToggle::query()
            ->orderBy('module')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?FeatureToggle
    {
        return FeatureToggle::query()->find($id);
    }

    public function findByKey(string $key): ?FeatureToggle
    {
        return Cache::remember(
            self::CACHE_KEY_PREFIX.$key,
            self::CACHE_TTL,
            fn () => FeatureToggle::query()->where('key', $key)->first()
        );
    }

    public function create(array $data): FeatureToggle
    {
        $toggle = FeatureToggle::query()->create($data);
        $this->flushCache($toggle->key ?? null);

        return $toggle;
    }

    public function update(FeatureToggle $toggle, array $data): FeatureToggle
    {
        $toggle->fill($data);
        $toggle->save();

        $this->flushCache($toggle->key ?? null);

        return $toggle->refresh();
    }

    public function delete(FeatureToggle $toggle): void
    {
        $this->flushCache($toggle->key ?? null);
        $toggle->delete();
    }

    private function flushCache(?string $key): void
    {
        Cache::forget(self::CACHE_KEY_ALL);
        if ($key !== null) {
            Cache::forget(self::CACHE_KEY_PREFIX.$key);
        }
    }
}
