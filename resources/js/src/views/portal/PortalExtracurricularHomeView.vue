<template>
  <div class="mx-auto max-w-7xl space-y-4 px-4 py-4 sm:px-6 sm:py-6">
    <PortalDispatchSummaryBar
      variant="extracurricular"
      :loading="summaryLoading"
      :summary="summary"
      @quick-filter="onKpiGoList"
    />

    <div class="grid gap-3 sm:grid-cols-2">
      <RouterLink
        :to="{ name: 'portalExtracurricularList' }"
        class="flex min-h-[88px] flex-col justify-center rounded-xl border border-slate-200/80 bg-white px-5 py-4 shadow-sm transition hover:border-va-200 hover:shadow-md"
        data-testid="portal-ec-hub-list"
      >
        <span class="text-base font-semibold text-slate-900">{{ t('portal.extracurricular_module.card_list_title') }}</span>
        <span class="mt-1 text-sm text-slate-600">{{ t('portal.extracurricular_module.card_list_hint') }}</span>
      </RouterLink>
      <RouterLink
        :to="{ name: 'portalExtracurricularCreate' }"
        class="flex min-h-[88px] flex-col justify-center rounded-xl border border-slate-200/80 bg-white px-5 py-4 shadow-sm transition hover:border-va-200 hover:shadow-md"
        data-testid="portal-ec-hub-create"
      >
        <span class="text-base font-semibold text-slate-900">{{ t('portal.extracurricular_module.card_create_title') }}</span>
        <span class="mt-1 text-sm text-slate-600">{{ t('portal.extracurricular_module.card_create_hint') }}</span>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getPortalRequestsSummary } from '../../api/requests'
import PortalDispatchSummaryBar from '../../components/portal/PortalDispatchSummaryBar.vue'

const { t } = useI18n()
const router = useRouter()

const summary = ref(null)
const summaryLoading = ref(true)

async function loadSummary() {
  summaryLoading.value = true
  try {
    summary.value = await getPortalRequestsSummary({ module: 'extracurricular' })
  } catch {
    summary.value = null
  } finally {
    summaryLoading.value = false
  }
}

function onKpiGoList(payload) {
  router.push({
    name: 'portalExtracurricularList',
    query: payload?.key ? { kpi: payload.key } : {},
  })
}

onMounted(() => {
  loadSummary()
})
</script>
