<template>
  <div
    class="min-h-full w-full bg-[#09180f] pb-[calc(7rem+env(safe-area-inset-bottom))] text-white"
  >
    <div
      class="mx-auto w-full max-w-[390px] space-y-3 px-3 pt-[max(0.75rem,env(safe-area-inset-top))] sm:px-4"
    >
      <!-- Header chrome -->
      <div class="flex items-center justify-between gap-3 pt-1">
        <h1 class="text-xl font-bold tracking-tight text-white">
          {{ t('nav.bottom_driver_account') }}
        </h1>
        <NotificationBell />
      </div>

      <p
        v-if="errorMsg"
        class="rounded-2xl border border-amber-700/40 bg-amber-950/30 px-3 py-2 text-xs text-amber-100/90"
      >
        {{ errorMsg }}
      </p>

      <!-- S1: Header card -->
      <section
        class="rounded-[20px] border border-white/[0.06] bg-[#0f2318] px-4 pb-5 pt-6 sm:px-5"
      >
        <div class="relative mx-auto flex w-fit flex-col items-center">
          <div
            class="relative -mt-2 h-[72px] w-[72px] shrink-0 overflow-hidden rounded-full border border-white/[0.08] bg-[#051a12]"
          >
            <img
              v-if="avatarUrl"
              :src="avatarUrl"
              alt=""
              class="h-full w-full object-cover"
            />
            <div
              v-else
              class="flex h-full w-full items-center justify-center text-lg font-bold text-[#2dd4a0]"
            >
              {{ initials }}
            </div>
            <button
              type="button"
              class="absolute -bottom-0.5 -right-0.5 flex h-[22px] w-[22px] items-center justify-center rounded-full bg-[#2dd4a0] text-[#09180f] ring-2 ring-[#0f2318] transition active:scale-95"
              :aria-label="t('driver_account.update_photo_a11y')"
              @click="onCameraClick"
            >
              <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </button>
          </div>
        </div>

        <p class="mt-4 text-center text-[20px] font-bold leading-tight text-white">
          {{ user?.name || '—' }}
        </p>

        <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
          <span
            v-if="employeeCodeDisplay"
            class="rounded-full border border-[#2dd4a0] px-2.5 py-0.5 text-[11px] font-medium leading-none text-[#2dd4a0]"
          >
            {{ employeeCodeDisplay }}
          </span>
          <span
            class="inline-flex items-center gap-1.5 rounded-full border border-white/[0.08] bg-[#09180f]/60 px-2.5 py-0.5 text-[11px] font-medium text-white"
          >
            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-400" aria-hidden="true" />
            {{ t('driver_account.active_status') }}
          </span>
        </div>

        <div class="my-5 h-px w-full bg-white/[0.06]" aria-hidden="true" />

        <div class="grid grid-cols-3 gap-2 text-center">
          <div v-for="col in headerCols" :key="col.k" class="min-w-0 px-0.5">
            <p
              class="text-[10px] font-medium uppercase leading-tight tracking-[0.5px] text-[#4ade80]/85"
            >
              {{ col.label }}
            </p>
            <p class="mt-1.5 text-[13px] font-bold leading-tight text-white">
              {{ col.value }}
            </p>
          </div>
        </div>
      </section>

      <!-- S2: Stats -->
      <section class="rounded-[20px] border border-white/[0.06] bg-[#0f2318] px-2 py-4 sm:px-3">
        <div v-if="loading" class="grid grid-cols-3 gap-0">
          <div v-for="n in 3" :key="n" class="flex flex-col items-center gap-2 py-2">
            <div class="h-10 w-10 animate-pulse rounded-full bg-[#2dd4a0]/10" />
            <div class="h-8 w-10 animate-pulse rounded bg-white/5" />
            <div class="h-3 w-16 animate-pulse rounded bg-white/5" />
          </div>
        </div>
        <div v-else class="grid grid-cols-3 divide-x divide-white/[0.06]">
          <div
            v-for="cell in statCells"
            :key="cell.key"
            class="flex min-h-[120px] flex-col items-center justify-start gap-2 px-1 py-2 text-center first:pl-0 last:pr-0"
          >
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#2dd4a0]/15 text-[#2dd4a0] [&_svg]:h-5 [&_svg]:w-5"
              v-html="cell.iconSvg"
            />
            <span class="text-[32px] font-bold tabular-nums leading-none text-white">
              {{ cell.value }}
            </span>
            <span class="text-[11px] leading-tight text-[#94a3b8]">
              {{ cell.label }}
            </span>
          </div>
        </div>
      </section>

      <!-- S3: Work info -->
      <section class="rounded-[20px] border border-white/[0.06] bg-[#0f2318] p-4 sm:p-5">
        <div class="mb-4 flex items-center gap-2">
          <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#2dd4a0]" />
          <h2 class="text-[13px] font-medium text-white">
            {{ t('driver_account.work_info_title') }}
          </h2>
        </div>
        <ul class="space-y-0 divide-y divide-white/[0.06]">
          <li
            v-for="row in workRows"
            :key="row.k"
            class="flex items-center gap-3 py-3 first:pt-0 last:pb-0"
          >
            <component :is="row.icon" class="h-5 w-5 shrink-0 text-[#2dd4a0]" aria-hidden="true" />
            <span class="min-w-0 flex-1 text-[13px] text-[#94a3b8]">{{ row.label }}</span>
            <span class="max-w-[55%] text-right text-[13px] font-bold text-white truncate">{{
              row.value
            }}</span>
          </li>
        </ul>
      </section>

      <!-- S4: Documents -->
      <section class="rounded-[20px] border border-white/[0.06] bg-[#0f2318] p-4 sm:p-5">
        <div class="mb-3 flex items-start justify-between gap-3">
          <div class="flex items-center gap-2">
            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#2dd4a0]" />
            <h2 class="text-[13px] font-medium text-white">
              {{ t('driver_account.documents_title') }}
            </h2>
          </div>
          <RouterLink
            to="/profile"
            class="shrink-0 text-[13px] font-medium text-[#2dd4a0] transition hover:text-[#4ade80]"
          >
            {{ t('driver_account.update_cta') }} ›
          </RouterLink>
        </div>
        <ul class="space-y-0 divide-y divide-white/[0.06]">
          <li v-for="doc in documentRows" :key="doc.id" class="flex gap-3 py-3 first:pt-0 last:pb-0">
            <DocumentTextIcon class="h-5 w-5 shrink-0 text-[#2dd4a0]" aria-hidden="true" />
            <div class="min-w-0 flex-1">
              <p class="text-[13px] font-bold text-white">{{ doc.title }}</p>
              <p class="mt-0.5 text-[11px] leading-snug text-[#94a3b8]">{{ doc.subtitle }}</p>
            </div>
            <span
              class="h-fit shrink-0 self-center rounded-full px-2 py-0.5 text-[11px] font-semibold leading-tight"
              :class="doc.badgeClass"
            >
              {{ doc.badgeText }}
            </span>
          </li>
        </ul>
      </section>

      <!-- S5: Recent trips -->
      <section class="rounded-[20px] border border-white/[0.06] bg-[#0f2318] p-4 sm:p-5">
        <div class="mb-3 flex items-start justify-between gap-3">
          <div class="flex items-center gap-2">
            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#2dd4a0]" />
            <h2 class="text-[13px] font-medium text-white">
              {{ t('driver_account.recent_trips_title') }}
            </h2>
          </div>
          <RouterLink
            to="/driver/schedule"
            class="shrink-0 text-[13px] font-medium text-[#2dd4a0] transition hover:text-[#4ade80]"
          >
            {{ t('driver_account.see_all') }} ›
          </RouterLink>
        </div>
        <p
          v-if="!loading && recentTrips.length === 0"
          class="py-4 text-center text-sm text-[#94a3b8]"
        >
          {{ t('driver_account.empty_trips') }}
        </p>
        <ul v-else class="space-y-0">
          <li
            v-for="(trip, idx) in recentTrips"
            :key="trip.id"
            class="border-b border-white/[0.06] py-3 last:border-b-0 last:pb-0"
            :class="idx === 0 ? 'pt-0' : ''"
          >
            <RouterLink
              :to="`/driver/trips/${trip.id}`"
              class="flex gap-2 text-left transition active:opacity-90"
            >
              <div class="w-[52px] shrink-0">
                <p class="text-[14px] font-bold tabular-nums text-white">{{ tripTime(trip) }}</p>
                <p class="mt-0.5 text-[11px] text-[#94a3b8]">{{ tripDateShort(trip) }}</p>
              </div>
              <div class="flex min-w-0 flex-1 items-start gap-1.5">
                <span
                  class="mt-0.5 shrink-0 rounded-md bg-emerald-500/20 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-300"
                >
                  {{ tripTypeBadgeText(trip) }}
                </span>
                <div class="min-w-0 flex-1 space-y-1">
                  <div class="flex items-start gap-2">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-emerald-400" />
                    <p class="min-w-0 text-[13px] font-medium leading-snug text-white">
                      {{ tripOrigin(trip) }}
                    </p>
                  </div>
                  <div class="flex items-start gap-2">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[#3b82f6]" />
                    <p class="min-w-0 text-[13px] font-medium leading-snug text-white">
                      {{ tripDestination(trip) }}
                    </p>
                  </div>
                </div>
              </div>
              <div class="shrink-0 text-right">
                <span
                  class="inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold"
                  :class="tripStatusBadgeClass(trip)"
                >
                  {{ tripStatusLabel(trip) }}
                </span>
                <p class="mt-1 text-[11px] text-[#94a3b8]">#{{ trip.id }}</p>
              </div>
            </RouterLink>
          </li>
        </ul>
      </section>

      <!-- S6: Actions -->
      <section class="rounded-[20px] border border-white/[0.06] bg-[#0f2318] p-4 sm:p-5">
        <div class="flex flex-col gap-3">
          <a
            :href="passwordChangeUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-[10px] border border-[#2dd4a0] bg-transparent px-4 text-sm font-semibold text-[#2dd4a0] transition hover:bg-[#2dd4a0]/10 active:scale-[0.99]"
          >
            <LockClosedIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
            {{ t('driver_account.change_password') }}
          </a>
          <button
            type="button"
            class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-[10px] border border-[#f43f5e] bg-transparent px-4 text-sm font-semibold text-[#f43f5e] transition hover:bg-[#f43f5e]/10 active:scale-[0.99]"
            @click="logoutConfirmOpen = true"
          >
            <ArrowRightOnRectangleIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
            {{ t('driver_account.logout') }}
          </button>
        </div>
      </section>
    </div>

    <!-- Logout confirm (dark) -->
    <Teleport to="body">
      <div
        v-if="logoutConfirmOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center bg-black/60 p-4 backdrop-blur-[2px] sm:items-center"
        role="presentation"
        @click.self="logoutConfirmOpen = false"
      >
        <div
          role="dialog"
          aria-modal="true"
          aria-labelledby="driver-logout-title"
          class="w-full max-w-md rounded-[20px] border border-white/[0.06] bg-[#0f2318] p-5 text-white"
        >
          <h2 id="driver-logout-title" class="text-base font-semibold">
            {{ t('app.logout_confirm_title') }}
          </h2>
          <p class="mt-2 text-sm leading-relaxed text-[#94a3b8]">
            {{ t('app.logout_confirm_body') }}
          </p>
          <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3">
            <button
              type="button"
              class="inline-flex w-full items-center justify-center rounded-[10px] border border-white/[0.08] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-white/5 sm:w-auto"
              @click="logoutConfirmOpen = false"
            >
              {{ t('app.cancel') }}
            </button>
            <button
              type="button"
              class="inline-flex w-full items-center justify-center rounded-[10px] bg-[#f43f5e] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#e11d48] sm:w-auto"
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
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowRightOnRectangleIcon,
  BuildingOffice2Icon,
  CalendarDaysIcon,
  DocumentTextIcon,
  IdentificationIcon,
  LockClosedIcon,
  MapPinIcon,
  UserIcon,
} from '@heroicons/vue/24/outline'
import { getDriverSummary } from '../../api/driver'
import { listTripsAll } from '../../api/trips'
import NotificationBell from '../../components/notifications/NotificationBell.vue'
import { useAuthStore } from '../../store'
import {
  formatDepartForTrip,
  tripDestination,
  tripOrigin,
  tripTypeBadgeText,
} from '../../composables/useDriverTripDisplay'

const { t, locale } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const loading = ref(true)
const errorMsg = ref('')
const rawListItems = ref([])
const myDriverId = ref(null)
const summary = ref(null)
const logoutConfirmOpen = ref(false)

const passwordChangeUrl = 'https://myaccount.google.com/signinoptions/password'

const STAT_SVG = {
  completed: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>`,
  inProgress: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-12.75a.75.75 0 0 0-1.5 0v4.59l-1.95 2.1a.75.75 0 1 0 1.1 1.02l2.25-2.43a.75.75 0 0 0 .1-.38v-5Z" clip-rule="evenodd" /></svg>`,
  cancelled: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" /></svg>`,
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
  inProgress: rawTrips.value.filter((x) => tripStatusNorm(x) === 'in_progress').length,
  cancelled: rawTrips.value.filter((x) => tripStatusNorm(x) === 'cancelled').length,
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
    key: 'cancelled',
    value: stats.value.cancelled,
    label: t('driver_home.stats_cancelled'),
    iconSvg: STAT_SVG.cancelled,
  },
])

