<?php

namespace App\Http\Requests\Api\Trips;

use App\Http\Requests\Api\ApiFormRequest;

class AssignTripRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['trip.assign']);
    }

    public function rules(): array
    {
        return [
            'lock_version'                              => ['required', 'integer', 'min:0'],
            'vehicle_id'                                => ['nullable', 'integer', 'min:1'],
            'driver_id'                                 => ['nullable', 'integer', 'min:1'],
            'transport_provider_id'                     => ['nullable', 'integer', 'min:1'],
            'external_vehicle_ref'                      => ['nullable', 'string', 'max:255'],
            'external_driver_ref'                       => ['nullable', 'string', 'max:255'],

            // Danh sách đầy đủ phương tiện bổ sung (taxi + NCC), lưu để hydrate lại sau refresh.
            'supplement_transports'                     => ['nullable', 'array'],
            'supplement_transports.taxis'               => ['nullable', 'array'],
            'supplement_transports.taxis.*.id'          => ['required', 'string'],
            'supplement_transports.taxis.*.label'       => ['required', 'string', 'max:255'],
            'supplement_transports.taxis.*.supplementSeats' => ['nullable', 'integer', 'min:1'],
            'supplement_transports.taxis.*.isCustom'    => ['nullable', 'boolean'],
            'supplement_transports.taxis.*.externalVehicleRef' => ['nullable', 'string', 'max:255'],
            'supplement_transports.taxis.*.externalDriverRef'  => ['nullable', 'string', 'max:255'],

            'supplement_transports.vendors'             => ['nullable', 'array'],
            'supplement_transports.vendors.*.id'        => ['required', 'string'],
            'supplement_transports.vendors.*.label'     => ['required', 'string', 'max:255'],
            'supplement_transports.vendors.*.supplementSeats' => ['nullable', 'integer', 'min:1'],
            'supplement_transports.vendors.*.isCustom'  => ['nullable', 'boolean'],
            'supplement_transports.vendors.*.externalVehicleRef' => ['nullable', 'string', 'max:255'],
            'supplement_transports.vendors.*.externalDriverRef'  => ['nullable', 'string', 'max:255'],
        ];
    }
}
