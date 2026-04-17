<template>
  <div class="space-y-4">
    <Card title="Tạo shipment nhanh">
      <form class="grid gap-3 md:grid-cols-2" @submit.prevent="create">
        <Input v-model="form.pickup_address" label="Lấy hàng" />
        <Input v-model="form.delivery_address" label="Giao hàng" />
        <Input v-model="form.sender_name" label="Người gửi" />
        <Input v-model="form.receiver_name" label="Người nhận" />
        <div class="md:col-span-2 flex gap-2">
          <Button :loading="creating" type="submit">Tạo</Button>
          <span v-if="msg" class="text-sm text-slate-600">{{ msg }}</span>
        </div>
      </form>
    </Card>

    <Card>
      <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div class="grid w-full gap-3 md:grid-cols-2 lg:grid-cols-4">
          <Select v-model="filters.status" label="Trạng thái" placeholder="Tất cả">
            <option value="pending">{{ labelCargoStatus('pending') }}</option>
            <option value="picked_up">{{ labelCargoStatus('picked_up') }}</option>
            <option value="in_transit">{{ labelCargoStatus('in_transit') }}</option>
            <option value="delivered">{{ labelCargoStatus('delivered') }}</option>
            <option value="failed">{{ labelCargoStatus('failed') }}</option>
            <option value="cancelled">{{ labelCargoStatus('cancelled') }}</option>
          </Select>
          <Input v-model="filters.from" label="Từ ngày" type="date" />
          <Input v-model="filters.to" label="Đến ngày" type="date" />
          <Input v-model="filters.q" label="Tìm kiếm" placeholder="Mã, địa chỉ, người gửi/nhận" />
        </div>
        <Button variant="secondary" :loading="loading" @click="reload">Lọc</Button>
      </div>
      <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
        Khoảng ngày áp dụng cho ngày tạo shipment (gần với cột «Ngày nhận» trên phiếu hàng hóa cũ).
      </p>
    </Card>

    <Card title="Danh sách cargo">
      <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
      <div v-else class="space-y-2">
        <div v-for="s in items" :key="s.id" class="space-y-3 rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-600">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
              <div class="font-semibold text-slate-900 dark:text-slate-100">
                <span v-if="s.tracking_code" class="mr-2 font-mono text-xs text-slate-600 dark:text-slate-400">{{ s.tracking_code }}</span>
                #{{ s.id }} · {{ labelCargoStatus(s.status) }}
              </div>
              <div class="mt-1 text-slate-600 dark:text-slate-300">{{ s.pickup_address }} → {{ s.delivery_address }}</div>
              <div v-if="s.sla_due_at" class="mt-1 text-xs text-slate-500">SLA: {{ fmt(s.sla_due_at) }}</div>
            </div>
            <Button variant="secondary" class="shrink-0 text-xs" @click="toggleTimeline(s.id)">
              {{ expandedId === s.id ? 'Ẩn lịch sử' : 'Lịch sử / timeline' }}
            </Button>
          </div>

          <div v-if="expandedId === s.id" class="border-t border-slate-100 pt-3 dark:border-slate-700">
            <div v-if="timelineLoading[s.id]" class="text-xs text-slate-500">Đang tải lịch sử…</div>
            <ul v-else-if="(timelineCache[s.id] ?? []).length" class="relative ml-2 space-y-4 border-l-2 border-slate-200 pl-4 dark:border-slate-600">
              <li v-for="(ev, idx) in timelineCache[s.id]" :key="`${s.id}-${idx}-${ev.at}`" class="relative">
                <span
                  class="absolute -left-[calc(0.5rem+5px)] top-1.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-va-600 dark:border-slate-900"
                />
                <div class="flex flex-wrap justify-between gap-2 text-xs">
                  <span class="font-medium text-slate-800 dark:text-slate-200">{{ timelineTitle(ev) }}</span>
                  <span class="text-slate-500">{{ fmt(ev.at) }}</span>
                </div>
                <div v-if="timelineSubtitle(ev)" class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                  {{ timelineSubtitle(ev) }}
                </div>
                <div v-if="ev.kind === 'audit' && ev.actor?.name" class="mt-0.5 text-[11px] text-slate-500">
                  {{ ev.actor.name }}
                </div>
              </li>
            </ul>
            <p v-else class="text-xs text-slate-500">Chưa có mốc lịch sử.</p>
          </div>

          <div v-if="(s.attachments ?? []).length" class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/40">
            <div class="text-xs font-medium text-slate-600 dark:text-slate-300">POD đã có ({{ s.attachments.length }})</div>
            <ul class="mt-2 space-y-3">
              <li
                v-for="pod in s.attachments"
                :key="pod.id"
                class="flex flex-wrap items-start gap-3 border-b border-slate-200 pb-3 last:border-0 last:pb-0 dark:border-slate-600"
              >
                <div class="min-w-0 flex-1">
                  <a
                    v-if="pod.url"
                    :href="pod.url"
                    target="_blank"
                    rel="noopener"
                    class="break-all text-sm font-medium text-slate-900 underline dark:text-slate-100"
                  >
                    {{ pod.original_name || 'Mở POD' }}
                  </a>
                  <div v-if="pod.mime_type" class="text-[11px] text-slate-400">{{ pod.mime_type }}</div>
                </div>
                <img
                  v-if="pod.mime_type?.startsWith('image/') && pod.url"
                  :src="pod.url"
                  alt=""
                  class="max-h-28 max-w-[200px] rounded border object-cover"
                />
              </li>
            </ul>
          </div>
          <div v-else class="text-xs text-slate-400">Chưa có POD.</div>

          <FileUpload
            :key="`pod-${s.id}`"
            label="Upload POD"
            hint="Bằng chứng giao hàng (ảnh/PDF). Cần quyền cargo.manage."
            :upload-fn="(file, onProgress) => uploadCargoPod(s.id, file, onProgress)"
            @uploaded="reload"
          />
        </div>
        <div v-if="!items.length" class="text-slate-500">Không có.</div>
      </div>

      <div class="mt-4 flex items-center justify-between text-sm">
        <span class="text-slate-500">Total {{ meta.total ?? 0 }}</span>
        <div class="flex gap-2">
          <Button variant="secondary" :disabled="(meta.current_page ?? 1) <= 1" @click="page(-1)">Trước</Button>
          <Button variant="secondary" :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1)" @click="page(1)">
            Sau
          </Button>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import FileUpload from '../../components/ui/FileUpload.vue'
