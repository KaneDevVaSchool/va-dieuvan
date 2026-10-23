<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateUserProfileRequest;
use App\Services\FeatureToggleService;
use App\Services\Hr\UserWorkInfoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserProfileController extends Controller
{
    public function show(Request $request, FeatureToggleService $featureToggles): JsonResponse
    {
        $user = $request->user()->load(['roles:id,name,display_name,guard_name', 'department:id,name,code']);
        $permissions = $user->getAllPermissions()->pluck('name')->unique()->values()->all();
        $payload = array_merge($user->toArray(), [
            'permissions' => $permissions,
            'is_superadmin' => $user->isSuperAdmin(),
            'can_impersonate' => $user->canImpersonate(),
            'feature_toggles' => $featureToggles->mapForUser($user),
            'feature_toggle_states' => $featureToggles->mapStatesForRuntime(),
            'impersonation' => $request->attributes->get('impersonation'),
        ]);

        $payload['work_info'] = app(UserWorkInfoService::class)->forUser($user);

        return response()->json($payload);
    }

    public function update(UpdateUserProfileRequest $request, FeatureToggleService $featureToggles): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $localUpdates = [];
        if (array_key_exists('phone', $validated)) {
            $localUpdates['phone'] = ($validated['phone'] === '' || $validated['phone'] === null)
                ? null
                : $validated['phone'];
        }

        if ($request->hasFile('avatar')) {
            $disk = Storage::disk('public');
            $dir = 'avatars/'.$user->id;
            if ($disk->exists($dir)) {
                foreach ($disk->files($dir) as $existing) {
                    $disk->delete($existing);
                }
            }
            $storedPath = $disk->putFile($dir, $request->file('avatar'));
            $localUpdates['avatar_url'] = $disk->url($storedPath);
        }

        if ($localUpdates !== []) {
            DB::transaction(function () use ($user, $localUpdates) {
                $user->fill($localUpdates)->save();
                if (array_key_exists('phone', $localUpdates)) {
                    $user->hrEmployee()->update(['phone' => $localUpdates['phone'], 'updated_at' => now()]);
                }
            });
        }

        $user->refresh()->load(['roles:id,name,display_name,guard_name', 'department:id,name,code']);
        $permissions = $user->getAllPermissions()->pluck('name')->unique()->values()->all();
        $payload = array_merge($user->toArray(), [
            'permissions' => $permissions,
            'is_superadmin' => $user->isSuperAdmin(),
            'can_impersonate' => $user->canImpersonate(),
            'feature_toggles' => $featureToggles->mapForUser($user),
            'feature_toggle_states' => $featureToggles->mapStatesForRuntime(),
            'impersonation' => $request->attributes->get('impersonation'),
        ]);
        $payload['work_info'] = app(UserWorkInfoService::class)->forUser($user);

        return response()->json($payload);
    }
}
