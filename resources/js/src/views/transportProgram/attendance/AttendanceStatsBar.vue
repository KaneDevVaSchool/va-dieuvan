<template>
  <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
    <div class="flex items-center gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 shadow-sm">
      <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15">
        <CheckCircleIcon class="h-5 w-5 text-emerald-600" />
      </div>
      <div>
        <p class="text-xs font-medium text-emerald-700">Có mặt</p>
        <p class="text-2xl font-bold leading-none text-emerald-800">{{ summary.present }}</p>
      </div>
    </div>
    <div class="flex items-center gap-3 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 shadow-sm">
      <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-500/15">
        <XCircleIcon class="h-5 w-5 text-rose-600" />
      </div>
      <div>
        <p class="text-xs font-medium text-rose-700">Vắng mặt</p>
        <p class="text-2xl font-bold leading-none text-rose-800">{{ summary.absent_total }}</p>
      </div>
    </div>
    <div class="flex items-center gap-3 rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 shadow-sm">
      <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/15">
        <ClockIcon class="h-5 w-5 text-amber-600" />
      </div>
      <div>
        <p class="text-xs font-medium text-amber-700">Có phép</p>
        <p class="text-2xl font-bold leading-none text-amber-800">{{ summary.excused }}</p>
      </div>
    </div>
    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
      <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100">
        <UsersIcon class="h-5 w-5 text-slate-500" />
      </div>
      <div class="min-w-0 flex-1">
        <p class="text-xs font-medium text-slate-500">Tổng / Tỉ lệ</p>
        <div class="flex items-end gap-1.5">
          <p class="text-2xl font-bold leading-none text-slate-800">{{ summary.total }}</p>
          <p class="mb-0.5 text-sm font-semibold text-slate-500">{{ summary.attendance_rate }}%</p>
        </div>
        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
          <div
            class="h-full rounded-full transition-all duration-500"
            :class="summary.attendance_rate >= 90 ? 'bg-emerald-500' : summary.attendance_rate >= 75 ? 'bg-amber-500' : 'bg-rose-500'"
            :style="{ width: `${summary.attendance_rate}%` }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { CheckCircleIcon, ClockIcon, UsersIcon, XCircleIcon } from '@heroicons/vue/24/outline'

defineProps({
  summary: { type: Object, required: true },
})
</script>
