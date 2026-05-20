<?php

namespace App\Services\P2pPolicy;

use App\Models\PolicyRoute;
use App\Models\PolicyStudent;
use App\Models\Student;
use App\Services\Auditing\AuditLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader;

class PolicyStudentImportService
{
    public const PREVIEW_CACHE_PREFIX = 'p2p_policy_import_preview:';

    public const PREVIEW_TTL_SECONDS = 1800;

    public const MAX_COMMIT_ROWS = 2000;

    /**
     * @return array{
     *   preview_id: string,
     *   summary: array{create: int, update: int, error: int, total: int},
     *   rows: list<array<string, mixed>>,
     *   truncated: bool,
     *   warning: string|null
     * }
     */
    public function previewFromStoragePath(string $storagePath, int $p2pPolicyTermId, string $originalFilename): array
    {
        $fullPath = storage_path('app/'.$storagePath);
        if (! is_readable($fullPath)) {
            return [
                'preview_id' => '',
                'summary' => ['create' => 0, 'update' => 0, 'error' => 1, 'total' => 0],
                'rows' => [['row' => 0, 'action' => 'error', 'messages' => ['Không đọc được file.']]],
                'truncated' => false,
                'warning' => null,
            ];
        }

        $parsed = $this->parseFile($fullPath, $p2pPolicyTermId, $originalFilename, dryRun: true);
        $previewId = (string) Str::uuid();

        Cache::put(self::PREVIEW_CACHE_PREFIX.$previewId, [
            'storage_path' => $storagePath,
            'p2p_policy_term_id' => $p2pPolicyTermId,
            'filename' => $originalFilename,
            'rows' => $parsed['rows'],
        ], self::PREVIEW_TTL_SECONDS);

        $displayRows = array_slice($parsed['rows'], 0, 500);
        $validCount = $parsed['summary']['create'] + $parsed['summary']['update'];
        $warning = null;
        if ($validCount > self::MAX_COMMIT_ROWS) {
            $warning = 'File có quá '.self::MAX_COMMIT_ROWS.' dòng hợp lệ; chỉ áp dụng tối đa '.self::MAX_COMMIT_ROWS.' dòng đầu tiên khi xác nhận.';
        }

        return [
            'preview_id' => $previewId,
            'summary' => $parsed['summary'],
            'rows' => array_map(fn ($r) => $this->publicPreviewRow($r), $displayRows),
            'truncated' => count($parsed['rows']) > 500,
            'warning' => $warning,
        ];
    }

