<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReferencePricing\ListReferencePricingRevisionsRequest;
use App\Http\Requests\Api\ReferencePricing\SuggestReferencePricingRequest;
use App\Http\Requests\Api\ReferencePricing\UpdateCargoFareRateRequest;
use App\Http\Requests\Api\ReferencePricing\UpdatePassengerFareRateRequest;
use App\Http\Requests\Api\ReferencePricing\UpdatePricingNoteRequest;
use App\Models\CargoFareRate;
use App\Models\PassengerFareRate;
use App\Models\PricingNote;
use App\Models\ReferencePricingRevision;
use App\Services\ReferencePricing\PricingSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReferencePricingController extends Controller
{
    use ApiResponses;

    /**
     * Bảng giá tham chiếu — tra cứu nội bộ.
     */
    public function index(): JsonResponse
    {
        return $this->ok([
            'passenger_fares' => PassengerFareRate::query()->orderBy('sort_order')->get(),
            'cargo_fares' => CargoFareRate::query()->orderBy('sort_order')->get(),
            'notes' => PricingNote::query()->orderBy('category')->orderBy('sort_order')->get(),
        ])->header('Cache-Control', 'private, no-cache, no-store, must-revalidate');
    }

    public function suggest(SuggestReferencePricingRequest $request, PricingSuggestionService $service): JsonResponse
    {
        $data = $request->validated();

        return $this->ok($service->suggest(
            (string) $data['trip_type'],
            $data['origin'] ?? null,
            $data['destination'] ?? null,
            isset($data['passenger_count']) ? (int) $data['passenger_count'] : null,
        ));
    }

    /**
     * Lịch sử thay đổi theo từng bản ghi (snapshot trạng thái trước khi lưu phiên mới).
     */
    public function revisions(ListReferencePricingRevisionsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $class = $this->entityClass($data['type']);
        $model = $class::query()->find($data['id']);
        if (! $model) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], 404);
        }

        $items = ReferencePricingRevision::query()
            ->where('revisionable_type', $class)
            ->where('revisionable_id', $model->getKey())
            ->with(['user:id,name,email'])
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return $this->ok([
            'items' => $items,
        ]);
    }

    public function updatePassengerFare(UpdatePassengerFareRateRequest $request, PassengerFareRate $passengerFareRate): JsonResponse
    {
        $validated = $request->validated();
        if ($validated === []) {
            return response()->json(['message' => 'Không có trường hợp lệ để cập nhật.'], 422);
        }

        $passengerFareRate = $passengerFareRate->fresh();
        $beforeSnapshot = $passengerFareRate->toArray();
        $passengerFareRate->fill($validated);
        if (! $passengerFareRate->isDirty()) {
            return $this->ok(['passenger_fare' => $passengerFareRate]);
        }

        DB::transaction(function () use ($passengerFareRate, $beforeSnapshot, $request) {
            ReferencePricingRevision::query()->create([
                'revisionable_type' => PassengerFareRate::class,
                'revisionable_id' => $passengerFareRate->id,
                'user_id' => $request->user()?->id,
                'snapshot' => $beforeSnapshot,
            ]);
            $passengerFareRate->save();
        });

        return $this->ok(['passenger_fare' => $passengerFareRate->fresh()]);
    }

    public function updateCargoFare(UpdateCargoFareRateRequest $request, CargoFareRate $cargoFareRate): JsonResponse
    {
        $validated = $request->validated();
        if ($validated === []) {
            return response()->json(['message' => 'Không có trường hợp lệ để cập nhật.'], 422);
        }

        $cargoFareRate = $cargoFareRate->fresh();
        $beforeSnapshot = $cargoFareRate->toArray();
        $cargoFareRate->fill($validated);
        if (! $cargoFareRate->isDirty()) {
            return $this->ok(['cargo_fare' => $cargoFareRate]);
        }

        DB::transaction(function () use ($cargoFareRate, $beforeSnapshot, $request) {
            ReferencePricingRevision::query()->create([
                'revisionable_type' => CargoFareRate::class,
                'revisionable_id' => $cargoFareRate->id,
                'user_id' => $request->user()?->id,
                'snapshot' => $beforeSnapshot,
            ]);
            $cargoFareRate->save();
        });

        return $this->ok(['cargo_fare' => $cargoFareRate->fresh()]);
    }

    public function updatePricingNote(UpdatePricingNoteRequest $request, PricingNote $pricingNote): JsonResponse
    {
        $validated = $request->validated();
        if ($validated === []) {
            return response()->json(['message' => 'Không có trường hợp lệ để cập nhật.'], 422);
        }

        $pricingNote = $pricingNote->fresh();
        $beforeSnapshot = $pricingNote->toArray();
        $pricingNote->fill($validated);
        if (! $pricingNote->isDirty()) {
            return $this->ok(['note' => $pricingNote]);
        }

        DB::transaction(function () use ($pricingNote, $beforeSnapshot, $request) {
            ReferencePricingRevision::query()->create([
                'revisionable_type' => PricingNote::class,
                'revisionable_id' => $pricingNote->id,
                'user_id' => $request->user()?->id,
                'snapshot' => $beforeSnapshot,
            ]);
            $pricingNote->save();
        });

        return $this->ok(['note' => $pricingNote->fresh()]);
    }

    /**
     * @return class-string<PassengerFareRate|CargoFareRate|PricingNote>
     */
    private function entityClass(string $type): string
    {
        return match ($type) {
            'passenger_fare_rate' => PassengerFareRate::class,
            'cargo_fare_rate' => CargoFareRate::class,
            'pricing_note' => PricingNote::class,
        };
    }
}
