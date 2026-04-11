<?php

namespace Database\Seeders;

use App\Models\CargoFareRate;
use App\Models\PassengerFareRate;
use App\Models\PricingNote;
use Illuminate\Database\Seeder;

/**
 * Bảng giá tham chiếu (xe khách + hàng hóa) — nguồn: tài liệu điều vận VA.
 */
class ReferencePricingSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPassengerFares();
        $this->seedCargoFares();
        $this->seedNotes();
    }

    private function seedPassengerFares(): void
    {
        $rows = [
            [
                'sort_order' => 10,
                'package_code' => 'hcm_4h_50km',
                'package_label' => 'TPHCM/4h/50km',
                'seat_7' => 1_296_000,
                'seat_15' => 1_620_000,
                'seat_28' => 2_376_000,
                'seat_33' => 2_700_000,
                'seat_45' => 3_024_000,
                'limo_9' => null,
                'limo_11' => null,
                'driver_self_support' => 540_000,
            ],
            [
                'sort_order' => 20,
                'package_code' => 'hcm_8h_100km',
                'package_label' => 'TPHCM/8h/100km',
                'seat_7' => 1_728_000,
                'seat_15' => 2_376_000,
                'seat_28' => 3_024_000,
                'seat_33' => 3_456_000,
                'seat_45' => 4_104_000,
                'limo_9' => null,
                'limo_11' => null,
                'driver_self_support' => 540_000,
            ],
            [
                'sort_order' => 30,
                'package_code' => 'hcm_cu_chi_1d',
                'package_label' => 'TPHCM - Củ Chi (1 ngày)',
                'seat_7' => 1_944_000,
                'seat_15' => 2_700_000,
                'seat_28' => 3_240_000,
                'seat_33' => 3_780_000,
                'seat_45' => 4_320_000,
                'limo_9' => null,
                'limo_11' => null,
                'driver_self_support' => 540_000,
            ],
            [
                'sort_order' => 40,
                'package_code' => 'hcm_vung_tau_1d',
                'package_label' => 'TPHCM - Cơ sở Vũng Tàu (1 ngày)',
                'seat_7' => 2_700_000,
                'seat_15' => 3_240_000,
                'seat_28' => 4_320_000,
                'seat_33' => 5_184_000,
                'seat_45' => 5_940_000,
                'limo_9' => 4_320_000,
                'limo_11' => 4_860_000,
                'driver_self_support' => 540_000,
            ],
            [
                'sort_order' => 50,
                'package_code' => 'hcm_vung_tau_2d',
                'package_label' => 'TPHCM - Cơ sở Vũng Tàu (2 ngày)',
                'seat_7' => 3_780_000,
                'seat_15' => 4_320_000,
                'seat_28' => 7_020_000,
                'seat_33' => 8_100_000,
                'seat_45' => 9_180_000,
                'limo_9' => 8_100_000,
                'limo_11' => 9_180_000,
                'driver_self_support' => 540_000,
            ],
            [
                'sort_order' => 60,
                'package_code' => 'hcm_can_tho_2d',
                'package_label' => 'TPHCM - Cơ sở Cần Thơ (2 ngày)',
                'seat_7' => 4_320_000,
                'seat_15' => 5_400_000,
                'seat_28' => 8_100_000,
                'seat_33' => 9_180_000,
                'seat_45' => 10_260_000,
                'limo_9' => 8_640_000,
                'limo_11' => 9_720_000,
                'driver_self_support' => 540_000,
            ],
        ];

        foreach ($rows as $row) {
            PassengerFareRate::updateOrCreate(
                ['package_code' => $row['package_code']],
                $row
            );
        }
    }

    private function seedCargoFares(): void
    {
        $rows = [
            ['sort_order' => 10, 'route_code' => 'tb_bt_3km', 'route_label' => 'VA Tân Bình - VA Bình Thới (3 km)', 'distance_km' => 3.0, 'one_crate_50_40_50' => 30_000, 'crates_2_to_5_50_40_50' => 90_000, 'van_500kg' => 180_000, 'van_1000kg' => 280_000, 'van_2000kg' => 430_000, 'loading_assist_per_point' => 100_000, 'waiting_fee_per_hour' => 60_000],
            ['sort_order' => 20, 'route_code' => 'tb_tth_65km', 'route_label' => 'VA Tân Bình - VA Thông Tây Hội (6,5 km)', 'distance_km' => 6.5, 'one_crate_50_40_50' => 50_000, 'crates_2_to_5_50_40_50' => 150_000, 'van_500kg' => 250_000, 'van_1000kg' => 350_000, 'van_2000kg' => 500_000, 'loading_assist_per_point' => 100_000, 'waiting_fee_per_hour' => 60_000],
            ['sort_order' => 30, 'route_code' => 'tb_ht_85km', 'route_label' => 'VA Tân Bình - VA Hạnh Thông (8,5 km)', 'distance_km' => 8.5, 'one_crate_50_40_50' => 70_000, 'crates_2_to_5_50_40_50' => 210_000, 'van_500kg' => 300_000, 'van_1000kg' => 400_000, 'van_2000kg' => 550_000, 'loading_assist_per_point' => 100_000, 'waiting_fee_per_hour' => 60_000],
            ['sort_order' => 40, 'route_code' => 'tb_vh_85km', 'route_label' => 'VA Tân Bình - VA Vĩnh Hội (8,5 km)', 'distance_km' => 8.5, 'one_crate_50_40_50' => 70_000, 'crates_2_to_5_50_40_50' => 210_000, 'van_500kg' => 300_000, 'van_1000kg' => 400_000, 'van_2000kg' => 550_000, 'loading_assist_per_point' => 100_000, 'waiting_fee_per_hour' => 60_000],
            ['sort_order' => 50, 'route_code' => 'tb_pd_65km', 'route_label' => 'VA Tân Bình - VA Phú Định (MN & TiH-THCS) (6,5 km)', 'distance_km' => 6.5, 'one_crate_50_40_50' => 50_000, 'crates_2_to_5_50_40_50' => 150_000, 'van_500kg' => 250_000, 'van_1000kg' => 350_000, 'van_2000kg' => 500_000, 'loading_assist_per_point' => 100_000, 'waiting_fee_per_hour' => 60_000],
            ['sort_order' => 60, 'route_code' => 'tb_vt_12km', 'route_label' => 'VA Tân Bình - Vườn trường (12 km)', 'distance_km' => 12.0, 'one_crate_50_40_50' => 80_000, 'crates_2_to_5_50_40_50' => 240_000, 'van_500kg' => 300_000, 'van_1000kg' => 400_000, 'van_2000kg' => 550_000, 'loading_assist_per_point' => 100_000, 'waiting_fee_per_hour' => 60_000],
            ['sort_order' => 70, 'route_code' => 'bt_tth_14km', 'route_label' => 'VA Bình Thới - VA Thông Tây Hội (14 km)', 'distance_km' => 14.0, 'one_crate_50_40_50' => 100_000, 'crates_2_to_5_50_40_50' => 300_000, 'van_500kg' => 330_000, 'van_1000kg' => 430_000, 'van_2000kg' => 580_000, 'loading_assist_per_point' => 100_000, 'waiting_fee_per_hour' => 60_000],
            ['sort_order' => 80, 'route_code' => 'bt_ht_8km', 'route_label' => 'VA Bình Thới - VA Hạnh Thông (8 km)', 'distance_km' => 8.0, 'one_crate_50_40_50' => 70_000, 'crates_2_to_5_50_40_50' => 210_000, 'van_500kg' => 300_000, 'van_1000kg' => 400_000, 'van_2000kg' => 550_000, 'loading_assist_per_point' => 100_000, 'waiting_fee_per_hour' => 60_000],
            ['sort_order' => 90, 'route_code' => 'bt_vh_75km', 'route_label' => 'VA Bình Thới - VA Vĩnh Hội (7,5 km)', 'distance_km' => 7.5, 'one_crate_50_40_50' => 60_000, 'crates_2_to_5_50_40_50' => 180_000, 'van_500kg' => 290_000, 'van_1000kg' => 390_000, 'van_2000kg' => 540_000, 'loading_assist_per_point' => 100_000, 'waiting_fee_per_hour' => 60_000],
            ['sort_order' => 100, 'route_code' => 'bt_pd_5km', 'route_label' => 'VA Bình Thới - VA Phú Định (MN & TiH-THCS) (5 km)', 'distance_km' => 5.0, 'one_crate_50_40_50' => 40_000, 'crates_2_to_5_50_40_50' => 120_000, 'van_500kg' => 250_000, 'van_1000kg' => 350_000, 'van_2000kg' => 500_000, 'loading_assist_per_point' => 100_000, 'waiting_fee_per_hour' => 60_000],
            ['sort_order' => 110, 'route_code' => 'bt_vt_13km', 'route_label' => 'VA Bình Thới - Vườn trường (13 km)', 'distance_km' => 13.0, 'one_crate_50_40_50' => 100_000, 'crates_2_to_5_50_40_50' => 300_000, 'van_500kg' => 330_000, 'van_1000kg' => 430_000, 'van_2000kg' => 580_000, 'loading_assist_per_point' => 100_000, 'waiting_fee_per_hour' => 60_000],
            ['sort_order' => 120, 'route_code' => 'other_2_10km', 'route_label' => 'Lộ trình khác từ 2 km - 10 km', 'distance_km' => null, 'one_crate_50_40_50' => 70_000, 'crates_2_to_5_50_40_50' => 210_000, 'van_500kg' => 300_000, 'van_1000kg' => 400_000, 'van_2000kg' => 550_000, 'loading_assist_per_point' => 100_000, 'waiting_fee_per_hour' => 60_000],
            ['sort_order' => 130, 'route_code' => 'other_10_30km', 'route_label' => 'Lộ trình trên 10 km - 30 km', 'distance_km' => null, 'one_crate_50_40_50' => 100_000, 'crates_2_to_5_50_40_50' => 300_000, 'van_500kg' => 330_000, 'van_1000kg' => 430_000, 'van_2000kg' => 580_000, 'loading_assist_per_point' => 100_000, 'waiting_fee_per_hour' => 60_000],
            ['sort_order' => 140, 'route_code' => 'other_30_60km', 'route_label' => 'Lộ trình trên 30 km - 60 km', 'distance_km' => null, 'one_crate_50_40_50' => 150_000, 'crates_2_to_5_50_40_50' => 350_000, 'van_500kg' => 430_000, 'van_1000kg' => 630_000, 'van_2000kg' => 880_000, 'loading_assist_per_point' => 100_000, 'waiting_fee_per_hour' => 60_000],
        ];

        foreach ($rows as $row) {
            CargoFareRate::updateOrCreate(
                ['route_code' => $row['route_code']],
                $row
            );
        }
    }

    private function seedNotes(): void
    {
        $notes = [
            [
                'category' => 'passenger_general',
                'sort_order' => 10,
                'title' => 'Ghi chú xe hành khách',
                'body' => 'Giá đã bao gồm phí cầu đường, phí bến bãi, VAT 8%. Giá xe tính từ Thứ 2 - Thứ 6; Thứ 7 - Chủ nhật phụ thu 30%. Xe 45 chỗ không đi được tuyến vườn trường Hóc Môn.',
            ],
            [
                'category' => 'passenger_driver',
                'sort_order' => 20,
                'title' => 'Tài xế ăn ngủ',
                'body' => 'Tài xế ăn ngủ theo đoàn nếu xe đi tỉnh. Nếu tài xế ăn ngủ tự túc: phụ thu 540.000đ/người/2N1Đ (đã bao gồm 8% VAT). Cột "Chi phí tự túc TX" trong bảng giá: 540.000đ.',
            ],
            [
                'category' => 'passenger_cancel',
                'sort_order' => 30,
                'title' => 'Huỷ xe trước ngày thực hiện — báo hợp lệ',
                'body' => 'Có thông báo trước tối thiểu 12 giờ làm việc: không tính phí.',
            ],
            [
                'category' => 'passenger_cancel',
                'sort_order' => 40,
                'title' => 'Huỷ xe trước ngày thực hiện — báo muộn',
                'body' => 'Dưới 12 giờ làm việc: 50% giá trị chuyến xe (đã chốt lịch nhưng chưa điều xe).',
            ],
            [
                'category' => 'passenger_cancel',
                'sort_order' => 50,
                'title' => 'Huỷ khi đã điều xe đến điểm đón',
                'body' => 'Xe đã di chuyển hoặc có mặt tại điểm đón: 70% giá trị chuyến xe (tính theo thực tế điều động).',
            ],
            [
                'category' => 'passenger_cancel',
                'sort_order' => 60,
                'title' => 'Huỷ do bất khả kháng',
                'body' => 'Có chứng minh bằng văn bản hoặc thông tin xác nhận: hai bên cùng rà soát, không tính phí (ví dụ thiên tai, sự cố bất ngờ, quyết định hành chính).',
            ],
            [
                'category' => 'cargo_general',
                'sort_order' => 10,
                'title' => 'Ghi chú xe hàng hóa',
                'body' => 'Đơn giá một chiều, đã bao gồm VAT. Hỗ trợ bốc xếp: hàng 10-20 kiện — 1 người tại 1 điểm lấy hàng 100.000 VNĐ; bốc xếp 2 đầu 200.000 VNĐ. Phí chờ: 60.000 VNĐ/giờ đối với hàng từ 20 kiện hoặc 100kg trở lên. Lộ trình không có trong bảng: liên hệ NV điều vận.',
            ],
        ];

        foreach ($notes as $n) {
            PricingNote::query()->updateOrCreate(
                [
                    'category' => $n['category'],
                    'sort_order' => $n['sort_order'],
                ],
                [
                    'title' => $n['title'],
                    'body' => $n['body'],
                ]
            );
        }
    }
}
