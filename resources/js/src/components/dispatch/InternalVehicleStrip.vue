<template>
  <div
    class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900/50"
    data-testid="dispatch-internal-vehicle-strip"
  >
    <div class="border-b border-slate-200/70 bg-slate-50 px-3 py-2.5 dark:border-slate-800 dark:bg-slate-900/40">
      <div class="flex flex-wrap items-start gap-2">
        <div class="flex min-w-0 flex-1 items-center gap-2">
          <div
            class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-800/90 dark:text-slate-400"
          >
            <TruckIcon class="size-3.5" aria-hidden="true" />
          </div>
          <div class="min-w-0">
            <div class="text-[12px] font-semibold tracking-tight text-slate-800 dark:text-slate-100">
              {{ t('trip_detail.coordination.resource_section_internal_title') }}
            </div>
            <p class="mt-0.5 text-[11px] leading-snug text-slate-500 dark:text-slate-400">
              {{ t('trip_detail.coordination.internal_vehicle_strip_hint') }}
            </p>
          </div>
        </div>
        <span
          v-if="modelValue.length > 0"
          class="shrink-0 rounded-full bg-[#8B1A1A]/10 px-2.5 py-0.5 text-[10px] font-bold tabular-nums text-[#8B1A1A] dark:bg-[#8B1A1A]/25 dark:text-[#e57373]"
        >
          {{ selectionSummary }}
        </span>
      </div>
      <div v-if="!isLoading" class="mt-2">
        <label class="sr-only" :for="searchId">{{ t('trip_detail.coordination.resource_section_internal_ph') }}</label>
        <input
          :id="searchId"
          v-model="searchQuery"
          type="search"
          class="w-full rounded-xl border border-transparent bg-white py-2 pl-3 pr-2 text-[12px] text-slate-800 shadow-inner shadow-slate-900/5 outline-none placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-[#8B1A1A]/15 disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:focus:border-slate-600"
          :disabled="disabled"
          :placeholder="t('trip_detail.coordination.resource_section_internal_ph')"
          data-testid="dispatch-internal-vehicle-search"
        />
      </div>
    </div>

    <div class="px-3 py-3">
      <div v-if="isLoading" class="h-24 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800/60" />

      <p
        v-else-if="!filteredOptions.length"
        class="rounded-xl bg-slate-50 px-3 py-4 text-center text-[12px] text-slate-500 dark:bg-slate-800/40 dark:text-slate-400"
      >
        {{ t('trip_detail.coordination.internal_vehicle_strip_empty') }}
      </p>

      <div
        v-else
        class="-mx-1 flex gap-2 overflow-x-auto overscroll-x-contain px-1 pb-1 pt-0.5 scroll-smooth"
        role="list"
        :aria-label="t('trip_detail.coordination.resource_section_internal_title')"
      >
        <button
          v-for="opt in filteredOptions"
          :key="opt.id"
          type="button"
          role="listitem"
          class="flex w-[9.25rem] shrink-0 flex-col rounded-xl border px-2.5 py-2.5 text-left transition active:scale-[0.98] sm:w-[10rem]"
          :class="cardClass(opt)"
          :disabled="disabled || opt.available === false"
          :aria-pressed="isSelected(opt)"
          :data-testid="`dispatch-internal-vehicle-${opt.id}`"
          @click="toggle(opt)"
        >
          <div class="flex items-start justify-between gap-1">
            <span class="truncate text-[13px] font-bold tracking-tight text-slate-900 dark:text-slate-50">
              {{ plateOf(opt) }}
            </span>
            <span
              v-if="isSelected(opt)"
              class="flex size-5 shrink-0 items-center justify-center rounded-full bg-[#8B1A1A] text-[10px] font-bold text-white dark:bg-[#a82828]"
              aria-hidden="true"
            >
              ✓
            </span>
          </div>
          <span class="mt-0.5 line-clamp-2 min-h-[2rem] text-[11px] leading-snug text-slate-600 dark:text-slate-400">
            {{ typeOf(opt) }}
          </span>
          <div class="mt-auto flex flex-wrap items-center gap-1 pt-2">
            <span
              v-if="seatCountOf(opt) > 0"
              class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold tabular-nums text-slate-700 dark:bg-slate-800 dark:text-slate-300"
            >
              {{ t('trip_detail.coordination.seats_n', { n: seatCountOf(opt) }) }}
            </span>
            <span
              v-if="opt.available === false"
              class="rounded-md bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold text-rose-700 dark:bg-rose-900/40 dark:text-rose-300"
            >
              {{ t('trip_detail.coordination.resource_busy_badge') }}
            </span>
          </div>
        </button>
      </div>

      <p
        v-if="!isLoading && filteredOptions.length"
        class="mt-2 text-[10px] text-slate-500 dark:text-slate-500"
      >
        {{ t('trip_detail.coordination.internal_vehicle_strip_scroll_hint') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, useId } from 'vue'
import { useI18n } from 'vue-i18n'
import { TruckIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: { type: Array, required: true },
  options: { type: Array, required: true },
  isLoading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()
const searchId = useId()
const searchQuery = ref('')

function normalizeSearchText(s) {
  return String(s ?? '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[\s·.,\-_/|()]/g, '')
}

const filteredOptions = computed(() => {
  const qRaw = searchQuery.value.trim()
  const list = props.options || []
  if (!qRaw) return list
  const q = normalizeSearchText(qRaw)
  if (!q) return list
  return list.filter((opt) => {
    const label = normalizeSearchText(opt.label)
    const sub = normalizeSearchText(opt.sublabel)
    return label.includes(q) || sub.includes(q)
  })
})

const totalSelectedSeats = computed(() =>
  props.modelValue.reduce((acc, v) => acc + seatCountOf(v), 0),
)

const selectionSummary = computed(() =>
  t('trip_detail.coordination.internal_vehicle_strip_summary', {
    n: props.modelValue.length,
    seats: totalSelectedSeats.value,
  }),
)

function plateOf(opt) {
  const s = String(opt.label ?? '')
  const i = s.indexOf(' · ')
  return i >= 0 ? s.slice(0, i).trim() : s.trim()
}

function typeOf(opt) {
  const s = String(opt.label ?? '')
  const i = s.indexOf(' · ')
  if (i < 0) return '—'
  return s.slice(i + 3).replace(/\s*\(\d+\)\s*$/, '').trim() || '—'
}

function seatCountOf(opt) {
  const n = Number(opt.seatCount)
  if (Number.isFinite(n) && n > 0) return Math.floor(n)
  const m = String(opt.label ?? '').match(/\((\d+)\)\s*$/)
  if (m) return Number(m[1]) || 0
  return 0
}

function isSelected(opt) {
  return props.modelValue.some((v) => String(v.id) === String(opt.id))
}

function cardClass(opt) {
  if (opt.available === false) {
    return 'cursor-not-allowed border-slate-200/80 bg-slate-50/80 opacity-55 dark:border-slate-700 dark:bg-slate-900/30'
  }
  if (isSelected(opt)) {
    return 'border-[#8B1A1A]/50 bg-[#8B1A1A]/[0.06] ring-2 ring-[#8B1A1A]/35 dark:border-[#a82828]/45 dark:bg-[#8B1A1A]/15 dark:ring-[#a82828]/40'
  }
  return 'border-slate-200/90 bg-white hover:border-slate-300 hover:bg-slate-50/90 dark:border-slate-700 dark:bg-slate-950/40 dark:hover:border-slate-600 dark:hover:bg-slate-900/60'
}

function toggle(opt) {
  if (props.disabled || opt.available === false) return
  if (isSelected(opt)) {
    emit(
      'update:modelValue',
      props.modelValue.filter((v) => String(v.id) !== String(opt.id)),
    )
    return
  }
  emit('update:modelValue', [...props.modelValue, { ...opt }])
}
</script>
