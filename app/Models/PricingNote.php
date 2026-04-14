<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PricingNote extends Model
{
    protected $fillable = [
        'category',
        'sort_order',
        'title',
        'body',
    ];

    /** @return MorphMany<ReferencePricingRevision, $this> */
    public function referencePricingRevisions(): MorphMany
    {
        return $this->morphMany(ReferencePricingRevision::class, 'revisionable');
    }
}
