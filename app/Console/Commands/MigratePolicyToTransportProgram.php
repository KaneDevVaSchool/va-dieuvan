<?php

namespace App\Console\Commands;

use App\Models\TpEnrollment;
use App\Models\TpProgram;
use App\Models\TpStudent;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Di trú dữ liệu module cũ (policy_*) sang module mới (tp_*).
 *
 * - KHÔNG drop/đụng bảng policy_* — chỉ đọc.
 * - --dry-run: chỉ thống kê, không ghi.
 * - --verify: so sánh số bản ghi sau khi đã chạy.
 *
 * Mapping:
 *   students                 -> tp_students (theo student_code)
 *   student_policies (group) -> tp_programs (theo route_id+time_slot+semester+school_year)
 *   từng student_policy       -> tp_enrollments
 */
class MigratePolicyToTransportProgram extends Command
{
    protected $signature = 'tp:migrate-from-policy {--dry-run : Chỉ thống kê, không ghi} {--verify : So sánh số liệu sau di trú}';

    protected $description = 'Di trú dữ liệu policy_* sang tp_* (an toàn, không xóa bảng cũ)';

    public function handle(): int
    {
        if ($this->option('verify')) {
            return $this->verify();
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? '== DRY RUN (không ghi dữ liệu) ==' : '== Bắt đầu di trú policy_* -> tp_* ==');

        $students = DB::table('students')->get();
        $policies = DB::table('student_policies')->whereNull('deleted_at')->get();

        $this->line("students: {$students->count()} · student_policies: {$policies->count()}");

        if ($dryRun) {
            $groups = $policies->groupBy(fn ($p) => "{$p->route_id}|{$p->time_slot}|{$p->semester}|{$p->school_year}");
            $this->line('Sẽ tạo ~'.$groups->count().' chương trình và '.$policies->count().' lượt đăng ký.');
            $this->info('Dry-run hoàn tất. Không có thay đổi nào được ghi.');

            return self::SUCCESS;
        }

        $studentMap = [];
        $createdStudents = 0;

        DB::transaction(function () use ($students, $policies, &$studentMap, &$createdStudents) {
            foreach ($students as $s) {
                $code = $s->student_code ?: ('MIGR-'.$s->id);
                $tp = TpStudent::query()->updateOrCreate(
                    ['code' => $code],
                    [
                        'full_name' => $s->full_name,
                        'grade' => $s->grade,
                        'parent_name' => $s->guardian_name ?? null,
                        'parent_phone' => $s->guardian_phone ?? null,
                        'status' => ($s->is_active ?? true) ? 'active' : 'inactive',
                        'source' => 'migrated_policy',
                    ]
                );
                if ($tp->wasRecentlyCreated) {
                    $createdStudents++;
                }
                $studentMap[$s->id] = $tp->id;
            }

            $routeNames = DB::table('routes')->pluck('name', 'id');
            $groups = $policies->groupBy(fn ($p) => "{$p->route_id}|{$p->time_slot}|{$p->semester}|{$p->school_year}");

            foreach ($groups as $key => $rows) {
                [$routeId, $slot, $semester, $schoolYear] = explode('|', $key);
                $routeName = $routeNames[$routeId] ?? ('Tuyến #'.$routeId);
                $slotLabel = $slot === 'morning' ? 'Sáng' : 'Chiều';

                $program = TpProgram::query()->create([
                    'code' => 'MIGR-'.Str::upper(Str::random(8)),
                    'name' => "{$routeName} · {$slotLabel} · HK{$semester} {$schoolYear}",
                    'departure_time' => $slot === 'morning' ? '06:30' : '16:30',
                    'start_date' => Carbon::parse($rows->min('effective_from')),
                    'end_date' => Carbon::parse($rows->max('effective_to')),
                    'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri'],
                    'status' => 'draft',
                    'notes' => 'Di trú từ student_policies (route_id='.$routeId.').',
                ]);

                foreach ($rows as $policy) {
                    $tpStudentId = $studentMap[$policy->student_id] ?? null;
                    if (! $tpStudentId) {
                        continue;
                    }
                    TpEnrollment::query()->updateOrCreate(
                        ['program_id' => $program->id, 'student_id' => $tpStudentId],
                        ['enrolled_at' => Carbon::parse($policy->effective_from), 'unenrolled_at' => null]
                    );
                }
            }
        });

        $this->info("Hoàn tất. Học sinh mới tạo: {$createdStudents}. Chương trình: ".TpProgram::query()->where('code', 'like', 'MIGR-%')->count());
        $this->warn('Bảng policy_* được giữ nguyên. Hãy chạy --verify để đối chiếu.');

        return self::SUCCESS;
    }

    private function verify(): int
    {
        $this->info('== Đối chiếu policy_* vs tp_* ==');
        $policyStudents = DB::table('students')->count();
        $tpStudents = TpStudent::query()->count();
        $policies = DB::table('student_policies')->whereNull('deleted_at')->count();
        $enrollments = TpEnrollment::query()->count();

        $this->table(['Hạng mục', 'policy_*', 'tp_*'], [
            ['Học sinh', $policyStudents, $tpStudents],
            ['Đăng ký (policy/enrollment)', $policies, $enrollments],
        ]);

        if ($tpStudents < $policyStudents) {
            $this->warn('Số học sinh tp_* nhỏ hơn — có thể chưa di trú hoặc trùng mã.');
        } else {
            $this->info('Số liệu hợp lý.');
        }

        return self::SUCCESS;
    }
}
