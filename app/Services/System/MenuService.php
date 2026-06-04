<?php

namespace App\Services\System;

use App\Models\MenuItem;
use App\Models\User;
use App\Services\FeatureToggleService;
use Illuminate\Support\Collection;

class MenuService
{
    public function __construct(
        private readonly FeatureToggleService $featureToggles,
    ) {}

    /**
     * Cây menu cho user hiện tại (§6.2).
     *
     * @return list<array<string, mixed>>
     */
    public function treeForUser(User $user): array
    {
        if (! MenuItem::query()->exists()) {
            return [];
        }

        $items = MenuItem::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $permissionNames = $user->getAllPermissions()->pluck('name')->flip();

        $filtered = $items->filter(function (MenuItem $item) use ($user, $permissionNames) {
            if ($item->feature_key && ! $this->featureToggles->isEnabled($item->feature_key)) {
                return false;
            }
            if ($item->permission_id && $item->permission) {
                return $permissionNames->has($item->permission->name)
                    || $user->hasRole('superadmin');
            }

            return true;
        });

        return $this->buildTree($filtered);
    }

    /**
     * @param  Collection<int, MenuItem>  $items
     * @return list<array<string, mixed>>
     */
    private function buildTree(Collection $items, ?int $parentId = null): array
    {
        $nodes = [];
        foreach ($items->where('parent_id', $parentId) as $item) {
            $nodes[] = [
                'id' => $item->id,
                'type' => $item->type,
                'label' => $item->label,
                'label_key' => $item->label_key,
                'icon' => $item->icon,
                'route_name' => $item->route_name,
                'url' => $item->url,
                'target' => $item->target,
                'badge_key' => $item->badge_key,
                'feature_key' => $item->feature_key,
                'sort_order' => $item->sort_order,
                'children' => $this->buildTree($items, $item->id),
            ];
        }

        return $nodes;
    }

    /**
     * @param  list<array{id:int,parent_id:?int,sort_order:int}>  $rows
     */
    public function reorder(array $rows): void
    {
        foreach ($rows as $row) {
            MenuItem::query()
                ->where('id', $row['id'])
                ->update([
                    'parent_id' => $row['parent_id'] ?? null,
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                ]);
        }
    }
}
