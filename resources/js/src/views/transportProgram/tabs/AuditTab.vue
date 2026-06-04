<template>
  <div class="space-y-3">
    <div v-if="loading" class="py-10 text-center text-sm text-slate-500">Đang tải…</div>
    <div v-else-if="!items.length" class="rounded-xl border border-slate-200 bg-white py-10 text-center text-sm text-slate-500">
      Chưa có nhật ký.
    </div>
    <ol v-else class="space-y-2">
      <li v-for="log in items" :key="log.id" class="rounded-lg border border-slate-200 bg-white p-3 text-sm">
        <div class="flex items-center justify-between">
          <span class="font-medium text-slate-800">{{ actionLabel(log.action) }} · {{ log.entity_type }}</span>
          <span class="text-xs text-slate-400">{{ formatDate(log.created_at) }}</span>
        </div>
        <div class="text-xs text-slate-500">{{ log.actor_name || 'Hệ thống' }}</div>
      </li>
    </ol>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { getProgramAudit } from '../../../api/transportProgram'
import { showAppErrorFromApi } from '../../../composables/appMessage'

const props = defineProps({ program: { type: Object, required: true } })
const loading = ref(false)
const items = ref([])

async function load() {
  loading.value = true
  try {
    items.value = (await getProgramAudit(props.program.id))?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

function actionLabel(a) {
  return { created: 'Tạo', updated: 'Cập nhật', deleted: 'Xóa', activated: 'Kích hoạt', paused: 'Tạm dừng', cancelled: 'Hủy' }[a] || a
}
function formatDate(d) {
  return d ? new Date(d).toLocaleString('vi-VN') : ''
}

onMounted(load)
</script>
