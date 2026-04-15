<template>
  <div class="space-y-6">
    <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4 md:p-5">
      <h1 class="text-lg font-semibold text-indigo-950">Đề xuất tính năng &amp; lộ trình UX</h1>
      <p class="mt-1 text-sm text-indigo-900/90">
        Trang nội bộ tổng hợp hướng phát triển. Ưu tiên có thể điều chỉnh theo backlog thực tế của trường.
      </p>
    </div>

    <section class="space-y-3">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Đang có trong hệ thống</h2>
      <div class="grid gap-3 sm:grid-cols-2">
        <FeatureChip status="live" title="Yêu cầu &amp; duyệt" detail="BR-001, kênh phiếu giấy, đính kèm scan" />
        <FeatureChip status="live" title="Chuyến &amp; phân công" detail="Gán xe/TX, trạng thái, optimistic lock" />
        <FeatureChip status="live" title="Chi phí &amp; đối soát" detail="Nhập chi phí, kỳ, payment" />
        <FeatureChip status="live" title="Cargo &amp; SLA" detail="Shipment, POD, trạng thái" />
        <FeatureChip status="live" title="Bảng giá tham chiếu" detail="API + màn tra cứu" />
        <FeatureChip status="live" title="Idempotency" detail="Chống double-submit một số thao tác" />
      </div>
    </section>

    <section class="space-y-3">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Ưu tiên cao (đề xuất)</h2>
      <div class="grid gap-3 md:grid-cols-2">
        <RoadmapCard
          priority="P1"
          title="OCR phiếu giấy"
          body="Tự điền mã phiếu / nội dung từ ảnh scan; người dùng xác nhận trước khi lưu."
          tags="AI, Paper flow"
        />
        <RoadmapCard
          priority="P1"
          title="Menu &amp; màn hình theo quyền (RBAC UI)"
          body="Ẩn/hiện mục sidebar và hành động theo permission thay vì chỉ chặn API."
          tags="Security, UX"
        />
        <RoadmapCard
          priority="P1"
          title="Chọn xe / tài xế có UI"
          body="Thay nhập ID thô ở chi tiết chuyến bằng dropdown có tìm kiếm, ảnh biển số."
          tags="Dispatcher"
        />
        <RoadmapCard
          priority="P1"
          title="Thông báo trong app"
          body="In-app inbox: duyệt yêu cầu, nhắc SLA cargo, nhắc chuyến sắp chạy."
          tags="Notifications"
        />
      </div>
    </section>

    <section class="space-y-3">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Trung hạn</h2>
      <div class="grid gap-3 md:grid-cols-2">
        <RoadmapCard
          priority="P2"
          title="Bản đồ &amp; ETA"
          body="Hiển thị điểm đón, tích hợp map (Google/Mapbox) cho điều vận."
          tags="Maps"
        />
        <RoadmapCard
          priority="P2"
          title="PWA + offline nhẹ"
          body="Cài lên màn hình chính điện thoại, cache danh sách chuyến của tài xế."
          tags="Mobile"
        />
        <RoadmapCard
          priority="P2"
          title="Đa ngôn ngữ"
          body="Vi/En toggle cho nhân sự quốc tế hoặc tài liệu song ngữ."
          tags="i18n"
        />
      </div>
    </section>

    <section class="space-y-3">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Dài hạn / nâng cao</h2>
      <div class="grid gap-3 md:grid-cols-2">
        <RoadmapCard
          priority="P3"
          title="Ứng dụng tài xế riêng"
          body="Check-in điểm, chụp POD, chat điều vận (policy an toàn thông tin)."
          tags="Driver app"
        />
        <RoadmapCard
          priority="P3"
          title="Tối ưu tuyến (VRP nhẹ)"
          body="Gợi ý ghép chuyến / thứ tự điểm cho cargo hoặc D2D."
          tags="Optimization"
        />
        <RoadmapCard
          priority="P3"
          title="Ký số phiếu"
          body="Tích hợp HSM/ký số theo chuẩn nội bộ nhà nước/doanh nghiệp."
          tags="Compliance"
        />
        <RoadmapCard
          priority="P3"
          title="Dashboard BI"
          body="Xuất Excel/PDF, biểu đồ chi phí theo trường / tuyến / tháng."
          tags="Reports"
        />
      </div>
    </section>

    <Card title="Gợi ý nâng cấp UX (tổng quan)">
      <ul class="list-disc space-y-2 pl-5 text-sm text-slate-700">
        <li>Giảm thao tác: preset vai trò, nhớ bộ lọc cuối, bàn phím shortcut trên desktop.</li>
        <li>Phản hồi rõ: skeleton loading, toast sau hành động, empty state có nút dẫn hướng.</li>
        <li>Mobile: thanh hành động sticky, form một cột, touch target ≥ 44px.</li>
        <li>Truy cập: contrast, focus ring, hỗ trợ screen reader cho trạng thái quan trọng.</li>
      </ul>
    </Card>
  </div>
</template>

<script setup>
import Card from '../../components/ui/Card.vue'
import FeatureChip from './FeatureChip.vue'
import RoadmapCard from './RoadmapCard.vue'
</script>
