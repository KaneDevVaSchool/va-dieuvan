<template>
  <button
    type="button"
    class="w-full rounded-2xl border border-[#243a27] bg-[#1a2e1c] px-4 py-3.5 text-left shadow-md shadow-black/20 transition hover:border-[#2d4a32] active:scale-[0.99]"
    @click="goTrip"
  >
    <div class="flex gap-3">
      <span
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#fbbf24]/15 text-[#fbbf24]"
        aria-hidden="true"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
          <path
            fill-rule="evenodd"
            d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z"
            clip-rule="evenodd"
          />
        </svg>
      </span>
      <div class="min-w-0 flex-1">
        <p class="text-base font-bold leading-snug text-[#4ade80]">
          {{ titleLine }}
        </p>
        <p class="mt-1 text-sm leading-snug text-[#86b894]/90">
          {{ t('driver_home.upcoming_banner_subtitle') }}
        </p>
      </div>
    </div>
  </button>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  tripId: { type: [Number, String], required: true },
  departAt: { type: String, required: true },
  /** Ca chương trình đưa đón — điều hướng tới điểm danh thay vì chi tiết chuyến điều vận. */
  tpDayId: { type: Number, default: null },
  tpShift: { type: String, default: null },
})

const router = useRouter()
const { t } = useI18n()

const nowTick = ref(Date.now())
/** Cập nhật theo spec: 30 giây */
let timer = null

onMounted(() => {
  timer = setInterval(() => {
    nowTick.value = Date.now()
  }, 30000)
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})

const msUntil = computed(() => {
  const t0 = new Date(props.departAt).getTime()
  return t0 - nowTick.value
})

const timePhrase = computed(() => {
  const ms = msUntil.value
  if (!Number.isFinite(ms)) return ''
  const totalMin = Math.max(0, Math.ceil(ms / 60000))
  if (totalMin <= 0) return t('driver_home.upcoming_now')
  if (totalMin < 60) return t('driver_home.upcoming_minutes', { n: totalMin })
  const h = Math.floor(totalMin / 60)
  const m = totalMin % 60
  if (m === 0) return t('driver_home.upcoming_hours_only', { h })
  return t('driver_home.upcoming_hours_mins', { h, m })
})

const titleLine = computed(() =>
  t('driver_home.upcoming_banner_title', { id: props.tripId, time: timePhrase.value }),
)

function goTrip() {
  if (props.tpDayId != null) {
    const query = props.tpShift ? { shift: props.tpShift } : {}
    router.push({ path: `/driver/tp-days/${props.tpDayId}`, query })
    return
  }
  router.push(`/driver/trips/${props.tripId}`)
}
</script>
