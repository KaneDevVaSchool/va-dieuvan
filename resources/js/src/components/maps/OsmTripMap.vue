<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, shallowRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import L from 'leaflet'
import { delayMs, geocodeAddress, type MapPoint } from '../../composables/osmGeocode'

const props = withDefaults(
  defineProps<{
    queries: string[]
    minHeightClass?: string
  }>(),
  { minHeightClass: 'min-h-[200px]' },
)

const emit = defineEmits<{
  resolved: [payload: { points: MapPoint[] }]
}>()

const { t } = useI18n()

const rootRef = ref<HTMLElement | null>(null)
const loading = ref(false)
const fetchFailed = ref(false)
const layerRef = shallowRef<L.Map | null>(null)

const geocodeFailedBanner = computed(() => fetchFailed.value && !loading.value && !layerRef.value)

function destroyMap() {
  const m = layerRef.value
  if (m) {
    m.remove()
    layerRef.value = null
  }
}

async function buildPoints(): Promise<MapPoint[]> {
  const qs = [...props.queries.map((q) => q?.trim()).filter(Boolean)]
  const unique: string[] = []
  for (const q of qs) {
    if (!unique.includes(q)) unique.push(q)
  }
  const out: MapPoint[] = []
  for (let i = 0; i < unique.length; i += 1) {
    if (i > 0) await delayMs(400)
    const pt = await geocodeAddress(unique[i])
    if (pt) out.push(pt)
  }
  return out
}

async function render(points: MapPoint[]) {
  await nextTick()
  const el = rootRef.value
  if (!el) return
  destroyMap()

  const map = L.map(el, {
    zoomControl: true,
    attributionControl: true,
  })
  layerRef.value = map

  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  }).addTo(map)

  const latLngs = points.map((p) => L.latLng(p.lat, p.lon))

  for (let i = 0; i < latLngs.length; i += 1) {
    L.circleMarker(latLngs[i], {
      radius: 7,
      color: i === 0 ? '#059669' : i === latLngs.length - 1 ? '#0284c7' : '#64748b',
      weight: 2,
      fillOpacity: 0.9,
    }).addTo(map)
  }

  if (latLngs.length >= 2) {
    L.polyline(latLngs, { color: '#334155', weight: 3, opacity: 0.72 }).addTo(map)
  }

  if (latLngs.length === 1) {
    map.setView(latLngs[0], 14)
  } else {
    map.fitBounds(L.latLngBounds(latLngs), { padding: [28, 28], maxZoom: 15 })
  }

  requestAnimationFrame(() => map.invalidateSize())
}

watch(
  () => props.queries,
  async (qs) => {
    destroyMap()
    fetchFailed.value = false
    const list = qs?.length ? qs.map((q) => q?.trim()).filter(Boolean) : []

    if (!list.length) {
      loading.value = false
      emit('resolved', { points: [] })
      return
    }

    loading.value = true
    try {
      const pts = await buildPoints()
      if (!pts.length) {
        fetchFailed.value = true
        emit('resolved', { points: [] })
        return
      }
      await render(pts)
      emit('resolved', { points: pts })
    } catch {
      fetchFailed.value = true
      emit('resolved', { points: [] })
    } finally {
      loading.value = false
    }
  },
  { immediate: true, deep: true },
)

onBeforeUnmount(() => {
  destroyMap()
})

defineExpose({
  invalidateSize() {
    layerRef.value?.invalidateSize()
  },
})
</script>

<template>
  <div class="relative w-full rounded-[inherit]">
    <div
      v-if="!queries.length"
      :class="[
        'flex items-center justify-center rounded-[inherit] bg-slate-50 p-4 text-center text-sm text-slate-500 dark:bg-slate-800/80 dark:text-slate-400',
        minHeightClass,
      ]"
    >
      {{ t('trip_detail.route.map_placeholder') }}
    </div>

    <div v-else class="relative">
      <div
        v-if="loading"
        class="absolute inset-0 z-[500] flex items-center justify-center rounded-[inherit] bg-slate-100/85 text-xs font-medium text-slate-600 dark:bg-slate-900/75 dark:text-slate-300"
        role="status"
      >
        {{ t('trip_detail.route.map_loading') }}
      </div>

      <div
        v-if="geocodeFailedBanner"
        :class="[
          'flex items-center justify-center rounded-[inherit] bg-amber-50/90 p-4 text-center text-sm text-amber-900 dark:bg-amber-950/50 dark:text-amber-200',
          minHeightClass,
        ]"
      >
        {{ t('trip_detail.route.map_geocode_failed') }}
      </div>

      <div
        v-show="!geocodeFailedBanner"
        ref="rootRef"
        :class="['aspect-video w-full overflow-hidden rounded-[inherit] bg-slate-100 dark:bg-slate-800', minHeightClass]"
      />
    </div>
  </div>
</template>

<style>
@import 'leaflet/dist/leaflet.css';
</style>
