<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\CargoFareRate;
use App\Models\PassengerFareRate;
use App\Models\PricingNote;
use Illuminate\Http\JsonResponse;

class ReferencePricingController extends Controller
{
    use ApiResponses;

    /**
     * Bảng giá tham chiếu (seed) — chỉ đọc, dùng cho tra cứu nội bộ.
     */
    public function index(): JsonResponse
    {
        return $this->ok([
            'passenger_fares' => PassengerFareRate::query()->orderBy('sort_order')->get(),
            'cargo_fares' => CargoFareRate::query()->orderBy('sort_order')->get(),
            'notes' => PricingNote::query()->orderBy('category')->orderBy('sort_order')->get(),
        ]);
    }
}
