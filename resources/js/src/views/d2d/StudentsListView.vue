<template>
  <div class="space-y-4">
    <Card title="Học sinh">
      <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
      <div v-else class="divide-y">
        <div v-for="s in items" :key="s.id" class="py-2 text-sm">
          <span class="font-medium">{{ s.full_name }}</span>
          <span class="ml-2 text-slate-500">{{ s.student_code ?? '' }}</span>
        </div>
        <div v-if="!items.length" class="py-4 text-slate-500">Chưa có.</div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import Card from '../../components/ui/Card.vue'
import { listStudents } from '../../api/d2d'

const loading = ref(false)
const items = ref([])

onMounted(async () => {
  loading.value = true
  try {
    const res = await listStudents({ per_page: 100 })
    items.value = res.items ?? []
  } finally {
    loading.value = false
  }
})
</script>
