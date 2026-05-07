<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRenewalLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_item_id',
        'action',
        'amount_paid',
        'notes',
        'updated_by_user_id',
    ];

    public function maintenanceItem(): BelongsTo
    {
        return $this->belongsTo(VehicleMaintenanceItem::class, 'maintenance_item_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
