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
  </Teleport>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import {
  ArrowRightIcon,
  ChevronDownIcon,
  MapIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { formatVndWhileTyping } from '../../../util/money'

const props = defineProps({
  kmModalOpen: { type: Boolean, default: false },
  costModalOpen: { type: Boolean, default: false },
  startKmDisplay: { type: String, default: '' },
  endKm: { type: String, default: '' },
  kmNote: { type: String, default: '' },
  distancePreview: { type: String, default: '' },
  canSubmitKm: { type: Boolean, default: false },
  kmSaving: { type: Boolean, default: false },
  costForm: { type: Object, required: true },
  costTypes: { type: Array, default: () => [] },
  costError: { type: String, default: '' },
  costSaving: { type: Boolean, default: false },
})

const emit = defineEmits([
  'close-km',
  'close-cost',
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
