<?php

namespace App\Services\P2pPolicy;

use App\Models\PolicyRoute;
use App\Models\PolicyStudent;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader;

class PolicyStudentImportService
{
    /**
     * @return array{imported: int, errors: list<array{row: int, message: string}>}
     */
    public function importFromStoragePath(string $storagePath, int $p2pPolicyTermId, string $originalFilename): array
    {
        $fullPath = storage_path('app/'.$storagePath);
        if (! is_readable($fullPath)) {
            return ['imported' => 0, 'errors' => [['row' => 0, 'message' => 'Không đọc được file.']]];
        }

        $routesByName = PolicyRoute::query()
            ->where('p2p_policy_term_id', $p2pPolicyTermId)
            ->get()
            ->keyBy(fn (PolicyRoute $r) => mb_strtolower(trim($r->name)));

        $reader = new Reader;
        $reader->open($fullPath);

        $imported = 0;
        $errors = [];
        $rowNum = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowNum++;
                if ($rowNum === 1) {
                    continue;
                }

                $cells = $row->getCells();
                $values = [];
                foreach ($cells as $i => $cell) {
                    $values[$i] = trim((string) $cell->getValue());
                }

                $routeName = $values[0] ?? '';
                $studentCode = $values[1] ?? '';
                $studentName = $values[2] ?? '';
                if ($routeName === '' && $studentCode === '') {
                    continue;
                }

                $route = $routesByName->get(mb_strtolower($routeName));
                if ($route === null) {
                    $errors[] = ['row' => $rowNum, 'message' => "Không tìm thấy tuyến: {$routeName}"];

                    continue;
                }

                if ($studentCode === '' || $studentName === '') {
                    $errors[] = ['row' => $rowNum, 'message' => 'Thiếu mã hoặc tên học sinh'];

                    continue;
                }

                $direction = in_array($values[4] ?? '', ['one_way', 'two_way'], true) ? $values[4] : 'two_way';
                $policyType = ($values[5] ?? '') !== '' ? $values[5] : 'default';
                $effectiveFrom = ($values[8] ?? '') !== '' ? $values[8] : now()->toDateString();

                try {
                    DB::transaction(function () use (
                        $route,
                        $studentCode,
                        $studentName,
                        $values,
                        $direction,
                        $policyType,
                        $effectiveFrom,
                        $originalFilename,
                        &$imported,
                    ) {
                        $student = Student::query()->where('student_code', $studentCode)->first();
                        if ($student === null) {
                            if (! config('dispatch.p2p_policy_auto_create_students', true)) {
                                throw new \RuntimeException('Mã HS chưa tồn tại');
                            }
                            $student = Student::create([
                                'student_code' => $studentCode,
                                'full_name' => $studentName,
                                'grade' => $values[3] ?? null,
                                'is_active' => true,
                            ]);
                        }

                        PolicyStudent::updateOrCreate(
                            [
                                'policy_route_id' => $route->id,
                                'student_id' => $student->id,
                                'effective_from' => $effectiveFrom,
                            ],
                            [
                                'student_code' => $studentCode,
                                'student_name' => $studentName,
                                'class_name' => $values[3] ?? null,
                                'direction' => $direction,
                                'policy_type' => $policyType,
                                'contract_number' => ($values[6] ?? '') !== '' ? $values[6] : null,
                                'sbs_contract' => ($values[7] ?? '') !== '' ? $values[7] : null,
                                'effective_to' => ($values[9] ?? '') !== '' ? $values[9] : null,
                                'is_active' => ($values[10] ?? '1') !== '0',
                                'policy_note' => ($values[11] ?? '') !== '' ? $values[11] : null,
                                'imported_from' => $originalFilename,
                            ],
                        );
                        $imported++;
                    });
                } catch (\Throwable $e) {
                    $errors[] = ['row' => $rowNum, 'message' => $e->getMessage()];
                }
            }
            break;
        }

        $reader->close();

        return ['imported' => $imported, 'errors' => $errors];
    }
}
