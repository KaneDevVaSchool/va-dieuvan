<template>
  <div class="space-y-4">
    <Card title="Hộp thông báo">
      <div class="mb-3 flex flex-wrap items-center gap-2">
        <Button variant="secondary" type="button" :loading="loading" @click="load">Làm mới</Button>
        <Button variant="secondary" type="button" :loading="markingAll" @click="markAll">Đánh dấu đã đọc hết</Button>
      </div>
      <p v-if="error" class="text-sm text-rose-600">{{ error }}</p>
      <div v-if="!loading && !items.length" class="text-sm text-slate-500">Chưa có thông báo.</div>
      <ul class="divide-y divide-slate-100">
        <li v-for="n in items" :key="n.id" class="py-3">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
              <div class="text-sm font-medium text-slate-900">{{ n.data?.title ?? n.type }}</div>
              <div class="mt-0.5 text-sm text-slate-600">{{ n.data?.body ?? '' }}</div>
              <div class="mt-1 text-xs text-slate-400">{{ fmt(n.created_at) }}</div>
              <RouterLink
                v-if="n.data?.dispatch_request_id"
                class="mt-2 inline-block text-xs font-medium text-slate-900 underline"
                :to="`/requests/${n.data.dispatch_request_id}`"
              >
                Mở yêu cầu #{{ n.data.dispatch_request_id }}
              </RouterLink>
            </div>
            <div class="shrink-0">
              <span
                v-if="!n.read"
                class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-900"
              >
                Mới
              </span>
              <Button
                v-if="!n.read"
                variant="secondary"
                type="button"
                class="mt-2 text-xs"
                :loading="markingId === n.id"
                @click="markOne(n.id)"
              >
                Đã đọc
              </Button>
            </div>
          </div>
        </li>
      </ul>
    </Card>

    <Card title="Gợi ý liên quan">
      <ul class="grid gap-2 text-sm md:grid-cols-2">
        <li>
          <RouterLink class="text-slate-900 underline hover:text-slate-600" :to="{ path: '/requests', query: { status: 'pending' } }">
            Yêu cầu chờ duyệt
          </RouterLink>
        </li>
        <li>
          <RouterLink class="text-slate-900 underline hover:text-slate-600" to="/schedule">Lịch chuyến 7 ngày</RouterLink>
        </li>
        <li>
          <RouterLink class="text-slate-900 underline hover:text-slate-600" to="/cargo">Theo dõi cargo</RouterLink>
        </li>
        <li>
          <RouterLink class="text-slate-900 underline hover:text-slate-600" to="/costs">Danh sách chi phí</RouterLink>
        </li>
      </ul>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import { listInbox, markAllNotificationsRead, markNotificationRead } from '../../api/notifications'

const loading = ref(false)
const markingAll = ref(false)
const markingId = ref(null)
const error = ref('')
const items = ref([])

function fmt(v) {
  return v ? new Date(v).toLocaleString('vi-VN') : ''
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await listInbox({ per_page: 30 })
    items.value = res.items ?? []
  } catch (e) {
    error.value = e?.response?.data?.message ?? 'Không tải được thông báo.'
    items.value = []
  } finally {
    loading.value = false
  }
}

async function markOne(id) {
  markingId.value = id
  try {
    await markNotificationRead(id)
    await load()
  } finally {
    markingId.value = null
  }
}

async function markAll() {
  markingAll.value = true
  try {
    await markAllNotificationsRead()
    await load()
  } finally {
    markingAll.value = false
  }
}

onMounted(load)
</script>
