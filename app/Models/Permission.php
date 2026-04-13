<?php

namespace App\Models;

use App\Support\PermissionPlainVi;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $appends = ['plain_summary'];

    /**
     * Mô tả dễ hiểu: ưu tiên nội dung lưu DB, nếu trống thì lấy từ bản mẫu JSON theo tên quyền.
     */
    public function getPlainSummaryAttribute(): ?string
    {
        $raw = $this->attributes['plain_description'] ?? null;
        if (is_string($raw) && trim($raw) !== '') {
            return $raw;
        }

        return PermissionPlainVi::text($this->name);
    }
}
