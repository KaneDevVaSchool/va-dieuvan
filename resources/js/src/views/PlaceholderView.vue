<template>
  <Card>
    <div class="mb-4 flex justify-center border-b border-slate-100 pb-4">
      <AppLogo size="md" align="center" />
    </div>
    <div class="text-sm text-slate-600">
      Màn hình này đã có backend API và permission, đang triển khai UI “full ops”.
    </div>
    <div class="mt-4 rounded-lg border bg-slate-50 p-3 text-xs text-slate-600">
      Gợi ý nâng cấp:
      <ul class="mt-2 list-disc pl-5">
        <li>Danh sách + filter + paginate</li>
        <li>Chi tiết + timeline trạng thái</li>
        <li>Chống double-submit (idempotency key)</li>
        <li>Tối ưu thao tác mobile (bottom sheet / quick actions)</li>
      </ul>
    </div>
  </Card>
</template>

<script setup>
import AppLogo from '../components/branding/AppLogo.vue'
import Card from '../components/ui/Card.vue'
</script>

