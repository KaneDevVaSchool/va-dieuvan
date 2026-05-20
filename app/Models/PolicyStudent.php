<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyStudent extends Model
{
    protected $fillable = [
        'policy_route_id',
        'student_id',
        'student_code',
        'student_name',
        'class_name',
        'direction',
        'policy_type',
        'policy_note',
        'contract_number',
        'sbs_contract',
        'effective_from',
        'effective_to',
        'is_active',
        'active_weekdays_mask',
        'imported_from',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
        'active_weekdays_mask' => 'integer',
    ];

    public function policyRoute(): BelongsTo
    {
        return $this->belongsTo(PolicyRoute::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
