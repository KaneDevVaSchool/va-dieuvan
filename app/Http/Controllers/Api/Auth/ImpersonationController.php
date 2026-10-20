<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use App\Support\Auth\ImpersonationToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    use ApiResponses;

    public function start(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor->is_active, 401, 'Tài khoản đã bị khóa.');
        abort_if(ImpersonationToken::current($request), 403, 'Hãy thoát phiên giả lập hiện tại trước.');
        abort_unless($actor->isSuperAdmin(), 403, 'Chỉ Super Admin mới được giả lập người dùng.');
        abort_if($actor->id === $user->id, 403, 'Bạn không thể giả lập chính mình.');
        abort_if($user->isSuperAdmin(), 403, 'Không thể giả lập tài khoản Super Admin.');
        abort_unless($user->is_active, 422, 'Tài khoản này đã bị khóa.');

        $data = $request->validate(['role' => ['nullable', 'string', 'max:64']]);
        $roles = $user->roles;
        abort_if($roles->isEmpty(), 422, 'Người dùng chưa có vai trò để giả lập.');
        $roleName = $data['role'] ?? ($roles->count() === 1 ? $roles->first()->name : null);
        abort_unless($roleName, 422, 'Vui lòng chọn một vai trò để trải nghiệm.');
        $role = $roles->firstWhere('name', $roleName);
        abort_unless($role, 422, 'Vai trò không hợp lệ cho tài khoản này.');

        $token = $user->createToken(ImpersonationToken::NAME, [
            'impersonation', 'impersonator:'.$actor->id, 'as_role:'.$roleName,
        ], now()->addHours(ImpersonationToken::TTL_HOURS));
        $metadata = [
            'active' => true,
            'impersonator' => ImpersonationToken::summary($actor),
            'target' => ImpersonationToken::summary($user),
            'role' => ['name' => $roleName, 'label' => $role->display_name ?? $roleName],
            'expires_at' => $token->accessToken->expires_at->toIso8601String(),
        ];
        app(AuditLogger::class)->log(
            actorId: $actor->id, event: 'impersonation.started', auditable: $user,
            metadata: $metadata,
        );

        return $this->ok([
            'token' => $token->plainTextToken, 'token_type' => 'Bearer', 'impersonation' => $metadata,
        ], 'Đã bắt đầu phiên giả lập.');
    }

    public function leave(Request $request): JsonResponse
    {
        $token = ImpersonationToken::current($request);
        abort_unless($token, 422, 'Không có phiên giả lập đang hoạt động.');
        app(AuditLogger::class)->log(
            actorId: ImpersonationToken::actorId($token), event: 'impersonation.ended', auditable: $request->user(),
            metadata: $request->attributes->get('impersonation'),
        );
        $token->delete();

        return $this->ok(null, 'Đã thoát phiên giả lập.');
    }
}
