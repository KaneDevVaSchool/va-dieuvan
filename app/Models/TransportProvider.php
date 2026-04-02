<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'contact_name',
        'contact_phone',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}