function formatDisplayDate(val) {
  if (val == null || val === '') return '—'
  const s = String(val)
  const d = s.length >= 10 ? s.slice(0, 10) : s
  const parts = d.split('-')
  if (parts.length === 3) {
    return `${parts[2]}/${parts[1]}/${parts[0]}`
  }
  return s
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

  const uid =
    ci && ci.user_id != null && String(ci.user_id).trim() !== ''
      ? String(ci.user_id)
      : u?.id != null
        ? String(u.id)
        : '—'

  const uidLabel =
    ci && ci.user_id != null && String(ci.user_id).trim() !== ''
      ? t('driver_account.label_user_id')
      : t('driver_account.account_id_fallback')

  const created = ci?.created_at ? formatDisplayDate(ci.created_at) : '—'
  const hq = isNonEmpty(ci.headquarter_name) ? String(ci.headquarter_name) : '—'
  const wp = isNonEmpty(ci.working_place) ? String(ci.working_place) : '—'

  return [
    { k: 'code', label: t('driver_account.label_employee_code'), value: code, icon: IdentificationIcon },
    { k: 'uid', label: uidLabel, value: uid, icon: UserIcon },
    { k: 'created', label: t('driver_account.label_created'), value: created, icon: CalendarDaysIcon },
    { k: 'hq', label: t('driver_account.label_hq'), value: hq, icon: BuildingOffice2Icon },
    { k: 'wp', label: t('driver_account.label_workplace'), value: wp, icon: MapPinIcon },
  ]
})

