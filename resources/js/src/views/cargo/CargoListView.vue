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

    <Card title="Danh sách cargo">
      <div class="mb-3">
        <Input v-model="filters.status" label="Trạng thái" placeholder="pending" />
        <Button class="mt-2" variant="secondary" :loading="loading" @click="reload">Lọc</Button>
      </div>
      <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
      <div v-else class="space-y-2">
        <div v-for="s in items" :key="s.id" class="space-y-3 rounded-lg border p-3 text-sm">
          <div>
            <div class="font-semibold">#{{ s.id }} {{ s.status }}</div>
            <div class="text-slate-600">{{ s.pickup_address }} → {{ s.delivery_address }}</div>
            <div v-if="s.sla_due_at" class="text-xs text-slate-500">SLA: {{ fmt(s.sla_due_at) }}</div>
          </div>

          <div v-if="(s.attachments ?? []).length" class="rounded-lg bg-slate-50 p-3">
            <div class="text-xs font-medium text-slate-600">POD đã có ({{ s.attachments.length }})</div>
            <ul class="mt-2 space-y-3">
              <li
                v-for="pod in s.attachments"
                :key="pod.id"
                class="flex flex-wrap items-start gap-3 border-b border-slate-200 pb-3 last:border-0 last:pb-0"
              >
                <div class="min-w-0 flex-1">
                  <a
                    v-if="pod.url"
                    :href="pod.url"
                    target="_blank"
                    rel="noopener"
                    class="break-all text-sm font-medium text-slate-900 underline"
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
    </Card>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import FileUpload from '../../components/ui/FileUpload.vue'
import { uploadCargoPod } from '../../api/attachments'
import { createCargoShipment, listCargoShipments } from '../../api/cargo'

const loading = ref(false)
const items = ref([])
const meta = ref({})
const filters = reactive({ status: '', page: 1, per_page: 30 })

const form = ref({ pickup_address: '', delivery_address: '', sender_name: '', receiver_name: '' })
const creating = ref(false)
const msg = ref('')

function fmt(v) {
  return v ? new Date(v).toLocaleString('vi-VN') : ''
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

async function create() {
  msg.value = ''
  creating.value = true
  try {
    await createCargoShipment(form.value)
    msg.value = 'Đã tạo'
    await reload()
  } catch (e) {
    msg.value = e?.response?.data?.message ?? 'Lỗi'
  } finally {
    creating.value = false
  }
}

onMounted(reload)
</script>
