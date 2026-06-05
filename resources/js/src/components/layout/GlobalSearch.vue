<template>
  <!-- Collapsed trigger: icon button on small screens, pill on md+ -->
  <button
    v-if="!open"
    type="button"
    class="flex items-center gap-2 rounded-lg border border-slate-200/80 bg-slate-50/90 text-slate-500 transition hover:border-slate-300 hover:bg-white hover:text-slate-700 dark:border-slate-600/80 dark:bg-slate-800/80 dark:text-slate-400 dark:hover:border-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-200"
    :class="[
      'h-9 px-2 md:px-3',
    ]"
    :aria-label="t('app.search_label')"
    @click="openSearch"
  >
    <MagnifyingGlassIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
    <span class="hidden max-w-[8rem] truncate text-sm md:inline">{{ t('app.search_placeholder') }}</span>
    <kbd
      class="hidden items-center gap-0.5 rounded border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-medium text-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-500 md:flex"
      aria-hidden="true"
    >
      <span>{{ isMac ? '⌘' : 'Ctrl' }}</span><span>K</span>
    </kbd>
  </button>

  <!-- Full search overlay (Teleport to body) -->
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-[250] flex items-start justify-center px-4 pt-[10vh] print:hidden"
    >
      <!-- Backdrop -->
      <div
        class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px]"
        @click="closeSearch"
      />

      <!-- Search panel -->
      <div
        class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl dark:border-slate-700/90 dark:bg-slate-900"
        role="dialog"
        aria-modal="true"
        :aria-label="t('app.search_label')"
      >
        <!-- Input row -->
        <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
          <MagnifyingGlassIcon class="h-5 w-5 shrink-0 text-slate-400 dark:text-slate-500" aria-hidden="true" />
          <input
            ref="inputRef"
            v-model="query"
            type="search"
            class="min-w-0 flex-1 bg-transparent text-base text-slate-900 placeholder-slate-400 focus:outline-none dark:text-slate-100 dark:placeholder-slate-500"
            :placeholder="t('app.search_placeholder')"
            :aria-label="t('app.search_label')"
            autocomplete="off"
            @keydown.escape="closeSearch"
            @keydown.arrow-down.prevent="moveFocus(1)"
            @keydown.arrow-up.prevent="moveFocus(-1)"
            @keydown.enter.prevent="selectFocused"
          />
          <kbd
            class="flex shrink-0 items-center rounded border border-slate-200 bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-400 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-500"
            aria-hidden="true"
          >
            Esc
          </kbd>
        </div>

        <!-- Results -->
        <ul
          class="py-2"
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
                <p class="truncate text-sm font-medium">{{ item.label }}</p>
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
const inputRef = ref(null)
const focusedIdx = ref(0)

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
  open.value = true
  query.value = ''
  focusedIdx.value = 0
  await nextTick()
  inputRef.value?.focus()
}

function closeSearch() {
  open.value = false
  query.value = ''
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
  closeSearch()
  router.push(item.to).catch(() => {})
}

function onKeyDown(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault()
    if (open.value) {
      closeSearch()
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
})

onUnmounted(() => {
  document.removeEventListener('keydown', onKeyDown)
})
</script>
