<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Models\User;
use App\Support\SuperAdminAccess;
use App\Services\FeatureToggleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponses;

    public function login(LoginRequest $request, FeatureToggleService $featureToggles)
    {
        $data = $request->validated();

        /** @var User|null $user */
        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Thông tin đăng nhập không hợp lệ.'],
            ]);
        }

        if (isset($user->is_active) && ! $user->is_active) {
            abort(403, 'Tài khoản đã bị khóa.');
        }

        SuperAdminAccess::ensureRole($user);

        $device = $data['device_name'] ?? 'spa';
        $token = $user->createToken($device)->plainTextToken;

        $user->load(['roles:id,name,display_name,guard_name']);
        $permissions = $user->getAllPermissions()->pluck('name')->unique()->values()->all();

        return $this->ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => array_merge($user->toArray(), [
                'permissions' => $permissions,
                'is_superadmin' => $user->isSuperAdmin(),
                'feature_toggles' => $featureToggles->mapForUser($user),
                'feature_toggle_states' => $featureToggles->mapStatesForRuntime(),
            ]),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->ok(['logged_out' => true]);
    }
}
