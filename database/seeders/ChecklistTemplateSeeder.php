<?php

namespace Database\Seeders;

use App\Models\ChecklistReminderSlot;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Template checklist Daily / Weekly lấy từ sheet "Nội dung Checklist" (file Dữ liệu tham khảo - Phần mềm Điều vận.xlsx).
 *
 * - Nhóm "Thông tin chuyến" không phải mục tick → header phiếu (trip_info).
 * - Nhóm "Kết luận" không phải mục tick → tính tự động từ các mục Critical.
 *
 * Idempotent: chỉ tạo template/mục/khung giờ khi chưa có — không ghi đè chỉnh sửa của admin.
 */
class ChecklistTemplateSeeder extends Seeder
{
    private const C = ChecklistTemplateItem::SEVERITY_CRITICAL;

    private const N = ChecklistTemplateItem::SEVERITY_NON_CRITICAL;

    public function run(): void
    {
        DB::transaction(function () {
            $this->seedTemplate(
                code: ChecklistTemplate::CODE_DAILY,
                name: 'Checklist an toàn xe hằng ngày (pre-trip)',
                frequency: ChecklistTemplate::FREQUENCY_DAILY,
                formMeta: [
                    'org_line_1' => 'HỆ THỐNG TRƯỜNG VIỆT MỸ',
                    'org_line_2' => 'PHÒNG MUA HÀNG',
                    'title' => 'PHIẾU KIỂM TRA AN TOÀN XE (DAILY)',
                    'purpose' => 'Đảm bảo an toàn và tuân thủ quy định Bộ GTVT.',
                    'confirm_statement' => 'Sau khi kiểm tra, tôi xác nhận xe :plate đủ điều kiện lưu thông trên đường và đảm bảo an toàn khi vận chuyển học sinh.',
                ],
                items: $this->dailyItems(),
                slots: [['remind_at' => '05:30', 'weekdays' => [1, 2, 3, 4, 5, 6], 'escalate_after_minutes' => 60]],
            );

            $this->seedTemplate(
                code: ChecklistTemplate::CODE_WEEKLY,
                name: 'Checklist định kỳ hằng tuần (giấy tờ & tuân thủ)',
                frequency: ChecklistTemplate::FREQUENCY_WEEKLY,
                formMeta: [
                    'org_line_1' => 'HỆ THỐNG TRƯỜNG VIỆT MỸ',
                    'org_line_2' => 'PHÒNG MUA HÀNG',
                    'title' => 'PHIẾU KIỂM TRA ĐỊNH KỲ (WEEKLY)',
                    'purpose' => 'Kiểm tra giấy tờ tài xế, xe, bảo hiểm, đào tạo và hồ sơ vận hành chuyến.',
                    'confirm_statement' => 'Tôi xác nhận các thông tin kiểm tra trên là đúng sự thật.',
                ],
                items: $this->weeklyItems(),
                slots: [['remind_at' => '07:00', 'weekdays' => [1], 'escalate_after_minutes' => null]],
            );
        });
    }

