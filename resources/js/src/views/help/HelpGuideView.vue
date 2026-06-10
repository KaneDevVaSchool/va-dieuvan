<template>
  <div class="space-y-4">
    <Card title="Hướng dẫn nhanh">
      <p class="text-sm leading-relaxed text-slate-700">
        Phần mềm hỗ trợ quy trình: <strong>tạo yêu cầu</strong> → <strong>duyệt</strong> → <strong>phân công chuyến</strong> →
        <strong>vận hành</strong> → <strong>doanh thu / đối soát</strong>.
      </p>
    </Card>

    <div class="grid gap-4 md:grid-cols-2">
      <Card title="BR-001 — Thời hạn tạo yêu cầu">
        <p class="text-sm text-slate-600">
          Yêu cầu phải được tạo <strong>trước giờ xuất phát ít nhất 2 giờ</strong>, trừ khi đánh dấu <strong>lệnh gấp</strong>.
        </p>
        <RouterLink class="mt-3 inline-flex text-sm font-medium text-slate-900 underline" to="/dispatch-requests/new">
          Mở form tạo yêu cầu →
        </RouterLink>
      </Card>

      <Card title="BR-002 — Trùng lịch xe / tài xế">
        <p class="text-sm text-slate-600">
          Khi phân công, hệ thống kiểm tra <strong>trùng khung thời gian</strong> giữa các chuyến. Nếu bị chặn, đổi xe, tài xế
          hoặc lịch.
        </p>
        <RouterLink class="mt-3 inline-flex text-sm font-medium text-slate-900 underline" to="/trips"> Danh sách chuyến → </RouterLink>
      </Card>

      <Card title="Phiếu giấy">
        <p class="text-sm text-slate-600">
          Chọn kênh <strong>Phiếu giấy</strong> khi tạo yêu cầu; sau đó tại chi tiết yêu cầu có thể <strong>upload scan</strong> và
          đánh dấu đã nhận phiếu.
        </p>
        <RouterLink class="mt-3 inline-flex text-sm font-medium text-slate-900 underline" to="/requests"> Danh sách yêu cầu → </RouterLink>
      </Card>

      <Card title="Bảng giá">
        <p class="text-sm text-slate-600">Tra cứu giá xe khách và hàng hóa tham chiếu nội bộ (đã seed trong DB).</p>
        <RouterLink class="mt-3 inline-flex text-sm font-medium text-slate-900 underline" to="/pricing"> Mở bảng giá → </RouterLink>
      </Card>
    </div>

    <Card title="Liên hệ hỗ trợ">
      <p class="text-sm text-slate-600">
        Vấn đề kỹ thuật hoặc đề xuất chức năng: liên hệ bộ phận IT / điều vận theo quy trình nội bộ trường.
      </p>
    </Card>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import Card from '../../components/ui/Card.vue'
</script>
