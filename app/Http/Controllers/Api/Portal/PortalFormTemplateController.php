<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Portal\StorePortalFormTemplateRequest;
use App\Http\Requests\Api\Portal\UpdatePortalFormTemplateRequest;
use App\Models\PortalFormTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalFormTemplateController extends Controller
{
    use ApiResponses;

    /** Giới hạn số biểu mẫu lưu trên mỗi user. */
    private const MAX_TEMPLATES_PER_USER = 10;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $templates = PortalFormTemplate::query()
            ->where('user_id', $user->getKey())
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get(['id', 'name', 'trip_type', 'created_at', 'updated_at']);

        return $this->ok($templates->values());
    }

    public function store(StorePortalFormTemplateRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $count = PortalFormTemplate::query()
            ->where('user_id', $user->getKey())
            ->count();

        if ($count >= self::MAX_TEMPLATES_PER_USER) {
            abort(422, __('portal.template_limit_error', ['max' => self::MAX_TEMPLATES_PER_USER]));
        }

        $template = PortalFormTemplate::create([
            'user_id' => $user->getKey(),
            'name' => $data['name'],
            'trip_type' => $data['trip_type'],
            'wizard_snapshot' => $data['wizard_snapshot'] ?? null,
        ]);

        return $this->created($this->presentTemplate($template));
    }

    public function show(Request $request, PortalFormTemplate $portalFormTemplate): JsonResponse
    {
        $this->authorizeOwner($request, $portalFormTemplate);

        return $this->ok($this->presentTemplate($portalFormTemplate, withSnapshot: true));
    }

    public function update(UpdatePortalFormTemplateRequest $request, PortalFormTemplate $portalFormTemplate): JsonResponse
    {
        $this->authorizeOwner($request, $portalFormTemplate);

        $portalFormTemplate->update(['name' => $request->validated()['name']]);

        return $this->ok($this->presentTemplate($portalFormTemplate));
    }

    public function destroy(Request $request, PortalFormTemplate $portalFormTemplate): JsonResponse
    {
        $this->authorizeOwner($request, $portalFormTemplate);

        $portalFormTemplate->delete();

        return $this->ok(['deleted' => true]);
    }

    private function authorizeOwner(Request $request, PortalFormTemplate $template): void
    {
        if ((int) $template->user_id !== (int) $request->user()->getKey()) {
            abort(403);
        }
    }

    private function presentTemplate(PortalFormTemplate $template, bool $withSnapshot = false): array
    {
        $data = [
            'id' => $template->id,
            'name' => $template->name,
            'trip_type' => $template->trip_type,
            'created_at' => $template->created_at?->toISOString(),
            'updated_at' => $template->updated_at?->toISOString(),
        ];

        if ($withSnapshot) {
            $data['wizard_snapshot'] = $template->wizard_snapshot;
        }

        return $data;
    }
}
