<?php

namespace App\Repositories;

use App\Models\FeatureToggle;
use Illuminate\Database\Eloquent\Collection;

class FeatureToggleRepository
{
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
        return FeatureToggle::query()->where('key', $key)->first();
    }

    public function create(array $data): FeatureToggle
    {
        return FeatureToggle::query()->create($data);
    }

    public function update(FeatureToggle $toggle, array $data): FeatureToggle
    {
        $toggle->fill($data);
        $toggle->save();

        return $toggle->refresh();
    }

    public function delete(FeatureToggle $toggle): void
    {
        $toggle->delete();
    }
}
