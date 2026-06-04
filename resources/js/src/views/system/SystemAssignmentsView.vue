<template>
  <div class="mx-auto max-w-6xl space-y-4 pb-8">
    <Card title="Gán vai trò người dùng">
      <div class="mb-4 flex flex-wrap gap-2">
        <input
          v-model="search"
          type="search"
          placeholder="Tìm tên hoặc email…"
          class="h-9 min-w-[200px] flex-1 rounded-lg border border-slate-200 px-3 text-sm dark:border-slate-600 dark:bg-slate-900"
          @keyup.enter="load"
        />
        <Button @click="load">Tìm</Button>
      </div>
      <p v-if="loading" class="text-sm text-slate-500">Đang tải…</p>
      <ul v-else class="divide-y divide-slate-100 dark:divide-slate-800">
        <li v-for="u in items" :key="u.id" class="flex flex-wrap items-center justify-between gap-2 py-3">
          <div>
            <p class="font-medium text-slate-900 dark:text-slate-100">{{ u.name }}</p>
            <p class="text-xs text-slate-500">{{ u.email }}</p>
          </div>
          <div class="flex flex-wrap gap-1">
            <span
              v-for="r in u.roles"
              :key="r.id"
              class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200"
            >
              {{ r.display_name || r.name }}
            </span>
            <span v-if="!u.roles?.length" class="text-xs text-slate-400">Chưa gán role</span>
          </div>
        </li>
      </ul>
      <p v-if="!loading && !items.length" class="text-sm text-slate-500">Không có kết quả.</p>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import { listAssignmentUsers } from '../../api/system'

const loading = ref(true)
const search = ref('')
const items = ref([])

async function load() {
  loading.value = true
  try {
    const data = await listAssignmentUsers({ q: search.value || undefined, per_page: 50 })
    items.value = data.items || []
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
