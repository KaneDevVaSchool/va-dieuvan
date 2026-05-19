<template>
  <div class="px-6 py-8 lg:px-10">
    <header class="max-w-5xl">
      <h1 class="text-2xl font-bold text-slate-900">{{ t('dept.list_approved_title') }}</h1>
    </header>
    <div v-if="error" class="mt-6 max-w-5xl rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
      {{ error }}
    </div>
    <div v-else class="mt-8 max-w-5xl space-y-4">
      <p v-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('dept.empty_approved') }}</p>
      <DeptRequestCard v-for="r in items" :key="r.id" :req="r" @detail="goDetail" />
      <div v-if="loading" class="py-8 text-center text-sm text-slate-500">{{ t('dept.loading') }}</div>
      <div v-else-if="page < lastPage" class="flex justify-center">
        <button
          type="button"
          class="rounded-xl border border-slate-200 bg-white px-6 py-2.5 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50"
          :disabled="loadingMore"
          @click="loadMore"
        >
          {{ loadingMore ? t('dept.loading') : t('dept.load_more') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { listRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import { DISPATCH_WEB_BASE } from '../../config/dispatchWebBase'
import DeptRequestCard from '../../components/dept/DeptRequestCard.vue'

const { t } = useI18n()
const router = useRouter()

const items = ref([])
const loading = ref(true)
const loadingMore = ref(false)
const error = ref('')
const page = ref(1)
const lastPage = ref(1)

async function fetchList(p, append) {
  const res = await listRequests({
    status: 'approved',
    per_page: 20,
    page: p,
    sort: 'depart_desc',
  })
  lastPage.value = res.meta?.last_page ?? 1
  if (append) items.value = items.value.concat(res.items ?? [])
  else items.value = res.items ?? []
}

async function loadMore() {
  loadingMore.value = true
  try {
    page.value += 1
    await fetchList(page.value, true)
  } catch (e) {
    error.value = formatApiError(e, t('dept.load_error'))
  } finally {
    loadingMore.value = false
  }
}

function goDetail(id) {
  router.push({ path: `${DISPATCH_WEB_BASE}/requests/${id}` })
}

onMounted(async () => {
  loading.value = true
  error.value = ''
  page.value = 1
  try {
    await fetchList(1, false)
  } catch (e) {
    error.value = formatApiError(e, t('dept.load_error'))
  } finally {
    loading.value = false
  }
})
</script>
