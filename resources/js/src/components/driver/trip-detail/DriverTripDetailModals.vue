<template>
  <Teleport to="body">
    <!-- KM modal -->
    <div
      v-if="kmModalOpen"
      class="fixed inset-0 z-[45] flex items-end justify-center bg-black/45 p-0 sm:items-center sm:p-4"
      role="presentation"
      @click.self="$emit('close-km')"
    >
      <div
        class="w-full max-w-md rounded-t-2xl bg-driver-card p-4 shadow-2xl ring-1 ring-white/10 sm:rounded-2xl"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="kmTitleId"
        @click.stop
      >
        <div class="mb-3 flex items-start justify-between gap-2">
          <h3 :id="kmTitleId" class="pr-6 text-lg font-bold leading-snug text-driver-ink sm:text-xl">{{ t('driver_trip_detail.km_modal_title') }}</h3>
          <button
            type="button"
            class="flex min-h-[44px] min-w-[44px] shrink-0 items-center justify-center rounded-full bg-driver-elevated text-driver-muted"
            :aria-label="t('driver_trip_detail.close')"
            @click="$emit('close-km')"
          >
            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
          </button>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="text-sm font-medium text-driver-muted sm:text-base">{{ t('driver_trip_detail.km_start') }}</label>
            <div class="mt-0.5 flex min-h-[48px] items-center rounded-xl border border-white/10 bg-driver-surface px-2 py-2.5 text-base text-driver-ink sm:text-lg">
              <input class="min-w-0 flex-1 border-0 bg-transparent p-0 text-base sm:text-lg" :value="startKmDisplay" readonly tabindex="-1" />
              <span class="ml-1 text-sm text-driver-muted/80">km</span>
            </div>
          </div>
          <div>
            <label class="text-sm font-medium text-driver-muted sm:text-base">{{ t('driver_trip_detail.km_end') }}</label>
            <div class="mt-0.5 flex min-h-[48px] items-center rounded-xl border border-white/10 bg-driver-surface px-2 py-2.5 focus-within:ring-2 focus-within:ring-[#7fdcc8]/50">
              <input
                :value="endKm"
                type="text"
                inputmode="numeric"
                class="min-w-0 flex-1 border-0 bg-transparent p-0 text-base text-driver-ink placeholder:text-driver-muted/50 sm:text-lg"
                :placeholder="t('driver_trip_detail.km_placeholder')"
                @input="onEndKmInput($event)"
              />
              <span class="ml-1 text-sm text-driver-muted/80">km</span>
            </div>
          </div>
        </div>
        <div class="mt-3 flex min-h-[44px] items-center gap-2 rounded-xl bg-driver-elevated px-3 py-2.5 text-sm text-driver-muted sm:text-base">
          <MapIcon class="h-4 w-4 shrink-0 text-driver-muted/80" aria-hidden="true" />
          <span>{{ t('driver_trip_detail.km_distance') }}</span>
          <span class="ml-auto font-bold tabular-nums text-driver-ink">{{ distancePreview }}</span>
        </div>
        <div class="mt-3">
          <label class="text-sm font-medium text-driver-muted sm:text-base">{{ t('driver_trip_detail.km_note') }}</label>
          <textarea
            :value="kmNote"
            rows="3"
            class="mt-0.5 w-full resize-none rounded-xl border border-white/10 bg-driver-surface px-3 py-2.5 text-base text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-[#7fdcc8]/50 sm:text-lg"
            :placeholder="t('driver_trip_detail.km_note_ph')"
            @input="$emit('update:kmNote', $event.target.value)"
          />
        </div>
        <div class="mt-4 flex gap-2">
          <button
            type="button"
            class="flex min-h-[48px] flex-1 items-center justify-center rounded-xl border border-white/10 py-3 text-base font-semibold text-driver-muted sm:text-lg"
            @click="$emit('close-km')"
          >
            {{ t('driver_trip_detail.cancel') }}
          </button>
          <button
            type="button"
            :disabled="kmSaving || !canSubmitKm"
            class="inline-flex min-h-[48px] flex-1 items-center justify-center gap-1 rounded-xl bg-driver-accent py-3 text-base font-bold text-driver-bg disabled:opacity-50 sm:text-lg"
            @click="$emit('submit-km')"
          >
            {{ t('driver_trip_detail.confirm') }}
            <ArrowRightIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          </button>
        </div>
      </div>
    </div>

    <!-- Cost modal -->
    <div
      v-if="costModalOpen"
      class="fixed inset-0 z-[45] flex items-end justify-center bg-black/45 p-0 sm:items-center sm:p-4"
      role="presentation"
      @click.self="$emit('close-cost')"
    >
      <div
        class="w-full max-w-md rounded-t-2xl bg-driver-card p-4 shadow-2xl ring-1 ring-white/10 sm:rounded-2xl"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="costTitleId"
        @click.stop
      >
        <div class="mb-3 flex items-center justify-between gap-2">
          <h3 :id="costTitleId" class="text-lg font-bold text-driver-ink sm:text-xl">{{ t('driver_trip_detail.cost_modal_title') }}</h3>
          <button
            type="button"
            class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full bg-driver-elevated text-driver-muted"
            :aria-label="t('driver_trip_detail.close')"
            @click="$emit('close-cost')"
          >
            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
          </button>
        </div>

        <template v-if="hasCostLegs">
          <label class="text-sm font-medium text-driver-muted sm:text-base">{{ t('driver_trip_detail.cost_leg') }}</label>
          <div class="relative mt-1.5">
            <select
              :value="costForm.leg_key"
              class="flex min-h-[48px] w-full appearance-none rounded-xl border border-white/10 bg-driver-surface px-3 py-3 pr-11 text-base text-driver-ink focus:outline-none focus:ring-2 focus:ring-[#7fdcc8]/50 sm:text-lg"
              data-testid="driver-cost-leg-select"
              @change="$emit('update:costForm', { ...costForm, leg_key: $event.target.value })"
            >
              <option value="">{{ t('driver_trip_detail.cost_leg_all') }}</option>
              <option v-for="l in costLegOptions" :key="l.key" :value="l.key">
                {{ l.label }}<template v-if="l.route"> · {{ l.route }}</template>
              </option>
            </select>
            <ChevronDownIcon class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-driver-muted/70" aria-hidden="true" />
          </div>

          <button
            type="button"
            class="mt-2 flex min-h-[44px] w-full items-center justify-between gap-2 rounded-xl bg-driver-elevated px-3 py-2.5 text-left text-sm font-medium text-driver-muted sm:text-base"
            :aria-expanded="legRouteOpen"
            @click="legRouteOpen = !legRouteOpen"
          >
            <span class="flex items-center gap-2">
              <MapPinIcon class="h-4 w-4 shrink-0 text-driver-muted/80" aria-hidden="true" />
              {{ t('driver_trip_detail.cost_leg_route') }}
            </span>
            <ChevronDownIcon class="h-5 w-5 shrink-0 text-driver-muted/70 transition-transform" :class="legRouteOpen ? 'rotate-180' : ''" aria-hidden="true" />
          </button>
          <div v-if="legRouteOpen" class="mt-1.5 rounded-xl border border-white/[0.06] bg-driver-surface/60 px-3 py-2.5">
            <template v-if="selectedCostLeg">
              <div class="space-y-2 text-sm text-driver-ink sm:text-base">
                <div class="flex items-start gap-2">
                  <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-emerald-400" aria-hidden="true" />
                  <span class="min-w-0">{{ selectedCostLeg.originMain || '—' }}<span v-if="selectedCostLeg.originSub" class="block text-xs text-driver-muted/80">{{ selectedCostLeg.originSub }}</span></span>
                </div>
                <div v-if="selectedCostLeg.waypointMain" class="flex items-start gap-2">
                  <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-amber-400" aria-hidden="true" />
                  <span class="min-w-0">{{ selectedCostLeg.waypointMain }}<span v-if="selectedCostLeg.waypointSub" class="block text-xs text-driver-muted/80">{{ selectedCostLeg.waypointSub }}</span></span>
                </div>
                <div class="flex items-start gap-2">
                  <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-rose-400" aria-hidden="true" />
                  <span class="min-w-0">{{ selectedCostLeg.destMain || '—' }}<span v-if="selectedCostLeg.destSub" class="block text-xs text-driver-muted/80">{{ selectedCostLeg.destSub }}</span></span>
                </div>
              </div>
            </template>
            <p v-else class="text-sm text-driver-muted/85 sm:text-base">{{ t('driver_trip_detail.cost_leg_all_hint') }}</p>
          </div>
          <div class="my-3 h-px bg-white/[0.06]" />
        </template>

        <label class="text-sm font-medium text-driver-muted sm:text-base">{{ t('driver_trip_detail.cost_type') }}</label>
        <div class="relative mt-1.5">
          <select
            :value="costForm.type"
            class="flex min-h-[48px] w-full appearance-none rounded-xl border border-white/10 bg-driver-surface px-3 py-3 pr-11 text-base text-driver-ink focus:outline-none focus:ring-2 focus:ring-[#7fdcc8]/50 sm:text-lg"
            @change="$emit('update:costForm', { ...costForm, type: $event.target.value })"
          >
            <option v-for="ct in costTypes" :key="ct.value" :value="ct.value">{{ ct.label }}</option>
          </select>
          <ChevronDownIcon class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-driver-muted/70" aria-hidden="true" />
        </div>

        <label class="mt-3 block text-sm font-medium text-driver-muted sm:text-base">{{ t('driver_trip_detail.cost_amount') }}</label>
        <input
          :value="costForm.amount"
          type="text"
          inputmode="numeric"
          autocomplete="off"
          class="mt-0.5 flex min-h-[48px] w-full rounded-xl border border-white/10 bg-driver-surface px-3 py-3 text-base tabular-nums text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-[#7fdcc8]/50 sm:text-lg"
          :placeholder="t('driver_trip_detail.cost_amount_ph')"
          @input="onCostAmountInput"
        />

        <label class="mt-2.5 block text-sm font-medium text-driver-muted sm:text-base">{{ t('driver_trip_detail.cost_desc') }}</label>
        <input
          :value="costForm.description"
          type="text"
          class="mt-0.5 flex min-h-[48px] w-full rounded-xl border border-white/10 bg-driver-surface px-3 py-3 text-base text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-[#7fdcc8]/50 sm:text-lg"
          :placeholder="t('driver_trip_detail.cost_desc_ph')"
          @input="$emit('update:costForm', { ...costForm, description: $event.target.value })"
        />

        <p v-if="costError" class="mt-2 text-sm text-rose-400 sm:text-base">{{ costError }}</p>

        <div class="mt-4 flex gap-2">
          <button
            type="button"
            class="flex min-h-[48px] flex-1 items-center justify-center rounded-xl border border-white/10 py-3 text-base font-semibold text-driver-muted sm:text-lg"
            @click="$emit('close-cost')"
          >
            {{ t('driver_trip_detail.cancel') }}
          </button>
          <button
            type="button"
            :disabled="costSaving"
            class="flex min-h-[48px] flex-1 items-center justify-center rounded-xl bg-driver-accent py-3 text-base font-bold text-driver-bg disabled:opacity-50 sm:text-lg"
            @click="$emit('submit-cost')"
          >
            {{ t('driver_trip_detail.confirm') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Decline / busy modal -->
    <div
      v-if="declineModalOpen"
      class="fixed inset-0 z-[80] flex items-end justify-center bg-black/60 px-3 pb-[max(1rem,env(safe-area-inset-bottom))] pt-12 sm:items-center sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="driver-trip-decline-modal-title"
      data-testid="driver-trip-decline-modal"
      @click.self="$emit('close-decline')"
    >
      <div
        class="w-full max-w-md rounded-3xl bg-driver-card p-5 shadow-2xl ring-1 ring-white/10"
        @click.stop
      >
        <h2 id="driver-trip-decline-modal-title" class="text-lg font-bold text-driver-ink sm:text-xl">
          {{ declineModalTitle }}
        </h2>
        <p v-if="declineTripSummary" class="mt-2 text-sm text-driver-muted sm:text-base">
          {{ declineTripSummary }}
        </p>

        <template v-if="declineStep === 'reason'">
          <p class="mt-4 text-sm text-driver-muted sm:text-base">
            {{ declineModalSubtitle }}
          </p>
          <label
            class="mt-3 block text-xs font-semibold uppercase tracking-wide text-driver-accent/80"
          >
            {{ declineModalReasonLabel }}
          </label>
          <textarea
            :value="declineReason"
            rows="4"
            class="mt-2 w-full resize-y rounded-xl border border-white/10 bg-driver-surface px-3 py-2.5 text-base text-driver-ink placeholder:text-driver-muted/50 focus:border-driver-accent/50 focus:outline-none focus:ring-2 focus:ring-driver-accent/25 sm:text-lg"
            :placeholder="t('driver_home.pending_decline_reason_placeholder')"
            autocomplete="off"
            data-testid="driver-trip-decline-reason"
            @input="$emit('update:declineReason', $event.target.value)"
          />
        </template>

        <template v-else>
          <p class="mt-4 text-sm font-semibold text-amber-200/95 sm:text-base">
            {{ declineModalConfirmTitle }}
          </p>
          <p class="mt-1 text-sm text-driver-muted sm:text-base">
            {{ declineModalConfirmHint }}
          </p>
          <div class="mt-3 rounded-xl bg-driver-surface px-3 py-2.5 text-sm text-driver-ink sm:text-base">
            {{ declineReason.trim() }}
          </div>
        </template>

        <p v-if="declineModalError" class="mt-3 text-sm font-medium text-rose-400 sm:text-base" role="alert">
          {{ declineModalError }}
        </p>

        <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:justify-end">
          <button
            type="button"
            class="order-last min-h-[48px] w-full rounded-xl bg-transparent px-4 text-sm font-semibold text-driver-muted hover:bg-white/5 sm:order-first sm:w-auto"
            :disabled="declineBusy"
            data-testid="driver-trip-decline-back"
            @click="
              declineStep === 'confirm'
                ? $emit('update:declineStep', 'reason')
                : $emit('close-decline')
            "
          >
            {{
              declineStep === 'confirm'
                ? t('driver_home.pending_decline_back')
                : t('driver_home.pending_decline_cancel')
            }}
          </button>
          <button
            v-if="declineStep === 'reason'"
            type="button"
            class="min-h-[48px] w-full rounded-xl bg-driver-accent px-4 text-sm font-bold text-driver-bg shadow-md active:scale-[0.99] sm:w-auto sm:min-w-[9rem]"
            :disabled="declineBusy"
            data-testid="driver-trip-decline-next"
            @click="$emit('decline-next')"
          >
            {{ t('driver_home.pending_decline_next') }}
          </button>
          <button
            v-else
            type="button"
            class="min-h-[48px] w-full rounded-xl bg-rose-600 px-4 text-sm font-bold text-white shadow-md hover:bg-rose-500 active:scale-[0.99] sm:w-auto sm:min-w-[9rem]"
            :disabled="declineBusy"
            data-testid="driver-trip-decline-submit"
            @click="$emit('decline-submit')"
          >
            {{ declineModalConfirmBtn }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowRightIcon,
  ChevronDownIcon,
  MapIcon,
  MapPinIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { formatVndWhileTyping } from '../../../util/money'

const props = defineProps({
  kmModalOpen: { type: Boolean, default: false },
  costModalOpen: { type: Boolean, default: false },
  declineModalOpen: { type: Boolean, default: false },
  declineStep: { type: String, default: 'reason' },
  declineReason: { type: String, default: '' },
  declineModalError: { type: String, default: '' },
  declineTripSummary: { type: String, default: '' },
  declineIsBusyFlow: { type: Boolean, default: false },
  declineBusy: { type: Boolean, default: false },
  startKmDisplay: { type: String, default: '' },
  endKm: { type: String, default: '' },
  kmNote: { type: String, default: '' },
  distancePreview: { type: String, default: '' },
  canSubmitKm: { type: Boolean, default: false },
  kmSaving: { type: Boolean, default: false },
  costForm: { type: Object, required: true },
  costTypes: { type: Array, default: () => [] },
  costLegOptions: { type: Array, default: () => [] },
  costError: { type: String, default: '' },
  costSaving: { type: Boolean, default: false },
})

const legRouteOpen = ref(false)
const hasCostLegs = computed(() => (props.costLegOptions?.length ?? 0) > 1)
const selectedCostLeg = computed(
  () => props.costLegOptions.find((l) => l.key === props.costForm?.leg_key) ?? null,
)

const declineModalTitle = computed(() =>
  props.declineIsBusyFlow ? t('driver_home.busy_title') : t('driver_home.pending_decline_title'),
)
const declineModalSubtitle = computed(() =>
  props.declineIsBusyFlow ? t('driver_home.busy_subtitle') : t('driver_home.pending_decline_subtitle'),
)
const declineModalReasonLabel = computed(() =>
  props.declineIsBusyFlow ? t('driver_home.busy_reason_label') : t('driver_home.pending_decline_reason_label'),
)
const declineModalConfirmTitle = computed(() =>
  props.declineIsBusyFlow ? t('driver_home.busy_confirm_title') : t('driver_home.pending_decline_confirm_title'),
)
const declineModalConfirmHint = computed(() =>
  props.declineIsBusyFlow ? t('driver_home.busy_confirm_hint') : t('driver_home.pending_decline_confirm_hint'),
)
const declineModalConfirmBtn = computed(() =>
  props.declineIsBusyFlow ? t('driver_home.busy_confirm_btn') : t('driver_home.pending_decline_confirm_btn'),
)

const emit = defineEmits([
  'close-km',
  'close-cost',
  'close-decline',
  'decline-next',
  'decline-submit',
  'update:declineReason',
  'update:declineStep',
  'submit-km',
  'submit-cost',
  'update:endKm',
  'update:kmNote',
  'update:costForm',
])

const { t } = useI18n()

const kmTitleId = 'driver-trip-km-modal-title'
const costTitleId = 'driver-trip-cost-modal-title'

function onEndKmInput(ev) {
  emit('update:endKm', ev.target.value)
}

function onCostAmountInput(ev) {
  emit('update:costForm', {
    ...props.costForm,
    amount: formatVndWhileTyping(ev.target.value),
  })
}
</script>
