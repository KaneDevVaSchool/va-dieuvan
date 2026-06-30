<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportProvider extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'contact_name',
        'contact_phone',
        'contact_email',
        'notes',
        'is_active',
        'contract_number',
        'contract_signed_at',
        'contract_expires_at',
        'services',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'contract_signed_at' => 'date',
        'contract_expires_at' => 'date',
        'services' => 'array',
    ];

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (TransportProvider $provider): void {
            if ($provider->isForceDeleting()) {
                return;
            }
            Trip::where('transport_provider_id', $provider->id)->update(['transport_provider_id' => null]);
        });
    }
}
