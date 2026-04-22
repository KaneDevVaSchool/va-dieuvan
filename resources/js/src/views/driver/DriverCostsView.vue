<template>
  <div class="max-w-lg mx-auto w-full space-y-4 sm:max-w-2xl">
    <div>
      <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl">
        {{ t('driver_costs.title') }}
      </h1>
      <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
        {{ t('driver_costs.subtitle') }}
      </p>
    </div>

    <div class="flex flex-wrap gap-2">
      <button
        v-for="f in statusFilters"
        :key="f.value || 'all'"
        type="button"
        class="rounded-full border px-3 py-1.5 text-xs font-semibold transition"
        :class="
          statusQ === f.value
            ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900'
            : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200'
        "
        @click="setStatus(f.value)"
      >
        {{ f.label }}
      </button>
    </div>

    <RouterLink
      to="/driver/schedule"
      class="block rounded-2xl border border-dashed border-sky-300/80 bg-sky-50/80 px-4 py-3 text-center text-sm font-semibold text-sky-800 transition hover:bg-sky-100/80 dark:border-sky-800 dark:bg-sky-950/30 dark:text-sky-200"
    >
      {{ t('driver_costs.hint_add_from_trip') }}
    </RouterLink>

    <p
      v-if="errorMsg"
      class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900"
    >
      {{ errorMsg }}
    </p>

    <p v-if="!loading && !items.length" class="text-center text-sm text-slate-500 dark:text-slate-400">
      {{ t('driver_costs.empty') }}
    </p>

    <ul v-else class="space-y-2.5">
      <li
        v-for="c in items"
        :key="c.id"
        class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/60"
      >
        <RouterLink
          :to="`/driver/costs/${c.id}`"
          class="block border-b border-slate-100 dark:border-slate-700/80"
        >
          <div class="flex items-start justify-between gap-2 px-3 py-2">
            <span
              class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-semibold"
              :class="statusClass(c.status)"
            >
              {{ statusLabel(c.status) }}
            </span>
            <span class="text-xs text-slate-500 dark:text-slate-400">#{{ c.trip_id }}</span>
          </div>
          <div class="px-3 py-2.5">
            <p class="font-semibold text-slate-900 dark:text-white">
              {{ formatVnd(c.amount) }}
              <span v-if="c.currency && c.currency !== 'VND'" class="ml-1 text-sm font-normal text-slate-500">
                {{ c.currency }}
              </span>
            </p>
            <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
              {{ typeLabel(c.type) }}
              <span v-if="c.description"> · {{ c.description }}</span>
            </p>
            <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-500">
              {{ t('driver_costs.trip_depart') }}: {{ formatDepart(c.trip) }}
            </p>
          </div>
        </RouterLink>
        <div class="px-3 pb-3 pt-1">
          <RouterLink
            :to="`/driver/trips/${c.trip_id}`"
            class="block w-full rounded-xl bg-slate-100 py-2 text-center text-sm font-semibold text-slate-900 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-white dark:hover:bg-slate-700"
          >
            {{ t('driver_costs.open_trip') }}
          </RouterLink>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { listTripCosts } from '../../api/costs'
import { formatVnd } from '../../util/labels'

const { t, te } = useI18n()
const route = useRoute()
const router = useRouter()

const loading = ref(true)
const errorMsg = ref('')
const items = ref([])

const statusQ = computed(() => (typeof route.query.status === 'string' ? route.query.status : ''))

const statusFilters = computed(() => [
  { value: '', label: t('driver_costs.filter_all') },
  { value: 'submitted', label: t('driver_costs.filter_submitted') },
  { value: 'confirmed', label: t('driver_costs.filter_confirmed') },
  { value: 'rejected', label: t('driver_costs.filter_rejected') },
])

function setStatus(v) {
  router.replace({ path: '/driver/costs', query: v ? { status: v } : {} })
}

function statusClass(st) {
  if (st === 'confirmed') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
  if (st === 'rejected') return 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-200'
  if (st === 'submitted') return 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
}

function statusLabel(st) {
  const k = `driver_costs.st_${st}`
  const tr = t(k)
  return tr === k && st ? st : tr
}

function typeLabel(type) {
  const raw = String(type ?? '').trim()
  if (!raw) return '—'
  const slug = raw.toLowerCase().replace(/[^a-z0-9_]/g, '_')
  const i18nKey = `trip_detail.costs.type_${slug}`
  if (te(i18nKey)) return t(i18nKey)
  return raw
}

function formatDepart(trip) {
  if (!trip?.depart_at) return '—'
  const d = new Date(trip.depart_at)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function load() {
  loading.value = true
  errorMsg.value = ''
  try {
    const p = { per_page: 50, page: 1 }
    if (statusQ.value) p.status = statusQ.value
    const res = await listTripCosts(p)
    items.value = res?.items ?? []
  } catch {
    errorMsg.value = t('driver_home.load_error')
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  load()
})

watch(
  () => [route.path, route.query.status],
  () => {
    if (route.path === '/driver/costs') load()
  },
)
</script>
