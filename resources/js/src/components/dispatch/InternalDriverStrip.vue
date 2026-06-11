<template>
  <div
    id="dispatch-internal-driver-section"
    class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900/50"
    data-testid="dispatch-internal-driver-strip"
  >
    <div class="border-b border-slate-200/70 bg-slate-50 px-3 py-2.5 dark:border-slate-800 dark:bg-slate-900/40">
      <div class="flex flex-wrap items-start gap-2">
        <div class="flex min-w-0 flex-1 items-center gap-2">
          <div
            class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-800/90 dark:text-slate-400"
          >
            <UserIcon class="size-3.5" aria-hidden="true" />
          </div>
          <div class="min-w-0">
            <div class="text-[12px] font-semibold tracking-tight text-slate-800 dark:text-slate-100">
              {{ t('trip_detail.coordination.resource_section_driver_title') }}
            </div>
            <p class="mt-0.5 text-[11px] leading-snug text-slate-500 dark:text-slate-400">
              {{ t('trip_detail.coordination.internal_driver_strip_hint') }}
            </p>
          </div>
        </div>
        <span
          v-if="modelValue.length > 0"
          class="shrink-0 rounded-full bg-[#378ADD]/12 px-2.5 py-0.5 text-[10px] font-bold tabular-nums text-[#378ADD] dark:bg-sky-900/40 dark:text-[#6cb3f5]"
        >
          {{ t('trip_detail.coordination.internal_driver_strip_summary', { n: modelValue.length }) }}
        </span>
      </div>
      <div v-if="!isLoading" class="mt-2 space-y-2">
        <button
          type="button"
          class="w-full rounded-xl bg-white px-2.5 py-1.5 text-left text-[11px] font-semibold leading-snug text-[#8B1A1A] shadow-sm shadow-slate-900/5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-45 dark:bg-slate-800/70 dark:text-[#e57373] dark:hover:bg-slate-800"
          :disabled="!tripDate || disabled"
          data-testid="dispatch-open-workload-panel"
          @click="$emit('open-workload')"
        >
          {{ t('trip_detail.coordination.workload_open_panel') }}
        </button>
        <label class="sr-only" :for="searchId">{{ t('trip_detail.coordination.resource_section_driver_ph') }}</label>
        <input
          :id="searchId"
          v-model="searchQuery"
          type="search"
          class="w-full rounded-xl border border-transparent bg-white py-2 pl-3 pr-2 text-[12px] text-slate-800 shadow-inner shadow-slate-900/5 outline-none placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-[#378ADD]/15 disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50"
          :disabled="disabled"
          :placeholder="t('trip_detail.coordination.resource_section_driver_ph')"
          data-testid="dispatch-internal-driver-search"
        />
      </div>
    </div>

    <div class="px-3 py-3">
      <div v-if="isLoading" class="h-24 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800/60" />

      <p
        v-else-if="!filteredOptions.length"
        class="rounded-xl bg-slate-50 px-3 py-4 text-center text-[12px] text-slate-500 dark:bg-slate-800/40 dark:text-slate-400"
      >
        {{ t('trip_detail.coordination.internal_driver_strip_empty') }}
      </p>

      <div
        v-else
        class="-mx-1 flex gap-2 overflow-x-auto overscroll-x-contain px-1 pb-1 pt-0.5 scroll-smooth"
        role="list"
      >
        <button
          v-for="opt in filteredOptions"
          :key="opt.id"
          type="button"
          role="listitem"
          class="flex w-[10.5rem] shrink-0 flex-col rounded-xl border px-2.5 py-2.5 text-left transition active:scale-[0.98] sm:w-[11.5rem]"
          :class="cardClass(opt)"
          :disabled="disabled || opt.available === false"
          :aria-pressed="isSelected(opt)"
          :data-testid="`dispatch-internal-driver-${opt.id}`"
          @click="toggle(opt)"
        >
          <div class="flex items-start justify-between gap-1">
            <span class="line-clamp-2 min-h-[2.25rem] text-[12px] font-bold leading-snug text-slate-900 dark:text-slate-50">
              {{ opt.label }}
            </span>
            <span
              v-if="isSelected(opt)"
              class="flex size-5 shrink-0 items-center justify-center rounded-full bg-[#378ADD] text-[10px] font-bold text-white"
              aria-hidden="true"
            >
              ✓
            </span>
          </div>
          <span v-if="opt.sublabel" class="mt-0.5 truncate text-[10px] text-slate-500 dark:text-slate-400">
            {{ opt.sublabel }}
          </span>
          <div class="mt-2 flex flex-wrap items-center gap-1">
            <span
              v-if="opt.available === false"
              class="rounded-md bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold text-rose-700 dark:bg-rose-900/40 dark:text-rose-300"
            >
              {{ t('trip_detail.coordination.resource_busy_badge') }}
            </span>
            <span
              v-else
              class="rounded-md bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300"
            >
              {{ t('trip_detail.coordination.driver_free') }}
            </span>
            <span
              v-if="workloadCompact(opt.id)"
              class="text-[10px] tabular-nums text-slate-500 dark:text-slate-400"
            >
              {{ workloadCompact(opt.id) }}
            </span>
          </div>
        </button>
      </div>

      <p
        v-if="!isLoading && filteredOptions.length"
        class="mt-2 text-[10px] text-slate-500"
      >
        {{ t('trip_detail.coordination.internal_vehicle_strip_scroll_hint') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, useId } from 'vue'
import { useI18n } from 'vue-i18n'
import { UserIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: { type: Array, required: true },
  options: { type: Array, required: true },
  isLoading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  tripDate: { type: String, default: '' },
  workloadMap: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue', 'open-workload'])

const { t } = useI18n()
const searchId = useId()
const searchQuery = ref('')

function normalizeSearchText(s) {
  return String(s ?? '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[\s·.,\-_/|]/g, '')
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

function workloadCompact(id) {
  const w = props.workloadMap?.[String(id)]
  if (!w) return ''
  const n = Number(w.trips_today)
  const score = Number(w.load_score)
  if (!Number.isFinite(n)) return ''
  return `${n} ca${Number.isFinite(score) ? ` · ${Math.round(score)}%` : ''}`
}

function isSelected(opt) {
  return props.modelValue.some((v) => String(v.id) === String(opt.id))
}

function cardClass(opt) {
  if (opt.available === false) {
    return 'cursor-not-allowed border-slate-200/80 bg-slate-50/80 opacity-55 dark:border-slate-700'
  }
  if (isSelected(opt)) {
    return 'border-[#378ADD]/50 bg-sky-50/80 ring-2 ring-[#378ADD]/30 dark:border-sky-600/45 dark:bg-sky-950/30'
  }
  return 'border-slate-200/90 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-950/40'
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
