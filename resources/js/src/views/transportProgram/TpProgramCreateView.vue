<template>
  <div class="mx-auto max-w-4xl space-y-5 pb-24">
    <div>
      <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl md:text-2xl">Tạo chương trình đưa đón</h1>
      <p class="mt-1 text-sm text-slate-500">Hệ thống sẽ tự sinh lịch vận hành theo cấu hình bên dưới.</p>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
      <section class="space-y-3 rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-sm font-semibold text-slate-700">1. Thông tin cơ bản</h2>
        <Input v-model="form.name" label="Tên chương trình" required placeholder="Đưa đón sáng Khối 1" />
        <Input v-model="form.code" label="Mã (để trống = tự sinh)" placeholder="TP-..." />
        <Input v-model="form.description" label="Mô tả" />
      </section>

      <section class="space-y-3 rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-sm font-semibold text-slate-700">2. Hành trình</h2>
        <Input v-model="form.origin_name" label="Điểm đi" />
        <Input v-model="form.destination_name" label="Điểm đến" />
        <div class="grid grid-cols-2 gap-3">
          <Input v-model="form.departure_time" type="time" label="Giờ đi" required />
          <Input v-model="form.return_time" type="time" label="Giờ về" />
        </div>
      </section>

      <section class="space-y-3 rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-sm font-semibold text-slate-700">3. Lịch vận hành</h2>
        <div class="grid grid-cols-2 gap-3">
          <Input v-model="form.start_date" type="date" label="Từ ngày" required />
          <Input v-model="form.end_date" type="date" label="Đến ngày" required />
        </div>
        <div>
          <div class="mb-1 text-xs font-medium text-slate-600">Chạy các ngày</div>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="d in weekdays"
              :key="d.key"
              type="button"
              :class="[
                'rounded-md border px-2.5 py-1 text-xs font-medium',
                form.runs_on.includes(d.key)
                  ? 'border-va-800 bg-va-800 text-white'
                  : 'border-slate-200 bg-white text-slate-600',
              ]"
              @click="toggleDay(d.key)"
            >
              {{ d.label }}
            </button>
          </div>
        </div>
      </section>

      <section class="space-y-3 rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-sm font-semibold text-slate-700">4. Tài xế &amp; chi phí mặc định</h2>
        <Select v-model="form.default_driver_id" label="Tài xế mặc định">
          <option value="">— Không —</option>
          <option v-for="d in drivers" :key="d.id" :value="String(d.id)">{{ d.full_name }}</option>
        </Select>
        <Select v-model="form.default_vehicle_id" label="Xe mặc định">
          <option value="">— Không —</option>
          <option v-for="v in vehicles" :key="v.id" :value="String(v.id)">{{ v.plate_number || v.name || ('#' + v.id) }}</option>
        </Select>
        <Input v-model="form.cost_per_trip" type="number" label="Chi phí mỗi chuyến (VND)" />
      </section>
    </div>

    <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
      Khoảng ngày &amp; lịch chạy đã chọn — hệ thống sẽ sinh lịch khi lưu.
    </div>

    <div class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur md:left-auto md:right-6 md:w-[28rem] md:rounded-tl-xl md:border">
      <div class="mx-auto flex max-w-4xl items-center justify-end gap-2">
        <Button variant="secondary" @click="goBack">Hủy</Button>
        <Button :loading="saving" @click="submit">Lưu &amp; sinh lịch</Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
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
