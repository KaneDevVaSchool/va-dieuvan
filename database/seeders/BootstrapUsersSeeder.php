<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Tạo/cập nhật user theo email Google trước khi họ đăng nhập OAuth lần đầu.
 *
 * php artisan db:seed --class=BootstrapUsersSeeder
 */
class BootstrapUsersSeeder extends Seeder
{
    private const GUARD = 'web';

    public function run(): void
    {
        $entries = config('bootstrap_users', []);
        if (! is_array($entries) || $entries === []) {
            $this->command?->warn('BootstrapUsersSeeder: bootstrap_users trống — bỏ qua.');

            return;
        }

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $email = isset($entry['email']) && is_string($entry['email'])
                ? Str::lower(trim($entry['email']))
                : '';
            $roleName = isset($entry['role']) && is_string($entry['role'])
                ? trim($entry['role'])
                : '';

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->command?->warn('BootstrapUsersSeeder: bỏ qua email không hợp lệ.');

                continue;
            }

            if ($roleName === '') {
                $this->command?->warn("BootstrapUsersSeeder: bỏ qua {$email} — thiếu role.");

                continue;
            }

            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', self::GUARD)
                ->first();

            if (! $role) {
                $this->command?->warn("BootstrapUsersSeeder: role «{$roleName}» chưa có — chạy RbacSeeder trước.");

                continue;
            }

            $displayName = $this->resolveDisplayName($entry, $email);

            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            if (! $user) {
                $user = User::query()->create([
                    'name' => $displayName,
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]);
                $this->command?->info("BootstrapUsersSeeder: tạo user {$email}");
            } else {
                $updates = [];
                if ($user->name === '' || $user->name === null) {
                    $updates['name'] = $displayName;
                }
                if (isset($user->is_active) && ! $user->is_active) {
                    $updates['is_active'] = true;
                }
                if ($updates !== []) {
                    $user->forceFill($updates)->save();
                }
                $this->command?->info("BootstrapUsersSeeder: cập nhật user {$email}");
            }

            $user->syncRoles([$role]);
            $user->forceFill([
                'primary_role_name' => $role->name,
                'primary_role_id' => $role->id,
            ])->save();

            if ($roleName === 'driver') {
                $this->ensureDriverProfile($user);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function resolveDisplayName(array $entry, string $email): string
    {
        $name = $entry['name'] ?? null;
        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        $local = strstr($email, '@', true) ?: $email;

        return Str::title(str_replace(['.', '_', '-'], ' ', $local));
    }

    private function ensureDriverProfile(User $user): void
    {
        $email = Str::lower(trim((string) $user->email));

        $driver = Driver::query()->where('user_id', $user->id)->first();
        if (! $driver && $email !== '') {
            $driver = Driver::query()
                ->whereNull('user_id')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
        }

        $payload = [
            'user_id' => $user->id,
            'full_name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'employment_status' => 'active',
            'availability_status' => 'available',
        ];

        if ($driver) {
            $driver->forceFill($payload)->save();

            return;
        }

        Driver::query()->create($payload);
    }
}
