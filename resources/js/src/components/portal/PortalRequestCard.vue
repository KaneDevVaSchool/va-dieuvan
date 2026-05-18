<template>
  <component
    :is="to ? RouterLink : 'div'"
    v-bind="to ? { to } : {}"
    class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition"
    :class="to ? 'block hover:border-indigo-200/90 hover:bg-indigo-50/30 hover:shadow-md' : ''"
  >
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
          <span class="font-mono text-sm font-semibold text-slate-900">#{{ req.id }}</span>
          <StatusBadge :status="req.status" size="sm" />
          <span
            v-if="req.is_urgent"
            class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700"
          >
            {{ t('portal.badge_urgent') }}
          </span>
        </div>
        <p class="mt-2 line-clamp-2 text-sm text-slate-600">
          <span class="font-medium text-slate-700">{{ summaryLine }}</span>
        </p>
        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500">
          <span v-if="tripTypeLabel">{{ tripTypeLabel }}</span>
          <span v-if="departFmt">{{ departFmt }}</span>
        </div>
      </div>
      <ChevronRightIcon class="h-5 w-5 shrink-0 text-slate-400 transition group-hover:text-indigo-500" aria-hidden="true" />
    </div>
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ChevronRightIcon } from '@heroicons/vue/24/outline'
import StatusBadge from '../ui/StatusBadge.vue'

const props = defineProps({
  req: { type: Object, required: true },
  to: { type: Object, default: null },
})

const { t } = useI18n()

const summaryLine = computed(() => {
  const o = (props.req.origin || '').trim()
  const d = (props.req.destination || '').trim()
  if (o || d) return `${o || '…'} → ${d || '…'}`.trim()
  return t('portal.card_no_route')
})

const departFmt = computed(() => {
  const raw = props.req.depart_at
  if (!raw) return ''
  try {
    const d = new Date(raw)
    const opts = {
      weekday: 'short',
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }
    if (d.getFullYear() !== new Date().getFullYear()) {
      opts.year = 'numeric'
    }
    return d.toLocaleString('vi-VN', opts)
  } catch {
    return ''
  }
})

const tripTypeLabel = computed(() => {
  const tt = props.req.trip_type
  if (!tt) return ''
  return t(`dispatch_wizard.trip_short.${tt}`)
})
</script>
