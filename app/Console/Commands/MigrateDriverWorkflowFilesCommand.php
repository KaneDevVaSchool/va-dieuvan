<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Services\Attachments\DriverWorkflowFileMigration;
use App\Services\Resources\ComplianceFileMigration;

class MigrateDriverWorkflowFilesCommand extends MigrateComplianceFilesCommand
{
    protected $signature = 'driver-workflow:migrate-files
        {--apply : Ghi file và metadata vào kho private}
        {--verify : Chỉ xác minh disk/path đích, không fallback}
        {--prune-binary : Kèm --apply, xóa binary sau khi đã xác minh file đích}';

    protected $description = 'Chuyển ảnh checklist và scan hồ sơ chi phí sang kho private, xác minh SHA-256';

    protected function migration(): ComplianceFileMigration
    {
        return app(DriverWorkflowFileMigration::class);
    }

    protected function attachmentTypes(): array
    {
        return Attachment::DRIVER_WORKFLOW_TYPES;
    }
}
