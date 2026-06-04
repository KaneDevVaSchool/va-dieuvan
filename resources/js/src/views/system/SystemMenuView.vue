<template>
  <div class="mx-auto max-w-4xl space-y-4 pb-8">
    <Card title="Menu động">
      <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
        Khi chưa có bản ghi menu trong DB, SPA vẫn dùng <code class="text-xs">nav.js</code>.
        API <code class="text-xs">GET /menu/me</code> trả cây từ DB khi đã seed.
      </p>
      <p v-if="loading" class="text-sm text-slate-500">Đang tải…</p>
      <ul v-else class="space-y-2 text-sm">
        <li
          v-for="item in flatItems"
          :key="item.id"
          class="rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700"
        >
          <span class="font-medium">{{ item.label }}</span>
          <span class="ml-2 text-slate-500">({{ item.type }})</span>
          <span v-if="item.route_name" class="block text-xs text-slate-400">{{ item.route_name }}</span>
          <span v-if="item.url" class="block text-xs text-slate-400">{{ item.url }}</span>
        </li>
      </ul>
      <p v-if="!loading && !flatItems.length" class="text-sm text-slate-500">
        Chưa có mục menu — thêm qua API hoặc seeder.
      </p>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Card from '../../components/ui/Card.vue'
import { listMenuItems } from '../../api/system'

const loading = ref(true)
const items = ref([])

const flatItems = computed(() => items.value?.items ?? items.value ?? [])

onMounted(async () => {
  loading.value = true
  try {
    const data = await listMenuItems()
    items.value = data.items ?? data ?? []
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
})
</script>
