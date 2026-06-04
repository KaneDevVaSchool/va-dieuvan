<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TpImportBatch extends Model
{
    protected $fillable = [
        'original_filename', 'stored_path', 'file_size', 'file_type',
        'total_rows', 'header_row', 'column_mapping', 'auto_fix_rules',
        'valid_rows', 'warning_rows', 'error_rows', 'imported_rows', 'skipped_rows',
        'status', 'target_program_id', 'error_report_path',
        'imported_by', 'completed_at', 'error_message', 'settings',
    ];

    protected $casts = [
        'header_row' => 'array',
        'column_mapping' => 'array',
        'auto_fix_rules' => 'array',
        'settings' => 'array',
        'completed_at' => 'datetime',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(TpImportRow::class, 'batch_id');
    }

    public function targetProgram(): BelongsTo
    {
        return $this->belongsTo(TpProgram::class, 'target_program_id');
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
