<template>
  <section
    class="border-t border-[#86c2b5]/15 first:border-t-0 transition-[border-color] duration-300"
    :class="open ? 'border-[#86c2b5]/15' : 'border-rose-900/55'"
  >
    <button
      type="button"
      class="sticky top-0 z-10 flex w-full min-h-[44px] items-center gap-3 border-b border-[#86c2b5]/15 bg-[#0d1f1c]/95 px-4 py-3 text-left backdrop-blur-[2px] supports-[backdrop-filter]:bg-[#0d1f1c]/92 sm:px-5"
      :aria-expanded="open"
      @click="$emit('toggle')"
    >
      <span class="min-w-0 flex-1 truncate text-base font-bold text-white">
        {{ title }}
      </span>
      <span
        v-if="!open && trips.length > 0"
        class="shrink-0 rounded-full bg-[#0d1f1c]/90 px-2.5 py-1 text-xs font-bold tabular-nums text-[#86c2b5] ring-1 ring-[#86c2b5]/30"
      >
        {{ t('driver_home.pending_collapsed_count', { n: trips.length }) }}
      </span>
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 20 20"
        fill="currentColor"
        class="h-5 w-5 shrink-0 text-[#86c2b5]/70 transition-transform duration-300 ease-out"
        :class="open ? 'rotate-180' : 'rotate-0'"
        aria-hidden="true"
      >
        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06z" clip-rule="evenodd" />
      </svg>
    </button>

    <Transition name="collapse">
      <div v-if="open" key="open" class="overflow-hidden">
        <div class="border-b border-[#86c2b5]/15 px-4 py-4 sm:px-5">
          <div v-if="trips.length === 0" class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-[#86c2b5]/30 bg-[#0d1f1c]/50 px-4 py-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-12 w-12 text-[#86c2b5]/50" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
            </svg>
            <p class="text-sm font-medium text-[#86c2b5]/70">
              {{ t('driver_home.pending_section_empty') }}
            </p>
          </div>

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
              class="flex w-full min-h-[44px] items-center justify-center rounded-xl border border-[#86c2b5]/25 bg-[#0d1f1c]/70 text-sm font-semibold text-[#86c2b5] transition-colors hover:bg-[#86c2b5]/10 active:bg-[#86c2b5]/15"
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
    max-height 0.35s ease,
    opacity 0.22s ease;
}
.collapse-enter-from,
.collapse-leave-to {
  max-height: 0;
  opacity: 0;
}
.collapse-enter-to,
.collapse-leave-from {
  max-height: 2600px;
  opacity: 1;
}
</style>
