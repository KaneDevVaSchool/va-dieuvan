<template>
  <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/60">
    <!-- Header: status badge + time -->
    <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-3 dark:border-slate-700/80">
      <span
        class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wide"
        :class="statusStyle.badge"
      >
        {{ statusStyle.label }}
      </span>
      <span class="shrink-0 text-sm tabular-nums font-medium text-slate-500 dark:text-slate-400">
        {{ timeRange }}
      </span>
    </div>

    <!-- Body: requester + destination -->
    <div class="flex gap-3 px-4 py-3">
      <img
        v-if="requesterAvatar"
        :src="requesterAvatar"
        alt=""
        class="h-11 w-11 shrink-0 rounded-full object-cover"
      />
      <div
        v-else
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-200 text-base font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-200"
      >
        {{ requesterInitials }}
      </div>
      <div class="min-w-0 flex-1">
        <p class="text-base font-semibold text-slate-900 dark:text-white">
          {{ requesterName }}
        </p>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
          {{ subline }}
        </p>
      </div>
    </div>

    <!-- CTA -->
    <div class="px-4 pb-4">
      <RouterLink
        :to="`/driver/trips/${trip.id}`"
        class="flex w-full items-center justify-center rounded-2xl px-4 text-base font-bold transition active:scale-[0.98]"
        :class="ctaClass"
        style="min-height: 48px;"
      >
        {{ ctaLabel }}
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'

const props = defineProps({
  trip: { type: Object, required: true },
})

const { t } = useI18n()

function formatHm(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false })
}

const timeRange = computed(() => {
  const start = formatHm(props.trip.depart_at)
  const end = formatHm(props.trip.arrive_by || props.trip.dispatch_request?.arrive_by)
  if (start && end) return `${start} – ${end}`
  return start || '—'
})

const requesterName = computed(() => {
  const dr = props.trip.dispatch_request
  return dr?.requester?.name?.trim() || dr?.origin || '—'
})

const requesterAvatar = computed(() => props.trip.dispatch_request?.requester?.avatar_url || null)

const requesterInitials = computed(() => {
  const n = requesterName.value
  if (n === '—') return '?'
  const parts = n.split(/\s+/)
  if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  return n.slice(0, 2).toUpperCase()
})

const subline = computed(() => {
  const dr = props.trip.dispatch_request
  const raw = dr?.trip_type
  let typeLabel
  if (!raw || raw === 'unspecified') {
    typeLabel = t('driver_home.line_route')
  } else {
    const key = `trips_page.trip_type.${raw}`
    const val = t(key)
    typeLabel = val === key ? raw : val
  }
  const dest = (dr?.destination || '').trim()
  return dest ? `${typeLabel} · ${dest}` : typeLabel
})

const statusStyle = computed(() => {
  const st = props.trip.status
  const key = `trips_page.trip_status.${st}`
  const translated = t(key)
  const label = translated === key ? t('driver_home.trip_pending') : translated
  if (st === 'in_progress') {
    return { label, badge: 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-200' }
  }
  return { label, badge: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200' }
})

const ctaLabel = computed(() => {
  const st = props.trip.status
  if (st === 'in_progress') return t('driver_home.btn_continue')
  if (st === 'completed') return t('driver_home.btn_view')
  return t('driver_home.btn_start')
})

const ctaClass = computed(() => {
  if (props.trip.status === 'in_progress') {
    return 'bg-[#78001e] text-white'
  }
  return 'bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white'
})
</script>
