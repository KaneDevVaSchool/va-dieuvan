<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Migrates users with multiple roles to a single primary role.
 *
 * Priority order (highest → lowest):
 *   superadmin → admin → dispatcher → accountant → department_head → driver → internal_user
 *
 * Usage:
 *   php artisan va:migrate-single-role          (dry-run, shows what would change)
 *   php artisan va:migrate-single-role --run     (applies changes)
 */
class MigrateSingleRole extends Command
{
    protected $signature   = 'va:migrate-single-role {--run : Apply the changes (omit for dry-run)}';
    protected $description = 'Enforce single-role-per-user: keep the highest-priority role for users with multiple roles.';

    private const PRIORITY = [
        'superadmin'      => 1,
        'admin'           => 2,
        'dispatcher'      => 3,
        'accountant'      => 4,
        'department_head' => 5,
        'driver'          => 6,
        'internal_user'   => 7,
    ];

    public function handle(): int
    {
        $isDryRun = ! $this->option('run');

        if ($isDryRun) {
            $this->warn('DRY-RUN mode — no changes will be saved. Add --run to apply.');
        } else {
            $this->info('APPLY mode — changes will be saved to the database.');
        }

        $multiRoleUsers = User::query()
            ->with('roles')
            ->get()
            ->filter(fn (User $u) => $u->roles->count() > 1);

        if ($multiRoleUsers->isEmpty()) {
            $this->info('No users with multiple roles found. All good!');

            return self::SUCCESS;
        }

        $this->line('');
        $this->line(sprintf('Found %d user(s) with multiple roles:', $multiRoleUsers->count()));
        $this->line('');

        foreach ($multiRoleUsers as $user) {
            $current = $user->roles->pluck('name')->join(', ');
            $highest = $this->resolveHighestRole($user->roles);

            $this->line(sprintf(
                '  [ID %d] %s (%s)  →  keep: %s  (was: %s)',
                $user->id,
                $user->name,
                $user->email,
                $highest->name,
                $current,
            ));

            if (! $isDryRun) {
                $user->syncRoles([$highest]);
                $user->update([
                    'primary_role_name' => $highest->name,
                    'primary_role_id'   => $highest->id,
                ]);
            }
        }

        if (! $isDryRun) {
            $this->line('');
            $this->info('Done. Also backfilling primary_role for users with exactly 1 role…');

            User::query()
                ->with('roles')
                ->whereNull('primary_role_name')
                ->chunk(200, function ($users) {
                    foreach ($users as $user) {
                        if ($user->roles->count() === 1) {
                            $role = $user->roles->first();
                            $user->update([
                                'primary_role_name' => $role->name,
                                'primary_role_id'   => $role->id,
                            ]);
                        }
                    }
                });

            $this->info('Migration complete.');
        } else {
            $this->line('');
            $this->warn('Dry-run complete. Run with --run to apply these changes.');
        }

        return self::SUCCESS;
    }

    private function resolveHighestRole(\Illuminate\Support\Collection $roles): Role
    {
        return $roles->sortBy(
            fn (Role $r) => self::PRIORITY[$r->name] ?? 99
        )->first();
    }
}
