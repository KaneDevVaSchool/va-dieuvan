<template>
  <div class="grid gap-4 sm:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-4">
      <div class="text-xs uppercase tracking-wide text-slate-400">Tổng ngày vận hành</div>
      <div class="mt-1 text-2xl font-bold text-slate-900">{{ program.day_count }}</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4">
      <div class="text-xs uppercase tracking-wide text-slate-400">Học sinh đăng ký</div>
      <div class="mt-1 text-2xl font-bold text-slate-900">{{ program.enrolled_count }}</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4">
      <div class="text-xs uppercase tracking-wide text-slate-400">Giờ đi</div>
      <div class="mt-1 text-2xl font-bold text-slate-900">{{ program.departure_time }}</div>
    </div>
  </div>
</template>

<script setup>
defineProps({ program: { type: Object, required: true } })
</script>
