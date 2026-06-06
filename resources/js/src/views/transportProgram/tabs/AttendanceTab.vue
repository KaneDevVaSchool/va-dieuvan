<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-bold text-slate-900">Điểm danh theo chuyến</h2>
        <p class="text-sm text-slate-500">
          Mỗi ca sáng / chiều là một chuyến độc lập — chọn chuyến để điểm danh.
        </p>
      </div>
      <input
        v-model="month"
        type="month"
        class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-base outline-none ring-va-800/20 focus:ring"
      />
    </div>

    <div v-if="loading" class="flex items-center justify-center rounded-2xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> Đang tải…
    </div>
    <div v-else-if="!tripCards.length" class="rounded-2xl border border-slate-200 bg-white py-16 text-center text-sm text-slate-500">
      Không có chuyến vận hành trong tháng này.
    </div>
    <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <button
        v-for="t in tripCards"
        :key="t.trip_key"
        class="group rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-va-800/30 hover:shadow-md"
        @click="openTrip(t)"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ weekday(t.scheduled_date) }}</div>
            <div class="text-lg font-bold text-slate-900">{{ formatDate(t.scheduled_date) }}</div>
          </div>
          <span
            class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
            :class="t.shift === 'afternoon' ? 'bg-violet-50 text-violet-700' : 'bg-amber-50 text-amber-800'"
          >
            {{ t.slot_label }}
          </span>
        </div>
        <p v-if="t.slot_departure" class="mt-1 text-xs text-slate-500">{{ t.slot_title }} · {{ t.slot_departure }}</p>
        <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-sm">
          <span class="flex items-center gap-1.5 text-slate-600"><UsersIcon class="h-4 w-4 text-slate-400" /> Sĩ số {{ t.expected_count }}</span>
          <span class="flex items-center gap-1 font-medium text-va-800 group-hover:underline">
            Điểm danh <ArrowRightIcon class="h-3.5 w-3.5" />
          </span>
        </div>
        <div class="mt-2 flex items-center gap-1.5 truncate text-xs text-slate-500">
          <UserCircleIcon class="h-4 w-4 shrink-0 text-slate-400" /> {{ t.effective_driver?.full_name || 'Chưa gán tài xế' }}
        </div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowPathIcon, ArrowRightIcon, UsersIcon, UserCircleIcon } from '@heroicons/vue/24/outline'
import { listProgramDays } from '../../../api/transportProgram'
import { expandOperatingDaysToTrips } from '../../../composables/tpProgramSlots'
import { showAppErrorFromApi } from '../../../composables/appMessage'

const props = defineProps({ program: { type: Object, required: true } })
const router = useRouter()
const loading = ref(false)
const days = ref([])
const month = ref(new Date().toISOString().slice(0, 7))

const WD = ['Chủ nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy']

const tripCards = computed(() => expandOperatingDaysToTrips(props.program, days.value))

async function load() {
  loading.value = true
  try {
    days.value = (await listProgramDays(props.program.id, { month: month.value }))?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

function openTrip(t) {
  const query = t.multi_slot ? { shift: t.shift } : {}
  router.push({ name: 'tpDayAttendance', params: { dayId: t.id }, query })
}

function weekday(dateStr) {
  return WD[new Date(dateStr + 'T00:00:00').getDay()]
}
function formatDate(dateStr) {
  const d = new Date(dateStr + 'T00:00:00')
  return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}`
}

watch(month, load)
onMounted(load)
</script>
