<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Driver;
use App\Services\Resources\ComplianceDocumentService;

/**
 * Gia hạn chứng từ xe / tài xế: bắt buộc file scan mới + ngày hết hạn mới.
 * Kiểm tra "hạn mới sau hạn cũ" nằm trong ComplianceDocumentService::renew (cần khóa bản cũ).
 */
class RenewComplianceDocumentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $permission = $this->route('driver') instanceof Driver
            ? 'resource.driver.manage'
            : 'resource.vehicle.manage';

        return $this->allowAnyOf([$permission]);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', ComplianceDocumentService::FILE_MIMES_RULE, 'max:10240'],
            'expires_at' => ['required', 'date'],
            'issued_at' => ['nullable', 'date', 'before_or_equal:expires_at'],
            'title' => ['nullable', 'string', 'max:255'],
            'document_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'file scan chứng từ',
            'expires_at' => 'ngày hết hạn',
            'issued_at' => 'ngày cấp',
            'document_no' => 'số chứng từ',
        ];
    }
}
