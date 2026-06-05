<template>
  <div ref="root" class="relative">
    <!-- Trigger -->
    <button
      type="button"
      :disabled="disabled || loading"
      :aria-expanded="open"
      aria-haspopup="listbox"
      :class="[
        'flex w-full items-center gap-3 rounded-xl border bg-white px-3 py-2.5 text-left transition disabled:cursor-not-allowed disabled:opacity-50',
        open ? openBorderClass : 'border-slate-200 hover:border-slate-300',
      ]"
      @click="toggle"
    >
      <template v-if="selected">
        <DriverAvatar :driver="selected" :accent="accent" />
        <span class="min-w-0 flex-1">
          <span class="block truncate text-sm font-semibold text-slate-900">{{ selected.full_name }}</span>
          <span class="block truncate text-xs text-slate-500">
            GPLX {{ selected.license_class || '—' }}<span v-if="selected.phone"> · {{ selected.phone }}</span>
            · <span :class="availTextClass(selected.availability_status)">{{ availLabel(selected.availability_status) }}</span>
          </span>
        </span>
      </template>
      <template v-else-if="loading">
        <ArrowPathIcon class="h-5 w-5 shrink-0 animate-spin text-slate-400" />
        <span class="flex-1 text-sm text-slate-400">Đang tải tài xế…</span>
      </template>
      <template v-else>
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-50 text-slate-300"><UserPlusIcon class="h-5 w-5" /></span>
        <span class="flex-1 truncate text-sm text-slate-400">{{ placeholder }}</span>
      </template>
      <ChevronUpDownIcon class="h-5 w-5 shrink-0 text-slate-400" />
    </button>

    <!-- Popover -->
    <div
      v-if="open"
      class="absolute left-0 right-0 z-30 mt-1.5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
      role="listbox"
    >
      <div class="border-b border-slate-100 p-2">
        <div class="relative">
          <MagnifyingGlassIcon class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            ref="searchEl"
            v-model="q"
            type="search"
            placeholder="Tìm theo tên, SĐT, GPLX…"
            class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-8 pr-2.5 text-sm outline-none transition focus:border-slate-300"
            @keydown.esc.prevent="close"
          />
        </div>
      </div>
      <div class="max-h-64 overflow-y-auto p-1">
        <button
          v-if="allowClear && hasValue"
          type="button"
          class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm font-medium text-rose-600 transition hover:bg-rose-50"
          @click="choose(null)"
        >
          <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-rose-50"><XMarkIcon class="h-4 w-4" /></span>
          Bỏ gán
        </button>
        <button
          v-for="d in filtered"
          :key="d.id"
          type="button"
          role="option"
          :aria-selected="String(d.id) === String(modelValue)"
          :disabled="isExcluded(d)"
          :class="[
            'flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left transition',
            isExcluded(d) ? 'cursor-not-allowed opacity-45' : 'hover:bg-slate-50',
            String(d.id) === String(modelValue) ? selectedItemClass : '',
          ]"
          @click="choose(d.id)"
        >
          <DriverAvatar :driver="d" :accent="accent" small />
          <span class="min-w-0 flex-1">
            <span class="flex items-center gap-1.5">
              <span class="truncate text-sm font-medium text-slate-800">{{ d.full_name }}</span>
              <CheckCircleIcon v-if="String(d.id) === String(modelValue)" :class="['h-4 w-4 shrink-0', accentTextClass]" />
            </span>
            <span class="block truncate text-xs text-slate-400">GPLX {{ d.license_class || '—' }}<span v-if="d.phone"> · {{ d.phone }}</span></span>
          </span>
          <span v-if="isExcluded(d)" class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-400">{{ excludeLabel || 'Đang dùng' }}</span>
          <span v-else :class="availBadgeClass(d.availability_status)">{{ availLabel(d.availability_status) }}</span>
        </button>
        <p v-if="!filtered.length" class="px-3 py-6 text-center text-sm text-slate-400">
          {{ drivers.length ? 'Không tìm thấy tài xế phù hợp.' : 'Chưa có tài xế nào.' }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, h, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import {
  MagnifyingGlassIcon,
  XMarkIcon,
  CheckCircleIcon,
  ChevronUpDownIcon,
  UserPlusIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  drivers: { type: Array, default: () => [] },
  /** Tài xế cần khóa (vd. tài xế đang giữ vai trò còn lại) */
  excludeId: { type: [Number, String], default: null },
  excludeLabel: { type: String, default: '' },
  placeholder: { type: String, default: 'Chọn tài xế' },
  /** 'brand' | 'amber' */
  accent: { type: String, default: 'brand' },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  allowClear: { type: Boolean, default: true },
})
const emit = defineEmits(['update:modelValue'])

const root = ref(null)
const searchEl = ref(null)
const open = ref(false)
const q = ref('')

const hasValue = computed(() => props.modelValue != null && props.modelValue !== '')
const selected = computed(
  () => props.drivers.find((d) => String(d.id) === String(props.modelValue)) || null,
)

const filtered = computed(() => {
  const term = q.value.trim().toLowerCase()
  if (!term) return props.drivers
  return props.drivers.filter((d) =>
    [d.full_name, d.phone, d.license_class]
      .filter(Boolean)
      .some((v) => String(v).toLowerCase().includes(term)),
  )
})

function isExcluded(d) {
  return props.excludeId != null && String(d.id) === String(props.excludeId)
}

function toggle() {
  open.value ? close() : openMenu()
}
function openMenu() {
  open.value = true
  q.value = ''
  nextTick(() => searchEl.value?.focus())
}
function close() {
  open.value = false
}
function choose(id) {
  if (id != null && isExcluded({ id })) return
  emit('update:modelValue', id)
  close()
}

function onDocPointer(e) {
  if (root.value && !root.value.contains(e.target)) close()
}
watch(open, (v) => {
  if (v) document.addEventListener('mousedown', onDocPointer)
  else document.removeEventListener('mousedown', onDocPointer)
})
onBeforeUnmount(() => document.removeEventListener('mousedown', onDocPointer))

// ── Styling theo accent ──────────────────────────────────────────────────────────
const openBorderClass = computed(() =>
  props.accent === 'amber'
    ? 'border-amber-300 ring-2 ring-amber-200/50'
    : 'border-va-800/40 ring-2 ring-va-800/15',
)
const accentTextClass = computed(() => (props.accent === 'amber' ? 'text-amber-600' : 'text-va-800'))
const selectedItemClass = computed(() =>
  props.accent === 'amber' ? 'bg-amber-50' : 'bg-va-800/[0.06]',
)

// ── Helpers ─────────────────────────────────────────────────────────────────────
function availLabel(s) {
  return { available: 'Rảnh', busy: 'Bận', offline: 'Nghỉ' }[s] || '—'
}
function availTextClass(s) {
  return { available: 'text-emerald-600', busy: 'text-amber-600', offline: 'text-rose-500' }[s] || 'text-slate-400'
}
function availBadgeClass(s) {
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
  const size = p.small ? 'h-8 w-8' : 'h-9 w-9'
  if (url) return h('img', { src: url, alt: d.full_name, class: `${size} shrink-0 rounded-full object-cover` })
  const initials = (d.full_name || '?').trim().split(/\s+/).slice(-2).map((w) => w[0]).join('').toUpperCase()
  const tone = p.accent === 'amber' ? 'bg-amber-100 text-amber-700' : 'bg-va-800/10 text-va-800'
  return h('span', { class: `${size} grid shrink-0 place-items-center rounded-full text-xs font-semibold ${tone}` }, initials)
}
DriverAvatar.props = ['driver', 'small', 'accent']
</script>
