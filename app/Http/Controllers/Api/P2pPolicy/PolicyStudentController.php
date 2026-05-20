<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\ImportPolicyStudentsRequest;
use App\Http\Requests\Api\P2pPolicy\ListPolicyStudentsRequest;
use App\Http\Requests\Api\P2pPolicy\StorePolicyStudentRequest;
use App\Http\Requests\Api\P2pPolicy\UpdatePolicyStudentRequest;
use App\Jobs\PolicyStudentImportJob;
use App\Models\PolicyStudent;
use App\Models\Student;
use App\Services\Auditing\AuditLogger;
use App\Services\P2pPolicy\PolicyStudentExportService;
use App\Services\P2pPolicy\PolicyStudentIndexQuery;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PolicyStudentController extends Controller
{
    use ApiResponses;

    public function index(ListPolicyStudentsRequest $request, PolicyStudentIndexQuery $queryBuilder)
    {
        $data = $request->validated();
        $perPage = (int) ($data['per_page'] ?? 25);
        $page = $queryBuilder->build($request)->paginate($perPage);

        return $this->ok([
            'items' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    public function store(StorePolicyStudentRequest $request)
    {
        $data = $request->validated();
        $studentId = $data['student_id'] ?? null;

        if ($studentId === null) {
            $student = Student::query()->where('student_code', $data['student_code'])->first();
            if ($student === null) {
                if (! config('dispatch.p2p_policy_auto_create_students', true)) {
                    abort(422, 'Mã học sinh chưa tồn tại trong hệ thống.');
                }
                $student = Student::create([
                    'student_code' => $data['student_code'],
                    'full_name' => $data['student_name'],
                    'grade' => $data['class_name'] ?? null,
                    'is_active' => true,
                ]);
            }
            $studentId = $student->id;
        } else {
            $student = Student::query()->findOrFail($studentId);
        }

        $row = PolicyStudent::create([
            'policy_route_id' => $data['policy_route_id'],
            'student_id' => $studentId,
            'student_code' => $data['student_code'] ?? $student->student_code,
            'student_name' => $data['student_name'] ?? $student->full_name,
            'class_name' => $data['class_name'] ?? $student->grade,
            'direction' => $data['direction'] ?? 'two_way',
            'policy_type' => $data['policy_type'],
            'policy_note' => $data['policy_note'] ?? null,
            'contract_number' => $data['contract_number'] ?? null,
            'sbs_contract' => $data['sbs_contract'] ?? null,
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'active_weekdays_mask' => $data['active_weekdays_mask'] ?? null,
        ]);

        app(AuditLogger::class)->log($request->user()->id, 'p2p_policy.student.create', $row, null, $row->toArray());

        return $this->created($row->load('policyRoute'));
    }

    public function update(UpdatePolicyStudentRequest $request, PolicyStudent $policyStudent)
    {
        $before = $policyStudent->toArray();
        $policyStudent->update($request->validated());

        app(AuditLogger::class)->log($request->user()->id, 'p2p_policy.student.update', $policyStudent, $before, $policyStudent->toArray());

        return $this->ok($policyStudent);
    }

    public function destroy(ListPolicyStudentsRequest $request, PolicyStudent $policyStudent)
    {
        $before = $policyStudent->toArray();
        $policyStudent->delete();

        app(AuditLogger::class)->log($request->user()->id, 'p2p_policy.student.delete', $policyStudent, $before, []);

        return $this->ok(['deleted' => true]);
    }

    public function import(ImportPolicyStudentsRequest $request)
    {
        $path = $request->file('file')->store('p2p-policy-imports');
        $jobId = uniqid('p2p-import-', true);

        PolicyStudentImportJob::dispatch(
            $jobId,
            $path,
            (int) $request->validated()['p2p_policy_term_id'],
            (int) $request->user()->id,
            $request->file('file')->getClientOriginalName(),
        );

        return $this->accepted([
            'import_job_id' => $jobId,
            'message' => 'File đang được xử lý trong hàng đợi.',
        ]);
    }

    public function export(ListPolicyStudentsRequest $request, PolicyStudentIndexQuery $queryBuilder, PolicyStudentExportService $export): StreamedResponse
    {
        $filename = 'p2p-policy-students-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($request, $queryBuilder, $export) {
            $export->stream($queryBuilder->build($request));
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function importTemplate(PolicyStudentExportService $export): StreamedResponse
    {
        return response()->streamDownload(function () use ($export) {
            $export->streamTemplate();
        }, 'p2p-policy-students-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
