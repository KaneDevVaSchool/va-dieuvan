<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;
use App\Services\LegacyImport\LegacyDispatchImportOrchestrator;
use Illuminate\Validation\Rule;

class LegacyDispatchImportRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf([
            'trip.view_all',
            'request.approve',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('dry_run')) {
            $v = $this->input('dry_run');
            if ($v === 'true' || $v === '1' || $v === 1 || $v === true) {
                $merge['dry_run'] = true;
            } elseif ($v === 'false' || $v === '0' || $v === 0 || $v === false) {
                $merge['dry_run'] = false;
            }
        }

        if ($this->has('sheets') && is_string($this->input('sheets'))) {
            $parts = array_filter(array_map('trim', explode(',', (string) $this->input('sheets'))));
            $merge['sheets'] = $parts;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx',
                'max:51200',
            ],
            'dry_run' => ['nullable', 'boolean'],
            'sheets' => ['nullable', 'array', 'min:1'],
            'sheets.*' => [
                'string',
                Rule::in(array_merge(['all'], LegacyDispatchImportOrchestrator::VALID_SHEETS)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Chỉ chấp nhận tệp Excel .xlsx đúng định dạng «Phiếu đề xuất ghi nhận».',
            'file.max' => 'Tệp import tối đa 50MB.',
        ];
    }
}