/** @returns {'ok'|'soon'|'missing'|'expired'} */
function expiryBucket(ymdStr) {
  if (!ymdStr) return 'missing'
  const d = new Date(`${String(ymdStr).slice(0, 10)}T12:00:00`)
  if (Number.isNaN(d.getTime())) return 'missing'
  const today = new Date()
  today.setHours(12, 0, 0, 0)
  if (d < today) return 'expired'
  const days = Math.ceil((d.getTime() - today.getTime()) / 86400000)
  if (days <= 90) return 'soon'
  return 'ok'
}

function formatMonthYearFromYmd(ymdStr) {
  if (!ymdStr) return ''
  const p = String(ymdStr).slice(0, 10).split('-')
  if (p.length !== 3) return ''
  return `${p[1]}/${p[0]}`
}

const documentRows = computed(() => {
  const ci = user.value?.cms_user_info || {}
  const drv = summary.value?.driver || null
  const vehicle = summary.value?.vehicle || null

  const idNum =
    (isNonEmpty(ci.identity) ? String(ci.identity).trim() : null) ||
    (drv?.national_id ? String(drv.national_id).trim() : '')
  const hasCccd = !!idNum

  let cccdBadgeKey = 'missing'
  let cccdSubtitle = t('driver_account.expiry_na')
  if (hasCccd) {
    cccdBadgeKey = 'valid'
    if (ci.identity_date) {
      cccdSubtitle = t('driver_account.issue_date', { date: formatDisplayDate(ci.identity_date) })
    } else {
      cccdSubtitle = t('driver_account.cccd_on_file')
    }
  }

  const licClass = (drv?.license_class && String(drv.license_class).trim()) || 'B2'
  const licExp = drv?.license_expires_at || null
  const licBucket = expiryBucket(licExp)
  let licStatusKey = 'missing'
  let licSubtitle = t('driver_account.expiry_na')
  if (licExp) {
    licSubtitle = t('driver_account.expiry_label', { date: formatMonthYearFromYmd(licExp) })
    if (licBucket === 'ok') licStatusKey = 'valid'
    else if (licBucket === 'soon') licStatusKey = 'soon'
    else if (licBucket === 'expired') licStatusKey = 'expired'
  }

  const inspExp = vehicle?.inspection_expires_at || null
  const inspBucket = expiryBucket(inspExp)
  let inspStatusKey = 'missing'
  let inspSubtitle = t('driver_account.expiry_na')
  if (inspExp) {
    inspSubtitle = t('driver_account.expiry_label', { date: formatMonthYearFromYmd(inspExp) })
    if (inspBucket === 'ok') inspStatusKey = 'valid'
    else if (inspBucket === 'soon') inspStatusKey = 'soon'
    else if (inspBucket === 'expired') inspStatusKey = 'expired'
  }

  const badge = (key) => {
    if (key === 'valid')
      return {
        badgeText: t('driver_account.status_valid'),
        badgeClass: 'bg-[#2dd4a0]/20 text-[#2dd4a0]',
      }
    if (key === 'soon')
      return {
        badgeText: t('driver_account.status_soon'),
        badgeClass: 'bg-amber-500/20 text-amber-200',
      }
    if (key === 'expired')
      return {
        badgeText: t('driver_account.status_expired'),
        badgeClass: 'bg-[#f43f5e]/20 text-[#f43f5e]',
      }
    return {
      badgeText: t('driver_account.status_missing'),
      badgeClass:
        'border border-dashed border-[#64748b]/50 bg-slate-600/20 text-[#94a3b8]',
    }
  }

  return [
    {
      id: 'cccd',
      title: t('driver_account.doc_cccd'),
      subtitle: cccdSubtitle,
      ...badge(cccdBadgeKey === 'valid' ? 'valid' : 'missing'),
    },
    {
      id: 'license',
      title: t('driver_account.doc_license_class', { rank: licClass }),
      subtitle: licSubtitle,
      ...badge(
        licStatusKey === 'valid'
          ? 'valid'
          : licStatusKey === 'soon'
            ? 'soon'
            : licStatusKey === 'expired'
              ? 'expired'
              : 'missing',
      ),
    },
    {
      id: 'insp',
      title: t('driver_account.doc_inspection'),
      subtitle: inspSubtitle,
      ...badge(
        inspStatusKey === 'valid'
          ? 'valid'
          : inspStatusKey === 'soon'
            ? 'soon'
            : inspStatusKey === 'expired'
              ? 'expired'
              : 'missing',
      ),
    },
  ]
})

