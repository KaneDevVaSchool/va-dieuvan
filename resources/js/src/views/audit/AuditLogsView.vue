<template>
  <div class="space-y-4">
    <Card>
      <div class="grid gap-3 md:grid-cols-4">
        <Input v-model="filters.event" label="Event" placeholder="api.request" />
        <Input v-model="filters.actor_id" label="Actor ID" type="number" placeholder="1" />
        <Input v-model="filters.from" label="From" type="date" />
        <Input v-model="filters.to" label="To" type="date" />
      </div>
      <div class="mt-3 flex items-center justify-between">
        <div class="text-xs text-slate-500">
          Tip: dùng filter <b>event=api.request</b> để xem activity log tự động từ middleware.
        </div>
        <div class="flex gap-2">
          <Button variant="secondary" :loading="loading" @click="reload">Lọc</Button>
        </div>
      </div>
    </Card>

    <Card title="Audit logs">
      <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
      <div v-else>
        <div v-if="!items.length" class="text-sm text-slate-500">Không có dữ liệu hoặc bạn chưa có quyền `audit_log.view`.</div>

        <div v-else class="space-y-2">
          <div v-for="l in items" :key="l.id" class="rounded-lg border bg-white p-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="text-sm font-semibold">
                #{{ l.id }} • {{ l.event }}
              </div>
              <div class="text-xs text-slate-500">{{ formatDate(l.created_at) }}</div>
            </div>
            <div class="mt-2 grid gap-2 text-xs text-slate-600 md:grid-cols-2">
              <div>
                <span class="text-slate-500">Actor:</span>
                <span class="ml-1">{{ l.actor?.name ?? l.actor_id ?? '-' }}</span>
              </div>
              <div class="truncate">
                <span class="text-slate-500">Target:</span>
                <span class="ml-1">{{ l.auditable_type ?? '-' }}#{{ l.auditable_id ?? '' }}</span>
              </div>
              <div class="md:col-span-2">
                <span class="text-slate-500">Metadata:</span>
                <span class="ml-1">{{ previewMeta(l.metadata) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm">
          <div class="text-slate-500">Total: {{ meta.total ?? 0 }}</div>
          <div class="flex items-center gap-2">
            <Button variant="secondary" :disabled="(meta.current_page ?? 1) <= 1" @click="goPage((meta.current_page ?? 1) - 1)">
              Trước
            </Button>
            <div class="text-xs text-slate-500">
              Trang {{ meta.current_page ?? 1 }} / {{ meta.last_page ?? 1 }}
            </div>
            <Button
              variant="secondary"
              :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1)"
              @click="goPage((meta.current_page ?? 1) + 1)"
            >
              Sau
            </Button>
          </div>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import { listAuditLogs } from '../../api/audit'

const loading = ref(false)
const items = ref([])
const meta = ref({})

const filters = reactive({
  event: 'api.request',
  actor_id: '',
  from: '',
  to: '',
  page: 1,
  per_page: 50,
})

function formatDate(v) {
  if (!v) return '-'
  try {
    return new Date(v).toLocaleString('vi-VN')
  } catch {
    return String(v)
  }
}

function previewMeta(m) {
  if (!m) return '-'
  const parts = []
  if (m.method) parts.push(`${m.method} ${m.path ?? ''}`.trim())
  if (m.status) parts.push(`status=${m.status}`)
  if (m.duration_ms != null) parts.push(`${m.duration_ms}ms`)
  return parts.join(' • ') || JSON.stringify(m)
}

async function reload() {
  loading.value = true
  try {
    const params = { ...filters }
    Object.keys(params).forEach((k) => (params[k] === '' ? delete params[k] : null))
    const res = await listAuditLogs(params)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  filters.page = p
  reload()
}

onMounted(reload)
</script>

