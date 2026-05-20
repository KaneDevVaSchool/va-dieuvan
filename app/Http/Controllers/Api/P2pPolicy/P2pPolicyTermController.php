<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\ActivateP2pPolicyTermRequest;
use App\Http\Requests\Api\P2pPolicy\ListP2pPolicyTermsRequest;
use App\Http\Requests\Api\P2pPolicy\StoreP2pPolicyTermRequest;
use App\Http\Requests\Api\P2pPolicy\SyncP2pPolicyTermCalendarRequest;
use App\Http\Requests\Api\P2pPolicy\UpdateP2pPolicyTermRequest;
use App\Models\P2pPolicyTerm;
use App\Models\PolicyTermHoliday;
use App\Models\PolicyTermSkipDate;
use App\Services\Auditing\AuditLogger;
use App\Services\P2pPolicy\P2pPolicyActivationService;
use App\Services\P2pPolicy\P2pPolicyReadinessValidator;
use App\Support\P2pPolicy;
use Illuminate\Support\Facades\DB;

class P2pPolicyTermController extends Controller
{
    use ApiResponses;

    public function index(ListP2pPolicyTermsRequest $request)
    {
        $data = $request->validated();
        $q = P2pPolicyTerm::query()
            ->with('academicTerm')
            ->orderByDesc('id');

        if (! empty($data['status'])) {
            $q->where('status', $data['status']);
        }

        $perPage = (int) ($data['per_page'] ?? 20);
        $page = $q->paginate($perPage);

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

    public function show(ListP2pPolicyTermsRequest $request, P2pPolicyTerm $p2pPolicyTerm)
    {
        $p2pPolicyTerm->load(['academicTerm', 'holidays', 'skipDates', 'activeGenerationRun']);

        return $this->ok($p2pPolicyTerm);
    }

    public function readiness(ListP2pPolicyTermsRequest $request, P2pPolicyTerm $p2pPolicyTerm, P2pPolicyReadinessValidator $validator)
    {
        $p2pPolicyTerm->load(['holidays', 'skipDates']);

        return $this->ok($validator->assess($p2pPolicyTerm));
    }

    public function store(StoreP2pPolicyTermRequest $request)
    {
        $data = $request->validated();
        $term = P2pPolicyTerm::create([
            'academic_term_id' => $data['academic_term_id'],
            'operating_from' => $data['operating_from'],
            'operating_to' => $data['operating_to'],
            'default_morning_start' => $this->timeValue($data['default_morning_start'] ?? '06:00'),
            'default_morning_end' => $this->timeValue($data['default_morning_end'] ?? '07:00'),
            'default_afternoon_start' => $this->timeValue($data['default_afternoon_start'] ?? '15:30'),
            'default_afternoon_end' => $this->timeValue($data['default_afternoon_end'] ?? '16:30'),
            'weekdays_mask' => $data['weekdays_mask'] ?? P2pPolicy::DEFAULT_WEEKDAYS_MASK,
            'status' => 'draft',
        ]);

        app(AuditLogger::class)->log($request->user()->id, 'p2p_policy.term.create', $term, null, $term->toArray());

        return $this->created($term->load('academicTerm'));
    }

    public function update(UpdateP2pPolicyTermRequest $request, P2pPolicyTerm $p2pPolicyTerm)
    {
        if ($p2pPolicyTerm->status !== 'draft') {
            abort(409, 'Chỉ chỉnh sửa kỳ P2P Policy ở trạng thái nháp.');
        }

        $data = $request->validated();
        foreach (['default_morning_start', 'default_morning_end', 'default_afternoon_start', 'default_afternoon_end'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null) {
                $data[$key] = $this->timeValue($data[$key]);
            }
        }

        $before = $p2pPolicyTerm->toArray();
        $p2pPolicyTerm->update($data);

        app(AuditLogger::class)->log($request->user()->id, 'p2p_policy.term.update', $p2pPolicyTerm, $before, $p2pPolicyTerm->toArray());

        return $this->ok($p2pPolicyTerm->fresh()->load('academicTerm'));
    }

    public function syncCalendar(SyncP2pPolicyTermCalendarRequest $request, P2pPolicyTerm $p2pPolicyTerm)
    {
        if ($p2pPolicyTerm->status !== 'draft') {
            abort(409, 'Chỉ chỉnh sửa lịch khi kỳ đang ở trạng thái nháp.');
        }

        $data = $request->validated();

        DB::transaction(function () use ($p2pPolicyTerm, $data, $request) {
            if (array_key_exists('holidays', $data)) {
                PolicyTermHoliday::query()->where('p2p_policy_term_id', $p2pPolicyTerm->id)->delete();
                foreach ($data['holidays'] ?? [] as $row) {
                    PolicyTermHoliday::create([
                        'p2p_policy_term_id' => $p2pPolicyTerm->id,
                        'holiday_date' => $row['holiday_date'],
                        'label' => $row['label'] ?? null,
                    ]);
                }
            }

            if (array_key_exists('skip_dates', $data)) {
                PolicyTermSkipDate::query()->where('p2p_policy_term_id', $p2pPolicyTerm->id)->delete();
                foreach ($data['skip_dates'] ?? [] as $row) {
                    PolicyTermSkipDate::create([
                        'p2p_policy_term_id' => $p2pPolicyTerm->id,
                        'skip_date' => $row['skip_date'],
                        'reason' => $row['reason'] ?? null,
                        'created_by' => $request->user()->id,
                    ]);
                }
            }
        });

        return $this->ok($p2pPolicyTerm->fresh()->load(['holidays', 'skipDates']));
    }

    public function activate(ActivateP2pPolicyTermRequest $request, P2pPolicyTerm $p2pPolicyTerm, P2pPolicyActivationService $activation)
    {
        $run = $activation->activate($p2pPolicyTerm, (int) $request->user()->id);

        app(AuditLogger::class)->log(
            $request->user()->id,
            'p2p_policy.term.activate',
            $p2pPolicyTerm,
            null,
            ['generation_run_id' => $run->id],
        );

        return $this->ok([
            'term' => $p2pPolicyTerm->fresh()->load('academicTerm'),
            'generation_run' => $run,
        ]);
    }

    private function timeValue(string $hm): string
    {
        return preg_match('/^\d{2}:\d{2}$/', $hm) ? "{$hm}:00" : $hm;
    }
}
