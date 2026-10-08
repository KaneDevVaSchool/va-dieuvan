<?php

namespace App\Http\Controllers\Api\Checklist;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Checklist\ChecklistComplianceRequest;
use App\Http\Requests\Api\Checklist\ReviewChecklistRequest;
use App\Http\Requests\Api\Checklist\StaffChecklistRequest;
use App\Models\ChecklistSubmission;
use App\Models\ChecklistTemplate;
use App\Services\Checklist\ChecklistPresenter;
use App\Services\Checklist\ChecklistPrintService;
use App\Services\Checklist\ChecklistQuery;
use App\Services\Checklist\ChecklistSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * Checklist — phía điều phối: ma trận tuân thủ, danh sách, chi tiết, đánh dấu đã xem, file in.
 */
class ChecklistController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ChecklistQuery $query,
        private readonly ChecklistPresenter $presenter,
        private readonly ChecklistPrintService $print,
    ) {}

    public function compliance(ChecklistComplianceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $template = ChecklistTemplate::query()->findOrFail($data['template_id']);

        return $this->ok($this->query->complianceMatrix(
            $template,
            Carbon::parse($data['date_from'], 'Asia/Ho_Chi_Minh'),
            Carbon::parse($data['date_to'], 'Asia/Ho_Chi_Minh'),
        ));
    }

    public function index(StaffChecklistRequest $request): JsonResponse
    {
        $page = $this->query->paginate($request->validated());

        return $this->ok([
            'items' => collect($page->items())->map(fn (ChecklistSubmission $s) => $this->presenter->summary($s))->all(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(StaffChecklistRequest $request, ChecklistSubmission $checklist): JsonResponse
    {
        return $this->ok($this->presenter->detail($checklist));
    }

    public function review(ReviewChecklistRequest $request, ChecklistSubmission $checklist, ChecklistSubmissionService $service): JsonResponse
    {
        $submission = $service->review($checklist, $request->user(), $request->validated()['review_note'] ?? null);

        return $this->ok($this->presenter->detail($submission), 'Đã đánh dấu đã xem.');
    }

    public function pdf(StaffChecklistRequest $request, ChecklistSubmission $checklist): Response
    {
        return $this->print->pdf($checklist)->download($checklist->code.'.pdf');
    }

    public function xlsx(StaffChecklistRequest $request, ChecklistSubmission $checklist): Response
    {
        return $this->xlsxResponse($this->print->xlsx($checklist), $checklist->code.'.xlsx');
    }

    public function export(StaffChecklistRequest $request): Response
    {
        $rows = $this->query->paginate([...$request->validated(), 'per_page' => 100, 'page' => 1]);
        $all = collect($rows->items());
        for ($p = 2; $p <= min($rows->lastPage(), 50); $p++) {
            $all = $all->merge($this->query->paginate([...$request->validated(), 'per_page' => 100, 'page' => $p])->items());
        }

        return $this->xlsxResponse($this->print->listXlsx($all), 'checklist_'.now()->format('Ymd_His').'.xlsx');
    }

    private function xlsxResponse(string $binary, string $filename): Response
    {
        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
