<template>
  <div class="text-slate-900">
    <div
      class="relative overflow-hidden border-b border-slate-200/60 bg-gradient-to-br from-slate-900 via-va-900 to-va-800 px-4 py-9 text-white shadow-lg shadow-slate-900/20 sm:px-8 xl:mx-auto xl:max-w-4xl xl:rounded-b-[2rem] xl:border-x xl:border-slate-200/40 xl:shadow-xl xl:shadow-va-950/30"
    >
      <div class="pointer-events-none absolute -right-16 -top-24 h-56 w-56 rounded-full bg-white/10 blur-3xl" aria-hidden="true" />
      <div class="pointer-events-none absolute -bottom-20 left-1/4 h-40 w-72 rounded-full bg-teal-400/15 blur-3xl" aria-hidden="true" />
      <div class="relative mx-auto flex max-w-3xl flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
          <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/70">{{ t('portal.nav_title') }}</p>
          <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">{{ t('portal.create.page_title') }}</h1>
          <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/85">{{ t('portal.create.page_subtitle') }}</p>
        </div>
        <div class="hidden shrink-0 sm:flex">
          <span
            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/12 shadow-inner ring-2 ring-white/25 backdrop-blur-md"
            aria-hidden="true"
          >
            <PaperAirplaneIcon class="h-9 w-9 text-white" />
          </span>
        </div>
      </div>
    </div>

    <main
      class="mx-auto max-w-4xl px-4 py-8 sm:px-6
             supports-[padding:max(0px)]:pl-[max(1rem,env(safe-area-inset-left))]
             supports-[padding:max(0px)]:pr-[max(1rem,env(safe-area-inset-right))]
             pb-[calc(7rem+env(safe-area-inset-bottom))] md:pb-10"
      :aria-busy="submitting ? 'true' : 'false'"
    >
      <div class="mx-auto mt-[-2rem] flex max-w-3xl flex-col gap-6 lg:mt-[-2.75rem]">
        <PortalStepper :steps="stepLabels" :current="step" :steps-nav-label="t('portal.steps_nav')" />

        <div
          class="rounded-[1.75rem] border border-slate-200/90 bg-white/95 p-5 shadow-2xl shadow-slate-900/[0.08] ring-1 ring-slate-900/[0.04] backdrop-blur-sm sm:p-8 md:p-10"
        >
          <div
            v-if="formError"
            ref="errorBannerRef"
            role="alert"
            tabindex="-1"
            class="mb-5 rounded-2xl border border-rose-200/80 bg-gradient-to-r from-rose-50 to-white px-4 py-3 text-sm font-semibold text-rose-900 shadow-sm outline-none ring-1 ring-rose-100/80"
          >
            {{ formError }}
          </div>

          <Transition name="portal-step" mode="out-in">
            <div v-if="step === 0" key="portal-step-0" class="space-y-4">
              <PortalTripTypeGrid
                :trip-types="tripTypes"
                :trip-type="tripType"
                :hint="t('portal.pick_type_hint')"
                :double-tap-hint="t('portal.double_tap_hint')"
                @select="onTripTypeClick"
              />
              <p
                v-if="!canGoNext"
                role="status"
                class="rounded-xl border border-amber-200/90 bg-amber-50 px-3 py-2.5 text-xs font-semibold text-amber-950 shadow-sm ring-1 ring-amber-100/90"
              >
                {{ t('portal.pick_type_to_continue') }}
              </p>
            </div>
            <div v-else-if="step === 1" key="portal-step-1" class="space-y-5">
              <PortalStepInfo
                v-model="infoModel"
                :requester-display="requesterDisplay"
                :show-passenger-count="showPassengerCount"
              />
            </div>
            <PortalStepConfirm v-else key="portal-step-2" :hint="t('portal.confirm_hint')" :summary="confirmSummary" />
          </Transition>

          <!-- Desktop footer nút -->
          <div class="mt-8 hidden items-center justify-between gap-4 border-t border-slate-100/90 pt-7 md:flex">
            <button
              v-if="step > 0"
              type="button"
              class="min-h-[46px] rounded-xl border border-slate-200/95 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-900/[0.04] transition hover:bg-slate-50 disabled:opacity-50"
              :disabled="submitting"
              @click="step -= 1"
            >
              {{ t('portal.back') }}
            </button>
            <span v-else />

            <button
              v-if="step < 2"
              type="button"
              class="inline-flex min-h-[46px] items-center gap-2 rounded-xl bg-gradient-to-r from-va-800 to-va-900 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-va-950/30 ring-1 ring-white/10 transition hover:from-va-900 hover:to-va-950 disabled:cursor-not-allowed disabled:opacity-45"
              :disabled="submitting || !canGoNext"
              @click="nextStep"
            >
              {{ t('portal.next') }}
              <ArrowRightIcon class="h-5 w-5" aria-hidden="true" />
            </button>
            <button
              v-else
              type="button"
              class="inline-flex min-h-[46px] items-center gap-2 rounded-xl bg-gradient-to-r from-va-800 to-va-900 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-va-950/30 ring-1 ring-white/10 transition hover:from-va-900 hover:to-va-950 disabled:cursor-not-allowed disabled:opacity-45"
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

    <div
      class="fixed inset-x-0 bottom-0 z-40 flex justify-center pb-[calc(1rem+env(safe-area-inset-bottom))] pt-4 md:hidden"
    >
      <div
        class="mx-auto flex w-full max-w-xl items-center gap-2 rounded-[1.75rem] border border-slate-200/95 bg-white/95 px-3 py-2.5 shadow-[0_-16px_40px_-12px_rgba(15,23,42,0.22)] backdrop-blur-xl ring-1 ring-slate-900/[0.05]
               supports-[padding:max(0px)]:pl-[max(0.75rem,env(safe-area-inset-left))]
               supports-[padding:max(0px)]:pr-[max(0.75rem,env(safe-area-inset-right))]"
      >
        <button
          v-if="step > 0"
          type="button"
          class="min-h-[48px] min-w-[96px] rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-900/[0.04] disabled:opacity-45"
          :disabled="submitting"
          @click="step -= 1"
        >
          {{ t('portal.back') }}
        </button>
        <span v-else class="min-w-[96px]" />

        <button
          v-if="step < 2"
          type="button"
          class="inline-flex min-h-[48px] flex-1 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-va-800 to-va-900 px-4 text-sm font-bold text-white shadow-lg shadow-va-950/25 ring-1 ring-white/10 hover:from-va-900 hover:to-va-950 disabled:cursor-not-allowed disabled:opacity-45"
              :disabled="submitting || !canGoNext"
              @click="nextStep"
        >
          {{ t('portal.next') }}
          <ArrowRightIcon class="h-5 w-5" aria-hidden="true" />
        </button>
        <button
          v-else
          type="button"
          class="inline-flex min-h-[48px] flex-1 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-va-800 to-va-900 px-4 text-sm font-bold text-white shadow-lg shadow-va-950/25 ring-1 ring-white/10 hover:from-va-900 hover:to-va-950 disabled:opacity-45"
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
import { computed, nextTick, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowRightIcon, PaperAirplaneIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../store'
import { createPortalDispatchRequest } from '../../api/requests'
import { formatApiError } from '../../api/http'
import PortalStepper from '../../components/portal/PortalStepper.vue'
import PortalTripTypeGrid from '../../components/portal/PortalTripTypeGrid.vue'
import PortalStepInfo from '../../components/portal/PortalStepInfo.vue'
import PortalStepConfirm from '../../components/portal/PortalStepConfirm.vue'

const TRIP_TYPES = ['door_to_door', 'point_to_point', 'business', 'cargo']

const DIRTY_KEY = 'portal_form_dirty'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const tripTypes = TRIP_TYPES
const step = ref(0)
const tripType = ref('point_to_point')

const infoModel = ref({
  origin: '',
  destination: '',
  departAtLocal: '',
  arriveByLocal: '',
  passengerCount: null,
  notes: '',
  isUrgent: false,
  urgentReason: '',
})

const formError = ref('')
const submitting = ref(false)
const errorBannerRef = ref(null)

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

const stepLabels = computed(() => [
  { key: 'type', label: t('portal.step_type') },
  { key: 'info', label: t('portal.step_info') },
  { key: 'confirm', label: t('portal.step_confirm') },
])

const showPassengerCount = computed(() => tripType.value !== 'cargo')

const requesterDisplay = computed(() =>
  auth.user ? `${auth.user.name} · ${auth.user.email || ''}`.trim() : '',
)

const canGoNext = computed(() => {
  if (step.value === 0) return TRIP_TYPES.includes(tripType.value)
  if (step.value === 1) return !!infoModel.value.departAtLocal?.trim()
  return true
})

const departPreviewComputed = computed(() => formatDatetimePreview(infoModel.value.departAtLocal))
const arrivePreviewComputed = computed(() => formatDatetimePreview(infoModel.value.arriveByLocal))

const confirmSummary = computed(() => ({
  tripType: tripType.value,
  requesterDisplay: requesterDisplay.value,
  origin: infoModel.value.origin,
  destination: infoModel.value.destination,
  departAtLocal: infoModel.value.departAtLocal,
  arriveByLocal: infoModel.value.arriveByLocal,
  departPreview: departPreviewComputed.value,
  arrivePreview: arrivePreviewComputed.value,
  showPassengerCount: showPassengerCount.value,
  passengerCount: infoModel.value.passengerCount,
  notes: infoModel.value.notes,
  isUrgent: infoModel.value.isUrgent,
  urgentReason: infoModel.value.urgentReason,
}))

watch(
  [step, infoModel, tripType],
  () => {
    const m = infoModel.value
    if (step.value === 0 && !m.origin && !m.departAtLocal) {
      sessionStorage.removeItem(DIRTY_KEY)
      return
    }
    const touched =
      step.value > 0 ||
      !!(m.origin || m.destination || m.departAtLocal || m.arriveByLocal || m.notes?.trim())
    if (touched || m.isUrgent || m.passengerCount) {
      sessionStorage.setItem(DIRTY_KEY, '1')
    } else {
      sessionStorage.removeItem(DIRTY_KEY)
    }
  },
  { deep: true },
)

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
  if (step.value === 1 && !infoModel.value.departAtLocal.trim()) {
    showError(t('portal.need_datetime'))
    return
  }
  if (step.value === 1 && infoModel.value.isUrgent && !infoModel.value.urgentReason.trim()) {
    showError(t('dispatch_wizard.validate.urgent_reason'))
    return
  }
  step.value += 1
}

