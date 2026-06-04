<template>
  <div class="mx-auto max-w-3xl space-y-4 pb-8">
    <Card :title="`Chi tiết audit #${id}`">
      <router-link
        :to="{ name: 'auditLogs' }"
        class="mb-4 inline-block text-sm text-teal-700 hover:underline dark:text-teal-400"
      >
        ← Quay lại nhật ký
      </router-link>
      <p v-if="loading" class="text-sm text-slate-500">Đang tải…</p>
      <template v-else-if="item">
        <dl class="grid gap-2 text-sm sm:grid-cols-2">
          <div><dt class="text-slate-500">Sự kiện</dt><dd class="font-medium">{{ item.event }}</dd></div>
          <div><dt class="text-slate-500">Thời gian</dt><dd>{{ item.created_at }}</dd></div>
          <div v-if="item.module"><dt class="text-slate-500">Module</dt><dd>{{ item.module }}</dd></div>
          <div v-if="item.ip_address"><dt class="text-slate-500">IP</dt><dd>{{ item.ip_address }}</dd></div>
        </dl>
        <h3 class="mt-6 text-sm font-semibold text-slate-800 dark:text-slate-200">Diff</h3>
        <ul class="mt-2 space-y-2">
          <li
            v-for="row in diff"
            :key="row.key"
            class="rounded-lg border px-3 py-2 text-sm"
            :class="diffClass(row.type)"
          >
            <span class="font-mono text-xs">{{ row.key }}</span>
            <span class="ml-2 text-xs uppercase">{{ row.type }}</span>
            <pre v-if="row.before != null" class="mt-1 text-xs text-rose-700 dark:text-rose-300">{{ fmt(row.before) }}</pre>
            <pre v-if="row.after != null" class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">{{ fmt(row.after) }}</pre>
          </li>
        </ul>
        <p v-if="!diff.length" class="mt-2 text-sm text-slate-500">Không có thay đổi before/after.</p>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import Card from '../../components/ui/Card.vue'
import { getAuditLogDetail } from '../../api/system'

const route = useRoute()
const id = computed(() => route.params.id)
const loading = ref(true)
const item = ref(null)
const diff = ref([])

function fmt(v) {
  return typeof v === 'object' ? JSON.stringify(v, null, 2) : String(v)
}

function diffClass(type) {
  if (type === 'added') return 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900'
  if (type === 'removed') return 'border-rose-200 bg-rose-50/50 dark:border-rose-900'
  return 'border-amber-200 bg-amber-50/50 dark:border-amber-900'
}

async function load() {
  loading.value = true
  try {
    const data = await getAuditLogDetail(id.value)
    item.value = data.item
    diff.value = data.diff || []
  } catch {
    item.value = null
    diff.value = []
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(id, load)
</script>
