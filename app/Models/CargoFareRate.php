<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargoFareRate extends Model
{
    protected $fillable = [
        'sort_order',
        'route_code',
        'route_label',
        'distance_km',
        'one_crate_50_40_50',
        'crates_2_to_5_50_40_50',
        'van_500kg',
        'van_1000kg',
        'van_2000kg',
        'loading_assist_per_point',
        'waiting_fee_per_hour',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'one_crate_50_40_50' => 'decimal:2',
        'crates_2_to_5_50_40_50' => 'decimal:2',
        'van_500kg' => 'decimal:2',
        'van_1000kg' => 'decimal:2',
        'van_2000kg' => 'decimal:2',
        'loading_assist_per_point' => 'decimal:2',
        'waiting_fee_per_hour' => 'decimal:2',
    ];
}
