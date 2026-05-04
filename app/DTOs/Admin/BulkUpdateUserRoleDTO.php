<?php

namespace App\DTOs\Admin;

use App\Http\Requests\Api\Admin\BulkUpdateUserRolesRequest;

final class BulkUpdateUserRoleDTO
{
    /**
     * @param  array<int>  $userIds
     * @param  array<string>  $roles
     */
    public function __construct(
        public array $userIds,
        public array $roles,
        public string $action,
    ) {}

    public static function fromRequest(BulkUpdateUserRolesRequest $request): self
    {
        $v = $request->validated();

        return new self(
            userIds: array_map(static fn ($id) => (int) $id, array_values($v['user_ids'])),
            roles: array_values($v['roles']),
            action: (string) $v['action'],
        );
    }
}
