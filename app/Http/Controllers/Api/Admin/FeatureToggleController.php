<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreFeatureToggleRequest;
use App\Http\Requests\Api\Admin\UpdateFeatureToggleRequest;
use App\Models\FeatureToggle;
use App\Services\FeatureToggleService;
use Illuminate\Http\JsonResponse;

class FeatureToggleController extends Controller
{
    use ApiResponses;

    public function __construct()
    {
        $this->middleware('permission:any,system.feature_toggles.manage');
    }

    public function index(FeatureToggleService $features): JsonResponse
    {
        return $this->ok($features->allFresh());
    }

    public function show(FeatureToggle $feature_toggle): JsonResponse
    {
        return $this->ok($feature_toggle);
    }

    public function store(StoreFeatureToggleRequest $request, FeatureToggleService $features): JsonResponse
    {
        return $this->created($features->create($request->validated()));
    }

    public function update(UpdateFeatureToggleRequest $request, FeatureToggle $feature_toggle, FeatureToggleService $features): JsonResponse
    {
        return $this->ok($features->update($feature_toggle, $request->validated()));
    }

    public function destroy(FeatureToggle $feature_toggle, FeatureToggleService $features): JsonResponse
    {
        $features->delete($feature_toggle);

        return $this->ok(['deleted' => true]);
    }
}
