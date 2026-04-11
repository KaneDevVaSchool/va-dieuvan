<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingNote extends Model
{
    protected $fillable = [
        'category',
        'sort_order',
        'title',
        'body',
    ];
}
