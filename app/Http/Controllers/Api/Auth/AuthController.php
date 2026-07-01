<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Models\User;
use App\Services\FeatureToggleService;
use App\Support\SuperAdminAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponses;

    /** Prefix cache cho mã đổi token một-lần sau OAuth (khớp GoogleAuthController). */
    public const OAUTH_EXCHANGE_CACHE_PREFIX = 'oauth_exchange:';

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

        return $this->ok($this->authPayload($user, $token, $featureToggles));
    }

    /**
     * Đổi mã một-lần (nhận qua URL sau OAuth) lấy token bearer.
     * Token KHÔNG còn truyền trực tiếp trên URL (tránh rò qua log/history/referer).
     */
    public function exchange(Request $request, FeatureToggleService $featureToggles)
    {
        $code = trim((string) $request->input('code', ''));
        if (strlen($code) < 32) {
            throw ValidationException::withMessages(['code' => ['Mã đăng nhập không hợp lệ.']]);
        }

        $userId = Cache::pull(self::OAUTH_EXCHANGE_CACHE_PREFIX.hash('sha256', $code));
        if (! $userId) {
            throw ValidationException::withMessages(['code' => ['Mã đăng nhập đã hết hạn hoặc đã được dùng. Vui lòng đăng nhập lại.']]);
        }

        /** @var User|null $user */
        $user = User::query()->find($userId);
        if (! $user) {
            throw ValidationException::withMessages(['code' => ['Tài khoản không tồn tại.']]);
        }

        if (isset($user->is_active) && ! $user->is_active) {
            abort(403, 'Tài khoản đã bị khóa.');
        }

        SuperAdminAccess::ensureRole($user);

        $token = $user->createToken('web')->plainTextToken;

        return $this->ok($this->authPayload($user, $token, $featureToggles));
    }

    /**
     * @return array<string, mixed>
     */
    private function authPayload(User $user, string $token, FeatureToggleService $featureToggles): array
    {
        $user->load(['roles:id,name,display_name,guard_name']);
        $permissions = $user->getAllPermissions()->pluck('name')->unique()->values()->all();

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => array_merge($user->toArray(), [
                'permissions' => $permissions,
                'is_superadmin' => $user->isSuperAdmin(),
                'feature_toggles' => $featureToggles->mapForUser($user),
                'feature_toggle_states' => $featureToggles->mapStatesForRuntime(),
            ]),
        ];
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->ok(['logged_out' => true]);
    }
}
