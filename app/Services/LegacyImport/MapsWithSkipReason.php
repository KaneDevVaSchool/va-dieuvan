<?php

namespace App\Services\LegacyImport;

trait MapsWithSkipReason
{
    private ?string $lastSkipReason = null;

    protected function skip(?string $reason): null
    {
        $this->lastSkipReason = $reason;

        return null;
    }

    public function consumeSkipReason(): ?string
    {
        $reason = $this->lastSkipReason;
        $this->lastSkipReason = null;

        return $reason;
    }
}
