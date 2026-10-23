<?php

use App\Models\ChecklistTemplate;
use App\Support\ChecklistPeriod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Checklist an toàn xe chuyển từ hằng ngày sang hằng tuần: tài xế làm một lần vào bất kỳ ngày nào
 * trong tuần (ISO, thứ Hai → Chủ nhật) là đủ cho cả tuần.
 * - Phiếu đã nộp giữ nguyên kỳ ngày cũ (lịch sử).
 * - Phiếu nháp đang dở chuyển sang kỳ tuần để tài xế nộp tiếp được.
 * - Bỏ báo quá hạn theo ngày; nhắc hằng ngày vẫn giữ nhưng chỉ gửi cho người chưa nộp trong tuần.
 */
return new class extends Migration
{
    private const OLD_NAME = 'Checklist an toàn xe hằng ngày (pre-trip)';

    private const NEW_NAME = 'Checklist an toàn xe hằng tuần';

    private const OLD_TITLE = 'PHIẾU KIỂM TRA AN TOÀN XE (DAILY)';

    private const NEW_TITLE = 'PHIẾU KIỂM TRA AN TOÀN XE';

    public function up(): void
    {
        $template = DB::table('checklist_templates')->where('code', ChecklistTemplate::CODE_DAILY)->first();
        if ($template === null) {
            return;
        }

        DB::transaction(function () use ($template) {
            $meta = json_decode((string) $template->form_meta, true) ?: [];
            if (($meta['title'] ?? null) === self::OLD_TITLE) {
                $meta['title'] = self::NEW_TITLE;
            }

            DB::table('checklist_templates')->where('id', $template->id)->update([
                'frequency' => ChecklistTemplate::FREQUENCY_WEEKLY,
                'name' => $template->name === self::OLD_NAME ? self::NEW_NAME : $template->name,
                'form_meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

            DB::table('checklist_reminder_slots')->where('template_id', $template->id)
                ->update(['escalate_after_minutes' => null, 'updated_at' => now()]);

            $drafts = DB::table('checklist_submissions')
                ->where('template_id', $template->id)
                ->where('status', 'draft')
                ->where('period_type', ChecklistTemplate::FREQUENCY_DAILY)
                ->whereNull('deleted_at')
                ->get(['id', 'started_at', 'created_at']);
            foreach ($drafts as $draft) {
                $at = Carbon::parse($draft->started_at ?? $draft->created_at ?? now(), 'UTC');
                DB::table('checklist_submissions')->where('id', $draft->id)->update([
                    'period_type' => ChecklistTemplate::FREQUENCY_WEEKLY,
                    'period_key' => ChecklistPeriod::key(ChecklistTemplate::FREQUENCY_WEEKLY, $at),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('checklist_templates')->where('code', ChecklistTemplate::CODE_DAILY)
            ->update(['frequency' => ChecklistTemplate::FREQUENCY_DAILY, 'updated_at' => now()]);
    }
};
