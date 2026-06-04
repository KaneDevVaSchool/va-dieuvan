<template>
  <div class="mx-auto max-w-5xl space-y-5 pb-28">
    <!-- Header -->
    <div class="flex items-center gap-3">
      <button
        type="button"
        class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50"
        aria-label="Quay lại"
        @click="goBack"
      >
        <ArrowLeftIcon class="h-5 w-5" />
      </button>
      <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Tạo Chương trình Đưa đón</h1>
        <p class="mt-0.5 text-sm text-slate-500">Hệ thống sẽ tự sinh lịch vận hành theo cấu hình bên dưới.</p>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <header class="flex items-center gap-3">
          <span class="grid h-9 w-9 place-items-center rounded-xl bg-va-800/10 text-sm font-bold text-va-800">1</span>
          <div>
            <h2 class="text-sm font-semibold text-slate-900">Thông tin cơ bản</h2>
            <p class="text-xs text-slate-500">Tên và mô tả chương trình</p>
          </div>
        </header>
        <Input v-model="form.name" label="Tên chương trình" required placeholder="Đưa đón sáng Khối 1" />
        <Input v-model="form.code" label="Mã (để trống = tự sinh)" placeholder="TP-..." />
        <Input v-model="form.description" label="Mô tả" />
      </section>

      <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <header class="flex items-center gap-3">
          <span class="grid h-9 w-9 place-items-center rounded-xl bg-sky-50 text-sky-600">
            <MapPinIcon class="h-5 w-5" />
          </span>
          <div>
            <h2 class="text-sm font-semibold text-slate-900">Hành trình</h2>
            <p class="text-xs text-slate-500">Điểm đi, điểm đến và khung giờ</p>
          </div>
        </header>
        <Input v-model="form.origin_name" label="Điểm đi" placeholder="Quận Tây Hồ" />
        <Input v-model="form.destination_name" label="Điểm đến" placeholder="Trường" />
        <div class="grid grid-cols-2 gap-3">
          <Input v-model="form.departure_time" type="time" label="Giờ đi" required />
          <Input v-model="form.return_time" type="time" label="Giờ về" />
        </div>
      </section>

      <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <header class="flex items-center gap-3">
          <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-50 text-emerald-600">
            <CalendarDaysIcon class="h-5 w-5" />
          </span>
          <div>
            <h2 class="text-sm font-semibold text-slate-900">Lịch vận hành</h2>
            <p class="text-xs text-slate-500">Khoảng ngày và các buổi chạy trong tuần</p>
          </div>
        </header>
        <div class="grid grid-cols-2 gap-3">
          <Input v-model="form.start_date" type="date" label="Từ ngày" required />
          <Input v-model="form.end_date" type="date" label="Đến ngày" required />
        </div>
        <div>
          <div class="mb-1.5 text-xs font-medium text-slate-600">Chạy các ngày</div>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="d in weekdays"
              :key="d.key"
              type="button"
              :class="[
                'rounded-lg border px-3 py-1.5 text-xs font-medium transition',
                form.runs_on.includes(d.key)
                  ? 'border-va-800 bg-va-800 text-white shadow-sm'
                  : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300',
              ]"
              @click="toggleDay(d.key)"
            >
              {{ d.label }}
            </button>
          </div>
        </div>
      </section>

      <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <header class="flex items-center gap-3">
          <span class="grid h-9 w-9 place-items-center rounded-xl bg-amber-50 text-amber-600">
            <TruckIcon class="h-5 w-5" />
          </span>
          <div>
            <h2 class="text-sm font-semibold text-slate-900">Tài xế &amp; chi phí mặc định</h2>
            <p class="text-xs text-slate-500">Có thể điều chỉnh theo từng ngày sau khi tạo</p>
          </div>
        </header>
        <Select v-model="form.default_driver_id" label="Tài xế mặc định">
          <option value="">— Không —</option>
          <option v-for="d in drivers" :key="d.id" :value="String(d.id)">{{ d.full_name }}</option>
        </Select>
        <Select v-model="form.default_vehicle_id" label="Xe mặc định">
          <option value="">— Không —</option>
          <option v-for="v in vehicles" :key="v.id" :value="String(v.id)">{{ v.plate_number || v.name || ('#' + v.id) }}</option>
        </Select>
        <Input v-model="form.cost_per_trip" type="number" label="Chi phí mỗi chuyến (VND)" placeholder="0" />
      </section>
    </div>

    <div class="flex items-start gap-2.5 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
      <InformationCircleIcon class="mt-0.5 h-5 w-5 shrink-0 text-sky-500" />
      <span>Khoảng ngày &amp; lịch chạy đã chọn — hệ thống sẽ sinh các ngày vận hành tương ứng ngay khi lưu.</span>
    </div>

    <div class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur md:left-auto md:right-6 md:w-[30rem] md:rounded-tl-2xl md:border">
      <div class="mx-auto flex max-w-5xl items-center justify-end gap-2">
        <Button variant="secondary" @click="goBack">Hủy</Button>
        <Button :loading="saving" @click="submit">Lưu &amp; sinh lịch</Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowLeftIcon,
  MapPinIcon,
  CalendarDaysIcon,
  TruckIcon,
  InformationCircleIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { createProgram } from '../../api/transportProgram'
import { listDrivers, listVehicles } from '../../api/p2p'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

const router = useRouter()
const saving = ref(false)
const drivers = ref([])
const vehicles = ref([])

const weekdays = [
  { key: 'mon', label: 'T2' },
  { key: 'tue', label: 'T3' },
  { key: 'wed', label: 'T4' },
  { key: 'thu', label: 'T5' },
  { key: 'fri', label: 'T6' },
  { key: 'sat', label: 'T7' },
  { key: 'sun', label: 'CN' },
]

const form = reactive({
  name: '',
  code: '',
  description: '',
  origin_name: '',
  destination_name: '',
  departure_time: '06:30',
  return_time: '',
  start_date: '',
  end_date: '',
  runs_on: ['mon', 'tue', 'wed', 'thu', 'fri'],
  default_driver_id: '',
  default_vehicle_id: '',
  cost_per_trip: '',
})

function toggleDay(key) {
  const i = form.runs_on.indexOf(key)
  if (i >= 0) form.runs_on.splice(i, 1)
  else form.runs_on.push(key)
}

function goBack() {
  router.push({ name: 'tpPrograms' })
}

async function submit() {
  saving.value = true
  try {
    const payload = {
      ...form,
      code: form.code || undefined,
      default_driver_id: form.default_driver_id || null,
      default_vehicle_id: form.default_vehicle_id || null,
      cost_per_trip: form.cost_per_trip === '' ? null : Number(form.cost_per_trip),
      return_time: form.return_time || null,
    }
    const res = await createProgram(payload)
    showAppSuccess(`Đã tạo chương trình và sinh ${res?.day_count ?? 0} ngày vận hành.`)
    router.push({ name: 'tpProgramWorkspace', params: { id: res.program.id } })
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  try {
    drivers.value = (await listDrivers())?.items ?? []
  } catch { drivers.value = [] }
  try {
    vehicles.value = (await listVehicles())?.items ?? []
  } catch { vehicles.value = [] }
})
</script>
