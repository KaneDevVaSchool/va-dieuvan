<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\MenuItem;
use App\Models\Role;
use App\Models\TpProgram;
use App\Models\TpStudent;
use App\Models\TransportProvider;
use App\Models\Vehicle;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TrashController extends Controller
{
    use ApiResponses;

    /** @var array<string, class-string> */
    private const MODELS = [
        'dispatch_request' => DispatchRequest::class,
        'driver' => Driver::class,
        'vehicle' => Vehicle::class,
        'transport_provider' => TransportProvider::class,
        'tp_student' => TpStudent::class,
        'tp_program' => TpProgram::class,
        'role' => Role::class,
        'menu_item' => MenuItem::class,
    ];

    /**
     * Quyền tối thiểu (any-of) để KHÔI PHỤC / XÓA VĨNH VIỄN từng loại dữ liệu.
     * Không dùng chung `trip.view_all` (quyền XEM) cho thao tác hủy — least privilege.
     *
     * @var array<string, list<string>>
     */
    private const MANAGE_PERMISSIONS = [
        'dispatch_request' => ['request.approve', 'trip.view_all'],
        'driver' => ['resource.driver.manage'],
        'vehicle' => ['resource.vehicle.manage'],
        'transport_provider' => ['resource.provider.manage'],
        'tp_student' => ['tp_student.manage'],
        'tp_program' => ['tp_program.manage'],
        'role' => ['system.roles.manage'],
        'menu_item' => ['system.feature_toggles.manage'],
    ];

    /** SuperAdmin (hasPermission luôn true) hoặc có ít nhất một quyền quản lý loại này. */
    private function assertCanManageType(Request $request, string $type): void
    {
        $perms = self::MANAGE_PERMISSIONS[$type] ?? null;
        abort_if($perms === null, 422, 'Loại dữ liệu không hợp lệ.');

        $user = $request->user();
        foreach ($perms as $p) {
            if ($user->hasPermission($p)) {
                return;
            }
        }

        abort(403, 'Không có quyền quản lý thùng rác cho loại dữ liệu này.');
    }

    /** GET /trash/summary */
    public function summary(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('trip.view_all'), 403, 'Không có quyền xem thùng rác.');

        $counts = [];
        foreach (self::MODELS as $type => $modelClass) {
            $counts[$type] = (int) $modelClass::onlyTrashed()->count();
        }
        $counts['total'] = (int) array_sum($counts);

        return $this->ok(['counts' => $counts]);
    }

    /**
     * GET /trash
     *
     * Tham số:
     *   - type  : lọc theo loại model (tùy chọn; mặc định = tất cả)
     *   - q     : tìm kiếm theo từ khóa
     *   - page  : trang hiện tại (chỉ hoạt động khi `type` được chỉ định)
     *   - per_page : số bản ghi mỗi trang (mặc định = 20, tối đa = 100)
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('trip.view_all'), 403, 'Không có quyền xem thùng rác.');

        $type = $request->query('type');
        $q = (string) $request->query('q', '');
        $perPage = min((int) $request->query('per_page', 20), 100);
        $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : null;
        $to = $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : null;

        if ($type && array_key_exists($type, self::MODELS)) {
            $modelClass = self::MODELS[$type];
            $query = $modelClass::onlyTrashed()->orderByDesc('deleted_at');

            if ($q !== '') {
                $query = $this->applySearch($query, $type, $q);
            }
            if ($from) {
                $query->where('deleted_at', '>=', $from);
            }
            if ($to) {
                $query->where('deleted_at', '<=', $to);
            }

            $paged = $query->paginate($perPage);
            $items = collect($paged->items())->map(fn ($m) => $this->toItem($m, $type))->values();

            return $this->ok([
                'items' => $items,
                'meta' => [
                    'current_page' => $paged->currentPage(),
                    'per_page' => $paged->perPage(),
                    'total' => $paged->total(),
                    'last_page' => $paged->lastPage(),
                ],
            ]);
        }

        // Không có type: gom top-50 từ từng model, sắp theo deleted_at giảm dần.
        $all = collect();
        foreach (self::MODELS as $key => $modelClass) {
            $q2 = $modelClass::onlyTrashed()->orderByDesc('deleted_at');
            if ($from) {
                $q2->where('deleted_at', '>=', $from);
            }
            if ($to) {
                $q2->where('deleted_at', '<=', $to);
            }
            $rows = $q2->limit(50)->get();
            foreach ($rows as $m) {
                $all->push($this->toItem($m, $key));
            }
        }

        $sorted = $all->sortByDesc('deleted_at')->values();

        return $this->ok([
            'items' => $sorted,
            'meta' => [
                'current_page' => 1,
                'per_page' => $sorted->count(),
                'total' => $sorted->count(),
                'last_page' => 1,
            ],
        ]);
    }

    /** POST /trash/restore — một nhóm `{ type, ids }` hoặc nhiều nhóm `{ groups: [...] }` (một request). */
    public function restore(Request $request): JsonResponse
    {
        $groups = $this->validatedTrashGroups($request);
        $actorId = $request->user()->id;
        $restored = 0;

        DB::transaction(function () use ($request, $groups, $actorId, &$restored) {
            foreach ($groups as $group) {
                $this->assertCanManageType($request, $group['type']);
                $modelClass = self::MODELS[$group['type']];
                foreach ($group['ids'] as $id) {
                    $m = $modelClass::onlyTrashed()->find($id);
                    if (! $m) {
                        continue;
                    }
                    $m->restore();
                    app(AuditLogger::class)->log(
                        actorId: $actorId,
                        event: 'trash.restore',
                        auditable: $m,
                        before: null,
                        after: null,
                        metadata: ['type' => $group['type'], 'id' => $id],
                    );
                    $restored++;
                }
            }
        });

        return $this->ok(['restored' => $restored]);
    }

    /** POST /trash/force-delete — một nhóm `{ type, ids }` hoặc nhiều nhóm `{ groups: [...] }` (một request). */
    public function forceDelete(Request $request): JsonResponse
    {
        $groups = $this->validatedTrashGroups($request);
        $actorId = $request->user()->id;
        $deleted = 0;

        DB::transaction(function () use ($request, $groups, $actorId, &$deleted) {
            foreach ($groups as $group) {
                $this->assertCanManageType($request, $group['type']);
                $modelClass = self::MODELS[$group['type']];
                foreach ($group['ids'] as $id) {
                    $m = $modelClass::onlyTrashed()->find($id);
                    if (! $m) {
                        continue;
                    }
                    $before = $m->toArray();
                    $m->forceDelete();
                    app(AuditLogger::class)->log(
                        actorId: $actorId,
                        event: 'trash.force_delete',
                        auditable: null,
                        before: $before,
                        after: null,
                        metadata: ['type' => $group['type'], 'id' => $id],
                    );
                    $deleted++;
                }
            }
        });

        return $this->ok(['deleted' => $deleted]);
    }

    /**
     * @return list<array{type: string, ids: list<int>}>
     */
    private function validatedTrashGroups(Request $request): array
    {
        $typeRule = 'in:'.implode(',', array_keys(self::MODELS));

        if ($request->has('groups')) {
            $data = $request->validate([
                'groups' => ['required', 'array', 'min:1', 'max:'.count(self::MODELS)],
                'groups.*.type' => ['required', 'string', $typeRule],
                'groups.*.ids' => ['required', 'array', 'min:1', 'max:100'],
                'groups.*.ids.*' => ['required', 'integer', 'min:1'],
            ]);

            return $data['groups'];
        }

        $data = $request->validate([
            'type' => ['required', 'string', $typeRule],
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'min:1'],
        ]);

        return [
            ['type' => $data['type'], 'ids' => $data['ids']],
        ];
    }

    /** @return array<string, mixed> */
    private function toItem(mixed $model, string $type): array
    {
        return [
            'id' => $model->getKey(),
            'type' => $type,
            'label' => $this->getLabel($model, $type),
            'sublabel' => $this->getSublabel($model, $type),
            'deleted_at' => $model->deleted_at?->toISOString(),
        ];
    }

    private function getLabel(mixed $model, string $type): string
    {
        return match ($type) {
            'dispatch_request' => "#{$model->id} — ".($model->origin ?? '').' → '.($model->destination ?? ''),
            'driver' => $model->full_name ?? "Tài xế #{$model->id}",
            'vehicle' => $model->license_plate ?? "Xe #{$model->id}",
            'transport_provider' => $model->name ?? "Nhà CC #{$model->id}",
            'tp_student' => $model->full_name ?? "Học sinh #{$model->id}",
            'tp_program' => $model->name ?? "Chương trình #{$model->id}",
            'role' => $model->display_name ?? $model->name ?? "Vai trò #{$model->id}",
            'menu_item' => $model->label ?? $model->label_key ?? "Mục #{$model->id}",
            default => "#{$model->id}",
        };
    }

    private function getSublabel(mixed $model, string $type): string
    {
        return match ($type) {
            'dispatch_request' => 'Phiếu điều vận'.($model->status ? ' — '.$model->status : ''),
            'driver' => ($model->phone ?? $model->email ?? ''),
            'vehicle' => ($model->type ?? '').($model->seat_count ? " · {$model->seat_count} chỗ" : ''),
            'transport_provider' => ($model->contact_name ?? $model->contact_phone ?? ''),
            'tp_student' => ($model->grade ?? '').($model->class_name ? " {$model->class_name}" : ''),
            'tp_program' => ($model->code ? "[{$model->code}] " : '').($model->status ?? ''),
            'role' => ($model->name !== ($model->display_name ?? null) ? $model->name : '').($model->guard_name ? " [{$model->guard_name}]" : ''),
            'menu_item' => ($model->route_name ?? $model->url ?? ''),
            default => '',
        };
    }

    private function applySearch(mixed $query, string $type, string $q): mixed
    {
        $like = '%'.addcslashes($q, '%_\\').'%';

        return match ($type) {
            'dispatch_request' => $query->where(fn ($b) => $b->where('origin', 'like', $like)
                ->orWhere('destination', 'like', $like)
                ->orWhere('notes', 'like', $like)
                ->when(ctype_digit($q), fn ($i) => $i->orWhere('id', (int) $q))
            ),
            'driver' => $query->where(fn ($b) => $b->where('full_name', 'like', $like)->orWhere('phone', 'like', $like)
            ),
            'vehicle' => $query->where(fn ($b) => $b->where('license_plate', 'like', $like)->orWhere('owner_name', 'like', $like)
            ),
            'transport_provider' => $query->where(fn ($b) => $b->where('name', 'like', $like)->orWhere('contact_name', 'like', $like)
            ),
            'tp_student' => $query->where(fn ($b) => $b->where('full_name', 'like', $like)->orWhere('code', 'like', $like)
            ),
            'tp_program' => $query->where(fn ($b) => $b->where('name', 'like', $like)->orWhere('code', 'like', $like)
            ),
            'role' => $query->where(fn ($b) => $b->where('name', 'like', $like)->orWhere('display_name', 'like', $like)
            ),
            'menu_item' => $query->where(fn ($b) => $b->where('label', 'like', $like)->orWhere('label_key', 'like', $like)->orWhere('route_name', 'like', $like)
            ),
            default => $query,
        };
    }
}
