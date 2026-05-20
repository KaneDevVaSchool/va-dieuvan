<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\ListP2pPolicyTermsRequest;
use App\Http\Requests\Api\P2pPolicy\ShowPolicyGenerationRunRequest;
use App\Models\PolicyGenerationRun;

class PolicyGenerationRunController extends Controller
{
    use ApiResponses;

    public function show(ShowPolicyGenerationRunRequest $request, PolicyGenerationRun $policyGenerationRun)
    {
        $policyGenerationRun->load('p2pPolicyTerm.academicTerm');

        return $this->ok($policyGenerationRun);
    }

    public function index(ListP2pPolicyTermsRequest $request)
    {
        $termId = (int) $request->query('p2p_policy_term_id', 0);
        $q = PolicyGenerationRun::query()->orderByDesc('id');
        if ($termId > 0) {
            $q->where('p2p_policy_term_id', $termId);
        }

        $page = $q->paginate(20);

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
}
