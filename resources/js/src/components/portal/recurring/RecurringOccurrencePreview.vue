<template>
  <div
    class="overflow-hidden rounded-2xl border shadow-sm transition-colors"
    :class="
      count > 0
        ? 'border-va-200/80 bg-gradient-to-br from-va-50/90 via-white to-teal-50/40 ring-1 ring-va-100/60'
        : 'border-amber-200/80 bg-amber-50/40'
    "
    role="status"
    :aria-live="count > 0 ? 'polite' : 'polite'"
  >
    <div class="flex flex-wrap items-center gap-4 border-b border-white/60 px-4 py-3 sm:px-5">
      <div
        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-2xl font-bold tabular-nums shadow-inner"
        :class="count > 0 ? 'bg-va-800 text-white' : 'bg-amber-100 text-amber-900'"
      >
        {{ count > 0 ? count : '—' }}
      </div>
      <div class="min-w-0 flex-1">
        <p class="text-xs font-semibold uppercase tracking-wide text-va-900/80">
          {{ t('portal.recurring_plan.preview_heading') }}
        </p>
        <p class="mt-0.5 text-base font-semibold text-slate-900">
          {{
            count > 0
              ? t('portal.recurring_plan.preview_count', { n: count })
              : t('portal.recurring_plan.preview_empty')
          }}
        </p>
        <p v-if="count > 0 && timeRangeLabel" class="mt-1 text-sm text-slate-600">
          <span class="font-medium text-slate-800">{{ timeRangeLabel }}</span>
          <span v-if="rangeLabel" class="text-slate-500"> · {{ rangeLabel }}</span>
        </p>
        <p v-else-if="hint" class="mt-1 text-sm text-amber-900/90">{{ hint }}</p>
      </div>
    </div>

    <div v-if="count > 0" class="px-4 py-4 sm:px-5">
      <ul class="flex flex-wrap gap-2">
        <li
          v-for="d in visibleDates"
          :key="d"
          class="inline-flex items-center gap-1.5 rounded-full border border-va-200/70 bg-white px-3 py-1.5 text-xs font-semibold text-va-900 shadow-sm"
        >
          <span class="h-1.5 w-1.5 rounded-full bg-teal-500" aria-hidden="true" />
          {{ formatDateWithWeekday(d) }}
        </li>
        <li
          v-if="hiddenCount > 0"
          class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600"
        >
          {{ t('portal.recurring_plan.preview_more', { n: hiddenCount }) }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  dates: { type: Array, default: () => [] },
  formatDate: { type: Function, required: true },
  departTime: { type: String, default: '' },
  returnTime: { type: String, default: '' },
  hint: { type: String, default: '' },
  maxVisible: { type: Number, default: 24 },
})

const { t, locale } = useI18n()

const count = computed(() => props.dates?.length ?? 0)

const visibleDates = computed(() => (props.dates || []).slice(0, props.maxVisible))

const hiddenCount = computed(() => Math.max(0, count.value - props.maxVisible))

const rangeLabel = computed(() => {
  if (!count.value) return ''
  const first = props.dates[0]
  const last = props.dates[count.value - 1]
  if (!first || !last) return ''
  if (first === last) return props.formatDate(first)
  return `${props.formatDate(first)} → ${props.formatDate(last)}`
})

const timeRangeLabel = computed(() => {
  const dep = formatTimeShort(props.departTime)
  const ret = formatTimeShort(props.returnTime)
  if (!dep && !ret) return ''
  if (dep && ret) return `${dep} – ${ret}`
  return dep || ret
})

function formatTimeShort(raw) {
  const s = String(raw ?? '').trim()
  const m = s.match(/^(\d{1,2}):(\d{2})/)
  if (!m) return ''
  return `${m[1].padStart(2, '0')}:${m[2]}`
}

function formatDateWithWeekday(ymd) {
  try {
    const [y, m, d] = String(ymd).split('-').map(Number)
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    const wd = new Intl.DateTimeFormat(loc, { weekday: 'short' }).format(new Date(y, m - 1, d))
    return `${wd} ${props.formatDate(ymd)}`
  } catch {
    return props.formatDate(ymd)
  }
}
</script>