const recentTrips = computed(() => {
  return rawTrips.value
    .slice()
    .sort((a, b) => (new Date(b.depart_at).getTime() || 0) - (new Date(a.depart_at).getTime() || 0))
    .slice(0, 3)
})

const localeTag = computed(() => (locale.value === 'vi' ? 'vi' : 'en'))

function tripTime(trip) {
  return formatDepartForTrip(trip, localeTag.value).time
}

function tripDateShort(trip) {
  const line = formatDepartForTrip(trip, localeTag.value).dateLine
  if (!line) return ''
  const iso = trip?.depart_at || trip?.dispatch_request?.depart_at
  if (!iso) return line
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return line
  const loc = locale.value === 'vi' ? 'vi-VN' : 'en-US'
  return d.toLocaleDateString(loc, { day: '2-digit', month: '2-digit' })
}

function tripStatusLabel(trip) {
  const s = tripStatusNorm(trip)
  if (s === 'completed') return t('driver_home.calendar_status_done')
  if (s === 'cancelled') return t('driver_home.calendar_status_cancelled')
  if (s === 'in_progress') return t('driver_home.calendar_status_running')
  return t('driver_home.calendar_status_waiting')
}

function tripStatusBadgeClass(trip) {
  const s = tripStatusNorm(trip)
  if (s === 'completed') return 'bg-[#2dd4a0]/20 text-[#2dd4a0]'
  if (s === 'cancelled') return 'bg-[#f43f5e]/20 text-[#f43f5e]'
  if (s === 'in_progress') return 'bg-[#3b82f6]/20 text-[#3b82f6]'
  return 'bg-amber-500/20 text-amber-200'
}

function onCameraClick() {
  router.push('/profile')
}

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
    summary.value = sum
    myDriverId.value = sum?.driver?.id ?? null
    rawListItems.value = listRes?.items ?? []
  } catch {
    errorMsg.value = t('driver_home.load_error')
    summary.value = null
    myDriverId.value = null
    rawListItems.value = []
  } finally {
    loading.value = false
  }
}

async function confirmLogout() {
  logoutConfirmOpen.value = false
  await auth.logout()
  await router.push({ name: 'login' })
}

onMounted(async () => {
  if (!auth.user) {
    try {
      await auth.fetchMe()
    } catch {
      /* handled by guard */
    }
  }
  await fetchData()
})
</script>
