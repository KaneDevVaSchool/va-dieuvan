<template>
  <div class="space-y-4">
    <!-- Defaults -->
    <div class="grid gap-3 sm:grid-cols-2">
      <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-va-800/10 text-va-800">
          <IdentificationIcon class="h-6 w-6" />
        </span>
        <div class="min-w-0">
          <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Tài xế mặc định</div>
          <div class="truncate text-base font-semibold text-slate-900">{{ program.default_driver?.full_name || '— chưa gán —' }}</div>
        </div>
      </div>
      <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-teal-50 text-teal-600">
          <TruckIcon class="h-6 w-6" />
        </span>
        <div class="min-w-0">
          <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Xe mặc định</div>
          <div class="truncate text-base font-semibold text-slate-900">{{ program.default_vehicle?.label || program.settings?.vehicle?.type || '— chưa gán —' }}</div>
        </div>
      </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
      <p class="text-sm text-slate-500">Phân công theo từng ngày (override) — ưu tiên hơn mặc định.</p>
      <input
        v-model="month"
        type="month"
        class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-base outline-none ring-va-800/20 focus:ring"
      />
    </div>

    <div v-if="loading" class="flex items-center justify-center rounded-2xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> Đang tải…
    </div>
    <div v-else-if="!rows.length" class="rounded-2xl border border-slate-200 bg-white py-16 text-center text-sm text-slate-500">
      Không có ngày vận hành trong tháng này.
    </div>
    <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
      <table class="w-full min-w-[40rem] text-left text-base">
        <thead class="bg-slate-50 text-sm uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">Ngày</th>
            <th class="px-4 py-3 font-medium">Tài xế hiệu lực</th>
            <th class="px-4 py-3 font-medium">Nguồn</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="d in rows" :key="d.id" class="hover:bg-slate-50/60">
            <td class="px-4 py-3">
              <div class="font-medium text-slate-900">{{ formatDate(d.scheduled_date) }}</div>
              <div class="text-xs text-slate-400">{{ weekday(d.scheduled_date) }}</div>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">
                  {{ initials(d.effective_driver?.full_name) }}
                </span>
                <span class="text-slate-700">{{ d.effective_driver?.full_name || '—' }}</span>
              </div>
            </td>
            <td class="px-4 py-3">
              <span v-if="d.driver_id" class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Override</span>
              <span v-else class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">Mặc định</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { IdentificationIcon, TruckIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'
import { listProgramDays } from '../../../api/transportProgram'
import { showAppErrorFromApi } from '../../../composables/appMessage'

const props = defineProps({ program: { type: Object, required: true } })
const loading = ref(false)
const days = ref([])
const month = ref(new Date().toISOString().slice(0, 7))

const WD = ['CN', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy']

const rows = computed(() => days.value.filter((d) => d.day_type !== 'cancelled'))

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

function initials(name) {
  if (!name) return '—'
  return name.trim().split(/\s+/).slice(-2).map((w) => w[0]).join('').toUpperCase()
}
function weekday(dateStr) {
  return WD[new Date(dateStr + 'T00:00:00').getDay()]
}
function formatDate(dateStr) {
  const d = new Date(dateStr + 'T00:00:00')
  return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`
}

watch(month, load)
onMounted(load)
</script>
