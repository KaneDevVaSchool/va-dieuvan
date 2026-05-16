<template>
  <div class="portal-page min-h-dvh bg-slate-100 text-slate-900">
    <!-- Header: safe-area top, sticky -->
    <header
      class="sticky top-0 z-10 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur
             supports-[padding:max(0px)]:pt-[env(safe-area-inset-top)]
             supports-[padding:max(0px)]:pl-[env(safe-area-inset-left)]
             supports-[padding:max(0px)]:pr-[env(safe-area-inset-right)]"
    >
      <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
        <div class="flex min-w-0 items-center gap-3">
          <img
            :src="LOGO_PWA_URL"
            alt="VA Dispatch"
            width="48"
            height="48"
            class="h-10 w-10 shrink-0 object-contain"
            decoding="async"
          />
          <div class="min-w-0">
            <h1 class="truncate text-base font-semibold text-slate-900 sm:text-lg">
              {{ t('portal.title') }}
            </h1>
            <p class="truncate text-xs text-slate-500 sm:text-sm">
              {{ t('portal.subtitle') }}
            </p>
            <!-- Tên user luôn hiển thị, kể cả mobile -->
            <p v-if="auth.user" class="mt-0.5 truncate text-xs text-slate-400">
              {{ auth.user.name }}
            </p>
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 disabled:opacity-50"
            :disabled="submitting"
            @click="onLogout"
          >
            {{ t('portal.logout') }}
          </button>
        </div>
      </div>
    </header>

    <!-- Main: safe-area left/right -->
    <main
      class="mx-auto max-w-4xl px-4 py-6 sm:px-6 sm:py-8
             supports-[padding:max(0px)]:pl-[max(1rem,env(safe-area-inset-left))]
             supports-[padding:max(0px)]:pr-[max(1rem,env(safe-area-inset-right))]"
    >
      <!-- Màn thành công -->
      <div v-if="success" class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm">
        <div class="flex items-start gap-4">
          <CheckCircleIcon class="h-8 w-8 shrink-0 text-emerald-600" />
          <div class="min-w-0 flex-1 space-y-2">
            <h2 class="text-lg font-semibold text-slate-900">{{ t('portal.success_title') }}</h2>
            <div class="flex flex-wrap items-center gap-2">
              <span
                class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800"
              >
                {{ t('portal.status_pending') }}
              </span>
            </div>
            <p class="text-3xl font-bold tracking-tight text-slate-900">#{{ success.id }}</p>
            <p class="text-sm text-slate-500">{{ t('portal.success_hint') }}</p>
            <div class="mt-4 flex flex-wrap gap-2">
              <button
                v-if="clipboardSupported"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50"
                @click="copyId"
              >
                <ClipboardDocumentIcon class="h-4 w-4 text-slate-500" />
                {{ copied ? t('portal.copied') : t('portal.copy_id') }}
              </button>
              <button
                type="button"
                class="rounded-lg bg-va-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-va-900"
                @click="resetAnother"
              >
                {{ t('portal.another') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Wizard form -->
      <div v-else class="space-y-6 pb-24 md:pb-0">
        <!-- Stepper -->
        <nav
          class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm"
          :aria-label="t('portal.steps_nav')"
        >
          <ol class="flex gap-1 sm:gap-2">
            <li
              v-for="(s, i) in stepLabels"
              :key="s.key"
              class="flex min-w-0 flex-1 items-center gap-1.5 rounded-lg px-2 py-2 text-xs font-medium sm:gap-2 sm:text-sm"
              :class="
                step === i
                  ? 'bg-slate-50 text-slate-900 ring-1 ring-va-800/25'
                  : step > i
                    ? 'text-emerald-800'
                    : 'text-slate-400'
              "
            >
              <span
                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                :class="
                  step === i ? 'bg-va-800 text-white' : step > i ? 'bg-emerald-600 text-white' : 'bg-slate-200'
                "
                >{{ step > i ? '✓' : i + 1 }}</span
              >
              <span class="truncate">{{ s.label }}</span>
            </li>
          </ol>
        </nav>

        <!-- Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
          <!-- Error banner -->
          <div
            v-if="formError"
            ref="errorBannerRef"
            role="alert"
            tabindex="-1"
            class="mb-4 rounded-lg bg-rose-50 px-3 py-2.5 text-sm font-medium text-rose-800 outline-none"
          >
            {{ formError }}
          </div>

          <!-- Step 0: Loại chuyến -->
          <div v-show="step === 0" class="space-y-4">
            <p class="text-sm font-medium text-slate-700">{{ t('portal.pick_type_hint') }}</p>
            <div class="grid gap-3 sm:grid-cols-2">
              <button
                v-for="tt in tripTypes"
                :key="tt"
                type="button"
                class="flex items-start gap-3 rounded-xl border px-4 py-3 text-left transition-all duration-150"
                :class="
                  tripType === tt
                    ? 'border-va-800 bg-va-800/5 ring-2 ring-va-800/30'
                    : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                "
                @click="onTripTypeClick(tt)"
              >
                <component
                  :is="tripTypeIcon(tt)"
                  class="mt-0.5 h-5 w-5 shrink-0"
                  :class="tripType === tt ? 'text-va-800' : 'text-slate-400'"
                />
                <span>
                  <span class="block font-semibold text-slate-900">
                    {{ t(`dispatch_wizard.trip_type.${tt}.label`) }}
                  </span>
                  <span class="mt-0.5 block text-xs text-slate-500">
                    {{ t(`dispatch_wizard.trip_type.${tt}.hint`) }}
                  </span>
                </span>
              </button>
            </div>
            <p class="text-xs text-slate-400">{{ t('portal.double_tap_hint') }}</p>
          </div>

          <!-- Step 1: Thông tin chuyến -->
          <div v-show="step === 1" class="space-y-4">
            <div class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">
              <span class="font-medium text-slate-800">{{ t('portal.requester') }}:</span>
              {{ auth.user?.name }} · {{ auth.user?.email }}
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('portal.origin') }}</span>
                <input
                  v-model.trim="origin"
                  type="text"
                  autocomplete="off"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800"
                />
              </label>
              <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('portal.destination') }}</span>
                <input
                  v-model.trim="destination"
                  type="text"
                  autocomplete="off"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800"
                />
              </label>
            </div>

            <label class="block">
              <span class="mb-1 block text-xs font-medium text-slate-600">
                {{ t('portal.depart_at') }}
                <span class="text-rose-600">*</span>
              </span>
              <input
                v-model="departAtLocal"
                type="datetime-local"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800"
              />
              <p v-if="departPreview" class="mt-1 text-xs text-slate-500">{{ departPreview }}</p>
            </label>

            <label class="block">
              <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('portal.arrive_by') }}</span>
              <input
                v-model="arriveByLocal"
                type="datetime-local"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800"
              />
              <p v-if="arriveByPreview" class="mt-1 text-xs text-slate-500">{{ arriveByPreview }}</p>
            </label>

            <label v-if="showPassengerCount" class="block">
              <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('portal.passenger_count') }}</span>
              <input
                v-model.number="passengerCount"
                type="number"
                min="1"
                max="999"
                class="w-full max-w-[12rem] rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800"
              />
            </label>

            <label class="block">
              <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('portal.notes') }}</span>
              <textarea
                v-model.trim="notes"
                rows="3"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800"
              />
            </label>

            <label class="flex cursor-pointer items-start gap-2">
              <input v-model="isUrgent" type="checkbox" class="mt-1 rounded border-slate-300 text-va-800" />
              <span class="text-sm text-slate-800">{{ t('portal.urgent') }}</span>
            </label>
            <label v-if="isUrgent" class="block">
              <span class="mb-1 block text-xs font-medium text-slate-600">
                {{ t('portal.urgent_reason') }}
                <span class="text-rose-600">*</span>
              </span>
              <input
                v-model.trim="urgentReason"
                type="text"
                maxlength="500"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800"
              />
            </label>
          </div>

          <!-- Step 2: Xác nhận -->
          <div v-show="step === 2" class="space-y-3 text-sm">
            <p class="text-sm font-medium text-slate-700">{{ t('portal.confirm_hint') }}</p>
            <dl class="divide-y divide-slate-100 rounded-xl border border-slate-100 overflow-hidden">
              <div class="flex items-center justify-between gap-4 px-3 py-2.5">
                <dt class="text-slate-500">{{ t('dispatch_wizard.steps.type') }}</dt>
                <dd class="flex items-center gap-1.5 font-medium text-slate-900">
                  <component :is="tripTypeIcon(tripType)" class="h-4 w-4 text-slate-400" />
                  {{ t(`dispatch_wizard.trip_short.${tripType}`) }}
                </dd>
              </div>
              <div class="flex justify-between gap-4 px-3 py-2.5">
                <dt class="text-slate-500">{{ t('portal.origin') }}</dt>
                <dd class="text-right font-medium text-slate-900">{{ origin || '—' }}</dd>
              </div>
              <div class="flex justify-between gap-4 px-3 py-2.5">
                <dt class="text-slate-500">{{ t('portal.destination') }}</dt>
                <dd class="text-right font-medium text-slate-900">{{ destination || '—' }}</dd>
              </div>
              <div class="flex justify-between gap-4 px-3 py-2.5">
                <dt class="text-slate-500">{{ t('portal.depart_at') }}</dt>
                <dd class="text-right font-medium text-slate-900">{{ departPreview || departAtLocal || '—' }}</dd>
              </div>
              <div v-if="arriveByLocal" class="flex justify-between gap-4 px-3 py-2.5">
                <dt class="text-slate-500">{{ t('portal.arrive_by') }}</dt>
                <dd class="text-right font-medium text-slate-900">{{ arriveByPreview || arriveByLocal }}</dd>
              </div>
              <div v-if="showPassengerCount && passengerCount" class="flex justify-between gap-4 px-3 py-2.5">
                <dt class="text-slate-500">{{ t('portal.passenger_count') }}</dt>
                <dd class="font-medium text-slate-900">{{ passengerCount }}</dd>
              </div>
              <div v-if="notes" class="flex justify-between gap-4 px-3 py-2.5">
                <dt class="text-slate-500">{{ t('portal.notes') }}</dt>
                <dd class="max-w-[60%] text-right font-medium text-slate-900">{{ notes }}</dd>
              </div>
              <div v-if="isUrgent" class="flex justify-between gap-4 px-3 py-2.5">
                <dt class="text-slate-500">{{ t('portal.urgent') }}</dt>
                <dd class="text-right font-medium text-rose-700">{{ urgentReason || '—' }}</dd>
              </div>
            </dl>
          </div>

          <!-- Footer nút — chỉ hiện trên md+ -->
          <div class="mt-6 hidden items-center justify-between gap-2 border-t border-slate-100 pt-4 md:flex">
            <button
              v-if="step > 0"
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50 disabled:opacity-50"
              :disabled="submitting"
              @click="step -= 1"
            >
              {{ t('portal.back') }}
            </button>
            <span v-else />

            <button
              v-if="step < 2"
              type="button"
              class="inline-flex items-center gap-2 rounded-lg bg-va-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-va-900 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="submitting || !canGoNext"
              @click="nextStep"
            >
              {{ t('portal.next') }}
              <ArrowRightIcon class="h-4 w-4" />
            </button>
            <button
              v-else
              type="button"
              class="inline-flex items-center gap-2 rounded-lg bg-va-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-va-900 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="submitting"
              @click="submit"
            >
              <span
                v-if="submitting"
                class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"
              />
              {{ t('portal.submit') }}
            </button>
          </div>
        </div>
      </div>
    </main>

    <!-- Sticky footer nút chính — chỉ mobile (md:hidden) -->
    <div
      v-if="!success"
      class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-3 shadow-[0_-2px_8px_rgba(0,0,0,0.06)] backdrop-blur md:hidden
             supports-[padding:max(0px)]:pl-[max(1rem,env(safe-area-inset-left))]
             supports-[padding:max(0px)]:pr-[max(1rem,env(safe-area-inset-right))]"
    >
      <div class="flex items-center justify-between gap-2">
        <button
          v-if="step > 0"
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 disabled:opacity-50"
          :disabled="submitting"
          @click="step -= 1"
        >
          {{ t('portal.back') }}
        </button>
        <span v-else />

        <button
          v-if="step < 2"
          type="button"
          class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-va-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-va-900 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="submitting || !canGoNext"
          @click="nextStep"
        >
          {{ t('portal.next') }}
          <ArrowRightIcon class="h-4 w-4" />
        </button>
        <button
          v-else
          type="button"
          class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-va-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-va-900 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="submitting"
          @click="submit"
        >
          <span
            v-if="submitting"
            class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"
          />
          {{ t('portal.submit') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowRightIcon,
  BriefcaseIcon,
  CheckCircleIcon,
  ClipboardDocumentIcon,
  CubeIcon,
  HomeIcon,
  MapPinIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../store'
import { createPortalDispatchRequest } from '../../api/requests'
import { formatApiError } from '../../api/http'
import { confirmAction } from '../../composables/useConfirm'

/** `public/images/logo/logo-2.png` — bind via JS so Rollup/Vite không resolve như URL tuyệt đối trong template. */
const LOGO_PWA_URL = '/images/logo/logo-2.png'

const TRIP_TYPES = ['door_to_door', 'point_to_point', 'business', 'cargo']

const TRIP_TYPE_ICONS = {
  door_to_door: HomeIcon,
  point_to_point: MapPinIcon,
  business: BriefcaseIcon,
  cargo: CubeIcon,
}

function tripTypeIcon(tt) {
  return TRIP_TYPE_ICONS[tt] ?? MapPinIcon
}

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const tripTypes = TRIP_TYPES
const step = ref(0)
const tripType = ref('point_to_point')
const origin = ref('')
const destination = ref('')
const departAtLocal = ref('')
const arriveByLocal = ref('')
const passengerCount = ref(null)
const notes = ref('')
const isUrgent = ref(false)
const urgentReason = ref('')
const formError = ref('')
const submitting = ref(false)
const success = ref(null)
const copied = ref(false)
const errorBannerRef = ref(null)

const clipboardSupported = typeof navigator !== 'undefined' && !!navigator.clipboard

const stepLabels = computed(() => [
  { key: 'type', label: t('portal.step_type') },
  { key: 'info', label: t('portal.step_info') },
  { key: 'confirm', label: t('portal.step_confirm') },
])

const showPassengerCount = computed(() => tripType.value !== 'cargo')

const canGoNext = computed(() => {
  if (step.value === 0) return TRIP_TYPES.includes(tripType.value)
  if (step.value === 1) return !!departAtLocal.value.trim()
  return true
})

const departPreview = computed(() => formatDatetimePreview(departAtLocal.value))
const arriveByPreview = computed(() => formatDatetimePreview(arriveByLocal.value))

function formatDatetimePreview(v) {
  if (!v || typeof v !== 'string') return ''
  try {
    const d = new Date(v)
    if (Number.isNaN(d.getTime())) return ''
    return d.toLocaleString('vi-VN', {
      weekday: 'short',
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return ''
  }
}

function showError(msg) {
  formError.value = msg
  nextTick(() => errorBannerRef.value?.focus())
}

function onTripTypeClick(tt) {
  if (tripType.value === tt) {
    nextStep()
    return
  }
  tripType.value = tt
}

function toIsoMaybe(v) {
  if (!v || typeof v !== 'string') return null
  try {
    const d = new Date(v)
    if (Number.isNaN(d.getTime())) return null
    return d.toISOString()
  } catch {
    return null
  }
}

function nextStep() {
  formError.value = ''
  if (step.value === 0 && !TRIP_TYPES.includes(tripType.value)) {
    showError(t('portal.pick_type'))
    return
  }
  if (step.value === 1 && !departAtLocal.value.trim()) {
    showError(t('portal.need_datetime'))
    return
  }
  if (step.value === 1 && isUrgent.value && !urgentReason.value.trim()) {
    showError(t('dispatch_wizard.validate.urgent_reason'))
    return
  }
  step.value += 1
}

async function submit() {
  formError.value = ''
  const departIso = toIsoMaybe(departAtLocal.value)
  if (!departIso) {
    showError(t('portal.need_datetime'))
    return
  }
  const arriveIso = arriveByLocal.value.trim() ? toIsoMaybe(arriveByLocal.value) : null
  if (arriveIso && new Date(arriveIso) < new Date(departIso)) {
    showError(t('portal.arrive_after_depart'))
    return
  }

  submitting.value = true
  try {
    const payload = {
      trip_type: tripType.value,
      source_channel: 'portal',
      origin: origin.value.trim() || undefined,
      destination: destination.value.trim() || undefined,
      depart_at: departIso,
      arrive_by: arriveIso || undefined,
      passenger_count:
        showPassengerCount.value && passengerCount.value != null && passengerCount.value >= 1
          ? Math.round(Number(passengerCount.value))
          : undefined,
      notes: notes.value.trim() || undefined,
      is_urgent: isUrgent.value,
      urgent_reason: isUrgent.value ? urgentReason.value.trim() || undefined : undefined,
    }
    Object.keys(payload).forEach((k) => {
      if (payload[k] === undefined) delete payload[k]
    })
    const data = await createPortalDispatchRequest(payload)
    success.value = data
  } catch (e) {
    showError(formatApiError(e, t('dispatch_wizard.validate.create_fail')))
  } finally {
    submitting.value = false
  }
}

async function copyId() {
  if (!success.value?.id || !navigator.clipboard) return
  try {
    await navigator.clipboard.writeText(String(success.value.id))
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch {
    /* ignore */
  }
}

function resetAnother() {
  success.value = null
  step.value = 0
  tripType.value = 'point_to_point'
  origin.value = ''
  destination.value = ''
  departAtLocal.value = ''
  arriveByLocal.value = ''
  passengerCount.value = null
  notes.value = ''
  isUrgent.value = false
  urgentReason.value = ''
  formError.value = ''
  copied.value = false
}

async function onLogout() {
  const dirty =
    step.value > 0 || !!origin.value || !!destination.value || !!departAtLocal.value
  if (dirty) {
    const ok = await confirmAction({
      title: t('portal.logout_confirm_title'),
      message: t('portal.logout_confirm_msg'),
      confirmLabel: t('portal.logout'),
      danger: true,
    })
    if (!ok) return
  }
  await auth.logout()
  router.replace({ name: 'login' })
}
</script>