    /**
     * @param  array<string, mixed>  $formMeta
     * @param  list<array<string, mixed>>  $items
     * @param  list<array<string, mixed>>  $slots
     */
    private function seedTemplate(string $code, string $name, string $frequency, array $formMeta, array $items, array $slots): void
    {
        $template = ChecklistTemplate::withTrashed()->firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'frequency' => $frequency, 'version' => 1, 'is_active' => true, 'form_meta' => $formMeta],
        );

        if (! $template->items()->exists()) {
            foreach ($items as $i => $item) {
                ChecklistTemplateItem::query()->create([
                    'template_id' => $template->id,
                    'sort_order' => ($i + 1) * 10,
                    'is_active' => true,
                    'requires_photo_on_ng' => $item['severity'] === self::C,
                    ...$item,
                ]);
            }
        }

        if (! $template->reminderSlots()->exists()) {
            foreach ($slots as $slot) {
                ChecklistReminderSlot::query()->create([
                    'template_id' => $template->id,
                    'is_active' => true,
                    ...$slot,
                ]);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function dailyItems(): array
    {
        $g1 = 'An toàn vận hành';
        $g2 = 'Kỹ thuật - giảm hỏng đột xuất';
        $g3 = 'Vệ sinh - phục vụ';
        $g4 = 'Hồ sơ hành trình';

        return [
            $this->item($g1, 'Phanh', 'Thử phanh chân/phanh tay, không trượt, không bất thường', self::C, 'Dừng xe/đổi xe; gọi kỹ thuật/cứu hộ'),
            $this->item($g1, 'Lốp', 'Áp suất phù hợp, độ mòn an toàn, không nứt/rách/phồng', self::C, 'Thay lốp/sửa; không khởi hành nếu không khắc phục'),
            $this->item($g1, 'Bánh dự phòng & dụng cụ', 'Nếu xe có trang bị: còn đủ bánh + kích + dụng cụ', self::N, 'Bổ sung/khắc phục trước ca tiếp theo; báo điều hành', allowNa: true),
            $this->item($g1, 'Đèn pha/cốt', 'Bật thử, sáng đều, không chập chờn', self::C, 'Sửa ngay/đổi xe nếu không khắc phục kịp'),
            $this->item($g1, 'Đèn xi nhan/đèn phanh', 'Bật thử, hoạt động đủ 2 bên', self::C, 'Sửa ngay/đổi xe; không khởi hành nếu không đạt'),
            $this->item($g1, 'Đèn cảnh báo khẩn cấp (hazard)', 'Bật thử, nháy đồng đều', self::C, 'Sửa ngay/đổi xe nếu không khắc phục kịp'),
            $this->item($g1, 'Còi', 'Bấm thử, âm lượng rõ', self::C, 'Sửa ngay/đổi xe nếu không khắc phục kịp'),
            $this->item($g1, 'Gương chiếu hậu', 'Chỉnh đúng tầm nhìn, không nứt/vỡ, quan sát rõ', self::C, 'Sửa/đổi gương; không khởi hành nếu không đảm bảo tầm nhìn'),
            $this->item($g1, 'Cần gạt mưa', 'Bật thử, gạt sạch, không kẹt', self::C, 'Sửa/đổi cần gạt; không khởi hành khi trời mưa nếu không đạt'),
            $this->item($g1, 'Nước rửa kính', 'Đủ nước, phun tốt', self::N, 'Bổ sung trong ca; ưu tiên hoàn tất trước khởi hành'),
            $this->item($g1, 'Cửa lên/xuống', 'Đóng mở trơn tru, chốt an toàn hoạt động', self::C, 'Dừng xe/đổi xe; sửa ngay'),
            $this->item($g1, 'Dây an toàn', 'Kiểm tra ngẫu nhiên 3–5 vị trí: khóa cài chắc, dây kéo/nhả OK', self::C, 'Không nhận HS ở vị trí dây lỗi; ưu tiên đổi xe/khắc phục trước chuyến'),
            $this->item($g1, 'Bình chữa cháy', 'Còn hạn, niêm phong OK, đặt đúng vị trí, dễ lấy', self::C, 'Bổ sung/đổi bình trước khởi hành; không khởi hành nếu thiếu/ hết hạn'),
            $this->item($g1, 'Búa thoát hiểm/thiết bị thoát hiểm', 'Đủ số lượng, đúng vị trí, không hư hỏng', self::C, 'Bổ sung/khắc phục trước khởi hành; không khởi hành nếu thiếu'),
            $this->item($g1, 'Hộp sơ cứu (nếu áp dụng)', 'Đủ vật tư cơ bản, còn hạn, dễ truy cập', self::N, 'Bổ sung trong ngày; báo điều hành', allowNa: true),
            $this->item($g1, 'Tam giác phản quang/đèn cảnh báo dừng xe', 'Đầy đủ, sẵn sàng sử dụng', self::C, 'Bổ sung trước khởi hành; không khởi hành nếu thiếu'),
            $this->item($g1, 'Camera/thiết bị giám sát (nếu có)', 'Bật nguồn, ghi hình OK (kiểm tra trạng thái)', self::C, 'Báo điều hành; xử lý thiết bị/đổi xe theo quy định tuyến', allowNa: true),
            $this->item($g2, 'Nhiên liệu', 'Đạt mức tối thiểu trước chuyến (khuyến nghị ≥ 1/2 bình)', self::C, 'Đổ bổ sung ngay; không khởi hành nếu dưới ngưỡng'),
            $this->item($g2, 'Ắc quy/đề', 'Đề nổ ổn định, không báo lỗi điện, không đề yếu', self::C, 'Báo kỹ thuật/đổi xe; chuẩn bị bộ kích bình nếu cần'),
            $this->item($g2, 'Nhiệt độ máy', 'Ổn định, không cảnh báo quá nhiệt sau khi nổ máy', self::C, 'Dừng xe; kiểm tra nước làm mát/kỹ thuật; đổi xe nếu cần'),
            $this->item($g2, 'Rò rỉ dưới gầm', 'Không có rò rỉ dầu/nước', self::C, 'Dừng xe; gọi kỹ thuật; không khởi hành'),
            $this->item($g2, 'Dầu máy/nước làm mát', 'Mức phù hợp theo tiêu chuẩn xe', self::C, 'Bổ sung/khắc phục; không khởi hành nếu thiếu nghiêm trọng'),
            $this->item($g2, 'Điều hòa', 'Hoạt động ổn định, đủ mát, không mùi lạ', self::N, 'Báo điều hành; sửa theo lịch; ưu tiên xe đạt chuẩn cho HS'),
            $this->item($g2, 'Âm thanh/mùi bất thường', 'Không có tiếng rít/gõ, không mùi khét', self::C, 'Dừng xe; báo điều hành/kỹ thuật; đổi xe'),
            $this->item($g3, 'Vệ sinh khoang xe', 'Sạch sẽ, không mùi khó chịu', self::N, 'Vệ sinh nhanh trước đón HS; nếu không đạt báo điều hành đổi xe'),
            $this->item($g3, 'An toàn khoang xe', 'Không vật sắc nhọn/rác; ghế khô ráo', self::C, 'Dọn ngay; nếu không đảm bảo an toàn thì không khởi hành'),
            $this->item($g3, 'Lối đi & cửa thoát hiểm', 'Thông thoáng, không chắn lối/cửa thoát hiểm', self::C, 'Dọn ngay; không khởi hành nếu còn chắn'),
            $this->item($g4, 'Danh sách HS/tuyến/điểm đón', 'Có bản mới nhất (giấy/app), dễ đối chiếu', self::C, 'Không khởi hành; yêu cầu điều hành cập nhật danh sách'),
            $this->item($g4, 'Liên lạc khẩn', 'Có hotline điều hành + đầu mối trường + bảo mẫu', self::C, 'Không khởi hành; bổ sung ngay thông tin liên hệ'),
            $this->item($g4, 'Phương án dự phòng', 'Có thông tin xe thay thế/cứu hộ và điều kiện kích hoạt', self::N, 'Bổ sung; nhắc điều hành cập nhật định kỳ'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function weeklyItems(): array
    {
        return [
            $this->item('Tài xế - giấy tờ cá nhân', 'GPLX', 'Đúng hạng xe, còn hiệu lực', self::C, 'Không phân công lái; thay tài xế hợp lệ', autoSource: 'driver_doc:license'),
            $this->item('Tài xế - giấy tờ cá nhân', 'CCCD/định danh', 'Còn hiệu lực, mang theo khi làm nhiệm vụ', self::N, 'Bổ sung ngay trong ngày; lưu bản đối chiếu nội bộ (nếu có)', autoSource: 'driver_doc:id_card'),
            $this->item('Tài xế - sức khỏe', 'Giấy khám sức khỏe', 'Còn hiệu lực theo quy định DN/luật (nếu áp dụng)', self::C, 'Không phân công; bổ sung khám/giấy tờ trước khi chạy', allowNa: true, autoSource: 'driver_doc:medical_certificate'),
            $this->item('Xe - giấy tờ bắt buộc', 'Đăng ký xe', 'Bản gốc/bản hợp lệ theo quy định, mang trên xe', self::C, 'Không khởi hành; bổ sung giấy tờ hợp lệ', autoSource: 'vehicle_doc:registration'),
            $this->item('Xe - giấy tờ bắt buộc', 'Đăng kiểm', 'Còn hiệu lực', self::C, 'Không khởi hành; đưa xe đi kiểm định/đổi xe', autoSource: 'vehicle_doc:inspection_certificate'),
            $this->item('Xe - giấy tờ bắt buộc', 'Bảo hiểm TNDS', 'Bảo hiểm bắt buộc còn hiệu lực', self::C, 'Không khởi hành; bổ sung/ gia hạn bảo hiểm', autoSource: 'vehicle_doc:insurance_certificate'),
            $this->item('Xe - bảo hiểm (khuyến nghị)', 'Bảo hiểm vật chất xe', 'Nếu có: còn hiệu lực', self::N, 'Bổ sung theo chính sách; báo cáo kế hoạch mua/gia hạn', allowNa: true, autoSource: 'vehicle_doc:insurance_certificate'),
            $this->item('Xe - bảo hiểm (khuyến nghị)', 'Bảo hiểm tai nạn hành khách', 'Nếu có: còn hiệu lực', self::N, 'Bổ sung theo chính sách; báo cáo kế hoạch mua/gia hạn', allowNa: true),
            $this->item('Tài xế - đào tạo', 'Chứng nhận PCCC', 'Nếu DN yêu cầu: chứng chỉ hợp lệ', self::C, 'Không phân công tuyến HS; hoàn tất đào tạo trước khi chạy', allowNa: true, autoSource: 'driver_doc:training_certificate'),
            $this->item('Tài xế - đào tạo', 'Chứng nhận sơ cứu/y tế cơ bản', 'Nếu DN yêu cầu: chứng chỉ hợp lệ', self::N, 'Lên lịch đào tạo bổ sung; báo cáo tiến độ', allowNa: true),
            $this->item('Tài xế - đào tạo', 'Đào tạo vận chuyển học sinh', 'Xác nhận đã được đào tạo quy trình/ứng xử/an toàn', self::C, 'Không phân công tuyến HS; hoàn tất đào tạo trước khi chạy'),
            $this->item('Vận tải (nếu thuộc diện)', 'Phù hiệu/biển hiệu', 'Đúng loại hình vận tải, còn hiệu lực/đúng quy định', self::C, 'Không khởi hành; bổ sung phù hiệu/đổi xe đủ điều kiện', allowNa: true, autoSource: 'vehicle_doc:transport_permit'),
            $this->item('Vận hành chuyến', 'Lệnh vận chuyển/giấy điều xe/nhật trình', 'Khớp tuyến/ngày/chuyến, theo quy định đơn vị', self::N, 'Bổ sung trước khi chạy; lưu bản điện tử nếu áp dụng'),
            $this->item('Vận hành chuyến', 'Checklist pre-trip', 'Đã tick đầy đủ và ký trước khi xuất bến', self::C, 'Không khởi hành; hoàn tất checklist'),
            $this->item('Vận hành chuyến', 'Danh sách HS & điểm đón', 'Bản mới nhất, khớp tuyến', self::C, 'Không khởi hành; cập nhật danh sách từ điều hành'),
            $this->item('Vận hành chuyến', 'Hotline điều hành/cứu hộ', 'Ghi rõ và dễ truy cập khi cần', self::C, 'Bổ sung ngay; không khởi hành nếu thiếu đầu mối liên lạc'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(
        string $group,
        string $title,
        string $requirement,
        string $severity,
        string $ngAction,
        bool $allowNa = false,
        ?string $autoSource = null,
    ): array {
        return [
            'group_name' => $group,
            'title' => $title,
            'requirement' => $requirement,
            'severity' => $severity,
            'ng_action' => $ngAction,
            'allow_na' => $allowNa,
            'auto_source' => $autoSource,
        ];
    }
}
