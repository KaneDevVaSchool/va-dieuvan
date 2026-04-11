<template>
  <div class="space-y-4">
    <Card title="Tuyến door-to-door">
      <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
      <div v-else class="space-y-2">
        <div v-for="r in items" :key="r.id" class="rounded-lg border p-3 text-sm">
          <div class="font-semibold">{{ r.name }}</div>
          <div class="text-xs text-slate-500">{{ r.versions_count ?? 0 }} phiên bản</div>
        </div>
        <div v-if="!items.length" class="text-slate-500">Chưa có tuyến.</div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import Card from '../../components/ui/Card.vue'
import { listRoutes } from '../../api/d2d'

const loading = ref(false)
const items = ref([])

onMounted(async () => {
  loading.value = true
  try {
    const res = await listRoutes({ per_page: 50 })
    items.value = res.items ?? []
  } finally {
    loading.value = false
  }
})
</script>
