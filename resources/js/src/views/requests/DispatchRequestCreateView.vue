<template>
  <div class="grid gap-4 pb-24 lg:grid-cols-3 lg:pb-4">
    <div class="lg:col-span-2 space-y-4">
      <Card title="Tạo yêu cầu điều vận">
        <p class="mb-3 text-sm text-slate-600">
          Chọn mẫu nhanh hoặc điền thủ công. Giờ xuất phát được kiểm tra
          <span class="font-medium">BR-001</span>
          ngay khi bạn chọn.
        </p>

        <div class="mb-4 flex flex-wrap gap-2">
          <span class="w-full text-xs font-medium text-slate-500 lg:w-auto lg:py-1">Mẫu nhanh</span>
          <button
            v-for="p in presets"
            :key="p.id"
            type="button"
            class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-left text-sm hover:border-slate-300 hover:bg-white active:scale-[0.98]"
            @click="applyPreset(p)"
          >
            {{ p.label }}
          </button>
        </div>

        <form class="grid gap-3 md:grid-cols-2" @submit.prevent="submit">
          <Select v-model="form.trip_type" label="Loại chuyến" placeholder="Chọn loại chuyến">
            <option value="door_to_door">Đưa đón (door-to-door)</option>
            <option value="point_to_point">Điểm - điểm</option>
            <option value="business">Công tác</option>
            <option value="cargo">Hàng hóa</option>
          </Select>

          <Select v-model="form.source_channel" label="Kênh" placeholder="portal / zalo / paper">
            <option value="portal">Portal</option>
            <option value="zalo">Zalo</option>
            <option value="paper">Phiếu giấy</option>
          </Select>

          <Input v-model="form.origin" label="Điểm đi" placeholder="VD: VA Tân Bình" />
          <Input v-model="form.destination" label="Điểm đến" placeholder="VD: Cơ sở Vũng Tàu" />

          <div class="md:col-span-2 space-y-2">
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-end">
              <div class="min-w-0 flex-1">
                <Input
                  v-model="form.depart_at"
                  type="datetime-local"
                  label="Giờ xuất phát"
                  :min="departMin"
                  hint="BR-001: tạo yêu cầu trước giờ xuất phát ít nhất 2 giờ (trừ lệnh gấp)."
                />
              </div>
              <div class="flex flex-wrap gap-2 pb-0.5">
                <Button variant="secondary" type="button" class="whitespace-nowrap text-xs sm:text-sm" @click="bumpDepartHours(2)">
                  +2 giờ
                </Button>
                <Button variant="secondary" type="button" class="whitespace-nowrap text-xs sm:text-sm" @click="setNextMorning(7)">
                  Sáng mai 7:00
                </Button>
                <Button variant="secondary" type="button" class="whitespace-nowrap text-xs sm:text-sm" @click="setNextMorning(13)">
                  Mai 13:00
                </Button>
              </div>
            </div>
          </div>

          <Input v-model="form.arrive_by" type="datetime-local" label="Giờ đến (dự kiến)" />

          <Input v-model="form.passenger_count" type="number" label="Số người / số khách" placeholder="Bỏ trống nếu hàng hóa" />

          <label class="flex items-center gap-2 rounded-md border bg-white px-3 py-2 md:col-span-2">
            <input v-model="form.is_urgent" type="checkbox" class="h-4 w-4 shrink-0" />
            <span class="text-sm">Lệnh gấp (bỏ qua BR-001)</span>
          </label>

          <div
            v-if="br001.kind !== 'skipped'"
            role="status"
            class="md:col-span-2 rounded-lg border px-3 py-2.5 text-sm"
            :class="br001Ui.boxClass"
          >
            <div class="font-medium" :class="br001Ui.titleClass">{{ br001Ui.title }}</div>
            <p class="mt-0.5 text-slate-700">{{ br001.message }}</p>
          </div>

          <label class="block md:col-span-2">
            <div class="mb-1 text-xs font-medium text-slate-600">Ghi chú</div>
            <textarea
              v-model="form.notes"
              rows="4"
              class="w-full rounded-md border bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring"
              placeholder="Mô tả nhu cầu, người đi, khối lượng hàng…"
            />
          </label>

          <div
            v-if="form.source_channel === 'paper'"
            class="md:col-span-2 rounded-lg border border-amber-200 bg-amber-50/80 px-3 py-3 text-sm text-amber-950"
          >
            <div class="font-medium">Phiếu giấy</div>
            <p class="mt-1 text-amber-900/90">
              Sau khi tạo yêu cầu, có thể bổ sung <strong>ảnh scan / PDF</strong> tại trang chi tiết. Giai đoạn sau: OCR tự điền
              mã phiếu &amp; nội dung (đang lập kế hoạch).
            </p>
          </div>

          <div
            class="md:col-span-2 flex flex-col gap-3 border-t border-slate-100 pt-4 lg:flex-row lg:items-center lg:justify-between"
          >
            <div v-if="error" class="text-sm text-rose-600">{{ error }}</div>
            <div v-else class="hidden text-xs text-slate-400 lg:block">Mỗi lần gửi dùng Idempotency-Key — tránh trùng do double-tap.</div>
            <div class="flex w-full gap-2 lg:w-auto">
              <Button variant="secondary" type="button" class="flex-1 lg:flex-none" @click="reset">Xóa form</Button>
              <Button
                class="flex-1 lg:flex-none"
                :loading="loading"
                type="submit"
                :disabled="submitDisabled"
              >
                Tạo yêu cầu
              </Button>
            </div>
          </div>
        </form>
      </Card>
    </div>

    <div class="space-y-4">
      <Card title="Tóm tắt">
        <div v-if="created" class="space-y-2 text-sm">
          <div class="font-semibold">Đã tạo yêu cầu #{{ created.id }}</div>
          <div class="text-slate-600">Trạng thái: {{ created.status }}</div>
          <div class="text-slate-600">Phiếu giấy: {{ created.paper_status }}</div>
          <RouterLink class="inline-flex rounded-md border px-3 py-2 text-sm hover:bg-slate-50" to="/requests">
            Về danh sách
          </RouterLink>
        </div>
        <div v-else class="space-y-2 text-sm text-slate-600">
          <p>BR-001 được kiểm tra phía server khi gửi; ô màu bên trái chỉ là gợi ý tức thời.</p>
          <p class="text-xs text-slate-500">Desktop: form rộng. Mobile: cuộn xuống để gửi — đã chừa khoảng đệm dưới cùng.</p>
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { createDispatchRequest } from '../../api/requests'
import { formatApiError } from '../../api/http'
import { newIdempotencyKey } from '../../util/idempotency'
import {
  br001Status,
  minDepartDatetimeLocalValue,
  suggestBr001CompliantLocal,
  toDatetimeLocalValue,
} from '../../util/datetime'

