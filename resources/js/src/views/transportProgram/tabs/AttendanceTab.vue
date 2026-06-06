<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-bold text-slate-900">Điểm danh theo ngày</h2>
        <p class="text-sm text-slate-500">
          Mỗi ngày một lối vào — chọn ca sáng / chiều trên màn điểm danh chi tiết.
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
    <div v-else-if="!dayCards.length" class="rounded-2xl border border-slate-200 bg-white py-16 text-center text-sm text-slate-500">
      Không có ngày vận hành trong tháng này.
    </div>
    <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <button
        v-for="d in dayCards"
        :key="d.id"
        class="group rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-va-800/30 hover:shadow-md"
        @click="openDay(d)"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ weekday(d.scheduled_date) }}</div>
            <div class="text-lg font-bold text-slate-900">{{ formatDate(d.scheduled_date) }}</div>
          </div>
          <span
            v-if="d.slot_summary"
            class="shrink-0 max-w-[8.5rem] truncate rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600"
            :title="d.slot_summary"
          >
            {{ d.slot_summary }}
          </span>
        </div>
        <p v-if="d.slot_detail" class="mt-1 text-xs text-slate-500">{{ d.slot_detail }}</p>
        <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-sm">
          <span class="flex items-center gap-1.5 text-slate-600"><UsersIcon class="h-4 w-4 text-slate-400" /> Sĩ số {{ d.expected_count }}</span>
          <span class="flex items-center gap-1 font-medium text-va-800 group-hover:underline">
            Điểm danh <ArrowRightIcon class="h-3.5 w-3.5" />
          </span>
        </div>
        <div class="mt-2 flex items-center gap-1.5 truncate text-xs text-slate-500">
          <UserCircleIcon class="h-4 w-4 shrink-0 text-slate-400" /> {{ d.effective_driver?.full_name || 'Chưa gán tài xế' }}
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
import { slotsForProgram } from '../../../composables/tpProgramSlots'
import { showAppErrorFromApi } from '../../../composables/appMessage'

const props = defineProps({ program: { type: Object, required: true } })
const router = useRouter()
const loading = ref(false)
const days = ref([])
const month = ref(new Date().toISOString().slice(0, 7))

const WD = ['Chủ nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy']

const dayCards = computed(() => {
  const slots = slotsForProgram(props.program)
  const multi = slots.length > 1

  return days.value
    .filter((d) => d.day_type === 'operating')
    .map((day) => {
      let slot_summary = ''
      let slot_detail = ''
      if (multi) {
        slot_summary = `${slots.length} ca`
        slot_detail = slots
          .map((s) => {
            const time = s.departure ? ` ${s.departure}` : ''
            return `${s.label}${time}`
          })
          .join(' · ')
      } else if (slots[0]?.departure) {
        slot_detail = `${slots[0].label} · ${slots[0].departure}`
      }

      return { ...day, slot_summary, slot_detail }
    })
    .sort((a, b) => a.scheduled_date.localeCompare(b.scheduled_date))
})

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

function openDay(day) {
  router.push({ name: 'tpDayAttendance', params: { dayId: day.id } })
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
