<template>
  <div class="text-slate-900">
    <div class="border-b border-slate-200/80 bg-slate-50/90 px-4 py-8 sm:px-6 xl:mx-auto xl:max-w-4xl xl:rounded-b-3xl xl:border-x xl:border-b xl:border-slate-200 xl:bg-white xl:shadow-sm xl:backdrop-blur-none">
      <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ t('portal.create.page_title') }}</h1>
      <p class="mt-2 max-w-2xl text-sm text-slate-600">{{ t('portal.create.page_subtitle') }}</p>
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

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-lg shadow-slate-900/5 sm:p-8">
          <div
            v-if="formError"
            ref="errorBannerRef"
            role="alert"
            tabindex="-1"
            class="mb-4 rounded-xl bg-rose-50 px-3 py-2.5 text-sm font-medium text-rose-800 outline-none"
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
              <p v-if="!canGoNext" role="status" class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-900">
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
          <div class="mt-8 hidden items-center justify-between gap-4 border-t border-slate-100 pt-6 md:flex">
            <button
              v-if="step > 0"
              type="button"
              class="min-h-[44px] rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-50 disabled:opacity-50"
              :disabled="submitting"
              @click="step -= 1"
            >
              {{ t('portal.back') }}
            </button>
            <span v-else />

            <button
              v-if="step < 2"
              type="button"
              class="inline-flex min-h-[44px] items-center gap-2 rounded-xl bg-va-800 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-va-900/15 hover:bg-va-900 disabled:cursor-not-allowed disabled:opacity-45"
              :disabled="submitting || !canGoNext"
              @click="nextStep"
            >
              {{ t('portal.next') }}
              <ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
            </button>
            <button
              v-else
              type="button"
              class="inline-flex min-h-[44px] items-center gap-2 rounded-xl bg-va-800 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-va-900/15 hover:bg-va-900 disabled:cursor-not-allowed disabled:opacity-45"
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
        class="mx-auto flex w-full max-w-xl items-center gap-2 rounded-[1.75rem] border border-slate-200/95 bg-white/95 px-3 py-2.5 shadow-[0_-12px_32px_-12px_rgba(15,23,42,0.18)] backdrop-blur-md
               supports-[padding:max(0px)]:pl-[max(0.75rem,env(safe-area-inset-left))]
               supports-[padding:max(0px)]:pr-[max(0.75rem,env(safe-area-inset-right))]"
      >
        <button
          v-if="step > 0"
          type="button"
          class="min-h-[48px] min-w-[96px] rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 shadow-sm disabled:opacity-45"
          :disabled="submitting"
          @click="step -= 1"
        >
          {{ t('portal.back') }}
        </button>
        <span v-else class="min-w-[96px]" />

        <button
          v-if="step < 2"
          type="button"
          class="inline-flex min-h-[48px] flex-1 items-center justify-center gap-2 rounded-xl bg-va-800 px-4 text-sm font-semibold text-white shadow-md hover:bg-va-900 disabled:cursor-not-allowed disabled:opacity-45"
          :disabled="submitting || !canGoNext"
          @click="nextStep"
        >
          {{ t('portal.next') }}
          <ArrowRightIcon class="h-5 w-5" aria-hidden="true" />
        </button>
        <button
          v-else
          type="button"
          class="inline-flex min-h-[48px] flex-1 items-center justify-center gap-2 rounded-xl bg-va-800 px-4 text-sm font-semibold text-white shadow-md hover:bg-va-900 disabled:opacity-45"
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
import { ArrowRightIcon } from '@heroicons/vue/24/outline'
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
