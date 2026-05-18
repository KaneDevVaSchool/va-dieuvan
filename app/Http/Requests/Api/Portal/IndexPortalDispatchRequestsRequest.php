<?php

namespace App\Http\Requests\Api\Portal;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\User;

class IndexPortalDispatchRequestsRequest extends ApiFormRequest
{
    use EnsuresPortalUser;

    public function authorize(): bool
    {
        return $this->portalUserMayAccess($this->user());
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'q' => ['sometimes', 'string', 'max:120'],
            'sort' => ['sometimes', 'string', 'in:depart_desc,depart_asc,created_desc,created_asc'],
            'filter' => ['sometimes', 'string', 'in:all,pending,approved,rejected,returned'],
            'trip_type' => ['sometimes', 'string', 'in:business,cargo,door_to_door,point_to_point'],
            'is_urgent' => ['sometimes', 'boolean'],
            'date_from' => ['sometimes', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'date_format:Y-m-d'],
        ];
    }
}
