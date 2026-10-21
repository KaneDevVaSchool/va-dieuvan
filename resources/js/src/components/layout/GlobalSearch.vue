<template>
  <!-- Ô tìm kiếm inline: gõ ngay trong header, kết quả sổ xuống bên dưới (không modal). -->
  <div ref="rootRef" class="relative w-full min-w-0 md:w-60">
    <MagnifyingGlassIcon
      class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 md:left-3 dark:text-slate-500"
      aria-hidden="true"
    />
    <input
      ref="inputRef"
      v-model="query"
      type="search"
      role="combobox"
      :aria-expanded="open"
      aria-controls="global-search-results"
      aria-autocomplete="list"
      class="h-10 w-full min-w-0 truncate rounded-full border border-slate-200 bg-slate-50 pl-10 pr-3.5 text-base text-slate-900 transition placeholder:text-slate-500 hover:border-slate-300 focus:border-va-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-va-700/15 md:h-9 md:rounded-lg md:pl-9 md:pr-14 md:text-sm dark:border-slate-600/80 dark:bg-slate-800/80 dark:text-slate-100 dark:placeholder:text-slate-400"
      :placeholder="t('app.search_placeholder')"
      :aria-label="t('app.search_label')"
      autocomplete="off"
      @focus="openSearch"
      @click="openSearch"
      @keydown.escape="closeSearch(true)"
      @keydown.arrow-down.prevent="moveFocus(1)"
      @keydown.arrow-up.prevent="moveFocus(-1)"
      @keydown.enter.prevent="selectFocused"
    />
    <kbd
      v-if="!open"
      class="pointer-events-none absolute right-2 top-1/2 hidden -translate-y-1/2 items-center gap-0.5 rounded border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-medium text-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-500 md:flex"
      aria-hidden="true"
    >
      <span>{{ isMac ? '⌘' : 'Ctrl' }}</span><span>K</span>
    </kbd>
  </div>

  <!-- Dropdown kết quả: teleport ra body để không bị header (backdrop-blur) cắt; vị trí bám theo ô nhập. -->
  <Teleport to="body">
    <div
      v-if="open"
      ref="panelRef"
      class="fixed z-[250] overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xl ring-1 ring-slate-900/5 print:hidden dark:border-slate-700/90 dark:bg-slate-900"
      :style="panelStyle"
    >
        <ul
          id="global-search-results"
          class="max-h-[min(24rem,60dvh)] overflow-y-auto py-2"
          role="listbox"
          :aria-label="t('app.search_label')"
        >
          <li
            v-if="results.length === 0"
            class="px-4 py-8 text-center text-sm text-slate-400 dark:text-slate-500"
            role="option"
            aria-selected="false"
          >
            {{ t('app.search_no_results') }}
          </li>
          <li
            v-for="(item, idx) in results"
            :key="item.to"
            role="option"
            :aria-selected="focusedIdx === idx"
            class="mx-2 cursor-pointer rounded-xl px-3 py-2.5 transition"
            :class="focusedIdx === idx
              ? 'bg-sky-50 text-sky-900 dark:bg-sky-950/50 dark:text-sky-100'
              : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800/60'"
            @click="navigate(item)"
            @mouseenter="focusedIdx = idx"
          >
            <div class="flex items-center gap-3">
              <div
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg"
                :class="focusedIdx === idx
                  ? 'bg-sky-100 text-sky-600 dark:bg-sky-900/60 dark:text-sky-300'
                  : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'"
                aria-hidden="true"
              >
                <DocumentTextIcon class="h-4 w-4" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="break-words text-sm font-medium leading-snug">{{ item.label }}</p>
                <p
                  v-if="item.sectionLabel"
                  class="truncate text-[11px] text-slate-400 dark:text-slate-500"
                >
                  {{ item.sectionLabel }}
                </p>
              </div>
            </div>
          </li>
        </ul>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { MagnifyingGlassIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'
import { useNavSections } from '../../composables/useNavSections'

const { t, locale } = useI18n()
const router = useRouter()
const { sections } = useNavSections()

const open = ref(false)
const query = ref('')
const rootRef = ref(null)
const inputRef = ref(null)
const panelRef = ref(null)
const focusedIdx = ref(0)
const panelStyle = ref({})

/** Mobile: dropdown rộng đúng bằng ô nhập; md+: canh phải theo ô nhập, tối thiểu 352px. */
function placePanel() {
  const rect = inputRef.value?.getBoundingClientRect()
  if (!rect) return
  const top = `${Math.round(rect.bottom + 8)}px`
  if (window.innerWidth < 768) {
    panelStyle.value = { top, left: `${Math.round(rect.left)}px`, width: `${Math.round(rect.width)}px` }
  } else {
    const width = Math.max(rect.width, 352)
    panelStyle.value = { top, left: `${Math.round(Math.max(12, rect.right - width))}px`, width: `${width}px` }
  }
}

function onPointerDownOutside(e) {
  if (!open.value) return
  if (rootRef.value?.contains(e.target) || panelRef.value?.contains(e.target)) return
  closeSearch()
}

const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform)

/** Flatten all navigable nav items (with `to`) for search */
const allItems = computed(() => {
  const out = []
  for (const sec of sections.value) {
    const sectionLabel = sec.headingKey ? t(sec.headingKey) : ''
    for (const item of sec.items) {
      if (item.to) {
        out.push({ to: item.to, label: t(item.labelKey), sectionLabel })
      }
      if (item.children) {
        for (const child of item.children) {
          if (child.to) {
            out.push({ to: child.to, label: t(child.labelKey), sectionLabel: t(item.labelKey) })
          }
        }
      }
    }
  }
  return out
})

const results = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return allItems.value.slice(0, 8)
  return allItems.value
    .filter((item) =>
      item.label.toLowerCase().includes(q) ||
      item.sectionLabel.toLowerCase().includes(q),
    )
    .slice(0, 8)
})

async function openSearch() {
  if (!open.value) {
    focusedIdx.value = 0
    open.value = true
  }
  placePanel()
  await nextTick()
  if (document.activeElement !== inputRef.value) inputRef.value?.focus()
}

function closeSearch(blur = false) {
  open.value = false
  query.value = ''
  if (blur) inputRef.value?.blur()
}

function moveFocus(delta) {
  const len = results.value.length
  if (!len) return
  focusedIdx.value = (focusedIdx.value + delta + len) % len
}

function selectFocused() {
  const item = results.value[focusedIdx.value]
  if (item) navigate(item)
}

function navigate(item) {
  closeSearch(true)
  router.push(item.to).catch(() => {})
}

function onKeyDown(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault()
    if (open.value) {
      closeSearch(true)
    } else {
      openSearch()
    }
  }
}

watch(results, () => {
  focusedIdx.value = 0
})

onMounted(() => {
  document.addEventListener('keydown', onKeyDown)
  document.addEventListener('pointerdown', onPointerDownOutside)
  window.addEventListener('resize', placePanel)
})

onUnmounted(() => {
  document.removeEventListener('keydown', onKeyDown)
  document.removeEventListener('pointerdown', onPointerDownOutside)
  window.removeEventListener('resize', placePanel)
})
</script>
