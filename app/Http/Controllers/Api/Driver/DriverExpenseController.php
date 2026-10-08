<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Expenses\DriverExpenseRequest;
use App\Http\Requests\Api\Expenses\ExpenseCostCandidatesRequest;
use App\Http\Requests\Api\Expenses\SaveExpenseDocumentRequest;
use App\Http\Requests\Api\Expenses\SubmitExpenseDocumentRequest;
use App\Http\Requests\Api\Expenses\UploadExpenseScanRequest;
use App\Models\ExpenseDocument;
use App\Services\Expenses\ExpenseDocumentService;
use App\Services\Expenses\ExpensePresenter;
use App\Services\Expenses\ExpensePrintService;
use App\Services\Expenses\ExpenseQuery;
use App\Services\Expenses\ExpenseSettingsService;
use App\Support\ExpenseDocumentAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Hồ sơ đề xuất / thanh toán chi phí xe — phía người lập (tài xế).
 */
class DriverExpenseController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ExpenseDocumentService $service,
        private readonly ExpenseQuery $query,
        private readonly ExpensePresenter $presenter,
    ) {}

    public function index(DriverExpenseRequest $request): JsonResponse
    {
        $page = $this->query->paginate($request->validated(), $request->user());

        return $this->ok([
            'items' => collect($page->items())->map(fn (ExpenseDocument $d) => $this->presenter->summary($d))->all(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function defaults(DriverExpenseRequest $request, ExpenseSettingsService $settings): JsonResponse
    {
        return $this->ok($settings->driverDefaults($request->user()));
    }

    public function costCandidates(ExpenseCostCandidatesRequest $request): JsonResponse
    {
        $data = $request->validated();

        return $this->ok(['items' => $this->query->costCandidates(
            $request->user(),
            $data['doc_type'],
            isset($data['vehicle_id']) ? (int) $data['vehicle_id'] : null,
            isset($data['document_id']) ? (int) $data['document_id'] : null,
        )]);
    }

    public function show(DriverExpenseRequest $request, ExpenseDocument $expense): JsonResponse
    {
        abort_unless(ExpenseDocumentAccess::userOwns($request->user(), $expense), 403);

        return $this->ok($this->presenter->detail($expense, $request->user()));
    }

    public function store(SaveExpenseDocumentRequest $request): JsonResponse
    {
        $doc = $this->service->create($request->user(), $request->validated());

        return $this->created($this->presenter->detail($doc, $request->user()));
    }

    public function update(SaveExpenseDocumentRequest $request, ExpenseDocument $expense): JsonResponse
    {
        $doc = $this->service->update($expense, $request->user(), $request->validated());

        return $this->ok($this->presenter->detail($doc, $request->user()));
    }

    public function submit(SubmitExpenseDocumentRequest $request, ExpenseDocument $expense): JsonResponse
    {
        $doc = $this->service->submit($expense, $request->user(), (int) $request->validated()['lock_version']);

        return $this->ok($this->presenter->detail($doc, $request->user()), 'Đã nộp hồ sơ — in ra, trình ký rồi upload bản scan.');
    }

    public function cancel(DriverExpenseRequest $request, ExpenseDocument $expense): JsonResponse
    {
        $doc = $this->service->cancel($expense, $request->user());

        return $this->ok($this->presenter->detail($doc, $request->user()));
    }

    public function uploadScan(UploadExpenseScanRequest $request, ExpenseDocument $expense): JsonResponse
    {
        $this->service->uploadSignedScan($expense, $request->user(), $request->file('file'));

        return $this->ok($this->presenter->detail($expense->fresh(), $request->user()));
    }

    public function pdf(DriverExpenseRequest $request, ExpenseDocument $expense, ExpensePrintService $print): Response
    {
        abort_unless(ExpenseDocumentAccess::userOwns($request->user(), $expense), 403);

        return $print->pdf($expense)->download($expense->code.'.pdf');
    }

    public function xlsx(DriverExpenseRequest $request, ExpenseDocument $expense, ExpensePrintService $print): Response
    {
        abort_unless(ExpenseDocumentAccess::userOwns($request->user(), $expense), 403);

        return response($print->xlsx($expense), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$expense->code.'.xlsx"',
        ]);
    }
}
