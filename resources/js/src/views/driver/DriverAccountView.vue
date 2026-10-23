<template>
  <div
    class="min-h-full w-full bg-driver-bg text-driver-ink"
  >
    <DriverPageHeader :title="t('nav.bottom_driver_account')">
      <template #actions>
        <NotificationBell compact />
      </template>
    </DriverPageHeader>

    <div
      class="driver-stagger mx-auto w-full min-w-0 max-w-full space-y-3 px-3 pt-3 sm:px-4"
    >

      <p
        v-if="errorMsg"
        class="rounded-xl border border-amber-700/40 bg-amber-950/30 px-3 py-2.5 text-xs leading-relaxed text-amber-100/90 sm:text-sm"
      >
        {{ errorMsg }}
      </p>

      <!-- S1: Header card -->
      <section
        class="rounded-2xl border border-white/[0.06] bg-driver-card px-3.5 pb-4 pt-4 sm:px-4"
      >
        <div class="relative mx-auto flex w-fit flex-col items-center">
          <!-- Inner circle uses overflow-hidden; FAB sits outside so it is not clipped -->
          <div class="relative h-16 w-16 shrink-0">
            <div
              class="relative h-full w-full overflow-hidden rounded-full border border-white/[0.08] bg-driver-bg"
            >
              <img
                v-if="avatarUrl"
                :src="avatarUrl"
                alt=""
                class="h-full w-full object-cover"
              />
              <div
                v-else
                class="flex h-full w-full items-center justify-center text-lg font-bold text-driver-accent"
              >
                {{ initials }}
              </div>
              <div
                v-if="avatarUploading"
                class="absolute inset-0 flex items-center justify-center rounded-full bg-black/55"
                aria-live="polite"
              >
                <span class="sr-only">{{ t('driver_account.avatar_uploading_a11y') }}</span>
                <svg
                  class="h-6 w-6 animate-spin text-driver-accent"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  />
                </svg>
              </div>
            </div>
            <input
              ref="avatarFileInput"
              type="file"
              class="sr-only"
              accept="image/jpeg,image/png,image/webp"
              @change="onAvatarFileChange"
            />
            <button
              type="button"
              class="absolute -bottom-px -right-px z-10 flex h-6 w-6 items-center justify-center rounded-full bg-driver-accent text-driver-bg shadow-sm shadow-black/25 ring-2 ring-driver-card transition hover:brightness-105 active:scale-95 disabled:pointer-events-none disabled:opacity-60"
              :disabled="avatarUploading"
              :aria-label="t('driver_account.update_photo_a11y')"
              @click="openAvatarPicker"
            >
              <ArrowUpTrayIcon class="h-3 w-3" aria-hidden="true" />
            </button>
          </div>
        </div>

        <p class="mt-2.5 text-center text-base font-bold leading-tight text-driver-ink sm:text-lg">
          {{ user?.name || '—' }}
        </p>

        <div class="mt-2.5 flex flex-wrap items-center justify-center gap-1.5">
          <span
            v-if="employeeCodeDisplay"
            class="rounded-full border border-driver-accent px-2.5 py-0.5 text-xs font-medium leading-none text-driver-accent"
          >
            {{ employeeCodeDisplay }}
          </span>
          <span
            class="inline-flex items-center gap-1.5 rounded-full border border-white/[0.08] bg-driver-bg/60 px-2.5 py-0.5 text-xs font-medium text-driver-ink"
          >
            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-400" aria-hidden="true" />
            {{ t('driver_account.active_status') }}
          </span>
        </div>

        <div class="my-3.5 h-px w-full bg-white/[0.06]" aria-hidden="true" />

        <div class="grid grid-cols-3 gap-2 text-center">
          <div v-for="col in headerCols" :key="col.k" class="min-w-0 px-0.5">
            <p
              class="text-[11px] font-semibold uppercase leading-tight tracking-[0.06em] text-driver-accent-bright/90 sm:text-xs"
            >
              {{ col.label }}
            </p>
            <p class="mt-1.5 text-sm font-bold leading-snug text-driver-ink tabular-nums">
              {{ col.value }}
            </p>
          </div>
        </div>
      </section>

      <!-- S2: Stats -->
      <section class="rounded-2xl border border-white/[0.06] bg-driver-card px-2 py-3 sm:px-3">
        <div v-if="loading" class="grid grid-cols-3 gap-0">
          <div v-for="n in 3" :key="n" class="flex flex-col items-center gap-1.5 py-1">
            <div class="h-8 w-8 animate-pulse rounded-full bg-driver-accent/10" />
            <div class="h-6 w-8 animate-pulse rounded bg-white/5" />
            <div class="h-2.5 w-12 animate-pulse rounded bg-white/5" />
          </div>
        </div>
        <div v-else class="grid grid-cols-3 divide-x divide-white/[0.06]">
          <div
            v-for="cell in statCells"
            :key="cell.key"
            class="flex min-h-[92px] flex-col items-center justify-start gap-1.5 px-1 py-1 text-center first:pl-0 last:pr-0"
          >
            <div
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-driver-accent/15 text-driver-accent [&_svg]:h-5 [&_svg]:w-5"
              v-html="cell.iconSvg"
            />
            <span class="text-xl font-bold tabular-nums leading-none text-driver-ink sm:text-2xl">
              {{ cell.value }}
            </span>
            <span class="text-xs leading-snug text-driver-muted">
              {{ cell.label }}
            </span>
          </div>
        </div>
      </section>

      <!-- S3: Work info -->
      <section class="rounded-2xl border border-white/[0.06] bg-driver-card p-3.5 sm:p-4">
        <div class="mb-3 flex items-center gap-2">
          <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-driver-accent" />
          <h2 class="text-sm font-semibold text-driver-ink">
            {{ t('driver_account.work_info_title') }}
          </h2>
        </div>
        <ul class="space-y-0 divide-y divide-white/[0.06]">
          <li
            v-for="row in workRows"
            :key="row.k"
            class="flex items-center gap-2.5 py-2.5 first:pt-0 last:pb-0"
          >
            <component :is="row.icon" class="h-5 w-5 shrink-0 text-driver-accent" aria-hidden="true" />
            <span class="min-w-0 flex-1 text-xs leading-snug text-driver-muted sm:text-sm">{{ row.label }}</span>
            <span class="max-w-[58%] text-right text-xs font-bold leading-snug text-driver-ink truncate sm:text-sm">{{
              row.value
            }}</span>
          </li>
        </ul>
      </section>

      <!-- S4: Xe & giấy tờ -->
      <section class="rounded-2xl border border-white/[0.06] bg-driver-card p-3.5 sm:p-4" data-testid="driver-documents">
        <div class="mb-3 flex items-center gap-2">
          <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-driver-accent" />
          <h2 class="text-sm font-semibold text-driver-ink">{{ t('driver_account.docs_title') }}</h2>
        </div>

        <div v-if="docsLoading" class="space-y-2">
          <div v-for="n in 3" :key="n" class="h-9 animate-pulse rounded-lg bg-white/5" />
        </div>
        <p v-else-if="docsError" class="text-xs text-amber-100/90">{{ docsError }}</p>
        <template v-else-if="docs">
          <div
            v-if="docs.alerts?.length"
            class="mb-3 rounded-xl bg-rose-500/12 px-3 py-2.5 ring-1 ring-rose-400/30"
            role="alert"
          >
            <p class="flex items-center gap-1.5 text-xs font-bold text-rose-100 sm:text-sm">
              <ExclamationTriangleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ t('driver_account.docs_alert_title', { n: docs.alerts.length }) }}
            </p>
            <ul class="mt-1.5 space-y-1 text-xs leading-snug text-rose-50/90">
              <li v-for="(a, i) in docs.alerts" :key="i">• {{ alertLine(a) }}</li>
            </ul>
          </div>
          <p v-else class="mb-3 rounded-xl bg-emerald-500/10 px-3 py-2 text-xs text-emerald-100 ring-1 ring-emerald-400/25">
            {{ t('driver_account.docs_all_ok') }}
          </p>

          <ul class="divide-y divide-white/[0.06]">
            <li v-if="docs.license" class="flex items-center gap-2.5 py-2.5 first:pt-0">
              <IdentificationIcon class="h-5 w-5 shrink-0 text-driver-accent" aria-hidden="true" />
              <span class="min-w-0 flex-1 text-xs text-driver-muted sm:text-sm">{{ t('driver_account.doc_license') }}</span>
              <span :class="docPillClass(docs.license.state)">{{ expiryText(docs.license) }}</span>
            </li>
          </ul>

          <p v-if="!docs.vehicles?.length" class="mt-2 text-xs text-driver-muted">{{ t('driver_account.docs_no_vehicle') }}</p>
          <div v-for="v in docs.vehicles" :key="v.id" class="mt-3 rounded-xl bg-driver-bg/50 p-3 ring-1 ring-white/[0.06]">
            <p class="flex items-center gap-2 text-sm font-bold text-driver-ink">
              <TruckIcon class="h-5 w-5 shrink-0 text-driver-accent" aria-hidden="true" />
              {{ v.license_plate }}
              <span v-if="v.type" class="truncate text-xs font-medium text-driver-muted">· {{ v.type }}</span>
            </p>
            <ul class="mt-2 divide-y divide-white/[0.06]">
              <li v-for="d in v.documents" :key="d.key" class="flex items-center gap-2 py-2">
                <span class="min-w-0 flex-1 text-xs text-driver-muted sm:text-sm">{{ t(`driver_account.doc_${d.key}`) }}</span>
                <span :class="docPillClass(d.state)">{{ expiryText(d) }}</span>
              </li>
              <li class="flex items-center gap-2 py-2">
                <span class="min-w-0 flex-1 text-xs text-driver-muted sm:text-sm">{{ t('driver_account.doc_maintenance') }}</span>
                <span :class="docPillClass(v.maintenance.state)">{{ maintenanceText(v.maintenance) }}</span>
              </li>
            </ul>
          </div>
          <p class="mt-3 text-[11px] leading-snug text-driver-muted">{{ t('driver_account.docs_footer', { n: docs.soon_days, m: docs.maintenance_stale_days }) }}</p>
        </template>
      </section>

      <!-- Đăng xuất -->
      <section class="rounded-2xl border border-white/[0.06] bg-driver-card p-3.5 sm:p-4">
        <button
          type="button"
          class="inline-flex min-h-[42px] w-full items-center justify-center gap-2 rounded-xl bg-driver-accent/12 px-4 text-sm font-bold text-driver-accent ring-1 ring-driver-accent/30 transition hover:bg-driver-accent/20 active:scale-[0.98]"
          @click="openLogoutConfirm"
        >
          <ArrowRightOnRectangleIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          {{ t('driver_account.logout') }}
        </button>
      </section>
    </div>

    <!-- Logout confirm (dark) -->
    <Teleport to="body">
      <div
        v-if="logoutConfirmOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center bg-black/60 p-4 sm:items-center"
        role="presentation"
        @click.self="logoutConfirmOpen = false"
      >
        <div
          role="dialog"
          aria-modal="true"
          aria-labelledby="driver-logout-title"
          class="w-full max-w-md rounded-[20px] border border-white/[0.06] bg-driver-card p-5 text-driver-ink"
        >
          <div class="flex items-start justify-between gap-3">
            <h2 id="driver-logout-title" class="text-lg font-bold sm:text-xl">
              {{ t('app.logout_confirm_title') }}
            </h2>
            <ModalCloseButton @click="logoutConfirmOpen = false" />
          </div>
          <p class="mt-3 text-base leading-relaxed text-driver-muted">
            {{ t('app.logout_confirm_body') }}
          </p>
          <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end sm:gap-3">
            <button
              type="button"
              class="inline-flex w-full min-h-[50px] items-center justify-center rounded-xl border border-white/[0.12] px-4 text-base font-semibold text-driver-ink transition hover:bg-white/5 active:scale-[0.98] sm:w-auto"
              @click="logoutConfirmOpen = false"
            >
              {{ t('app.cancel') }}
            </button>
            <button
              type="button"
              class="inline-flex w-full min-h-[50px] items-center justify-center rounded-xl bg-driver-accent px-4 text-base font-bold text-driver-bg transition hover:brightness-110 active:scale-[0.98] sm:w-auto"
              @click="confirmLogout"
            >
              {{ t('app.logout_confirm_action') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import ModalCloseButton from '../../components/ui/ModalCloseButton.vue'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowRightOnRectangleIcon,
  ArrowUpTrayIcon,
  BriefcaseIcon,
  BuildingOffice2Icon,
  ExclamationTriangleIcon,
  IdentificationIcon,
  MapPinIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'
import { getDriverDocuments, getDriverSummary } from '../../api/driver'
import { listTripsAll } from '../../api/trips'
import NotificationBell from '../../components/notifications/NotificationBell.vue'
import DriverPageHeader from '../../components/driver/DriverPageHeader.vue'
import { useAuthStore } from '../../store'
import { useAuthLogout } from '../../composables/useAuthLogout'
import { useHaptics } from '../../composables/useHaptics'
const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const { performLogout } = useAuthLogout()
const haptics = useHaptics()

function openLogoutConfirm() {
  haptics.tap()
  logoutConfirmOpen.value = true
}

const loading = ref(true)
const errorMsg = ref('')
const rawListItems = ref([])
const myDriverId = ref(null)
const logoutConfirmOpen = ref(false)
const avatarFileInput = ref(null)
const avatarUploading = ref(false)

function openAvatarPicker() {
  haptics.tap()
  avatarFileInput.value?.click()
}

async function onAvatarFileChange(ev) {
  const input = ev.target
  if (!(input instanceof HTMLInputElement)) return
  const file = input.files?.[0]
  input.value = ''
  if (!file) return
  if (file.size > 4 * 1024 * 1024) {
    errorMsg.value = t('driver_account.avatar_too_big')
    return
  }
  avatarUploading.value = true
  errorMsg.value = ''
  try {
    const fd = new FormData()
    fd.append('avatar', file)
    await auth.patchProfile(fd)
  } catch {
    errorMsg.value = t('driver_account.avatar_upload_error')
  } finally {
    avatarUploading.value = false
  }
}

const STAT_SVG = {
  completed: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>`,
  inProgress: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-12.75a.75.75 0 0 0-1.5 0v4.59l-1.95 2.1a.75.75 0 1 0 1.1 1.02l2.25-2.43a.75.75 0 0 0 .1-.38v-5Z" clip-rule="evenodd" /></svg>`,
  overdue: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 0-.75.75v3.5a.75.75 0 0 0 1.5 0v-3.5A.75.75 0 0 0 10 5Zm1 8a1 1 0 1 0-2 0 1 1 0 0 0 2 0Z" clip-rule="evenodd" /></svg>`,
}

const user = computed(() => auth.user)
const avatarUrl = computed(() => user.value?.avatar_url || null)
const initials = computed(() => {
  const n = (user.value?.name || '?').trim()
  const parts = n.split(/\s+/)
  if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  return n.slice(0, 2).toUpperCase()
})

function tripStatusNorm(x) {
  return String(x?.status ?? '').trim().toLowerCase()
}

function ymd(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function tripDepartYmdKey(trip) {
  const dd = trip?.depart_date
  if (dd && typeof dd === 'string' && /^\d{4}-\d{2}-\d{2}/.test(dd)) {
    return dd.slice(0, 10)
  }
  const iso = trip?.depart_at ?? trip?.dispatch_request?.depart_at
  if (!iso) return null
  const d = new Date(iso)
  return Number.isNaN(d.getTime()) ? null : ymd(d)
}

function tripDepartAtMs(trip) {
  const iso = trip?.depart_at ?? trip?.dispatch_request?.depart_at
  if (!iso) return NaN
  const t = new Date(iso).getTime()
  return Number.isFinite(t) ? t : NaN
}

function startOfLocalTodayMs() {
  const d = new Date()
  d.setHours(0, 0, 0, 0)
  return d.getTime()
}

function isTripOverdueForStats(trip) {
  const s = tripStatusNorm(trip)
  if (s === 'completed' || s === 'cancelled') return false
  const ms = tripDepartAtMs(trip)
  if (Number.isFinite(ms)) return ms < startOfLocalTodayMs()
  const key = tripDepartYmdKey(trip)
  if (!key) return false
  return key < ymd(new Date())
}

function isNonEmpty(val) {
  if (val === null || val === undefined) return false
  return String(val).trim() !== ''
}

const employeeCodeDisplay = computed(() => {
  const ci = user.value?.cms_user_info
  const code = ci?.code != null ? String(ci.code) : user.value?.employee_code
  return code && String(code).trim() !== '' ? String(code).trim() : ''
})

const headerCols = computed(() => {
  const ci = user.value?.cms_user_info || {}
  const dash = '—'
  return [
    {
      k: 'dept',
      label: t('driver_account.label_dept'),
      value: isNonEmpty(ci.department_name) ? String(ci.department_name) : dash,
    },
    {
      k: 'hq',
      label: t('driver_account.label_hq'),
      value: isNonEmpty(ci.headquarter_name) ? String(ci.headquarter_name) : dash,
    },
    {
      k: 'wp',
      label: t('driver_account.label_workplace'),
      value: isNonEmpty(ci.working_place) ? String(ci.working_place) : dash,
    },
  ]
})

const rawTrips = computed(() => {
  const id = myDriverId.value
  if (id == null) return []
  return rawListItems.value.filter(
    (x) => x.driver_id != null && Number(x.driver_id) === Number(id),
  )
})

const stats = computed(() => ({
  completed: rawTrips.value.filter((x) => tripStatusNorm(x) === 'completed').length,
  inProgress: rawTrips.value.filter(
    (x) => tripStatusNorm(x) === 'in_progress' && !isTripOverdueForStats(x),
  ).length,
  overdue: rawTrips.value.filter((x) => isTripOverdueForStats(x)).length,
}))

const statCells = computed(() => [
  {
    key: 'completed',
    value: stats.value.completed,
    label: t('driver_home.stats_completed'),
    iconSvg: STAT_SVG.completed,
  },
  {
    key: 'inProgress',
    value: stats.value.inProgress,
    label: t('driver_home.stats_in_progress'),
    iconSvg: STAT_SVG.inProgress,
  },
  {
    key: 'overdue',
    value: stats.value.overdue,
    label: t('driver_home.stats_overdue'),
    iconSvg: STAT_SVG.overdue,
  },
])

function positionDisplay(ci) {
  const parts = []
  if (isNonEmpty(ci?.position_name)) parts.push(String(ci.position_name).trim())
  if (isNonEmpty(ci?.concurrent_position_name)) parts.push(String(ci.concurrent_position_name).trim())
  return parts.length ? parts.join(' · ') : '—'
}

const workRows = computed(() => {
  const u = user.value
  const ci = u?.cms_user_info || {}
  const code =
    ci?.code != null && String(ci.code).trim() !== ''
      ? String(ci.code).trim()
      : u?.employee_code && String(u.employee_code).trim() !== ''
        ? String(u.employee_code).trim()
        : '—'

  const hq = isNonEmpty(ci.headquarter_name) ? String(ci.headquarter_name) : '—'
  const wp = isNonEmpty(ci.working_place) ? String(ci.working_place) : '—'

  return [
    { k: 'code', label: t('driver_account.label_employee_code'), value: code, icon: IdentificationIcon },
    {
      k: 'position',
      label: t('driver_account.label_position'),
      value: positionDisplay(ci),
      icon: BriefcaseIcon,
    },
    { k: 'hq', label: t('driver_account.label_hq'), value: hq, icon: BuildingOffice2Icon },
    { k: 'wp', label: t('driver_account.label_workplace'), value: wp, icon: MapPinIcon },
  ]
})

async function fetchData() {
  loading.value = true
  errorMsg.value = ''
  const now = new Date()
  const past = new Date(now)
  past.setDate(past.getDate() - 30)
  const horizon = new Date(now)
  horizon.setDate(horizon.getDate() + 21)
  try {
    const [sum, listRes] = await Promise.all([
      getDriverSummary(),
      listTripsAll({ from: ymd(past), to: ymd(horizon), per_page: 100 }),
    ])
    myDriverId.value = sum?.driver?.id ?? null
    rawListItems.value = listRes?.items ?? []
  } catch {
    errorMsg.value = t('driver_home.load_error')
    myDriverId.value = null
    rawListItems.value = []
  } finally {
    loading.value = false
  }
}

const docs = ref(null)
const docsLoading = ref(true)
const docsError = ref('')

async function fetchDocuments() {
  docsLoading.value = true
  docsError.value = ''
  try {
    docs.value = await getDriverDocuments()
  } catch {
    docsError.value = t('driver_account.docs_load_error')
  } finally {
    docsLoading.value = false
  }
}

function dmy(iso) {
  if (!iso) return ''
  const [y, m, d] = String(iso).slice(0, 10).split('-')
  return `${d}/${m}/${y}`
}

function docPillClass(state) {
  const base = 'shrink-0 rounded-md px-2 py-0.5 text-[11px] font-semibold tabular-nums sm:text-xs'
  if (state === 'exp' || state === 'stale') return `${base} bg-rose-500/20 text-rose-100`
  if (state === 'soon') return `${base} bg-amber-500/20 text-amber-100`
  if (state === 'ok') return `${base} bg-emerald-500/15 text-emerald-100`
  return `${base} bg-white/[0.06] text-driver-muted`
}

function expiryText(doc) {
  if (doc.state === 'none') return t('driver_account.doc_unset')
  if (doc.state === 'exp') return t('driver_account.doc_expired', { date: dmy(doc.expires_at) })
  if (doc.state === 'soon') return t('driver_account.doc_soon', { n: doc.days, date: dmy(doc.expires_at) })
  return t('driver_account.doc_valid', { date: dmy(doc.expires_at) })
}

function maintenanceText(m) {
  if (m.state === 'none') return t('driver_account.doc_unset')
  if (m.state === 'stale') return t('driver_account.maint_stale', { date: dmy(m.last_at), n: m.days_since })
  return t('driver_account.maint_ok', { date: dmy(m.last_at) })
}

function alertLine(a) {
  const name = t(`driver_account.doc_${a.key}`)
  const who = a.license_plate ? `${name} (${a.license_plate})` : name
  if (a.state === 'stale') return t('driver_account.alert_maint', { doc: who, n: a.days })
  if (a.state === 'exp') return t('driver_account.alert_expired', { doc: who, date: dmy(a.expires_at) })
  return t('driver_account.alert_soon', { doc: who, n: a.days, date: dmy(a.expires_at) })
}

async function confirmLogout() {
  haptics.impact()
  logoutConfirmOpen.value = false
  await performLogout()
}

onMounted(async () => {
  if (!auth.user) {
    try {
      await auth.fetchMe()
    } catch {
      /* handled by guard */
    }
  }
  await Promise.all([fetchData(), fetchDocuments()])
})
</script>
