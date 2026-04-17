<template>
  <div class="space-y-4 sm:space-y-6">
    <Card title="Lọc nhật ký hoạt động">
      <p class="mb-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
        Nhật ký ghi lại thao tác và lượt truy cập hệ thống. Chọn loại sự kiện và khoảng thời gian để thu hẹp kết quả.
        Để trống ngày nếu không cần giới hạn theo thời gian.
      </p>
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <Select
          v-model="filters.event"
          label="Loại sự kiện"
          hint="«Tất cả» tải mọi loại — có thể chậm hơn khi dữ liệu lớn."
          placeholder=""
        >
          <option value="">Tất cả sự kiện</option>
          <option v-for="e in auditEventPresets" :key="e.value" :value="e.value">{{ e.label }}</option>
        </Select>
        <Input
          v-model="filters.actor_id"
          label="Mã người thực hiện"
          type="number"
          placeholder="Ví dụ: 12"
          hint="Số định danh tài khoản trong hệ thống (bảng người dùng)."
        />
        <Input
          v-model="filters.from"
          label="Từ ngày"
          type="date"
          placeholder=""
          hint="Lọc theo ngày tạo bản ghi (giờ trên máy bạn)."
        />
        <Input
          v-model="filters.to"
          label="Đến ngày"
          type="date"
          placeholder=""
          hint="Bao gồm cả ngày chọn; để trống nếu không giới hạn cuối."
        />
      </div>
      <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">
          Gợi ý: chọn «api.request» để xem các lần gọi API; đổi sang sự kiện nghiệp vụ (tạo yêu cầu, duyệt…) khi cần truy vết chi tiết.
        </p>
        <Button variant="secondary" class="w-full shrink-0 sm:w-auto" :loading="loading" @click="reload(true)">
          Áp dụng bộ lọc
        </Button>
      </div>
    </Card>

    <Card title="Danh sách nhật ký">
      <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
      <div v-else>
        <div v-if="!items.length" class="text-sm text-slate-500">
          Không có dữ liệu phù hợp hoặc bạn chưa có quyền xem nhật ký.
        </div>

        <div v-else class="space-y-2">
          <div
            v-for="l in items"
            :key="l.id"
            class="rounded-lg border border-slate-200/90 bg-white p-3 dark:border-slate-700 dark:bg-slate-900/80"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                #{{ l.id }} · {{ l.event }}
              </div>
              <div class="text-xs text-slate-500">{{ formatDate(l.created_at) }}</div>
            </div>
            <div class="mt-2 grid gap-2 text-xs text-slate-600 dark:text-slate-400 md:grid-cols-2">
              <div>
                <span class="text-slate-500">Người thực hiện:</span>
                <span class="ml-1">{{ l.actor?.name ?? l.actor_id ?? '—' }}</span>
              </div>
              <div class="truncate">
                <span class="text-slate-500">Đối tượng:</span>
                <span class="ml-1">{{ l.auditable_type ?? '—' }}#{{ l.auditable_id ?? '' }}</span>
              </div>
              <div class="md:col-span-2">
                <span class="text-slate-500">Chi tiết:</span>
                <span class="ml-1">{{ previewMeta(l.metadata) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 text-sm dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
          <div class="text-slate-500">Tổng: {{ meta.total ?? 0 }}</div>
          <div class="flex flex-wrap items-center gap-2">
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
import Select from '../../components/ui/Select.vue'
import { listAuditLogs } from '../../api/audit'
import { AUDIT_EVENT_PRESETS } from '../../config/systemSeedOptions'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { formatApiError } from '../../api/http'

const loading = ref(false)
const items = ref([])
const meta = ref({})
const auditEventPresets = AUDIT_EVENT_PRESETS

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
  if (m.status) parts.push(`trạng thái ${m.status}`)
  if (m.duration_ms != null) parts.push(`${m.duration_ms} ms`)
  return parts.join(' · ') || JSON.stringify(m)
}

async function reload(notify = false) {
  loading.value = true
  try {
    const params = { ...filters }
    Object.keys(params).forEach((k) => (params[k] === '' ? delete params[k] : null))
    const res = await listAuditLogs(params)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
    if (notify) showAppSuccess('Đã tải danh sách theo bộ lọc.', 'Thành công')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  filters.page = p
  reload(false)
}

onMounted(() => reload(false))
</script>
