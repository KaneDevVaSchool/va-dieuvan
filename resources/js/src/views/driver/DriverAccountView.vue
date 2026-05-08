<template>
  <div
    class="min-h-full w-full bg-driver-bg pb-[calc(7rem+env(safe-area-inset-bottom))] text-driver-ink"
  >
    <div
      class="mx-auto w-full max-w-[390px] space-y-3 px-3 pt-[max(0.75rem,env(safe-area-inset-top))] sm:px-4"
    >
      <!-- Header chrome -->
      <div class="flex items-center justify-between gap-3 pt-1">
        <h1 class="text-2xl font-bold tracking-tight text-driver-ink md:text-[1.75rem]">
          {{ t('nav.bottom_driver_account') }}
        </h1>
        <NotificationBell />
      </div>

      <p
        v-if="errorMsg"
        class="rounded-2xl border border-amber-700/40 bg-amber-950/30 px-4 py-3 text-sm leading-relaxed text-amber-100/90"
      >
        {{ errorMsg }}
      </p>

      <!-- S1: Header card -->
      <section
        class="rounded-[20px] border border-white/[0.06] bg-driver-card px-4 pb-5 pt-6 sm:px-5"
      >
        <div class="relative mx-auto flex w-fit flex-col items-center">
          <!-- Inner circle uses overflow-hidden; FAB sits outside so it is not clipped -->
          <div class="relative h-20 w-20 shrink-0">
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
                class="flex h-full w-full items-center justify-center text-xl font-bold text-driver-accent"
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
                  class="h-7 w-7 animate-spin text-driver-accent"
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
              class="absolute -bottom-px -right-px z-10 flex h-[26px] w-[26px] items-center justify-center rounded-full bg-driver-accent text-driver-bg shadow-sm shadow-black/25 ring-2 ring-driver-card transition hover:brightness-105 active:scale-95 disabled:pointer-events-none disabled:opacity-60"
              :disabled="avatarUploading"
              :aria-label="t('driver_account.update_photo_a11y')"
              @click="openAvatarPicker"
            >
              <ArrowUpTrayIcon class="h-[14px] w-[14px]" aria-hidden="true" />
            </button>
          </div>
        </div>

        <p class="mt-4 text-center text-[22px] font-bold leading-tight text-driver-ink">
          {{ user?.name || '—' }}
        </p>

        <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
          <span
            v-if="employeeCodeDisplay"
            class="rounded-full border border-driver-accent px-3 py-1 text-sm font-medium leading-none text-driver-accent"
          >
            {{ employeeCodeDisplay }}
          </span>
          <span
            class="inline-flex items-center gap-1.5 rounded-full border border-white/[0.08] bg-driver-bg/60 px-3 py-1 text-sm font-medium text-driver-ink"
          >
            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-400" aria-hidden="true" />
            {{ t('driver_account.active_status') }}
          </span>
        </div>

        <div class="my-5 h-px w-full bg-white/[0.06]" aria-hidden="true" />

        <div class="grid grid-cols-3 gap-2 text-center">
          <div v-for="col in headerCols" :key="col.k" class="min-w-0 px-0.5">
            <p
              class="text-[11px] font-semibold uppercase leading-tight tracking-[0.06em] text-driver-accent-bright/90 sm:text-xs"
            >
              {{ col.label }}
            </p>
            <p class="mt-2 text-base font-bold leading-snug text-driver-ink tabular-nums">
              {{ col.value }}
            </p>
          </div>
        </div>
      </section>

      <!-- S2: Stats -->
      <section class="rounded-[20px] border border-white/[0.06] bg-driver-card px-2 py-4 sm:px-3">
        <div v-if="loading" class="grid grid-cols-3 gap-0">
          <div v-for="n in 3" :key="n" class="flex flex-col items-center gap-2 py-2">
            <div class="h-10 w-10 animate-pulse rounded-full bg-driver-accent/10" />
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
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-driver-accent/15 text-driver-accent [&_svg]:h-6 [&_svg]:w-6"
              v-html="cell.iconSvg"
            />
            <span class="text-[32px] font-bold tabular-nums leading-none text-driver-ink sm:text-4xl">
              {{ cell.value }}
            </span>
            <span class="text-sm leading-snug text-driver-muted">
              {{ cell.label }}
            </span>
          </div>
        </div>
      </section>

      <!-- S3: Work info -->
      <section class="rounded-[20px] border border-white/[0.06] bg-driver-card p-4 sm:p-5">
        <div class="mb-4 flex items-center gap-2">
          <span class="h-2 w-2 shrink-0 rounded-full bg-driver-accent" />
          <h2 class="text-base font-semibold text-driver-ink">
            {{ t('driver_account.work_info_title') }}
          </h2>
        </div>
        <ul class="space-y-0 divide-y divide-white/[0.06]">
          <li
            v-for="row in workRows"
            :key="row.k"
            class="flex items-center gap-3 py-3.5 first:pt-0 last:pb-0"
          >
            <component :is="row.icon" class="h-6 w-6 shrink-0 text-driver-accent" aria-hidden="true" />
            <span class="min-w-0 flex-1 text-[15px] leading-snug text-driver-muted">{{ row.label }}</span>
            <span class="max-w-[58%] text-right text-[15px] font-bold leading-snug text-driver-ink truncate">{{
              row.value
            }}</span>
          </li>
        </ul>
      </section>

      <!-- S4: Documents -->
      <section class="rounded-[20px] border border-white/[0.06] bg-driver-card p-4 sm:p-5">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <span class="h-2 w-2 shrink-0 rounded-full bg-driver-accent" />
              <h2 class="text-base font-semibold text-driver-ink">
                {{ t('driver_account.documents_title') }}
              </h2>
            </div>
            <p class="mt-2 text-[15px] leading-relaxed text-driver-muted">
              {{ t('driver_account.contact_procurement_hint') }}
            </p>
          </div>
          <a
            v-if="procurementTelHref"
            :href="procurementTelHref"
            class="inline-flex min-h-[52px] shrink-0 items-center justify-center gap-2 rounded-xl bg-driver-accent px-5 text-base font-bold text-driver-bg shadow-lg shadow-black/20 transition hover:bg-driver-accent-bright active:scale-[0.99] sm:mt-0"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
              <path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 013.5 2h1.148a1.5 1.5 0 011.465 1.175l.716 3.223a1.5 1.5 0 01-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 006.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 011.767-1.052l3.223.716A1.5 1.5 0 0118 16.352V17.5a1.5 1.5 0 01-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 012.43 8.326 13.019 13.019 0 012 5V3.5z" clip-rule="evenodd" />
            </svg>
            {{ t('driver_account.contact_procurement_cta') }}
          </a>
          <p
            v-else
            class="text-[15px] leading-relaxed text-amber-200/90 sm:max-w-[16rem] sm:text-right"
          >
            {{ t('driver_account.procurement_phone_missing') }}
          </p>
        </div>
        <ul class="space-y-0 divide-y divide-white/[0.06]">
          <li v-for="doc in documentRows" :key="doc.id" class="flex gap-3 py-3.5 first:pt-0 last:pb-0">
            <DocumentTextIcon class="h-6 w-6 shrink-0 text-driver-accent" aria-hidden="true" />
            <div class="min-w-0 flex-1">
              <p class="text-[15px] font-bold leading-snug text-driver-ink">{{ doc.title }}</p>
              <p class="mt-1 text-[13px] leading-relaxed text-driver-muted">{{ doc.subtitle }}</p>
            </div>
            <span
              class="h-fit shrink-0 self-center rounded-full px-2.5 py-1 text-[12px] font-semibold leading-tight"
              :class="doc.badgeClass"
            >
              {{ doc.badgeText }}
            </span>
          </li>
        </ul>
      </section>

      <!-- S5: Recent trips -->
      <section class="rounded-[20px] border border-white/[0.06] bg-driver-card px-4 py-5 sm:px-6 sm:py-6">
        <div class="mb-5 flex items-start justify-between gap-3">
          <div class="flex items-center gap-2.5">
            <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-driver-accent" />
            <h2 class="text-lg font-bold text-driver-ink sm:text-xl">
              {{ t('driver_account.recent_trips_title') }}
            </h2>
          </div>
          <RouterLink
            to="/driver/schedule"
            class="shrink-0 text-lg font-bold text-driver-accent transition hover:text-driver-accent-bright sm:text-xl"
          >
            {{ t('driver_account.see_all') }} ›
          </RouterLink>
        </div>
        <p
          v-if="!loading && recentTrips.length === 0"
          class="py-8 text-center text-lg text-driver-muted"
        >
          {{ t('driver_account.empty_trips') }}
        </p>
        <ul v-else class="space-y-0">
          <li
            v-for="(trip, idx) in recentTrips"
            :key="trip.id"
            class="border-b border-white/[0.06] py-4 last:border-b-0 last:pb-0"
            :class="idx === 0 ? 'pt-0' : ''"
          >
            <RouterLink
              :to="`/driver/trips/${trip.id}`"
              class="flex gap-3 text-left transition active:opacity-90 sm:gap-4"
            >
              <div class="w-[68px] shrink-0 sm:w-[76px]">
                <p class="text-lg font-bold tabular-nums leading-tight text-driver-ink sm:text-xl">{{ tripTime(trip) }}</p>
                <p class="mt-1.5 text-sm text-driver-muted sm:text-base">{{ tripDateShort(trip) }}</p>
              </div>
              <div class="flex min-w-0 flex-1 items-start gap-2">
                <span
                  class="mt-1 shrink-0 rounded-lg bg-emerald-500/20 px-2 py-1 text-xs font-bold uppercase tracking-wide text-emerald-300 sm:text-sm"
                >
                  {{ tripTypeBadgeText(trip) }}
                </span>
                <div class="min-w-0 flex-1 space-y-2">
                  <div class="flex items-start gap-2.5">
                    <span class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-400" />
                    <p class="min-w-0 text-base font-semibold leading-snug text-driver-ink sm:text-lg">
                      {{ tripOrigin(trip) }}
                    </p>
                  </div>
                  <div class="flex items-start gap-2.5">
                    <span class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full bg-[#3b82f6]" />
                    <p class="min-w-0 text-base font-semibold leading-snug text-driver-ink sm:text-lg">
                      {{ tripDestination(trip) }}
                    </p>
                  </div>
                </div>
              </div>
              <div class="shrink-0 text-right">
                <span
                  class="inline-block rounded-full px-3 py-1.5 text-sm font-bold sm:text-base"
                  :class="tripStatusBadgeClass(trip)"
                >
                  {{ tripStatusLabel(trip) }}
                </span>
                <p class="mt-2 text-sm text-driver-muted tabular-nums sm:text-base">#{{ trip.id }}</p>
              </div>
            </RouterLink>
          </li>
        </ul>
      </section>

      <!-- Đăng xuất -->
      <section class="rounded-[20px] border border-white/[0.06] bg-driver-card p-4 sm:p-5">
        <button
          type="button"
          class="inline-flex min-h-[54px] w-full items-center justify-center gap-2 rounded-xl border-2 border-[#f43f5e] bg-transparent px-4 text-base font-bold text-[#f43f5e] transition hover:bg-[#f43f5e]/12 active:scale-[0.99]"
          @click="logoutConfirmOpen = true"
        >
          <ArrowRightOnRectangleIcon class="h-6 w-6 shrink-0" aria-hidden="true" />
          {{ t('driver_account.logout') }}
        </button>
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
          class="w-full max-w-md rounded-[20px] border border-white/[0.06] bg-driver-card p-5 text-driver-ink"
        >
          <h2 id="driver-logout-title" class="text-lg font-bold sm:text-xl">
            {{ t('app.logout_confirm_title') }}
          </h2>
          <p class="mt-3 text-base leading-relaxed text-driver-muted">
            {{ t('app.logout_confirm_body') }}
          </p>
          <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end sm:gap-3">
            <button
              type="button"
              class="inline-flex w-full min-h-[50px] items-center justify-center rounded-xl border border-white/[0.12] px-4 text-base font-semibold text-driver-ink transition hover:bg-white/5 sm:w-auto"
              @click="logoutConfirmOpen = false"
            >
              {{ t('app.cancel') }}
            </button>
            <button
              type="button"
              class="inline-flex w-full min-h-[50px] items-center justify-center rounded-xl bg-[#f43f5e] px-4 text-base font-bold text-driver-ink transition hover:bg-[#e11d48] sm:w-auto"
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
  ArrowUpTrayIcon,
  BuildingOffice2Icon,
  CalendarDaysIcon,
  DocumentTextIcon,
  IdentificationIcon,
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
const avatarFileInput = ref(null)
const avatarUploading = ref(false)

const procurementTelHref = computed(() => {
  const raw = import.meta.env.VITE_PROCUREMENT_PHONE
  if (raw == null || String(raw).trim() === '') return null
  const compact = String(raw).replace(/[\s()-]/g, '')
  if (!compact) return null
  return `tel:${compact}`
})

function contactProcurement() {
  const h = procurementTelHref.value
  if (h) {
    window.location.href = h
  }
}

function openAvatarPicker() {
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

  const licClass = (drv?.license_class && String(drv.license_class).trim()) || 'GPLX'
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
        badgeClass: 'bg-driver-accent/20 text-driver-accent',
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
        'border border-dashed border-[#64748b]/50 bg-slate-600/20 text-driver-muted',
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
  if (s === 'completed') return 'bg-driver-accent/20 text-driver-accent'
  if (s === 'cancelled') return 'bg-[#f43f5e]/20 text-[#f43f5e]'
  if (s === 'in_progress') return 'bg-[#3b82f6]/20 text-[#3b82f6]'
  return 'bg-amber-500/20 text-amber-200'
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
