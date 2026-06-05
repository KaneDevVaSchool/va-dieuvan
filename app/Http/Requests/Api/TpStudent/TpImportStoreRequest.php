<?php

namespace App\Http\Requests\Api\TpStudent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class TpImportStoreRequest extends FormRequest
{
    private const ALLOWED_EXT = ['xlsx', 'xls', 'csv'];

    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isSuperAdmin() || $user->can('tp_import.manage'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240'],
            'target_program_id' => ['nullable', 'integer', 'exists:tp_programs,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $file = $this->file('file');
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                return;
            }
            if ($this->isAllowedImportFile($file)) {
                return;
            }
            $v->errors()->add(
                'file',
                'Tệp phải có đuôi .xlsx, .xls hoặc .csv (tối đa 10MB).',
            );
        });
    }

    private function isAllowedImportFile(UploadedFile $file): bool
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (in_array($ext, self::ALLOWED_EXT, true)) {
            return true;
        }

        $mime = strtolower((string) $file->getMimeType());
        if ($mime === 'application/zip' && str_ends_with(strtolower($file->getClientOriginalName()), '.xlsx')) {
            return true;
        }

        $spreadsheetMimes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/msexcel',
            'application/x-msexcel',
            'application/x-ms-excel',
            'application/x-excel',
            'application/x-dosexcel',
            'application/xls',
            'text/csv',
            'text/plain',
            'application/csv',
            'text/comma-separated-values',
        ];

        return in_array($mime, $spreadsheetMimes, true);
    }
}
