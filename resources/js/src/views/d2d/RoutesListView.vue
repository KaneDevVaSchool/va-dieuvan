<template>
  <div class="space-y-4">
    <Card :title="t('routes_page.title')">
      <div v-if="loading" class="text-sm text-slate-500 dark:text-slate-400">{{ t('routes_page.loading') }}</div>
      <div v-else class="space-y-2">
        <div
          v-for="r in items"
          :key="r.id"
          class="rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-700"
        >
          <div class="font-semibold text-slate-900 dark:text-slate-100">{{ r.name }}</div>
          <div class="text-xs text-slate-500 dark:text-slate-400">
            {{ t('routes_page.versions', { n: r.versions_count ?? 0 }) }}
          </div>
        </div>
        <div v-if="!items.length" class="text-slate-500 dark:text-slate-400">{{ t('routes_page.empty') }}</div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import Card from '../../components/ui/Card.vue'
import { listRoutes } from '../../api/d2d'

const { t } = useI18n()
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
