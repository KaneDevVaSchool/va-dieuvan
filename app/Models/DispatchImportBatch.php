<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchImportBatch extends Model
{
    protected $fillable = [
        'original_filename',
        'stored_path',
        'file_size',
        'selected_sheets',
        'status',
        'analyze_stats',
        'execute_stats',
        'issues',
        'issue_count',
        'error_report_path',
        'imported_by',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'selected_sheets' => 'array',
        'analyze_stats' => 'array',
        'execute_stats' => 'array',
        'issues' => 'array',
        'completed_at' => 'datetime',
    ];

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
