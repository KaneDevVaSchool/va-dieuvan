<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Services\Resources\ComplianceDocumentService;
use App\Services\Resources\ComplianceFileMigration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateComplianceFilesCommand extends Command
{
    protected $signature = 'compliance:migrate-files
        {--apply : Ghi file và cập nhật metadata; mặc định chỉ kiểm tra nguồn}
        {--verify : Chỉ kiểm tra file tại disk/path đích, không fallback}
        {--prune-binary : Kèm --apply, chỉ xóa binary sau khi file đích đã xác minh}';

    protected $description = 'Chuyển scan xe/tài xế từ database/disk cũ sang kho private, kiểm tra SHA-256';

    protected function migration(): ComplianceFileMigration
    {
        return app(ComplianceFileMigration::class);
    }

    protected function attachmentTypes(): array
    {
        return Attachment::COMPLIANCE_TYPES;
    }

    public function handle(): int
    {
        $migration = $this->migration();
        $apply = (bool) $this->option('apply');
        $verify = (bool) $this->option('verify');
        $prune = (bool) $this->option('prune-binary');
        if (($prune && ! $apply) || ($verify && ($apply || $prune))) {
            $this->error('Dùng --prune-binary cùng --apply; --verify phải chạy riêng.');

            return self::INVALID;
        }
        try {
            $disk = ComplianceDocumentService::fileDisk();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info('Disk đích: '.$disk.'; mode: '.($verify ? 'verify' : ($apply ? ($prune ? 'prune-binary' : 'apply') : 'dry-run')));
        $checked = $failed = $binaryRemaining = 0;
        // Chỉ tải ID theo lô; mỗi lần đọc/khóa một blob, tránh giữ hàng trăm file trong RAM.
        Attachment::query()->whereIn('attachable_type', $this->attachmentTypes())
            ->select('id')->chunkById(100, function ($rows) use ($migration, $apply, $verify, $prune, &$checked, &$failed, &$binaryRemaining) {
                foreach ($rows as $row) {
                    $checked++;
                    try {
                        $hasBinary = DB::transaction(function () use ($row, $migration, $apply, $verify, $prune) {
                            $query = Attachment::query()->whereKey($row->id);
                            $attachment = ($apply ? $query->lockForUpdate() : $query)->firstOrFail();
                            if ($apply) {
                                $migration->migrate($attachment, $prune);
                            } elseif ($verify) {
                                $migration->verify($attachment);
                            } else {
                                $migration->source($attachment);
                            }

                            return $attachment->file_binary !== null;
                        });
                        $binaryRemaining += (int) $hasBinary;
                    } catch (Throwable $e) {
                        $failed++;
                        $this->error("Attachment #{$row->id}: ".$e->getMessage());
                    }
                }
            });
        $this->info("checked={$checked}; ok=".($checked - $failed)."; failed={$failed}; binary_remaining_on_success={$binaryRemaining}");
        if (! $apply && ! $verify) {
            $this->line('Dry-run: chưa ghi file, chưa đổi database.');
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
