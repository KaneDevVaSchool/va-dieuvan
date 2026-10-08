<?php

namespace App\Http\Controllers\Api\Checklist;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Checklist\ManageChecklistSettingsRequest;
use App\Models\ChecklistReminderSlot;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use App\Services\Checklist\ChecklistTemplateService;
use Illuminate\Http\JsonResponse;

/**
 * Cấu hình checklist: template, mục kiểm tra, khung giờ nhắc.
 */
class ChecklistSettingsController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly ChecklistTemplateService $service) {}

    public function index(ManageChecklistSettingsRequest $request): JsonResponse
    {
        return $this->ok(['templates' => $this->service->listForSettings()]);
    }

    public function updateTemplate(ManageChecklistSettingsRequest $request, ChecklistTemplate $template): JsonResponse
    {
        $t = $this->service->updateTemplate($template, $request->user(), $request->validated());

        return $this->ok($this->service->presentTemplate($t));
    }

    public function storeItem(ManageChecklistSettingsRequest $request, ChecklistTemplate $template): JsonResponse
    {
        return $this->created($this->service->saveItem($template, $request->user(), $request->validated()));
    }

    public function updateItem(ManageChecklistSettingsRequest $request, ChecklistTemplate $template, ChecklistTemplateItem $item): JsonResponse
    {
        return $this->ok($this->service->saveItem($template, $request->user(), $request->validated(), $item));
    }

    public function destroyItem(ManageChecklistSettingsRequest $request, ChecklistTemplate $template, ChecklistTemplateItem $item): JsonResponse
    {
        $this->service->deleteItem($template, $request->user(), $item);

        return $this->ok(['deleted' => true]);
    }

    public function reorderItems(ManageChecklistSettingsRequest $request, ChecklistTemplate $template): JsonResponse
    {
        $this->service->reorderItems($template, $request->user(), $request->validated()['ids']);

        return $this->ok(['reordered' => true]);
    }

    public function storeSlot(ManageChecklistSettingsRequest $request): JsonResponse
    {
        return $this->created($this->service->saveSlot($request->user(), $request->validated()));
    }

    public function updateSlot(ManageChecklistSettingsRequest $request, ChecklistReminderSlot $slot): JsonResponse
    {
        return $this->ok($this->service->saveSlot($request->user(), $request->validated(), $slot));
    }

    public function destroySlot(ManageChecklistSettingsRequest $request, ChecklistReminderSlot $slot): JsonResponse
    {
        $this->service->deleteSlot($request->user(), $slot);

        return $this->ok(['deleted' => true]);
    }
}
