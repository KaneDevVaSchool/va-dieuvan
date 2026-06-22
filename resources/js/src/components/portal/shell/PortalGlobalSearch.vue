<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { listPortalRequests } from '../../../api/requests'
import { formatDispatchRequestRefCode } from '../../../util/portalRequestFormat'

const { t } = useI18n()
const router = useRouter()

const open = ref(false)
const sheetOpen = ref(false)
const q = ref('')
const loading = ref(false)
const items = ref([])
const focusIdx = ref(-1)
const rootRef = ref(null)
const mobileInputRef = ref(null)

let debounceTimer = null

function closeInline() {
  open.value = false
  focusIdx.value = -1
}

function closeSheet() {
  sheetOpen.value = false
  focusIdx.value = -1
}

function closeAll() {
  closeInline()
  closeSheet()
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
  closeAll()
  q.value = ''
  router.push({ name: 'portalRequestList', query: searchQ ? { q: searchQ } : {} })
}

function goDetail(req) {
  closeAll()
  q.value = ''
  const name = req.dispatch_request_template_id ? 'portalExtracurricularDetail' : 'portalRequestDetail'
  router.push({ name, params: { id: String(req.id) } })
}

function onKeydown(e) {
  if (e.key === 'Escape') {
    if (sheetOpen.value) {
      closeSheet()
    } else {
      closeInline()
    }
    return
  }
  const suggestActive = sheetOpen.value || (open.value && String(q.value).trim())
  if (!suggestActive) return
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    if (!items.value.length) return
    focusIdx.value = (focusIdx.value + 1) % items.value.length
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    if (!items.value.length) return
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
  if (sheetOpen.value) return
  if (!rootRef.value?.contains(e.target)) closeInline()
}

async function openMobileSheet() {
  sheetOpen.value = true
  open.value = true
  await nextTick()
  mobileInputRef.value?.focus()
}

watch(q, () => {
  if (sheetOpen.value) {
    scheduleFetch()
    return
  }
  if (open.value) {
    scheduleFetch()
  }
})

watch(sheetOpen, (v) => {
  document.body.style.overflow = v ? 'hidden' : ''
  if (!v) closeInline()
})

onMounted(() => {
  document.addEventListener('pointerdown', onDocPointerDown, true)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onDocPointerDown, true)
  clearTimeout(debounceTimer)
  document.body.style.overflow = ''
})
</script>

<template>
  <button
    type="button"
    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 lg:hidden"
    :aria-label="t('portal.shell.mobile_search_open')"
    data-testid="portal-mobile-search-open"
    @click="openMobileSheet"
  >
    <MagnifyingGlassIcon class="h-5 w-5" aria-hidden="true" />
  </button>

  <div ref="rootRef" class="relative hidden min-w-0 max-w-[24rem] flex-1 lg:block">
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
      <li v-if="loading" class="px-3 py-2.5 text-slate-500">{{ t('portal.search_suggest_loading') }}</li>
      <template v-else-if="items.length">
        <li v-for="(req, idx) in items" :key="req.id" role="presentation">
          <button
            type="button"
            role="option"
            class="flex w-full flex-col gap-0.5 px-3 py-3 text-left transition"
            :class="idx === focusIdx ? 'bg-va-50' : 'hover:bg-slate-50'"
            @mousedown.prevent="goDetail(req)"
          >
            <span class="font-mono text-sm font-semibold text-slate-900">{{ formatDispatchRequestRefCode(req) }}</span>
            <span class="truncate text-xs text-slate-500">{{ req.origin || '—' }} → {{ req.destination || '—' }}</span>
          </button>
        </li>
      </template>
      <li v-else class="px-3 py-2.5 text-slate-500">{{ t('portal.search_suggest_empty') }}</li>
      <li class="border-t border-slate-100 px-2 py-1">
        <button
          type="button"
          class="min-h-[44px] w-full rounded-lg px-2 py-2 text-left text-xs font-semibold text-va-800 hover:bg-va-50"
          data-testid="portal-global-search-see-all"
          @mousedown.prevent="goList(String(q).trim())"
        >
          {{ t('portal.shell.global_search_see_all') }}
        </button>
      </li>
    </ul>
  </div>

  <Teleport to="body">
    <div
      v-if="sheetOpen"
      class="fixed inset-0 z-[80] flex flex-col bg-white lg:hidden
             pt-[env(safe-area-inset-top)] pb-[env(safe-area-inset-bottom)]"
      role="dialog"
      aria-modal="true"
      :aria-label="t('portal.shell.global_search_aria')"
      data-testid="portal-mobile-search-sheet"
    >
      <div class="flex items-center gap-2 border-b border-slate-100 px-3 py-2">
        <div class="relative min-w-0 flex-1">
          <MagnifyingGlassIcon
            class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
            aria-hidden="true"
          />
          <input
            ref="mobileInputRef"
            v-model="q"
            type="search"
            autocomplete="off"
            enterkeyhint="search"
            class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-3 text-base text-slate-900 placeholder:text-slate-400
                   focus:border-va-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-va-700/15"
            :placeholder="t('portal.shell.global_search_placeholder')"
            :aria-label="t('portal.shell.global_search_aria')"
            data-testid="portal-mobile-search-input"
            @keydown="onKeydown"
          />
        </div>
        <button
          type="button"
          class="inline-flex h-11 min-w-[44px] shrink-0 items-center justify-center rounded-xl px-3 text-sm font-semibold text-slate-700 hover:bg-slate-100"
          :aria-label="t('portal.shell.mobile_search_close')"
          data-testid="portal-mobile-search-close"
          @click="closeSheet"
        >
          <XMarkIcon class="h-6 w-6" aria-hidden="true" />
        </button>
      </div>

      <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-2 py-2">
        <p v-if="!String(q).trim()" class="px-3 py-4 text-sm text-slate-500">
          {{ t('portal.shell.mobile_search_hint') }}
        </p>
        <ul
          v-else
          class="overflow-hidden rounded-xl border border-slate-100 bg-white text-sm shadow-sm"
          role="listbox"
          :aria-label="t('portal.search_suggest_aria')"
        >
          <li v-if="loading" class="px-4 py-3 text-slate-500">{{ t('portal.search_suggest_loading') }}</li>
          <template v-else-if="items.length">
            <li v-for="(req, idx) in items" :key="req.id" role="presentation">
              <button
                type="button"
                role="option"
                class="flex w-full flex-col gap-1 border-b border-slate-50 px-4 py-3.5 text-left transition last:border-0"
                :class="idx === focusIdx ? 'bg-va-50' : 'active:bg-slate-50'"
                @click="goDetail(req)"
              >
                <span class="font-mono text-base font-semibold text-slate-900">{{ formatDispatchRequestRefCode(req) }}</span>
                <span class="text-sm text-slate-600">{{ req.origin || '—' }} → {{ req.destination || '—' }}</span>
              </button>
            </li>
          </template>
          <li v-else class="px-4 py-3 text-slate-500">{{ t('portal.search_suggest_empty') }}</li>
          <li v-if="String(q).trim()" class="border-t border-slate-100 p-2">
            <button
              type="button"
              class="flex min-h-[48px] w-full items-center justify-center rounded-xl bg-va-50 px-3 text-sm font-semibold text-va-900"
              data-testid="portal-mobile-search-see-all"
              @click="goList(String(q).trim())"
            >
              {{ t('portal.shell.global_search_see_all') }}
            </button>
          </li>
        </ul>
      </div>
    </div>
  </Teleport>
</template>