const loading = ref(false)
const error = ref('')
const created = ref(null)

const form = ref({
  trip_type: 'point_to_point',
  source_channel: 'portal',
  origin: '',
  destination: '',
  depart_at: suggestBr001CompliantLocal(15),
  arrive_by: '',
  passenger_count: '',
  notes: '',
  is_urgent: false,
})

const departMin = computed(() => (form.value.is_urgent ? '' : minDepartDatetimeLocalValue(15)))

const br001 = computed(() => br001Status(form.value.depart_at, form.value.is_urgent))

const br001Ui = computed(() => {
  const k = br001.value.kind
  if (k === 'ok' || k === 'skipped') {
    return {
      title: k === 'skipped' ? 'Lệnh gấp' : 'BR-001: đạt',
      boxClass: k === 'skipped' ? 'border-slate-200 bg-slate-50' : 'border-emerald-200 bg-emerald-50/80',
      titleClass: k === 'skipped' ? 'text-slate-800' : 'text-emerald-900',
    }
  }
  if (k === 'viol') {
    return {
      title: 'BR-001: chưa đạt',
      boxClass: 'border-rose-200 bg-rose-50',
      titleClass: 'text-rose-800',
    }
  }
  return {
    title: 'BR-001',
    boxClass: 'border-slate-200 bg-slate-50',
    titleClass: 'text-slate-800',
  }
})

