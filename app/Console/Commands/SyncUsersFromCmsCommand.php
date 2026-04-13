<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Maps CMS `cms_db_staging.users` + `user_info` into this app's `users` table.
 *
 * Mapping (CMS → Laravel):
 * - users.name → name
 * - users.email → email (unique key for upsert)
 * - users.email_verified_at → email_verified_at
 * - users.password → password (bcrypt hash copied as-is; not re-hashed)
 * - users.google_id → google_id
 * - users.deleted_at → is_active (inactive when soft-deleted on CMS)
 * - user_info.code → employee_code (latest row per user_id by max user_info.id)
 * - user_info.phone → phone
 */
class SyncUsersFromCmsCommand extends Command
{
    protected $signature = 'cms:sync-users
                            {--dry-run : List rows that would be synced without writing}
                            {--include-deleted : Also sync soft-deleted CMS users (marked is_active=false)}';

    protected $description = 'Sync users from CMS database (users + user_info) into local users';

    public function handle(): int
    {
        try {
            DB::connection('cms')->getPdo();
        } catch (\Throwable $e) {
            $this->error('Cannot connect to CMS database. Check CMS_DB_* in .env: '.$e->getMessage());

            return self::FAILURE;
        }

        $includeDeleted = (bool) $this->option('include-deleted');
        $dryRun = (bool) $this->option('dry-run');

        $has = [
            'google_id' => Schema::hasColumn('users', 'google_id'),
            'phone' => Schema::hasColumn('users', 'phone'),
            'employee_code' => Schema::hasColumn('users', 'employee_code'),
            'is_active' => Schema::hasColumn('users', 'is_active'),
        ];

        $missing = array_keys(array_filter($has, fn (bool $ok) => ! $ok));
        if ($missing !== []) {
            $this->warn('Missing columns: users.'.implode(', users.', $missing).' — run `php artisan migrate`. Sync will omit missing fields.');
        }

        $q = DB::connection('cms')
            ->table('users as u')
            ->leftJoin('user_info as ui', function ($join) {
                $join->on('ui.user_id', '=', 'u.id')
                    ->whereRaw('ui.id = (select max(id) from user_info where user_id = u.id)');
            })
            ->select([
                'u.id as cms_user_id',
                'u.name',
                'u.email',
                'u.email_verified_at',
                'u.password',
                'u.google_id',
                'u.deleted_at',
                'ui.code as employee_code',
                'ui.phone as phone',
            ])
            ->orderBy('u.id');

        if (! $includeDeleted) {
            $q->whereNull('u.deleted_at');
        }

        $rows = $q->get();

        $skipped = 0;
        $synced = 0;

        foreach ($rows as $row) {
            $email = is_string($row->email) ? trim($row->email) : '';
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->warn("Skip invalid email (cms user id {$row->cms_user_id}): ".($row->email ?? ''));
                $skipped++;

                continue;
            }

            $isActive = $row->deleted_at === null;

            $payload = [
                'name' => $row->name,
                'email_verified_at' => $row->email_verified_at,
            ];
            if ($has['google_id']) {
                $payload['google_id'] = $row->google_id;
            }
            if ($has['phone']) {
                $payload['phone'] = $row->phone;
            }
            if ($has['employee_code']) {
                $payload['employee_code'] = $row->employee_code;
            }
            if ($has['is_active']) {
                $payload['is_active'] = $isActive;
            }

            if ($has['google_id'] && $row->google_id !== null && $row->google_id !== '') {
                $gid = (string) $row->google_id;
                $googleIdTakenByOther = DB::table('users')
                    ->where('google_id', $gid)
                    ->where('email', '<>', $email)
                    ->exists();
                if ($googleIdTakenByOther) {
                    $this->warn("google_id {$gid} already used by another row; clearing for {$email} (cms #{$row->cms_user_id})");
                    $payload['google_id'] = null;
                }
            }

            if ($dryRun) {
                $this->line("[dry-run] {$email} ← cms #{$row->cms_user_id}");
                $synced++;

                continue;
            }

            $password = is_string($row->password) && $row->password !== ''
                ? $row->password
                : bcrypt(Str::random(32));

            $now = now();
            $data = array_merge($payload, [
                'email' => $email,
                'password' => $password,
                'updated_at' => $now,
            ]);

            $existingId = DB::table('users')->where('email', $email)->value('id');

            if ($existingId) {
                DB::table('users')->where('id', $existingId)->update($data);
            } else {
                $data['created_at'] = $now;
                DB::table('users')->insert($data);
            }

            $synced++;
        }

        $this->info("Done. Synced: {$synced}, skipped (bad email): {$skipped}".($dryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }
}
