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
        $supplementRules = [
            'supplement_transports' => ['nullable', 'array'],
            'supplement_transports.taxis' => ['nullable', 'array'],
            'supplement_transports.taxis.*.id' => ['required', 'string'],
            'supplement_transports.taxis.*.label' => ['required', 'string', 'max:255'],
            'supplement_transports.taxis.*.supplementSeats' => ['nullable', 'integer', 'min:1'],
            'supplement_transports.taxis.*.isCustom' => ['nullable', 'boolean'],
            'supplement_transports.taxis.*.externalVehicleRef' => ['nullable', 'string', 'max:255'],
            'supplement_transports.taxis.*.externalDriverRef' => ['nullable', 'string', 'max:255'],
            'supplement_transports.taxis.*.contactNotes' => ['nullable', 'string', 'max:500'],
            'supplement_transports.taxis.*.servicePrice' => ['nullable', 'numeric', 'min:0'],
            'supplement_transports.vendors' => ['nullable', 'array'],
            'supplement_transports.vendors.*.id' => ['required', 'string'],
            'supplement_transports.vendors.*.label' => ['required', 'string', 'max:255'],
            'supplement_transports.vendors.*.supplementSeats' => ['nullable', 'integer', 'min:1'],
            'supplement_transports.vendors.*.isCustom' => ['nullable', 'boolean'],
            'supplement_transports.vendors.*.externalVehicleRef' => ['nullable', 'string', 'max:255'],
            'supplement_transports.vendors.*.externalDriverRef' => ['nullable', 'string', 'max:255'],
            'supplement_transports.vendors.*.contactNotes' => ['nullable', 'string', 'max:500'],
            'supplement_transports.internal_vehicles' => ['nullable', 'array'],
            'supplement_transports.internal_vehicles.*.id' => ['required', 'string'],
            'supplement_transports.internal_vehicles.*.label' => ['required', 'string', 'max:255'],
            'supplement_transports.internal_drivers' => ['nullable', 'array'],
            'supplement_transports.internal_drivers.*.id' => ['required', 'string'],
            'supplement_transports.internal_drivers.*.label' => ['required', 'string', 'max:255'],
        ];

        return array_merge([
            'lock_version' => ['required', 'integer', 'min:0'],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
            'driver_id' => ['nullable', 'integer', 'min:1'],
            'transport_provider_id' => ['nullable', 'integer', 'min:1'],
            'external_vehicle_ref' => ['nullable', 'string', 'max:255'],
            'external_driver_ref' => ['nullable', 'string', 'max:255'],
            'schedule_assignments' => ['nullable', 'array'],
            'schedule_assignments.*.key' => ['required_with:schedule_assignments', 'string', 'max:64'],
            'schedule_assignments.*.vehicle_id' => ['nullable', 'integer', 'min:1'],
            'schedule_assignments.*.driver_id' => ['nullable', 'integer', 'min:1'],
            'schedule_assignments.*.transport_provider_id' => ['nullable', 'integer', 'min:1'],
            'schedule_assignments.*.external_vehicle_ref' => ['nullable', 'string', 'max:255'],
            'schedule_assignments.*.external_driver_ref' => ['nullable', 'string', 'max:255'],
        ], $this->prefixedSupplementRules('schedule_assignments.*.', $supplementRules), $supplementRules);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function prefixedSupplementRules(string $prefix, array $rules): array
    {
        $out = [];
        foreach ($rules as $key => $rule) {
            if (str_starts_with($key, 'supplement_transports')) {
                $out[$prefix.$key] = $rule;
            }
        }

        return $out;
    }
}
