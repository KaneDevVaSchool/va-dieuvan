<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

/**
 * Kho chứng từ: xem tập trung chứng từ xe (quyền như xem chứng từ trên màn xe) hoặc tài xế.
 */
class ListComplianceDocumentsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->input('owner_type') === 'driver'
            ? $this->allowAnyOf(['resource.driver.manage'])
            : $this->allowAnyOf(['resource.vehicle.manage', 'trip.assign']);
    }

    public function rules(): array
    {
        $docTypes = $this->input('owner_type') === 'driver'
            ? StoreDriverComplianceDocumentRequest::DOC_TYPES
            : StoreVehicleComplianceDocumentRequest::DOC_TYPES;

        return [
            'owner_type' => ['required', Rule::in(['vehicle', 'driver'])],
            'doc_type' => ['nullable', 'string', Rule::in($docTypes)],
            'state' => ['nullable', Rule::in(['exp', 'soon', 'ok', 'none'])],
            'status' => ['nullable', Rule::in(['active', 'superseded', 'all'])],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
