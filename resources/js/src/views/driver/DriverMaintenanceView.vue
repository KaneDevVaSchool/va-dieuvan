<template>
  <div class="max-w-lg mx-auto w-full space-y-4 sm:max-w-2xl">
    <div>
      <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl">
        {{ t('driver_maintenance.title') }}
      </h1>
      <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
        {{ t('driver_maintenance.subtitle') }}
      </p>
    </div>

    <p
      v-if="errorMsg"
      class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900"
    >
      {{ errorMsg }}
    </p>

    <p v-else-if="loading" class="text-center text-sm text-slate-500 dark:text-slate-400">…</p>

    <div
      v-else-if="!vehicle"
      class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 px-4 py-8 text-center text-sm text-slate-600 dark:border-slate-600 dark:bg-slate-900/40 dark:text-slate-400"
    >
      {{ t('driver_maintenance.no_vehicle') }}
    </div>

    <div
      v-else-if="vehicle"
      class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/60"
    >
      <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
        <p class="text-sm font-bold text-slate-900 dark:text-white">
          {{ vehicleLine }}
        </p>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          {{ vehicle.license_plate || '—' }}
        </p>
      </div>
    </div>

    <div v-if="!loading && items.length">
      <h2 class="mb-2 text-sm font-bold text-slate-900 dark:text-white">
        {{ t('driver_maintenance.due_soon') }}
      </h2>
      <ul class="space-y-2.5">
        <li
          v-for="(it, i) in items"
          :key="i"
          class="flex items-center justify-between gap-3 rounded-2xl border border-amber-200/80 bg-amber-50/60 px-3 py-2.5 dark:border-amber-900/50 dark:bg-amber-950/20"
        >
          <div>
            <p class="text-sm font-semibold text-amber-950 dark:text-amber-100">
              {{ itemLabel(it.key) }}
            </p>
            <p class="text-xs text-amber-800/80 dark:text-amber-200/80">
              {{ t('driver_maintenance.deadline') }}: {{ formatDate(it.date) }}
            </p>
          </div>
          <div class="shrink-0 text-right">
            <p class="text-lg font-bold tabular-nums text-amber-900 dark:text-amber-200">
              {{ it.days_left != null ? it.days_left : '—' }}
            </p>
            <p class="text-[10px] text-amber-800 dark:text-amber-300/90">
              {{ t('driver_maintenance.days_left') }}
            </p>
          </div>
        </li>
      </ul>
    </div>

    <p
      v-else-if="!loading && vehicle && !items.length"
      class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400"
    >
      {{ t('driver_maintenance.nothing_due') }}
    </p>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { getDriverSummary } from '../../api/driver'

const { t } = useI18n()

const loading = ref(true)
const errorMsg = ref('')
const vehicle = ref(null)
const items = ref([])

const vehicleLine = computed(() => {
  const v = vehicle.value
  if (!v) return ''
  const type = (v.type || '').trim()
  const seats = v.seat_count ? `${v.seat_count} chỗ` : ''
  if (type && seats) return `${type} ${seats}`
  return type || seats || '—'
})

function itemLabel(key) {
  const k = `driver_maintenance.field_${key}`
  const tr = t(k)
  return tr === k ? key : tr
}

function formatDate(ymd) {
  if (!ymd) return '—'
  const [y, m, d] = String(ymd).split('-')
  if (!y || !m || !d) return ymd
  return `${d}/${m}/${y}`
}

onMounted(async () => {
  loading.value = true
  errorMsg.value = ''
  try {
    const data = await getDriverSummary()
    vehicle.value = data?.vehicle ?? null
    items.value = data?.maintenance?.items ?? []
  } catch {
    errorMsg.value = t('driver_home.load_error')
    vehicle.value = null
    items.value = []
  } finally {
    loading.value = false
  }
})
</script>
