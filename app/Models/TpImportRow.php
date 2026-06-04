<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpImportRow extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'batch_id', 'row_number', 'raw_data', 'mapped_data', 'fixed_data',
        'validation_status', 'validation_errors', 'import_status',
        'student_id', 'import_error',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'mapped_data' => 'array',
        'fixed_data' => 'array',
        'validation_errors' => 'array',
        'created_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(TpImportBatch::class, 'batch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(TpStudent::class, 'student_id');
    }
}
