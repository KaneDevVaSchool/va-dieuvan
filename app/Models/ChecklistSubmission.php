<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChecklistSubmission extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_REVIEWED = 'reviewed';

    public const RESULT_PASS = 'pass';

    public const RESULT_FAIL = 'fail';

    protected $fillable = [
        'code',
        'template_id',
        'template_version',
        'driver_id',
        'user_id',
        'vehicle_id',
        'trip_id',
        'tp_program_day_id',
        'period_type',
        'period_key',
        'started_at',
        'submitted_at',
        'client_submitted_at',
        'location_text',
        'lat',
        'lng',
        'trip_info',
        'result',
        'critical_ng_count',
        'non_critical_ng_count',
        'note',
        'inspector_name',
        'confirmed_statement_at',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'lock_version',
    ];

    protected $casts = [
        'trip_info' => 'array',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'client_submitted_at' => 'datetime',
        'confirmed_statement_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'lat' => 'float',
        'lng' => 'float',
        'critical_ng_count' => 'integer',
        'non_critical_ng_count' => 'integer',
        'lock_version' => 'integer',
        'template_version' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id')->withTrashed();
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function tpProgramDay(): BelongsTo
    {
        return $this->belongsTo(TpProgramDay::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistSubmissionItem::class, 'submission_id')->orderBy('sort_order')->orderBy('id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
