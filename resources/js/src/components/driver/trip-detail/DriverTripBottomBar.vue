<template>
  <div
    class="safe-pb fixed bottom-[calc(var(--driver-bottom-nav-height,3.5rem)+env(safe-area-inset-bottom))] left-0 right-0 z-[35] border-t border-[#7fdcc8]/15 bg-driver-card/95 pt-2 backdrop-blur-md pl-[max(theme(spacing.3),env(safe-area-inset-left))] pr-[max(theme(spacing.3),env(safe-area-inset-right))] sm:static sm:bottom-auto sm:z-auto sm:mt-4 sm:border-0 sm:bg-transparent sm:px-0 sm:py-0 sm:backdrop-blur-0"
  >
    <div
      v-if="showPickupBar && tripStatus === 'in_progress'"
      class="mb-2 flex flex-wrap items-center gap-2 rounded-2xl bg-driver-surface px-3 py-2"
    >
      <span class="min-w-0 flex-1 text-sm font-semibold text-driver-muted sm:text-base">
        {{ t('driver_trip_detail.bottom_picked', { n: pickedCount, total: paxTotal }) }}
      </span>
      <span class="text-sm font-bold text-orange-400 sm:text-base">
        {{ t('driver_trip_detail.bottom_remaining', { n: paxTotal - pickedCount }) }}
      </span>
      <button
        type="button"
        class="flex min-h-[44px] shrink-0 items-center gap-1.5 rounded-full border border-white/10 px-4 py-2 text-sm font-bold text-driver-muted transition active:scale-[0.97] active:bg-driver-elevated sm:text-base"
        :class="isPaused ? 'border-[#7fdcc8] bg-[#7fdcc8]/10 text-driver-accent' : ''"
        @click="onTogglePause"
      >
        <PauseIcon v-if="!isPaused" class="h-4 w-4 shrink-0" aria-hidden="true" />
        <PlayIcon v-else class="h-4 w-4 shrink-0" aria-hidden="true" />
        {{ isPaused ? t('driver_trip_detail.btn_resume_trip') : t('driver_trip_detail.btn_pause') }}
      </button>
    </div>

    <button
      v-if="canStart"
      type="button"
      :disabled="actionBusy"
      class="mb-2 flex min-h-[52px] w-full items-center justify-center gap-2 rounded-2xl bg-emerald-500 px-4 py-3.5 text-base font-bold text-white shadow-md transition-transform disabled:opacity-50 active:scale-[0.98] active:opacity-90 sm:text-lg"
      @click="onStart"
    >
      <PlayIcon class="h-6 w-6 shrink-0 sm:h-5 sm:w-5" aria-hidden="true" />
      {{ t('driver_trip_detail.btn_start_trip') }}
    </button>
    <button
      v-else-if="canEndTrip"
      type="button"
      :disabled="actionBusy"
      class="mb-2 flex min-h-[52px] w-full items-center justify-center gap-2 rounded-2xl bg-driver-bg px-4 py-3.5 text-base font-bold text-white shadow-md transition-transform disabled:opacity-50 active:scale-[0.98] active:opacity-90 sm:text-lg"
      @click="onEnd"
    >
      <FlagIcon class="h-6 w-6 shrink-0 sm:h-5 sm:w-5" aria-hidden="true" />
      {{ t('driver_trip_detail.btn_end_trip') }}
    </button>
    <p
      v-else-if="tripStatus === 'completed'"
      class="mb-2 rounded-2xl border border-emerald-500/35 bg-emerald-950/35 px-4 py-3.5 text-center text-base font-medium leading-snug text-emerald-100 ring-1 ring-emerald-500/25 sm:text-lg"
      role="status"
    >
      {{ t('driver_trip_detail.done') }}
    </p>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { FlagIcon, PauseIcon, PlayIcon } from '@heroicons/vue/24/outline'
import { useHaptics } from '../../../composables/useHaptics'

defineProps({
  tripStatus: { type: String, default: '' },
  showPickupBar: { type: Boolean, default: false },
  pickedCount: { type: Number, default: 0 },
  paxTotal: { type: Number, default: 0 },
  isPaused: { type: Boolean, default: false },
  canStart: { type: Boolean, default: false },
  canEndTrip: { type: Boolean, default: false },
  actionBusy: { type: Boolean, default: false },
})

const emit = defineEmits(['toggle-pause', 'start-trip', 'end-trip'])

const { t } = useI18n()
const haptics = useHaptics()

function onStart() {
  haptics.impact()
  emit('start-trip')
}
function onEnd() {
  haptics.impact()
  emit('end-trip')
}
function onTogglePause() {
  haptics.tap()
  emit('toggle-pause')
}
</script>

<style scoped>
.safe-pb {
  padding-bottom: 0.75rem;
}
</style>
