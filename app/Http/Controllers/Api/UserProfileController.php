<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateUserProfileRequest;
use App\Services\CmsUserInfoService;
use App\Services\FeatureToggleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class UserProfileController extends Controller
{
    public function show(Request $request, CmsUserInfoService $cms, FeatureToggleService $featureToggles): JsonResponse
    {
        $user = $request->user()->load(['roles:id,name,display_name,guard_name']);
        $permissions = $user->getAllPermissions()->pluck('name')->unique()->values()->all();
        $payload = array_merge($user->toArray(), [
            'permissions' => $permissions,
            'is_superadmin' => $user->isSuperAdmin(),
            'feature_toggles' => $featureToggles->mapForUser($user),
            'feature_toggle_states' => $featureToggles->mapStatesForRuntime(),
        ]);

        $cmsUserId = $cms->findCmsUserIdByEmail($user->email);
        $payload['cms_user_info'] = $cmsUserId !== null
            ? $cms->getLatestUserInfoRow($cmsUserId)
            : null;

        return response()->json($payload);
    }

    public function update(UpdateUserProfileRequest $request, CmsUserInfoService $cms, FeatureToggleService $featureToggles): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $localUpdates = [];
        if (array_key_exists('phone', $validated)) {
            $localUpdates['phone'] = ($validated['phone'] === '' || $validated['phone'] === null)
                ? null
                : $validated['phone'];
        }
        if (array_key_exists('employee_code', $validated)) {
            $localUpdates['employee_code'] = ($validated['employee_code'] === '' || $validated['employee_code'] === null)
                ? null
                : $validated['employee_code'];
        }

        $cmsUserId = $cms->findCmsUserIdByEmail($user->email);

        $cmsSyncWarning = null;
        try {
            if ($cmsUserId !== null && $cms->isAvailable()) {
                $cms->updateUserInfo($cmsUserId, $validated);
            }
        } catch (Throwable $e) {
            report($e);
            $cmsSyncWarning = 'Không ghi được bảng user_info trên CMS; đã cập nhật bản cục bộ.';
        }

        if ($localUpdates !== []) {
            $user->fill($localUpdates);
            $user->save();
        }

        $user->refresh()->load(['roles:id,name,display_name,guard_name']);
        $permissions = $user->getAllPermissions()->pluck('name')->unique()->values()->all();
        $payload = array_merge($user->toArray(), [
            'permissions' => $permissions,
            'is_superadmin' => $user->isSuperAdmin(),
            'feature_toggles' => $featureToggles->mapForUser($user),
            'feature_toggle_states' => $featureToggles->mapStatesForRuntime(),
        ]);
        $payload['cms_user_info'] = $cmsUserId !== null
            ? $cms->getLatestUserInfoRow($cmsUserId)
            : null;
        if ($cmsSyncWarning !== null) {
            $payload['cms_sync_warning'] = $cmsSyncWarning;
        }

        return response()->json($payload);
    }
}