    /**
     * @return array{imported: int, updated: int, skipped: int, errors: list<array{row: int, message: string}>}
     */
    public function commitPreview(string $previewId, int $userId): array
    {
        $cacheKey = self::PREVIEW_CACHE_PREFIX.$previewId;
        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get($cacheKey);
        if ($cached === null) {
            abort(422, 'Preview đã hết hạn hoặc không tồn tại. Vui lòng tải file lại.');
        }

        $rows = $cached['rows'] ?? [];
        $filename = (string) ($cached['filename'] ?? '');
        $termId = (int) ($cached['p2p_policy_term_id'] ?? 0);
        $storagePath = (string) ($cached['storage_path'] ?? '');

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $applied = 0;

        foreach ($rows as $row) {
            if (! in_array($row['action'] ?? '', ['create', 'update'], true)) {
                if (($row['action'] ?? '') === 'error') {
                    $skipped++;
                }

                continue;
            }
            if ($applied >= self::MAX_COMMIT_ROWS) {
                $errors[] = ['row' => (int) ($row['row'] ?? 0), 'message' => 'Vượt giới hạn số dòng import.'];

                continue;
            }

            try {
                $result = DB::transaction(function () use ($row, $filename, $userId) {
                    return $this->applyRow($row, $filename, $userId);
                });
                if ($result === 'create') {
                    $imported++;
                } else {
                    $updated++;
                }
                $applied++;
            } catch (\Throwable $e) {
                $errors[] = ['row' => (int) ($row['row'] ?? 0), 'message' => $e->getMessage()];
            }
        }

        if ($storagePath !== '') {
            Storage::delete($storagePath);
        }
        Cache::forget($cacheKey);

        app(AuditLogger::class)->log(
            actorId: $userId,
            event: 'p2p_policy.students.import',
            auditable: null,
            before: null,
            after: [
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
            ],
            metadata: [
                'preview_id' => $previewId,
                'filename' => $filename,
                'p2p_policy_term_id' => $termId,
                'error_count' => count($errors),
            ],
        );

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{imported: int, errors: list<array{row: int, message: string}>}
     */
    public function importFromStoragePath(string $storagePath, int $p2pPolicyTermId, string $originalFilename, ?int $userId = null): array
    {
        $fullPath = storage_path('app/'.$storagePath);
        if (! is_readable($fullPath)) {
            return ['imported' => 0, 'errors' => [['row' => 0, 'message' => 'Không đọc được file.']]];
        }

        $parsed = $this->parseFile($fullPath, $p2pPolicyTermId, $originalFilename, dryRun: false);
        $imported = 0;
        $errors = [];

        foreach ($parsed['rows'] as $row) {
            if (! in_array($row['action'] ?? '', ['create', 'update'], true)) {
                if (($row['action'] ?? '') === 'error') {
                    foreach ($row['messages'] ?? [] as $msg) {
                        $errors[] = ['row' => (int) $row['row'], 'message' => $msg];
                    }
                }

                continue;
            }
            try {
                DB::transaction(function () use ($row, $originalFilename, $userId, &$imported) {
                    $this->applyRow($row, $originalFilename, $userId);
                    $imported++;
                });
            } catch (\Throwable $e) {
                $errors[] = ['row' => (int) $row['row'], 'message' => $e->getMessage()];
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, summary: array{create: int, update: int, error: int, total: int}}
     */
    private function parseFile(string $fullPath, int $p2pPolicyTermId, string $originalFilename, bool $dryRun): array
    {
        $routesByName = PolicyRoute::query()
            ->where('p2p_policy_term_id', $p2pPolicyTermId)
            ->get()
            ->keyBy(fn (PolicyRoute $r) => mb_strtolower(trim($r->name)));

        $reader = new Reader;
        $reader->open($fullPath);

        $rows = [];
        $summary = ['create' => 0, 'update' => 0, 'error' => 0, 'total' => 0];
        $rowNum = 0;
        $pastHeader = false;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowNum++;
                $values = $this->rowToValues($row);

                if (! $pastHeader) {
                    if (PolicyStudentSpreadsheetSpec::rowIsHeaderKeys($values)) {
                        $pastHeader = true;
                    }

                    continue;
                }

                $parsed = $this->parseDataRow($rowNum, $values, $routesByName, $originalFilename);
                if ($parsed === null) {
                    continue;
                }

                $rows[] = $parsed;
                $summary['total']++;
                $summary[$parsed['action']]++;
            }
            break;
        }

        $reader->close();

        return ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * @param  \OpenSpout\Common\Entity\Row  $row
     * @return list<string>
     */
    private function rowToValues($row): array
    {
        $values = [];
        foreach ($row->getCells() as $i => $cell) {
            $values[$i] = trim((string) $cell->getValue());
        }

        return $values;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, PolicyRoute>  $routesByName
     * @return array<string, mixed>|null
     */
    private function parseDataRow(int $rowNum, array $values, $routesByName, string $originalFilename): ?array
    {
        $routeName = $values[0] ?? '';
        $studentCode = $values[1] ?? '';
        $studentName = $values[2] ?? '';
        if ($routeName === '' && $studentCode === '') {
            return null;
        }

        if (PolicyStudentSpreadsheetSpec::rowIsHeaderKeys($values)) {
            return null;
        }

        $messages = [];
        $route = $routesByName->get(mb_strtolower($routeName));
        if ($route === null) {
            return [
                'row' => $rowNum,
                'route_name' => $routeName,
                'student_code' => $studentCode,
                'student_name' => $studentName,
                'action' => 'error',
                'messages' => ["Không tìm thấy tuyến: {$routeName}"],
            ];
        }

        if ($studentCode === '' || $studentName === '') {
            return [
                'row' => $rowNum,
                'route_name' => $routeName,
                'student_code' => $studentCode,
                'student_name' => $studentName,
                'action' => 'error',
                'messages' => ['Thiếu mã hoặc tên học sinh'],
            ];
        }

        $direction = in_array($values[4] ?? '', ['one_way', 'two_way'], true) ? $values[4] : 'two_way';
        $policyType = ($values[5] ?? '') !== '' ? $values[5] : 'default';
        $effectiveFrom = ($values[8] ?? '') !== '' ? $values[8] : now()->toDateString();

        $attributes = [
            'policy_route_id' => $route->id,
            'student_code' => $studentCode,
            'student_name' => $studentName,
            'class_name' => ($values[3] ?? '') !== '' ? $values[3] : null,
            'direction' => $direction,
            'policy_type' => $policyType,
            'contract_number' => ($values[6] ?? '') !== '' ? $values[6] : null,
            'sbs_contract' => ($values[7] ?? '') !== '' ? $values[7] : null,
            'effective_from' => $effectiveFrom,
            'effective_to' => ($values[9] ?? '') !== '' ? $values[9] : null,
            'is_active' => ($values[10] ?? '1') !== '0',
            'policy_note' => ($values[11] ?? '') !== '' ? $values[11] : null,
            'imported_from' => $originalFilename,
        ];

        $student = Student::query()->where('student_code', $studentCode)->first();
        $studentId = $student?->id;

        $existing = null;
        if ($studentId !== null) {
            $existing = PolicyStudent::query()
                ->where('policy_route_id', $route->id)
                ->where('student_id', $studentId)
                ->whereDate('effective_from', $effectiveFrom)
                ->first();
        }

        $action = $existing === null ? 'create' : 'update';

        return [
            'row' => $rowNum,
            'route_name' => $routeName,
            'student_code' => $studentCode,
            'student_name' => $studentName,
            'action' => $action,
            'messages' => $messages,
            'before' => $existing?->toArray(),
            'after' => $attributes,
            'apply' => [
                'route_id' => $route->id,
                'student_id' => $studentId,
                'student_code' => $studentCode,
                'student_name' => $studentName,
                'class_name' => $attributes['class_name'],
                'effective_from' => $effectiveFrom,
                'attributes' => $attributes,
                'existing_id' => $existing?->id,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return 'create'|'update'
     */
    private function applyRow(array $row, string $originalFilename, ?int $userId): string
    {
        $apply = $row['apply'] ?? null;
        if (! is_array($apply)) {
            throw new \RuntimeException('Dòng không hợp lệ để áp dụng.');
        }

        $studentId = $apply['student_id'] ?? null;
        $studentCode = $apply['student_code'];
        $studentName = $apply['student_name'];

        if ($studentId === null) {
            if (! config('dispatch.p2p_policy_auto_create_students', true)) {
                throw new \RuntimeException('Mã HS chưa tồn tại');
            }
            $student = Student::create([
                'student_code' => $studentCode,
                'full_name' => $studentName,
                'grade' => $apply['class_name'] ?? null,
                'is_active' => true,
            ]);
            $studentId = $student->id;
        }

        $attrs = $apply['attributes'];
        $attrs['student_id'] = $studentId;
        $attrs['imported_from'] = $originalFilename;

        $existingId = $apply['existing_id'] ?? null;
        if ($existingId !== null) {
            $ps = PolicyStudent::query()->findOrFail($existingId);
            $before = $ps->toArray();
            $ps->update($attrs);
            if ($userId !== null) {
                app(AuditLogger::class)->log($userId, 'p2p_policy.student.import_update', $ps, $before, $ps->fresh()->toArray());
            }

            return 'update';
        }

        $ps = PolicyStudent::create($attrs);
        if ($userId !== null) {
            app(AuditLogger::class)->log($userId, 'p2p_policy.student.import_create', $ps, null, $ps->toArray());
        }

        return 'create';
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function publicPreviewRow(array $row): array
    {
        return [
            'row' => $row['row'] ?? 0,
            'route_name' => $row['route_name'] ?? '',
            'student_code' => $row['student_code'] ?? '',
            'student_name' => $row['student_name'] ?? '',
            'action' => $row['action'] ?? 'error',
            'messages' => $row['messages'] ?? [],
        ];
    }
}
