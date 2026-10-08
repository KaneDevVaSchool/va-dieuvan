<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Checklist\DriverChecklistRequest;
use App\Http\Requests\Api\Checklist\SaveChecklistDraftRequest;
use App\Http\Requests\Api\Checklist\StartChecklistRequest;
use App\Http\Requests\Api\Checklist\SubmitChecklistRequest;
use App\Http\Requests\Api\Checklist\UploadChecklistEvidenceRequest;
use App\Models\Attachment;
use App\Models\ChecklistSubmission;
use App\Models\ChecklistSubmissionItem;
use App\Services\Checklist\ChecklistPresenter;
use App\Services\Checklist\ChecklistPrintService;
use App\Services\Checklist\ChecklistQuery;
use App\Services\Checklist\ChecklistSubmissionService;
use App\Support\ChecklistAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Checklist Daily/Weekly — phía tài xế.
 */
class DriverChecklistController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ChecklistSubmissionService $service,
        private readonly ChecklistQuery $query,
        private readonly ChecklistPresenter $presenter,
    ) {}

    public function overview(DriverChecklistRequest $request): JsonResponse
    {
        $driver = ChecklistAccess::driverFor($request->user());
        if ($driver === null) {
            return $this->ok(['periods' => [], 'failed_today' => [], 'has_driver_profile' => false]);
        }

        return $this->ok([...$this->query->driverOverview($driver), 'has_driver_profile' => true]);
    }

    public function options(DriverChecklistRequest $request): JsonResponse
    {
        $driver = ChecklistAccess::driverFor($request->user());
        abort_if($driver === null, 422, 'Tài khoản chưa được gắn hồ sơ tài xế — liên hệ điều phối.');

        return $this->ok($this->query->startOptions($driver));
    }

    public function index(DriverChecklistRequest $request): JsonResponse
    {
        $driver = ChecklistAccess::driverFor($request->user());
        if ($driver === null) {
            return $this->ok(['items' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 0]]);
        }

        $page = $this->query->paginate($request->validated(), $driver);

        return $this->ok([
            'items' => collect($page->items())->map(fn (ChecklistSubmission $s) => $this->presenter->summary($s))->all(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(DriverChecklistRequest $request, ChecklistSubmission $checklist): JsonResponse
    {
        abort_unless(ChecklistAccess::userOwns($request->user(), $checklist), 403);

        return $this->ok($this->presenter->detail($checklist));
    }

    public function store(StartChecklistRequest $request): JsonResponse
    {
        $submission = $this->service->start($request->user(), $request->validated());

        return $this->created($this->presenter->detail($submission));
    }

    public function update(SaveChecklistDraftRequest $request, ChecklistSubmission $checklist): JsonResponse
    {
        $submission = $this->service->saveDraft($checklist, $request->user(), $request->validated());

        return $this->ok($this->presenter->detail($submission));
    }

    public function submit(SubmitChecklistRequest $request, ChecklistSubmission $checklist): JsonResponse
    {
        $submission = $this->service->submit($checklist, $request->user(), $request->validated());

        return $this->ok($this->presenter->detail($submission), 'Đã nộp checklist.');
    }

    public function destroy(DriverChecklistRequest $request, ChecklistSubmission $checklist): JsonResponse
    {
        $this->service->discardDraft($checklist, $request->user());

        return $this->ok(['deleted' => true]);
    }

    public function uploadEvidence(UploadChecklistEvidenceRequest $request, ChecklistSubmission $checklist, ChecklistSubmissionItem $item): JsonResponse
    {
        abort_unless((int) $item->submission_id === (int) $checklist->id, 404);

        $attachment = $this->service->addEvidence($item, $request->file('file'), $request->user());

        return $this->created($attachment->only(['id', 'original_name', 'mime_type', 'size_bytes']));
    }

    public function deleteEvidence(DriverChecklistRequest $request, ChecklistSubmission $checklist, ChecklistSubmissionItem $item, Attachment $attachment): JsonResponse
    {
        abort_unless((int) $item->submission_id === (int) $checklist->id, 404);

        $this->service->removeEvidence($item, $attachment, $request->user());

        return $this->ok(['deleted' => true]);
    }

    public function pdf(DriverChecklistRequest $request, ChecklistSubmission $checklist, ChecklistPrintService $print): Response
    {
        abort_unless(ChecklistAccess::userOwns($request->user(), $checklist), 403);

        return $print->pdf($checklist)->download($checklist->code.'.pdf');
    }
}