async function submit() {
  formError.value = ''
  const departIso = toIsoMaybe(infoModel.value.departAtLocal)
  if (!departIso) {
    showError(t('portal.need_datetime'))
    return
  }
  const arriveIso = infoModel.value.arriveByLocal.trim() ? toIsoMaybe(infoModel.value.arriveByLocal) : null
  if (arriveIso && new Date(arriveIso) < new Date(departIso)) {
    showError(t('portal.arrive_after_depart'))
    return
  }

  const m = infoModel.value

  submitting.value = true
  try {
    const payload = {
      trip_type: tripType.value,
      source_channel: 'portal',
      origin: m.origin.trim() || undefined,
      destination: m.destination.trim() || undefined,
      depart_at: departIso,
      arrive_by: arriveIso || undefined,
      passenger_count:
        showPassengerCount.value && m.passengerCount != null && m.passengerCount >= 1
          ? Math.round(Number(m.passengerCount))
          : undefined,
      notes: m.notes.trim() || undefined,
      is_urgent: m.isUrgent,
      urgent_reason: m.isUrgent ? m.urgentReason.trim() || undefined : undefined,
    }
    Object.keys(payload).forEach((k) => {
      if (payload[k] === undefined) delete payload[k]
    })
    const data = await createPortalDispatchRequest(payload)
    sessionStorage.removeItem(DIRTY_KEY)
    await router.push({
      name: 'portalRequestDetail',
      params: { id: String(data.id) },
      query: { created: '1' },
    })
  } catch (e) {
    showError(formatApiError(e, t('dispatch_wizard.validate.create_fail')))
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.portal-step-enter-active,
.portal-step-leave-active {
  transition:
    opacity 0.22s ease,
    transform 0.22s ease;
}
.portal-step-enter-from {
  opacity: 0;
  transform: translateX(10px);
}
.portal-step-leave-to {
  opacity: 0;
  transform: translateX(-10px);
}
</style>
