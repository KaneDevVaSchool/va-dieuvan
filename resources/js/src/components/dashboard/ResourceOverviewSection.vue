<template>
  <div v-if="visible" class="space-y-4">
    <div
      class="rounded-2xl border border-slate-200 bg-gradient-to-b from-slate-50/90 to-white p-4 shadow-sm sm:p-5"
    >
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
          <h2 class="text-base font-semibold text-slate-900">{{ t('resource_overview.title') }}</h2>
          <p class="mt-0.5 text-sm text-slate-500">{{ t('resource_overview.subtitle') }}</p>
          <p v-if="lastUpdated && !loading" class="mt-1 text-xs text-slate-400">
            {{ t('resource_overview.updated_at', { time: formattedUpdated }) }}
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 disabled:opacity-50"
            :disabled="loading"
            :aria-busy="loading"
            @click="load"
          >
            <ArrowPathIcon class="h-4 w-4 shrink-0" :class="{ 'animate-spin': loading }" aria-hidden="true" />
            {{ t('resource_overview.refresh') }}
          </button>
          <RouterLink
            class="inline-flex items-center justify-center rounded-lg border border-teal-600 bg-teal-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-teal-700"
            to="/resources"
          >
            {{ t('resource_overview.open_resources') }}
          </RouterLink>
        </div>
      </div>

      <div v-if="loadError" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
        {{ loadError }}
      </div>

      <div class="mt-5 grid gap-4 lg:grid-cols-3">
        <!-- Fleet -->
        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
          <div class="text-sm font-semibold text-slate-900">{{ t('resource_overview.fleet_title') }}</div>
          <p class="mt-1 text-xs text-slate-500">{{ t('resource_overview.fleet_hint') }}</p>
          <template v-if="loading">
            <div class="mt-4 space-y-2">
              <div class="h-8 w-28 animate-pulse rounded bg-slate-200" />
              <div class="h-2 animate-pulse rounded-full bg-slate-200" />
              <div class="h-3 w-full animate-pulse rounded bg-slate-100" />
            </div>
          </template>
          <template v-else>
            <div class="mt-3 flex items-baseline justify-between gap-2">
              <span class="text-2xl font-bold tabular-nums text-slate-900">{{ vehiclesOperational }} / {{ vehiclesTotal }}</span>
              <span class="text-xs text-slate-500">{{ t('resource_overview.fleet_label') }}</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
              <div
                class="h-full rounded-full bg-teal-500 transition-[width]"
                :style="{ width: pct(vehiclesOperational, vehiclesTotal) }"
              />
            </div>
            <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-600">
              <span>
                {{ t('resource_overview.fleet_ready') }}:
                <strong class="font-semibold text-slate-900">{{ vehiclesReady }}</strong>
              </span>
              <span>
                {{ t('resource_overview.fleet_in_use') }}:
                <strong class="font-semibold text-slate-900">{{ vehiclesInUse }}</strong>
              </span>
              <span v-if="vehiclesMaintenance > 0" class="text-amber-800">
                {{ t('resource_overview.fleet_maintenance') }}:
                <strong>{{ vehiclesMaintenance }}</strong>
              </span>
              <span v-if="vehiclesBroken > 0" class="text-rose-600">
                {{ t('resource_overview.fleet_broken') }}:
                <strong>{{ vehiclesBroken }}</strong>
              </span>
            </div>
          </template>
        </div>

        <!-- Drivers -->
        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
          <div class="text-sm font-semibold text-slate-900">{{ t('resource_overview.drivers_title') }}</div>
          <p class="mt-1 text-xs text-slate-500">{{ t('resource_overview.drivers_hint') }}</p>
          <template v-if="loading">
            <div class="mt-4 space-y-2">
              <div class="h-8 w-28 animate-pulse rounded bg-slate-200" />
              <div class="h-2 animate-pulse rounded-full bg-slate-200" />
            </div>
          </template>
          <template v-else>
            <div class="mt-3 flex items-baseline justify-between gap-2">
              <span class="text-2xl font-bold tabular-nums text-slate-900">{{ driversAvailable }} / {{ driversEmployed }}</span>
              <span class="text-xs text-slate-500">{{ t('resource_overview.drivers_ratio_label') }}</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
              <div
                class="h-full rounded-full bg-amber-400 transition-[width]"
                :style="{ width: pct(driversAvailable, driversEmployed) }"
              />
            </div>
            <p class="mt-2 text-xs text-slate-600">
              {{ t('resource_overview.drivers_total_profiles', { n: driversTotal }) }}
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
              <span
                class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-900 ring-1 ring-teal-600/15"
              >
                {{ t('resource_overview.drivers_avail') }} · {{ driversAvailable }}
              </span>
              <span
                class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-900 ring-1 ring-amber-600/20"
              >
                {{ t('resource_overview.drivers_busy') }} · {{ driversBusy }}
              </span>
              <span
                class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-300/60"
              >
                {{ t('resource_overview.drivers_offline') }} · {{ driversOffline }}
              </span>
            </div>
          </template>
        </div>

        <!-- Providers -->
        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
          <div class="text-sm font-semibold text-slate-900">{{ t('resource_overview.providers_title') }}</div>
          <p class="mt-1 text-xs text-slate-500">{{ t('resource_overview.providers_hint') }}</p>
          <template v-if="loading">
            <div class="mt-4 space-y-2">
              <div class="h-8 w-28 animate-pulse rounded bg-slate-200" />
              <div class="h-2 animate-pulse rounded-full bg-slate-200" />
            </div>
          </template>
          <template v-else>
            <div class="mt-3 flex items-baseline justify-between gap-2">
              <span class="text-2xl font-bold tabular-nums text-slate-900">{{ providersActive }} / {{ providersTotal }}</span>
              <span class="text-xs text-slate-500">{{ t('resource_overview.providers_label') }}</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
              <div
                class="h-full rounded-full bg-slate-700 transition-[width]"
                :style="{ width: pct(providersActive, providersTotal) }"
              />
            </div>
          </template>
        </div>
      </div>

      <!-- Vehicle mix bar -->
      <div v-if="!loading && vehiclesTotal > 0" class="mt-5 rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
        <div class="text-sm font-semibold text-slate-900">{{ t('resource_overview.fleet_mix_title') }}</div>
        <div class="mt-3 flex h-3 w-full overflow-hidden rounded-full bg-slate-100">
          <div
            v-if="segReady > 0"
            class="h-full bg-teal-500"
            :style="{ width: segReady + '%' }"
            :title="`${t('resource_overview.fleet_ready')}: ${vehiclesReady}`"
          />
          <div
            v-if="segInUse > 0"
            class="h-full bg-sky-500"
            :style="{ width: segInUse + '%' }"
            :title="`${t('resource_overview.fleet_in_use')}: ${vehiclesInUse}`"
          />
          <div
            v-if="segMaint > 0"
            class="h-full bg-amber-400"
            :style="{ width: segMaint + '%' }"
            :title="`${t('resource_overview.fleet_maintenance')}: ${vehiclesMaintenance}`"
          />
          <div
            v-if="segBroken > 0"
            class="h-full bg-rose-500"
            :style="{ width: segBroken + '%' }"
            :title="`${t('resource_overview.fleet_broken')}: ${vehiclesBroken}`"
          />
        </div>
        <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-600">
          <li class="flex items-center gap-1.5">
            <span class="h-2 w-2 rounded-sm bg-teal-500" />
            {{ t('resource_overview.fleet_ready') }} ({{ vehiclesReady }})
          </li>
          <li class="flex items-center gap-1.5">
            <span class="h-2 w-2 rounded-sm bg-sky-500" />
            {{ t('resource_overview.fleet_in_use') }} ({{ vehiclesInUse }})
          </li>
          <li class="flex items-center gap-1.5">
            <span class="h-2 w-2 rounded-sm bg-amber-400" />
            {{ t('resource_overview.fleet_maintenance') }} ({{ vehiclesMaintenance }})
          </li>
          <li class="flex items-center gap-1.5">
            <span class="h-2 w-2 rounded-sm bg-rose-500" />
            {{ t('resource_overview.fleet_broken') }} ({{ vehiclesBroken }})
          </li>
        </ul>
      </div>

      <!-- Quick links -->
      <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-200/80 pt-4">
        <RouterLink
          class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 ring-slate-200 transition hover:border-teal-300 hover:bg-teal-50/50 hover:text-teal-900"
          to="/dispatcher"
        >
          {{ t('resource_overview.quick_dispatcher') }}
        </RouterLink>
        <RouterLink
          class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:border-teal-300 hover:bg-teal-50/50 hover:text-teal-900"
          to="/resources?tab=vehicles"
        >
          {{ t('resource_overview.quick_tab_vehicles') }}
        </RouterLink>
        <RouterLink
          class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:border-teal-300 hover:bg-teal-50/50 hover:text-teal-900"
          to="/resources?tab=drivers"
        >
          {{ t('resource_overview.quick_tab_drivers') }}
        </RouterLink>
        <RouterLink
          class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:border-teal-300 hover:bg-teal-50/50 hover:text-teal-900"
          to="/resources?tab=suppliers"
        >
          {{ t('resource_overview.quick_tab_suppliers') }}
        </RouterLink>
      </div>
    </div>

    <!-- Alerts -->
    <div v-if="!loading" class="space-y-2">
      <div
        v-if="vehiclesMaintenance > 0"
        class="flex flex-col gap-2 rounded-lg border border-amber-200 bg-amber-50/80 px-3 py-2 text-sm text-amber-950 sm:flex-row sm:items-center sm:justify-between"
      >
        <span>{{ t('resource_overview.alert_maintenance', { n: vehiclesMaintenance }) }}</span>
        <RouterLink class="shrink-0 font-medium text-amber-900 underline" to="/resources?tab=vehicles">
          {{ t('resource_overview.alert_maintenance_link') }}
        </RouterLink>
      </div>
      <div
        v-if="vehiclesBroken > 0"
        class="flex flex-col gap-2 rounded-lg border border-rose-200 bg-rose-50/80 px-3 py-2 text-sm text-rose-950 sm:flex-row sm:items-center sm:justify-between"
      >
        <span>{{ t('resource_overview.alert_broken', { n: vehiclesBroken }) }}</span>
        <RouterLink class="shrink-0 font-medium text-rose-900 underline" to="/resources?tab=vehicles">
          {{ t('resource_overview.alert_maintenance_link') }}
        </RouterLink>
      </div>
      <div
        v-if="providersInactive > 0"
        class="flex flex-col gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 sm:flex-row sm:items-center sm:justify-between"
      >
        <span>{{ t('resource_overview.alert_providers_inactive', { n: providersInactive }) }}</span>
        <RouterLink class="shrink-0 font-medium text-slate-900 underline" to="/resources?tab=suppliers">
          {{ t('resource_overview.alert_providers_link') }}
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'
import { listDrivers, listTransportProviders, listVehicles } from '../../api/operational'
import { useAuthStore } from '../../store'

