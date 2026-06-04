<template>
  <div class="mx-auto max-w-6xl space-y-6 pb-8">
    <Card title="Hệ thống — Tổng quan">
      <p v-if="loading" class="text-sm text-slate-500">Đang tải…</p>
      <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="card in cards"
          :key="card.key"
          class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900"
        >
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ card.value }}</p>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Card from '../../components/ui/Card.vue'
import { getSystemDashboard } from '../../api/system'

const loading = ref(true)
const summary = ref(null)

const cards = computed(() => {
  const s = summary.value || {}
  return [
    { key: 'users', label: 'Người dùng', value: s.users_total ?? '—' },
    { key: 'roles', label: 'Vai trò', value: s.roles_total ?? '—' },
    { key: 'roles_active', label: 'Vai trò active', value: s.roles_active ?? '—' },
    { key: 'assignments', label: 'Gán vai trò (active)', value: s.assignments_active ?? '—' },
    { key: 'audit_today', label: 'Audit hôm nay', value: s.audit_today ?? '—' },
    { key: 'audit_week', label: 'Audit 7 ngày', value: s.audit_week ?? '—' },
  ]
})

onMounted(async () => {
  loading.value = true
  try {
    summary.value = await getSystemDashboard()
  } catch {
    summary.value = null
  } finally {
    loading.value = false
  }
})
</script>