const submitDisabled = computed(() => {
  if (loading.value) return true
  if (form.value.is_urgent) return false
  return br001.value.kind === 'viol' || br001.value.kind === 'empty' || br001.value.kind === 'invalid'
})

const presets = [
  {
    id: 'school',
    label: 'Đi học (đưa đón)',
    patch: {
      trip_type: 'door_to_door',
      source_channel: 'portal',
      passenger_count: '40',
      notes: 'Đưa đón học sinh — ghi rõ khối/lớp và điểm tập trung.',
    },
  },
  {
    id: 'work',
    label: 'Công tác',
    patch: {
      trip_type: 'business',
      source_channel: 'portal',
      passenger_count: '4',
      notes: 'Công tác nội bộ — ghi mục đích, người tham dự.',
    },
  },
  {
    id: 'cargo',
    label: 'Hàng hóa',
    patch: {
      trip_type: 'cargo',
      source_channel: 'portal',
      passenger_count: '',
      notes: 'Chuyển hàng nội bộ — ghi số kiện, kích thước/weight (ước lượng).',
    },
  },
]

function applyPreset(p) {
  error.value = ''
  created.value = null
  form.value = {
    ...form.value,
    ...p.patch,
    passenger_count: p.patch.passenger_count ?? '',
  }
  if (!form.value.depart_at) {
    form.value.depart_at = suggestBr001CompliantLocal(15)
  }
}

function bumpDepartHours(h) {
  const base = form.value.depart_at ? new Date(form.value.depart_at) : new Date()
  if (Number.isNaN(base.getTime())) return
  base.setHours(base.getHours() + h)
  form.value.depart_at = toDatetimeLocalValue(base)
}

/** Ngày mai, giờ cố định; nếu không đủ BR-001 thì cộng thêm ngày. */
function setNextMorning(hour) {
  const d = new Date()
  d.setDate(d.getDate() + 1)
  d.setHours(hour, 0, 0, 0)
  let t = d.getTime()
  const min = Date.now() + 2 * 60 * 60 * 1000
  while (t < min) {
    d.setDate(d.getDate() + 1)
    d.setHours(hour, 0, 0, 0)
    t = d.getTime()
  }
  form.value.depart_at = toDatetimeLocalValue(d)
}

function reset() {
  error.value = ''
  created.value = null
  form.value = {
    trip_type: 'point_to_point',
    source_channel: 'portal',
    origin: '',
    destination: '',
    depart_at: suggestBr001CompliantLocal(15),
    arrive_by: '',
    passenger_count: '',
    notes: '',
    is_urgent: false,
  }
}

function toIsoMaybe(v) {
  if (!v) return null
  try {
    return new Date(v).toISOString()
  } catch {
    return v
  }
}

let submitInFlight = false

async function submit() {
  if (submitInFlight || loading.value) return
  error.value = ''
  created.value = null
  submitInFlight = true
  loading.value = true
  const idempotencyKey = newIdempotencyKey()
  try {
    const payload = {
      ...form.value,
      depart_at: toIsoMaybe(form.value.depart_at),
      arrive_by: form.value.arrive_by ? toIsoMaybe(form.value.arrive_by) : null,
      passenger_count: form.value.passenger_count ? Number(form.value.passenger_count) : null,
      is_urgent: !!form.value.is_urgent,
    }
    Object.keys(payload).forEach((k) => (payload[k] === '' ? delete payload[k] : null))
    created.value = await createDispatchRequest(payload, { idempotencyKey })
  } catch (e) {
    error.value = formatApiError(e, 'Tạo yêu cầu thất bại.')
  } finally {
    loading.value = false
    submitInFlight = false
  }
}

watch(
  () => form.value.is_urgent,
  (urgent) => {
    if (!urgent && form.value.depart_at) {
      const minStr = minDepartDatetimeLocalValue(15)
      const cur = new Date(form.value.depart_at).getTime()
      const minT = new Date(minStr).getTime()
      if (!Number.isNaN(cur) && !Number.isNaN(minT) && cur < minT) {
        form.value.depart_at = minStr
      }
    }
  },
)
</script>
