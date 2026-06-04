<template>
  <div class="space-y-4 md:space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl md:text-2xl">
          {{ t('p2p_policy_page.routes_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500">{{ t('p2p_policy_page.routes_subtitle') }}</p>
      </div>
      <button type="button" class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50" :disabled="loading" @click="load">
        <ArrowPathIcon class="h-4 w-4" :class="loading ? 'animate-spin' : ''" /> {{ t('p2p_policy_page.refresh') }}
      </button>
    </div>

    <div v-if="loading" class="flex items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> {{ t('p2p_policy_page.loading') }}
    </div>
    <div v-else-if="!items.length" class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-center">
      <MapPinIcon class="mb-3 h-12 w-12 text-slate-300" />
      <p class="text-sm font-medium text-slate-600">{{ t('p2p_policy_page.empty') }}</p>
    </div>

    <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="r in items" :key="r.id" class="rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm transition hover:shadow-md">
        <div class="flex items-start gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-100">
            <MapPinIcon class="h-5 w-5 text-teal-600" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="truncate font-semibold text-slate-900">{{ r.name }}</div>
            <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">
              <span class="inline-flex items-center rounded-md bg-indigo-100 px-2 py-0.5 text-indigo-700">
                {{ t('p2p_policy_page.route_policy_count', { n: r.policy_students_count }) }}
              </span>
              <span v-if="r.stops_count != null" class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5">
                {{ t('p2p_policy_page.route_stops_count', { n: r.stops_count }) }}
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
import { listPolicyRoutes } from '../../api/p2p'
import { showAppErrorFromApi } from '../../composables/appMessage'

const { t } = useI18n()
const loading = ref(false)
const items = ref([])

async function load() {
  loading.value = true
  try {
    items.value = (await listPolicyRoutes())?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
