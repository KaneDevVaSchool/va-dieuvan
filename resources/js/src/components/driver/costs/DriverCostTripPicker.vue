<template>
  <div ref="rootRef" class="space-y-3">
    <div>
      <label class="text-sm font-semibold text-driver-muted" :for="inputId">
        {{ t('driver_costs.trip_search') }}
      </label>
      <div class="relative mt-1.5">
        <MagnifyingGlassIcon
          class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-driver-muted/60"
          aria-hidden="true"
        />
        <input
          :id="inputId"
          v-model="search"
          type="search"
          autocomplete="off"
          class="flex min-h-[48px] w-full rounded-xl border border-white/10 bg-driver-surface py-2.5 pl-10 pr-10 text-base text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
          :placeholder="t('driver_costs.trip_search_ph')"
          :aria-expanded="listOpen ? 'true' : 'false'"
          :aria-controls="listboxId"
          role="combobox"
          @focus="openList"
          @input="openList"
        />
        <button
          v-if="search"
          type="button"
          class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-driver-muted transition hover:bg-white/5"
          :aria-label="t('driver_costs.trip_search_clear')"
          @click="clearSearch"
        >
          <XMarkIcon class="h-4 w-4" aria-hidden="true" />
        </button>
      </div>
    </div>

    <div
      v-if="selectedTrip"
      class="rounded-xl border border-driver-accent/30 bg-driver-accent/10 p-3 ring-1 ring-driver-accent/20"
    >
      <div class="flex items-start justify-between gap-2">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-driver-accent/90">
          {{ t('driver_costs.trip_picker_selected') }}
        </p>
        <button
          type="button"
          class="shrink-0 text-xs font-bold text-driver-accent underline-offset-2 hover:underline"
          @click="clearSelection"
        >
          {{ t('driver_costs.trip_change') }}
        </button>
      </div>
      <DriverCostTripOptionRow :trip="selectedTrip" :compact="false" class="mt-2" />
    </div>

    <div
      v-if="listOpen && !selectedTrip"
      :id="listboxId"
      role="listbox"
      class="max-h-[min(52vh,22rem)] overflow-y-auto overscroll-y-contain rounded-xl border border-white/10 bg-driver-surface ring-1 ring-white/[0.06]"
    >
      <p v-if="loading" class="px-4 py-6 text-center text-sm text-driver-muted">
        {{ t('driver_costs.trips_loading') }}
      </p>
      <p v-else-if="!groupedTrips.length" class="px-4 py-6 text-center text-sm text-driver-muted">
        {{ t('driver_costs.trips_empty') }}
      </p>
      <template v-else>
        <div
          v-for="group in groupedTrips"
          :key="group.dateKey"
          class="border-b border-white/[0.06] last:border-b-0"
        >
          <p
            class="sticky top-0 z-[1] bg-driver-surface/95 px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-driver-muted backdrop-blur-sm"
          >
            {{ group.dateLabel }}
          </p>
          <ul>
            <li v-for="tr in group.trips" :key="tr.id">
              <button
                type="button"
                role="option"
                class="w-full px-3 py-3 text-left transition hover:bg-white/[0.04] active:bg-white/[0.07]"
                :aria-selected="String(modelValue) === String(tr.id) ? 'true' : 'false'"
                @click="selectTrip(tr)"
              >
                <DriverCostTripOptionRow :trip="tr" />
              </button>
            </li>
          </ul>
        </div>
      </template>
    </div>

    <p v-if="!loading && !trips.length" class="text-xs text-driver-muted">
      {{ t('driver_costs.trips_empty') }}
    </p>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import DriverCostTripOptionRow from './DriverCostTripOptionRow.vue'
import { buildDriverCostTripGroups, findDriverCostTrip } from '../../../util/driverCostTripPicker'

const props = defineProps({
  modelValue: { type: String, default: '' },
  trips: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const { t, locale } = useI18n()

const inputId = 'driver-cost-trip-search'
const listboxId = 'driver-cost-trip-listbox'
const rootRef = ref(null)
const search = ref('')
const listOpen = ref(false)

const localeTag = computed(() => (locale.value === 'vi' ? 'vi' : 'en'))

const selectedTrip = computed(() =>
  findDriverCostTrip(props.trips, props.modelValue),
)

const groupedTrips = computed(() =>
  buildDriverCostTripGroups(props.trips, search.value, localeTag.value, t),
)

function openList() {
  listOpen.value = true
}

function closeList() {
  listOpen.value = false
}

function clearSearch() {
  search.value = ''
  openList()
}

function selectTrip(tr) {
  emit('update:modelValue', String(tr.id))
  search.value = ''
  closeList()
}

function clearSelection() {
  emit('update:modelValue', '')
  search.value = ''
  openList()
}

function onDocClick(ev) {
  const el = rootRef.value
  if (!el || el.contains(ev.target)) return
  closeList()
}

onMounted(() => document.addEventListener('click', onDocClick))
onUnmounted(() => document.removeEventListener('click', onDocClick))
</script>
