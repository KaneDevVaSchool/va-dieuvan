<?php

namespace App\Services\Attachments;

use App\Models\Attachment;
use App\Models\ExpenseDocument;
use App\Services\Resources\ComplianceFileMigration;

class DriverWorkflowFileMigration extends ComplianceFileMigration
{
    protected function folders(): string
    {
        return '(?:checklists|expenses)';
    }

    protected function supports(Attachment $attachment): bool
    {
        return $attachment->isDriverWorkflowFile();
    }

    protected function folder(Attachment $attachment): string
    {
        return in_array($attachment->attachable_type, [ExpenseDocument::class, 'expense_document'], true)
            ? 'expenses' : 'checklists';
    }
}
