<?php

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

final class ImpersonationToken
{
    public const NAME = 'va-dieuvan-impersonate';

    public const TTL_HOURS = 2;

    public static function current(Request $request): ?PersonalAccessToken
    {
        $token = $request->user()?->currentAccessToken();

        return $token instanceof PersonalAccessToken && $token->name === self::NAME ? $token : null;
    }

    public static function actorId(PersonalAccessToken $token): ?int
    {
        $value = self::abilityValue($token, 'impersonator:');

        return $value !== null && ctype_digit($value) && (int) $value > 0 ? (int) $value : null;
    }

    public static function roleName(PersonalAccessToken $token): ?string
    {
        return self::abilityValue($token, 'as_role:');
    }

    public static function summary(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
    }

    private static function abilityValue(PersonalAccessToken $token, string $prefix): ?string
    {
        foreach ($token->abilities ?? [] as $ability) {
            if (is_string($ability) && str_starts_with($ability, $prefix)) {
                return substr($ability, strlen($prefix)) ?: null;
            }
        }

        return null;
    }
}
