<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import { listPortalRequests } from '../../../api/requests'
import { formatDispatchRequestRefCode } from '../../../util/portalRequestFormat'

const { t } = useI18n()
const router = useRouter()

const open = ref(false)
const q = ref('')
const loading = ref(false)
const items = ref([])
const focusIdx = ref(-1)
const rootRef = ref(null)

let debounceTimer = null

function close() {
  open.value = false
  focusIdx.value = -1
}

async function fetchSuggest(term) {
  const trimmed = String(term ?? '').trim()
  if (!trimmed) {
    items.value = []
    return
  }
  loading.value = true
  try {
    const data = await listPortalRequests({ q: trimmed, per_page: 6, page: 1, sort: 'created_desc' })
    items.value = data.items ?? []
    focusIdx.value = items.value.length ? 0 : -1
  } catch {
    items.value = []
    focusIdx.value = -1
  } finally {
    loading.value = false
  }
}

function scheduleFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = window.setTimeout(() => fetchSuggest(q.value), 320)
}

function goList(searchQ) {
  close()
  q.value = ''
  router.push({ name: 'portalRequestList', query: searchQ ? { q: searchQ } : {} })
}

function goDetail(req) {
  close()
  q.value = ''
  const name = req.dispatch_request_template_id ? 'portalExtracurricularDetail' : 'portalRequestDetail'
  router.push({ name, params: { id: String(req.id) } })
}

function onKeydown(e) {
  if (e.key === 'Escape') {
    close()
    return
  }
  if (!open.value || !items.value.length) return
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    focusIdx.value = (focusIdx.value + 1) % items.value.length
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    focusIdx.value = (focusIdx.value - 1 + items.value.length) % items.value.length
  } else if (e.key === 'Enter') {
    e.preventDefault()
    if (focusIdx.value >= 0 && items.value[focusIdx.value]) {
      goDetail(items.value[focusIdx.value])
    } else {
      goList(String(q.value).trim())
    }
  }
}

function onDocPointerDown(e) {
  if (!rootRef.value?.contains(e.target)) close()
}

watch(q, () => {
  open.value = true
  scheduleFetch()
})

onMounted(() => {
  document.addEventListener('pointerdown', onDocPointerDown, true)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onDocPointerDown, true)
  clearTimeout(debounceTimer)
})
</script>

<template>
  <div ref="rootRef" class="relative hidden min-w-0 max-w-[24rem] flex-1 xl:block">
    <label class="sr-only" for="portal-global-search">{{ t('portal.shell.global_search_aria') }}</label>
    <MagnifyingGlassIcon
      class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
      aria-hidden="true"
    />
    <input
      id="portal-global-search"
      v-model="q"
      type="search"
      autocomplete="off"
      class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50/80 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400
             focus:border-va-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-va-700/15"
      :placeholder="t('portal.shell.global_search_placeholder')"
      :aria-label="t('portal.shell.global_search_aria')"
      data-testid="portal-global-search"
      @focus="open = true"
      @keydown="onKeydown"
    />
    <ul
      v-if="open && String(q).trim()"
      class="absolute z-[60] mt-1 max-h-64 w-full overflow-auto rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg ring-1 ring-slate-900/5"
      role="listbox"
      :aria-label="t('portal.search_suggest_aria')"
    >
      <li v-if="loading" class="px-3 py-2 text-slate-500">{{ t('portal.search_suggest_loading') }}</li>
      <template v-else-if="items.length">
        <li v-for="(req, idx) in items" :key="req.id" role="presentation">
          <button
            type="button"
            role="option"
            class="flex w-full flex-col gap-0.5 px-3 py-2 text-left transition"
            :class="idx === focusIdx ? 'bg-va-50' : 'hover:bg-slate-50'"
            @mousedown.prevent="goDetail(req)"
          >
            <span class="font-mono text-sm font-semibold text-slate-900">{{ formatDispatchRequestRefCode(req) }}</span>
            <span class="truncate text-xs text-slate-500">{{ req.origin || '—' }} → {{ req.destination || '—' }}</span>
          </button>
        </li>
      </template>
      <li v-else class="px-3 py-2 text-slate-500">{{ t('portal.search_suggest_empty') }}</li>
      <li class="border-t border-slate-100 px-2 py-1">
        <button
          type="button"
          class="w-full rounded-lg px-2 py-2 text-left text-xs font-semibold text-va-800 hover:bg-va-50"
          data-testid="portal-global-search-see-all"
          @mousedown.prevent="goList(String(q).trim())"
        >
          {{ t('portal.shell.global_search_see_all') }}
        </button>
      </li>
    </ul>
  </div>
</template>