const { t, locale } = useI18n()
const auth = useAuthStore()

const loading = ref(false)
const loadError = ref('')
const lastUpdated = ref(null)

const vehiclesTotal = ref(0)
const vehiclesReady = ref(0)
const vehiclesInUse = ref(0)
const vehiclesMaintenance = ref(0)
const vehiclesBroken = ref(0)

const driversTotal = ref(0)
const driversEmployed = ref(0)
const driversAvailable = ref(0)
const driversBusy = ref(0)
const driversOffline = ref(0)

const providersTotal = ref(0)
const providersActive = ref(0)

const visible = computed(() => {
  if (!auth.user) return false
  if (!auth.isFeatureEnabled('module.operations')) return false
  return auth.hasAnyPermission(['resource.vehicle.manage', 'trip.assign'])
})

const vehiclesOperational = computed(() => vehiclesReady.value + vehiclesInUse.value)

const providersInactive = computed(() => Math.max(0, providersTotal.value - providersActive.value))

const formattedUpdated = computed(() => {
  const d = lastUpdated.value
  if (!d) return ''
  const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  return new Intl.DateTimeFormat(loc, { dateStyle: 'short', timeStyle: 'short' }).format(d)
})

function segPct(part) {
  const t = Number(vehiclesTotal.value) || 0
  const p = Number(part) || 0
  if (t <= 0) return 0
  return Math.round((p / t) * 1000) / 10
}

