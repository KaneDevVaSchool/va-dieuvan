<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Auditing\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
 *
 * Quy tắc với user đã có bên điều vận:
 * - CMS để trống một trường → giữ giá trị hiện có (không xóa dữ liệu nhập tay / bổ sung bên điều vận).
 * - users.source = cms sau khi sync. User thêm tay (source = manual) khớp email → được "nối" vào CMS,
 *   giữ nguyên id + vai trò, ghi audit `user.linked_to_cms`.
 * - CMS có mã NV trùng với một user thêm tay nhưng khác email → chỉ cảnh báo (không tự gộp).
 */
class SyncUsersFromCmsCommand extends Command
{
    protected $signature = 'cms:sync-users
                            {--dry-run : List rows that would be synced without writing}
                            {--include-deleted : Also sync soft-deleted CMS users (marked is_active=false)}';

    protected $description = 'Sync users from CMS database (users + user_info) into local users';

    public function handle(AuditLogger $audit): int
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
            'source' => Schema::hasColumn('users', 'source'),
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

        $manualByCode = $this->manualUsersByEmployeeCode($has);

        $skipped = 0;
        $synced = 0;
        $linked = 0;
        $possibleDuplicates = 0;

        foreach ($rows as $row) {
            $email = is_string($row->email) ? trim($row->email) : '';
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->warn("Skip invalid email (cms user id {$row->cms_user_id}): ".($row->email ?? ''));
                $skipped++;

                continue;
            }

            $existing = DB::table('users')->where('email', $email)->first();

            // Giá trị từ CMS; null = CMS để trống.
            $fromCms = [
                'name' => $this->blankToNull($row->name),
                'email_verified_at' => $row->email_verified_at,
            ];
            if ($has['google_id']) {
                $fromCms['google_id'] = $this->blankToNull($row->google_id);
            }
            if ($has['phone']) {
                $fromCms['phone'] = $this->blankToNull($row->phone);
            }
            if ($has['employee_code']) {
                $fromCms['employee_code'] = $this->blankToNull($row->employee_code);
            }

            if ($has['google_id'] && $fromCms['google_id'] !== null) {
                $gid = (string) $fromCms['google_id'];
                $googleIdTakenByOther = DB::table('users')
                    ->where('google_id', $gid)
                    ->where('email', '<>', $email)
                    ->exists();
                if ($googleIdTakenByOther) {
                    $this->warn("google_id {$gid} already used by another row; skipping google_id for {$email} (cms #{$row->cms_user_id})");
                    $fromCms['google_id'] = null;
                }
            }

            // Ý 3: mã NV trùng với user thêm tay nhưng khác email → cảnh báo để admin xử lý tay.
            $code = $has['employee_code'] ? $fromCms['employee_code'] : null;
            if ($code !== null) {
                foreach ($manualByCode[$this->codeKey($code)] ?? [] as $manual) {
                    if (mb_strtolower($manual->email) === mb_strtolower($email)) {
                        continue;
                    }
                    $possibleDuplicates++;
                    $message = "Possible duplicate: CMS {$email} (cms #{$row->cms_user_id}) has employee code {$code}, "
                        ."same as manually added user #{$manual->id} {$manual->email}. Not merged — review roles manually.";
                    $this->warn($message);
                    Log::warning('cms.sync_users.possible_duplicate', [
                        'cms_user_id' => $row->cms_user_id,
                        'cms_email' => $email,
                        'employee_code' => $code,
                        'manual_user_id' => $manual->id,
                        'manual_email' => $manual->email,
                    ]);
                }
            }

            $willLink = $existing && $has['source'] && ($existing->source ?? null) === User::SOURCE_MANUAL;

            if ($dryRun) {
                $this->line("[dry-run] {$email} ← cms #{$row->cms_user_id}".($willLink ? ' (link manual user)' : ''));
                $synced++;
                $linked += $willLink ? 1 : 0;

                continue;
            }

            $now = now();
            $cmsPassword = is_string($row->password) && $row->password !== '' ? $row->password : null;

            if ($existing) {
                // Ý 1: CMS để trống → giữ giá trị đang có.
                $data = array_filter($fromCms, fn ($v) => $v !== null);
                if ($cmsPassword !== null) {
                    $data['password'] = $cmsPassword;
                }
            } else {
                $data = $fromCms;
                $data['name'] = $data['name'] ?? $email;
                $data['email'] = $email;
                $data['password'] = $cmsPassword ?? bcrypt(Str::random(32));
                $data['created_at'] = $now;
            }
            if ($has['is_active']) {
                $data['is_active'] = $row->deleted_at === null;
            }
            if ($has['source']) {
                $data['source'] = User::SOURCE_CMS;
            }
            $data['updated_at'] = $now;

            if ($existing) {
                DB::table('users')->where('id', $existing->id)->update($data);
            } else {
                DB::table('users')->insert($data);
            }

            if ($willLink) {
                $linked++;
                $after = DB::table('users')->where('id', $existing->id)->first();
                $audit->log(
                    actorId: null,
                    event: 'user.linked_to_cms',
                    auditable: User::query()->find($existing->id),
                    before: $this->auditSnapshot($existing),
                    after: $this->auditSnapshot($after),
                    metadata: ['cms_user_id' => $row->cms_user_id],
                );
                $this->line("Linked manually added user #{$existing->id} {$email} to cms #{$row->cms_user_id} (roles kept).");
            }

            $synced++;
        }

        $this->info("Done. Synced: {$synced}, linked manual users: {$linked}, possible duplicates: {$possibleDuplicates}, skipped (bad email): {$skipped}".($dryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }

    /**
     * User thêm tay có mã NV, nhóm theo mã (không phân biệt hoa thường / khoảng trắng).
     *
     * @param  array<string, bool>  $has
     * @return array<string, list<object>>
     */
    private function manualUsersByEmployeeCode(array $has): array
    {
        if (! $has['source'] || ! $has['employee_code']) {
            return [];
        }

        $out = [];
        $rows = DB::table('users')
            ->where('source', User::SOURCE_MANUAL)
            ->whereNotNull('employee_code')
            ->get(['id', 'email', 'employee_code']);
        foreach ($rows as $u) {
            $key = $this->codeKey((string) $u->employee_code);
            if ($key !== '') {
                $out[$key][] = $u;
            }
        }

        return $out;
    }

    private function codeKey(string $code): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', '', $code) ?? '');
    }

    private function blankToNull(mixed $value): mixed
    {
        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function auditSnapshot(?object $u): array
    {
        if (! $u) {
            return [];
        }

        return array_intersect_key((array) $u, array_flip(['name', 'email', 'employee_code', 'phone', 'is_active', 'source']));
    }
}
