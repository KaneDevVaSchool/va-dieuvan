<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\System\ListMenuItemsRequest;
use App\Http\Requests\Api\System\MenuMeRequest;
use App\Http\Requests\Api\System\ReorderMenuItemsRequest;
use App\Http\Requests\Api\System\StoreMenuItemRequest;
use App\Http\Requests\Api\System\UpdateMenuItemRequest;
use App\Models\MenuItem;
use App\Services\System\MenuService;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    use ApiResponses;

    public function me(MenuMeRequest $request, MenuService $menu): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user, 401);

        return $this->ok(['items' => $menu->treeForUser($user)]);
    }

    public function index(ListMenuItemsRequest $request): JsonResponse
    {
        $items = MenuItem::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $this->ok(['items' => $items]);
    }

    public function store(StoreMenuItemRequest $request): JsonResponse
    {
        $item = MenuItem::create([
            ...$request->validated(),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return $this->created($item);
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): JsonResponse
    {
        $menuItem->update([
            ...$request->validated(),
            'updated_by' => $request->user()?->id,
        ]);

        return $this->ok($menuItem->fresh());
    }

    public function destroy(ListMenuItemsRequest $request, MenuItem $menuItem): JsonResponse
    {
        $menuItem->delete();

        return $this->ok(['deleted' => true]);
    }

    public function reorder(ReorderMenuItemsRequest $request, MenuService $menu): JsonResponse
    {
        $menu->reorder($request->validated('items'));

        return $this->ok(['reordered' => true]);
    }
}
