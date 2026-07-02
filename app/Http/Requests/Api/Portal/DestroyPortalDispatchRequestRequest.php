<?php

namespace App\Http\Requests\Api\Portal;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;
use Illuminate\Validation\Validator;

class DestroyPortalDispatchRequestRequest extends ApiFormRequest
{
    use EnsuresPortalUser;

    public function authorize(): bool
    {
        if (! $this->portalUserMayAccess($this->user())) {
            return false;
        }

        $dr = $this->route('dispatchRequest');
        if (! $dr instanceof DispatchRequest || $dr->trashed()) {
            return false;
        }

        return (int) $dr->requester_id === (int) $this->user()->id;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            /** @var mixed $dr */
            $dr = $this->route('dispatchRequest');
            if (! $dr instanceof DispatchRequest) {
                return;
            }

            if (! in_array((string) $dr->status, ['pending', 'price_filled'], true)) {
                $v->errors()->add('status', 'Chỉ có thể xóa phiếu đang chờ duyệt.');
            }
        });
    }
}