const segReady = computed(() => segPct(vehiclesReady.value))
const segInUse = computed(() => segPct(vehiclesInUse.value))
const segMaint = computed(() => segPct(vehiclesMaintenance.value))
const segBroken = computed(() => segPct(vehiclesBroken.value))

function pct(part, total) {
  const t = Number(total) || 0
  const p = Number(part) || 0
  if (t <= 0) return '0%'
  return `${Math.min(100, Math.round((p / t) * 100))}%`
}

async function load() {
  if (!visible.value) return
  loading.value = true
  loadError.value = ''
  try {
    const [
      vAll,
      vReady,
      vUse,
      vMaint,
      vBroken,
      dAll,
      dEmployed,
      dAvail,
      dBusy,
      dOffline,
      pAll,
      pActive,
    ] = await Promise.all([
      listVehicles({ per_page: 1 }),
      listVehicles({ per_page: 1, status: 'ready' }),
      listVehicles({ per_page: 1, status: 'in_use' }),
      listVehicles({ per_page: 1, status: 'maintenance' }),
      listVehicles({ per_page: 1, status: 'broken' }),
      listDrivers({ per_page: 1 }),
      listDrivers({ per_page: 1, employment_status: 'active' }),
      listDrivers({ per_page: 1, employment_status: 'active', availability_status: 'available' }),
      listDrivers({ per_page: 1, employment_status: 'active', availability_status: 'busy' }),
      listDrivers({ per_page: 1, employment_status: 'active', availability_status: 'offline' }),
      listTransportProviders({ per_page: 1 }),
      listTransportProviders({ per_page: 1, is_active: true }),
    ])
    vehiclesTotal.value = vAll.meta?.total ?? 0
    vehiclesReady.value = vReady.meta?.total ?? 0
    vehiclesInUse.value = vUse.meta?.total ?? 0
    vehiclesMaintenance.value = vMaint.meta?.total ?? 0
    vehiclesBroken.value = vBroken.meta?.total ?? 0
    driversTotal.value = dAll.meta?.total ?? 0
    driversEmployed.value = dEmployed.meta?.total ?? 0
    driversAvailable.value = dAvail.meta?.total ?? 0
    driversBusy.value = dBusy.meta?.total ?? 0
    driversOffline.value = dOffline.meta?.total ?? 0
    providersTotal.value = pAll.meta?.total ?? 0
    providersActive.value = pActive.meta?.total ?? 0
    lastUpdated.value = new Date()
  } catch {
    loadError.value = t('resource_overview.load_error')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  load()
})
</script>
