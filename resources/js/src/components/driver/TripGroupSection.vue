<template>
  <section class="border-t border-[#7fdcc8]/10 first:border-t-0">
    <!-- Section header — mobile-app style, large touch target -->
    <button
      type="button"
      class="flex w-full min-h-[64px] items-center gap-3.5 px-5 py-4 text-left transition-colors active:bg-[#7fdcc8]/5"
      :aria-expanded="open"
      @click="$emit('toggle')"
    >
      <!-- Count badge -->
      <span
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg font-extrabold tabular-nums transition-colors"
        :class="
          trips.length > 0
            ? accent === 'emerald'
              ? 'bg-emerald-500/15 text-emerald-300'
              : 'bg-[#7fdcc8]/15 text-[#7fdcc8]'
            : 'bg-[#070f0d]/80 text-slate-500'
        "
      >
        {{ trips.length }}
      </span>

      <!-- Title -->
      <span class="min-w-0 flex-1 text-lg font-bold text-white">
        {{ title }}
      </span>

      <!-- Chevron -->
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 20 20"
        fill="currentColor"
        class="h-6 w-6 shrink-0 text-[#7fdcc8]/55 transition-transform duration-300 ease-out"
        :class="open ? 'rotate-180' : 'rotate-0'"
        aria-hidden="true"
      >
        <path
          fill-rule="evenodd"
          d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06z"
          clip-rule="evenodd"
        />
      </svg>
    </button>

    <Transition name="collapse">
      <div v-if="open" key="open" class="overflow-hidden">
        <div class="px-4 pb-5 pt-1 sm:px-5">
          <!-- Empty state -->
          <div
            v-if="trips.length === 0"
            class="flex flex-col items-center gap-4 rounded-2xl border border-dashed border-[#7fdcc8]/18 bg-[#070f0d]/40 px-4 py-10 text-center"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              class="h-14 w-14 text-[#7fdcc8]/35"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"
              />
            </svg>
            <p class="text-base font-semibold text-[#7fdcc8]/60">
              {{ t('driver_home.pending_section_empty') }}
            </p>
          </div>

          <!-- Trip cards -->
          <div v-else class="space-y-4">
            <DriverTripCard
              v-for="trip in visibleTrips"
              :key="trip.id"
              v-memo="[trip.id, busyId === trip.id]"
              :trip="trip"
              :busy="busyId === trip.id"
              @confirm="$emit('confirm', $event)"
              @decline="$emit('decline', $event)"
            />
            <button
              v-if="needsShowMore"
              type="button"
              class="flex w-full min-h-[52px] items-center justify-center gap-2 rounded-2xl bg-[#070f0d]/90 text-base font-bold text-[#7fdcc8] transition-colors hover:bg-[#7fdcc8]/10 active:bg-[#7fdcc8]/15"
              @click="showAll = true"
            >
              {{ t('driver_home.pending_show_more') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import DriverTripCard from './DriverTripCard.vue'

const props = defineProps({
  title: { type: String, required: true },
  trips: { type: Array, required: true },
  open: { type: Boolean, required: true },
  busyId: { type: [Number, String], default: null },
  /** 'mint' | 'emerald' — controls count badge color */
  accent: { type: String, default: 'mint' },
})

defineEmits(['toggle', 'confirm', 'decline'])

const { t } = useI18n()

const INITIAL = 5
const showAll = ref(false)

watch(
  () => props.trips.map((x) => x.id).join(','),
  () => {
    showAll.value = false
  },
)

const visibleTrips = computed(() =>
  showAll.value ? props.trips : props.trips.slice(0, INITIAL),
)

const needsShowMore = computed(() => !showAll.value && props.trips.length > INITIAL)
</script>

<style scoped>
.collapse-enter-active,
.collapse-leave-active {
  overflow: hidden;
  transition:
    max-height 0.32s ease,
    opacity 0.2s ease;
}
.collapse-enter-from,
.collapse-leave-to {
  max-height: 0;
  opacity: 0;
}
.collapse-enter-to,
.collapse-leave-from {
  max-height: 3200px;
  opacity: 1;
}
</style>
