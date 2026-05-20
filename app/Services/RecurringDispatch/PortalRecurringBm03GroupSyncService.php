<?php

namespace App\Services\RecurringDispatch;

use App\Models\Attachment;
use App\Models\DispatchRequest;
use App\Models\DispatchRequestTemplate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PortalRecurringBm03GroupSyncService
{
    /** Trường BM.03 theo từng buổi — không đồng bộ sang phiếu khác trong cùng kế hoạch. */
    private const FORM_EXCLUDE = [
        'proposed_date',
        'date_needed',
    ];

    public function syncSharedBm03FromInstance(DispatchRequest $source): int
    {
        $templateId = $source->dispatch_request_template_id;
        if ($templateId === null || ! $this->isExtracurricularInstance($source)) {
            return 0;
        }

        $source = $source->fresh();
        if ($source === null) {
            return 0;
        }

        $sourceForm = data_get($source->wizard_snapshot, 'form');
        if (! is_array($sourceForm)) {
            $sourceForm = [];
        }

        $siblings = DispatchRequest::query()
            ->where('dispatch_request_template_id', $templateId)
            ->whereKeyNot($source->getKey())
            ->get();

        $synced = 0;
        foreach ($siblings as $sibling) {
            if (! $this->canReceiveBm03Sync($sibling)) {
                continue;
            }

            $snap = is_array($sibling->wizard_snapshot) ? $sibling->wizard_snapshot : [];
            $form = is_array($snap['form'] ?? null) ? $snap['form'] : [];

            foreach ($sourceForm as $key => $value) {
                if (in_array($key, self::FORM_EXCLUDE, true)) {
                    continue;
                }
                $form[$key] = $value;
            }

            if ($sibling->depart_at !== null) {
                $form['date_needed'] = $this->dateNeededForInstance($sibling, $form);
            }

            $snap['form'] = $form;

            $rowUpdates = ['wizard_snapshot' => $snap];
            if ($source->origin !== null && $source->origin !== '') {
                $rowUpdates['origin'] = $source->origin;
            }
            if ($source->destination !== null && $source->destination !== '') {
                $rowUpdates['destination'] = $source->destination;
            }

            $sibling->update($rowUpdates);
            $this->copyProposalBasisIfPresent($source, $sibling, $source->requester_id);
            $synced++;
        }

        $this->syncTemplateWizardFromInstance($source, $sourceForm);

        return $synced;
    }

    public function stampProposedDateOnSubmit(DispatchRequest $instance): void
    {
        $timezone = config('app.timezone') ?: 'UTC';
        $today = Carbon::now($timezone)->format('Y-m-d');

        $snap = is_array($instance->wizard_snapshot) ? $instance->wizard_snapshot : [];
        $form = is_array($snap['form'] ?? null) ? $snap['form'] : [];
        $form['proposed_date'] = $today;
        $snap['form'] = $form;

        $instance->forceFill(['wizard_snapshot' => $snap])->saveQuietly();
    }

    protected function syncTemplateWizardFromInstance(DispatchRequest $source, array $sourceForm): void
    {
        $templateId = $source->dispatch_request_template_id;
        if ($templateId === null) {
            return;
        }

        /** @var DispatchRequestTemplate|null $template */
        $template = DispatchRequestTemplate::query()->find($templateId);
        if ($template === null) {
            return;
        }

        $snap = is_array($template->wizard_snapshot) ? $template->wizard_snapshot : [];
        $form = is_array($snap['form'] ?? null) ? $snap['form'] : [];

        foreach ($sourceForm as $key => $value) {
            if (in_array($key, self::FORM_EXCLUDE, true)) {
                continue;
            }
            $form[$key] = $value;
        }

        $snap['form'] = $form;

        $templateUpdates = ['wizard_snapshot' => $snap];
        if ($source->origin !== null && $source->origin !== '') {
            $templateUpdates['origin'] = $source->origin;
        }
        if ($source->destination !== null && $source->destination !== '') {
            $templateUpdates['destination'] = $source->destination;
        }

        $template->update($templateUpdates);
    }

    protected function canReceiveBm03Sync(DispatchRequest $instance): bool
    {
        if ($instance->trashed()) {
            return false;
        }
        if ($instance->student_count_submitted_at !== null) {
            return false;
        }
        if (! in_array((string) $instance->status, ['pending', 'price_filled'], true)) {
            return false;
        }
        if ($instance->trip()->exists()) {
            return false;
        }

        return $this->isExtracurricularInstance($instance);
    }

    protected function isExtracurricularInstance(DispatchRequest $dr): bool
    {
        if ($dr->dispatch_request_template_id === null) {
            return false;
        }

        $snap = $dr->wizard_snapshot;
        $purposeKind = is_array($snap)
            ? (data_get($snap, 'form.point_purpose_kind') ?? data_get($snap, 'point_purpose_kind'))
            : null;

        return $purposeKind === 'extracurricular';
    }

    /**
     * @param  array<string, mixed>  $form
     */
    protected function dateNeededForInstance(DispatchRequest $instance, array $form): string
    {
        $existing = data_get($form, 'date_needed');
        if (is_string($existing) && preg_match('/^\d{4}-\d{2}-\d{2}/', $existing) === 1) {
            return substr($existing, 0, 10);
        }

        $timezone = config('app.timezone') ?: 'UTC';

        return Carbon::parse($instance->depart_at, $timezone)->format('Y-m-d');
    }

    protected function copyProposalBasisIfPresent(DispatchRequest $source, DispatchRequest $target, ?int $uploadedBy): void
    {
        $sourceAtt = Attachment::query()
            ->where('attachable_type', $source->getMorphClass())
            ->where('attachable_id', $source->getKey())
            ->where('kind', 'proposal_basis')
            ->latest('id')
            ->first();

        if ($sourceAtt === null) {
            return;
        }

        Attachment::query()
            ->where('attachable_type', $target->getMorphClass())
            ->where('attachable_id', $target->getKey())
            ->where('kind', 'proposal_basis')
            ->delete();

        $disk = $sourceAtt->disk ?: 'public';
        $bytes = $sourceAtt->file_binary;
        if (! is_string($bytes) || $bytes === '') {
            if ($sourceAtt->path && Storage::disk($disk)->exists($sourceAtt->path)) {
                $bytes = Storage::disk($disk)->get($sourceAtt->path);
            }
        }
        if (! is_string($bytes) || $bytes === '') {
            return;
        }

        $ext = pathinfo((string) $sourceAtt->original_name, PATHINFO_EXTENSION);
        $storedName = uniqid('basis_', true).($ext !== '' ? '.'.$ext : '');
        $destPath = "attachments/dispatch_requests/{$target->getKey()}/{$storedName}";

        Storage::disk($disk)->put($destPath, $bytes);

        Attachment::create([
            'uploaded_by' => $uploadedBy,
            'attachable_type' => $target->getMorphClass(),
            'attachable_id' => $target->getKey(),
            'kind' => 'proposal_basis',
            'disk' => $disk,
            'path' => $destPath,
            'original_name' => $sourceAtt->original_name,
            'size_bytes' => $sourceAtt->size_bytes ?? strlen($bytes),
            'mime_type' => $sourceAtt->mime_type,
            'file_binary' => $bytes,
        ]);
    }
}
