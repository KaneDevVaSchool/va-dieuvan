<template>
  <div class="space-y-4 md:space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
          {{ t('p2p_policy_page.routes_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.routes_subtitle') }}</p>
      </div>
      <button
        type="button"
        class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
        :disabled="loading"
        @click="load"
      >
        <ArrowPathIcon class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
        Làm mới
      </button>
    </div>

    <div v-if="loading" class="flex items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-sm text-slate-500 dark:border-slate-800 dark:bg-slate-900/80">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> {{ t('p2p_policy_page.loading') }}
    </div>

    <div v-else-if="!items.length" class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-center dark:border-slate-800 dark:bg-slate-900/80">
      <MapPinIcon class="mb-3 h-12 w-12 text-slate-300 dark:text-slate-600" />
      <p class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ t('p2p_policy_page.empty') }}</p>
      <p class="mt-1 text-xs text-slate-400">Chưa có tuyến active hoặc chưa có quyền xem.</p>
    </div>

    <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="r in items"
        :key="r.id"
        class="rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-slate-700 dark:bg-slate-900/80"
      >
        <div class="flex items-start gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-100 dark:bg-teal-950/40">
            <MapPinIcon class="h-5 w-5 text-teal-600 dark:text-teal-300" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="font-semibold text-slate-900 dark:text-slate-100 truncate">{{ r.name }}</div>
            <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500 dark:text-slate-400">
              <span v-if="r.versions_count != null" class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 dark:bg-slate-800">
                {{ t('routes_page.versions', { n: r.versions_count }) }}
              </span>
              <span v-if="r.stops_count != null" class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 dark:bg-slate-800">
                {{ r.stops_count }} điểm dừng
              </span>
              <span v-if="r.policy_students_count != null" class="inline-flex items-center rounded-md bg-indigo-100 px-2 py-0.5 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
                {{ r.policy_students_count }} HS policy
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowPathIcon, MapPinIcon } from '@heroicons/vue/24/outline'
import { listPolicyRoutes } from '../../api/p2p.js'

const { t } = useI18n()
const loading = ref(false)
const items = ref([])

async function load() {
  loading.value = true
  try {
    const res = await listPolicyRoutes()
    items.value = res?.items ?? res ?? []
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
