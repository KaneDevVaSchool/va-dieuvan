<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TpAbsenceReason extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code', 'label_vi', 'default_category', 'active', 'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
