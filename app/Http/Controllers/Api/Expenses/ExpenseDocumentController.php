<?php

namespace App\Http\Controllers\Api\Expenses;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Expenses\ManageExpenseRequest;
use App\Http\Requests\Api\Expenses\StaffExpenseRequest;
use App\Http\Requests\Api\Expenses\UploadExpenseScanRequest;
use App\Models\ExpenseDocument;
use App\Services\Expenses\ExpenseDocumentService;
use App\Services\Expenses\ExpensePresenter;
use App\Services\Expenses\ExpensePrintService;
use App\Services\Expenses\ExpenseQuery;
use App\Services\Expenses\ExpenseSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Hồ sơ chi phí xe — back-office (điều phối / kế toán).
 */
class ExpenseDocumentController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ExpenseDocumentService $service,
        private readonly ExpenseQuery $query,
        private readonly ExpensePresenter $presenter,
    ) {}

    public function index(StaffExpenseRequest $request): JsonResponse
    {
        $page = $this->query->paginate($request->validated());

        return $this->ok([
            'items' => collect($page->items())->map(fn (ExpenseDocument $d) => $this->presenter->summary($d))->all(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(StaffExpenseRequest $request, ExpenseDocument $expense): JsonResponse
    {
        return $this->ok($this->presenter->detail($expense, $request->user()));
    }

    public function reject(ManageExpenseRequest $request, ExpenseDocument $expense): JsonResponse
    {
        $doc = $this->service->reject($expense, $request->user(), $request->validated()['reason']);

        return $this->ok($this->presenter->detail($doc, $request->user()), 'Đã trả lại hồ sơ.');
    }

    public function markPaid(ManageExpenseRequest $request, ExpenseDocument $expense): JsonResponse
    {
        $doc = $this->service->markPaid($expense, $request->user(), $request->validated()['paid_at'] ?? null);

        return $this->ok($this->presenter->detail($doc, $request->user()), 'Đã đánh dấu đã thanh toán.');
    }

    public function cancel(ManageExpenseRequest $request, ExpenseDocument $expense): JsonResponse
    {
        $doc = $this->service->cancel($expense, $request->user());

        return $this->ok($this->presenter->detail($doc, $request->user()));
    }

    public function uploadScan(UploadExpenseScanRequest $request, ExpenseDocument $expense): JsonResponse
    {
        $this->service->uploadSignedScan($expense, $request->user(), $request->file('file'));

        return $this->ok($this->presenter->detail($expense->fresh(), $request->user()));
    }

    public function pdf(StaffExpenseRequest $request, ExpenseDocument $expense, ExpensePrintService $print): Response
    {
        return $print->pdf($expense)->download($expense->code.'.pdf');
    }

    public function xlsx(StaffExpenseRequest $request, ExpenseDocument $expense, ExpensePrintService $print): Response
    {
        return response($print->xlsx($expense), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$expense->code.'.xlsx"',
        ]);
    }

    public function settings(ManageExpenseRequest $request, ExpenseSettingsService $settings): JsonResponse
    {
        return $this->ok($settings->present());
    }

    public function updateSettings(ManageExpenseRequest $request, ExpenseSettingsService $settings): JsonResponse
    {
        return $this->ok($settings->update($request->user(), $request->validated()), 'Đã lưu cấu hình mẫu in.');
    }
}