import { uploadCargoPod } from '../../api/attachments'
import { createCargoShipment, getCargoShipmentTimeline, listCargoShipments } from '../../api/cargo'
import { labelCargoStatus } from '../../util/labels'

const { t, te } = useI18n()

const loading = ref(false)
const items = ref([])
const meta = ref({})
const filters = reactive({ status: '', from: '', to: '', q: '', page: 1, per_page: 20 })

const form = ref({ pickup_address: '', delivery_address: '', sender_name: '', receiver_name: '' })
const creating = ref(false)
const msg = ref('')

const expandedId = ref(null)
const timelineCache = reactive({})
const timelineLoading = reactive({})

function fmt(v) {
  return v ? new Date(v).toLocaleString('vi-VN') : ''
}

function timelineTitle(ev) {
  if (ev.kind === 'milestone') {
    const key = `labels.cargo_timeline.${ev.code}`
    return te(key) ? t(key) : ev.code
  }
  const key = `labels.cargo_audit.${ev.code}`
  return te(key) ? t(key) : ev.code
}

function timelineSubtitle(ev) {
  if (ev.kind === 'milestone' && ev.detail) return ev.detail
  if (ev.kind === 'audit' && ev.code === 'cargo.status_change' && ev.after?.status) {
    const st = ev.after.status
    const key = `labels.cargo_status.${st}`
    return te(key) ? t(key) : st
  }
  return ''
}

async function toggleTimeline(id) {
  if (expandedId.value === id) {
    expandedId.value = null
    return
  }
  expandedId.value = id
  if (timelineCache[id]) return
  timelineLoading[id] = true
  try {
    const data = await getCargoShipmentTimeline(id)
    timelineCache[id] = data.items ?? []
  } catch {
    timelineCache[id] = []
  } finally {
    timelineLoading[id] = false
  }
}

async function reload() {
  loading.value = true
  try {
    const p = { ...filters }
    Object.keys(p).forEach((k) => (p[k] === '' ? delete p[k] : null))
    const res = await listCargoShipments(p)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
  } finally {
    loading.value = false
  }
}

function page(d) {
  filters.page = (meta.value.current_page ?? 1) + d
  reload()
}

async function create() {
  msg.value = ''
  creating.value = true
  try {
    await createCargoShipment(form.value)
    msg.value = 'Đã tạo'
    filters.page = 1
    await reload()
  } catch (e) {
    msg.value = e?.response?.data?.message ?? 'Lỗi'
  } finally {
    creating.value = false
  }
}

onMounted(reload)
</script>
