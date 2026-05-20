<?php

namespace App\Http\Requests\Api\Portal;

use App\Http\Requests\Api\ApiFormRequest;
use App\Http\Requests\Api\Portal\Concerns\ValidatesPortalRecurringExtracurricularInstance;
use App\Models\DispatchRequest;
use Illuminate\Validation\Validator;

class PortalUploadProposalBasisRequest extends ApiFormRequest
{
    use EnsuresPortalUser;
    use ValidatesPortalRecurringExtracurricularInstance;

    public function authorize(): bool
    {
        if (! $this->portalUserMayAccess($this->user())) {
            return false;
        }

        $dr = $this->route('dispatchRequest');
        if (! $dr instanceof DispatchRequest || $dr->trashed()) {
            return false;
        }

        if ((int) $dr->requester_id !== (int) $this->user()->id) {
            return false;
        }

        return $this->user()->can('adjustPassengerCount', $dr);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,webp',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            /** @var mixed $dr */
            $dr = $this->route('dispatchRequest');
            if (! $dr instanceof DispatchRequest) {
                return;
            }

            $this->validatePortalRecurringInstanceBase($v, $dr);
            $this->validatePortalRecurringWithin24hOrSubmitted($v, $dr);

            if (! in_array((string) $dr->status, ['pending', 'price_filled'], true)) {
                $v->errors()->add('status', 'Không thể đính kèm căn cứ ở trạng thái hiện tại.');
            }
        });
    }
}
