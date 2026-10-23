<?php

use App\Models\ChecklistTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Chỉnh nội dung phiếu Daily theo góp ý nghiệp vụ:
 * - Lốp: thêm "Còn hạn sử dụng".
 * - Búa thoát hiểm: "Có trang bị Búa thoát hiểm".
 * - Câu xác nhận cuối phiếu rút gọn.
 * Phiếu đã tạo giữ snapshot cũ; chỉ phiếu mới nhận nội dung mới.
 */
return new class extends Migration
{
    private const STATEMENT = 'Sau khi kiểm tra, tôi xác nhận xe :plate đủ điều kiện lưu thông trên đường.';

    private const TIRE_REQUIREMENT = 'Còn hạn sử dụng; áp suất phù hợp, độ mòn an toàn, không nứt/rách/phồng';

    public function up(): void
    {
        $template = DB::table('checklist_templates')->where('code', ChecklistTemplate::CODE_DAILY)->first();
        if ($template === null) {
            return;
        }

        DB::transaction(function () use ($template) {
            $items = DB::table('checklist_template_items')->where('template_id', $template->id);

            $tires = (clone $items)->where('title', 'Lốp')
                ->where('requirement', 'not like', '%hạn sử dụng%')
                ->update(['requirement' => self::TIRE_REQUIREMENT, 'updated_at' => now()]);

            $hammer = (clone $items)->where('title', 'like', 'Búa thoát hiểm%')
                ->update(['title' => 'Búa thoát hiểm', 'requirement' => 'Có trang bị Búa thoát hiểm', 'updated_at' => now()]);

            $meta = json_decode((string) $template->form_meta, true) ?: [];
            $meta['confirm_statement'] = self::STATEMENT;

            DB::table('checklist_templates')->where('id', $template->id)->update([
                'form_meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                'version' => $template->version + (($tires + $hammer) > 0 ? 1 : 0),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        // Không khôi phục câu chữ cũ — có thể chỉnh lại trong màn Cấu hình checklist.
    }
};
