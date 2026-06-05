<template>
  <Modal :open="open" :title="title" :description="description" @close="$emit('close')">
    <div class="space-y-3">
      <div class="relative">
        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
        <input
          ref="searchEl"
          v-model="q"
          type="search"
          placeholder="Tìm tài xế theo tên, SĐT, hạng GPLX…"
          class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-base outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring"
        />
      </div>

      <div class="max-h-[22rem] space-y-1 overflow-y-auto">
        <button
          v-if="allowClear && selectedId"
          type="button"
          class="flex w-full items-center gap-3 rounded-xl border border-dashed border-rose-200 px-3 py-2.5 text-left text-sm font-medium text-rose-600 transition hover:bg-rose-50"
          @click="$emit('select', null)"
        >
          <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-rose-50"><XMarkIcon class="h-5 w-5" /></span>
          {{ clearLabel }}
        </button>

        <button
          v-for="d in filtered"
          :key="d.id"
          type="button"
          :disabled="excludeIds.includes(d.id)"
          :class="[
            'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition',
            excludeIds.includes(d.id) ? 'cursor-not-allowed opacity-45' : 'hover:bg-slate-50',
            String(d.id) === String(selectedId) ? 'bg-va-800/[0.06] ring-1 ring-inset ring-va-800/20' : '',
          ]"
          @click="!excludeIds.includes(d.id) && $emit('select', d.id)"
        >
          <DriverAvatar :driver="d" />
          <span class="min-w-0 flex-1">
            <span class="flex items-center gap-1.5">
              <span class="truncate text-sm font-semibold text-slate-900">{{ d.full_name }}</span>
              <CheckCircleIcon v-if="String(d.id) === String(selectedId)" class="h-4 w-4 shrink-0 text-va-800" />
            </span>
            <span class="mt-0.5 block truncate text-xs text-slate-400">
              GPLX {{ d.license_class || '—' }}<span v-if="d.phone"> · {{ d.phone }}</span>
            </span>
          </span>
          <span v-if="excludeIds.includes(d.id)" class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-400">Đang dùng</span>
          <span v-else :class="availabilityBadgeClass(d.availability_status)">{{ availabilityLabel(d.availability_status) }}</span>
        </button>

        <p v-if="!filtered.length" class="px-3 py-8 text-center text-sm text-slate-400">Không tìm thấy tài xế phù hợp.</p>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed, h, nextTick, ref, watch } from 'vue'
import { MagnifyingGlassIcon, XMarkIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'
import Modal from '../../../components/ui/Modal.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: 'Chọn tài xế' },
  description: { type: String, default: '' },
  drivers: { type: Array, default: () => [] },
  selectedId: { type: [Number, String], default: null },
  /** Tài xế cần ẩn/khóa (vd. tài xế chính khi chọn sơ cua) */
  excludeIds: { type: Array, default: () => [] },
  allowClear: { type: Boolean, default: false },
  clearLabel: { type: String, default: 'Bỏ phân công' },
})

defineEmits(['select', 'close'])

const q = ref('')
const searchEl = ref(null)

const filtered = computed(() => {
  const term = q.value.trim().toLowerCase()
  if (!term) return props.drivers
  return props.drivers.filter((d) =>
    [d.full_name, d.phone, d.license_class].filter(Boolean).some((v) => String(v).toLowerCase().includes(term)),
  )
})

watch(
  () => props.open,
  (v) => {
    if (v) {
      q.value = ''
      nextTick(() => searchEl.value?.focus())
    }
  },
)

function availabilityLabel(s) {
  return { available: 'Rảnh', busy: 'Bận', offline: 'Nghỉ' }[s] || '—'
}
function availabilityBadgeClass(s) {
  const base = 'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium '
  return base + ({
    available: 'bg-emerald-50 text-emerald-600',
    busy: 'bg-amber-50 text-amber-600',
    offline: 'bg-rose-50 text-rose-500',
  }[s] || 'bg-slate-100 text-slate-500')
}

const DriverAvatar = (p) => {
  const d = p.driver || {}
  const url = d.avatar_url || d.user?.avatar_url
  if (url) return h('img', { src: url, alt: d.full_name, class: 'h-10 w-10 shrink-0 rounded-full object-cover' })
  const initials = (d.full_name || '?').trim().split(/\s+/).slice(-2).map((w) => w[0]).join('').toUpperCase()
  return h('span', { class: 'grid h-10 w-10 shrink-0 place-items-center rounded-full bg-va-800/10 text-xs font-semibold text-va-800' }, initials)
}
DriverAvatar.props = ['driver']
</script>
