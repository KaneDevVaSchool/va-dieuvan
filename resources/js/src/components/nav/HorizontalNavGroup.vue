<template>
  <div ref="rootRef" class="relative flex min-w-0 flex-1 items-stretch justify-center">
    <button
      type="button"
      class="inline-flex w-full min-w-0 items-center justify-center gap-1 rounded-md border-b-2 border-transparent px-2 py-2 text-center text-[10px] font-medium leading-tight text-slate-600 transition sm:gap-1.5 sm:px-2 sm:text-[11px] dark:text-slate-300"
      :title="fullLabel || label"
      :class="
        isGroupActive
          ? 'border-va-800 bg-va-50 text-va-900 shadow-sm dark:border-va-500 dark:bg-va-950/40 dark:text-va-100'
          : 'hover:border-slate-200 hover:bg-slate-50/90 dark:hover:border-slate-600 dark:hover:bg-slate-800/80'
      "
      :aria-expanded="open"
      aria-haspopup="menu"
      :aria-controls="panelId"
      @click="toggle"
    >
      <span class="min-w-0 truncate">{{ label }}</span>
      <ChevronDownIcon
        class="h-3.5 w-3.5 shrink-0 opacity-70 transition-transform sm:h-4 sm:w-4"
        :class="open ? 'rotate-180' : ''"
        aria-hidden="true"
      />
    </button>

    <Teleport to="body">
      <div
        v-show="open"
        :id="panelId"
        ref="panelRef"
        role="menu"
        class="fixed z-[60] overflow-hidden rounded-xl border border-slate-200/90 bg-white py-1.5 shadow-xl ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900 dark:ring-white/10"
        :style="panelStyle"
      >
        <template v-for="item in items" :key="item.to || item.labelKey">
          <template v-if="item.children?.length">
            <div
              class="border-t border-slate-100 px-3 pb-1 pt-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500 first:border-t-0 first:pt-0 dark:border-slate-700 dark:text-slate-400"
              role="presentation"
            >
              {{ labelFor(item) }}
            </div>
            <RouterLink
              v-for="child in item.children"
              :key="child.to"
              role="menuitem"
              :to="child.to"
              class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-va-50 hover:text-va-900 dark:text-slate-200 dark:hover:bg-va-950/50 dark:hover:text-va-100"
              :class="
                isItemActive(child.to)
                  ? 'bg-va-50 font-medium text-va-900 dark:bg-va-950/40 dark:text-va-100'
                  : ''
              "
              @click="open = false"
            >
              <component
                :is="iconFor(child)"
                class="h-5 w-5 shrink-0 text-slate-500 opacity-90 dark:text-slate-400"
                aria-hidden="true"
              />
              <span class="min-w-0 flex-1 truncate">{{ labelFor(child) }}</span>
              <span
                v-if="developingLabelFor?.(child)"
                class="max-w-[5.5rem] shrink-0 truncate rounded border border-amber-300/80 bg-amber-50 px-1 py-px text-[9px] font-semibold text-amber-900 dark:border-amber-600/50 dark:bg-amber-950/50 dark:text-amber-100"
              >
                {{ developingLabelFor(child) }}
              </span>
              <span
                v-if="badgeCount(child) > 0"
                class="inline-flex min-w-[1.25rem] shrink-0 items-center justify-center rounded-full bg-rose-100 px-1.5 text-[10px] font-bold text-rose-800 dark:bg-rose-900/50 dark:text-rose-100"
              >
                {{ badgeCount(child) > 99 ? '99+' : badgeCount(child) }}
              </span>
            </RouterLink>
          </template>
          <RouterLink
            v-else
            role="menuitem"
            :to="item.to"
            class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-va-50 hover:text-va-900 dark:text-slate-200 dark:hover:bg-va-950/50 dark:hover:text-va-100"
            :class="
              isItemActive(item.to)
                ? 'bg-va-50 font-medium text-va-900 dark:bg-va-950/40 dark:text-va-100'
                : ''
            "
            @click="open = false"
          >
            <component
              :is="iconFor(item)"
              class="h-5 w-5 shrink-0 text-slate-500 opacity-90 dark:text-slate-400"
              aria-hidden="true"
            />
            <span class="min-w-0 flex-1 truncate">{{ labelFor(item) }}</span>
            <span
              v-if="developingLabelFor?.(item)"
              class="max-w-[5.5rem] shrink-0 truncate rounded border border-amber-300/80 bg-amber-50 px-1 py-px text-[9px] font-semibold text-amber-900 dark:border-amber-600/50 dark:bg-amber-950/50 dark:text-amber-100"
            >
              {{ developingLabelFor(item) }}
            </span>
            <span
              v-if="badgeCount(item) > 0"
              class="inline-flex min-w-[1.25rem] shrink-0 items-center justify-center rounded-full bg-rose-100 px-1.5 text-[10px] font-bold text-rose-800 dark:bg-rose-900/50 dark:text-rose-100"
            >
              {{ badgeCount(item) > 99 ? '99+' : badgeCount(item) }}
            </span>
          </RouterLink>
        </template>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, nextTick, onUnmounted, ref, useId, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import { isDispatchStaffHomePath } from '../../config/dispatchWebBase'
import { NAV_ICON_MAP } from '../../config/navIconMap'

const panelId = useId()

const props = defineProps({
  label: { type: String, required: true },
  /** Tooltip khi nhãn trên thanh đã rút gọn */
  fullLabel: { type: String, default: null },
  items: { type: Array, required: true },
  badgeCount: { type: Function, required: true },
  /** i18n t() */
  t: { type: Function, required: true },
  /** (navItem) => string — nhãn «đang phát triển» khi bảo trì */
  developingLabelFor: { type: Function, default: null },
})

const route = useRoute()
const open = ref(false)
const rootRef = ref(null)
const panelRef = ref(null)
const panelStyle = ref({})

function iconFor(item) {
  return NAV_ICON_MAP[item.icon] ?? NAV_ICON_MAP.home
}

function labelFor(item) {
  return props.t(item.labelKey)
}

function isItemActive(to) {
  if (!to) return false
  if (isDispatchStaffHomePath(to)) {
    return isDispatchStaffHomePath(route.path)
  }
  return route.path === to || route.path.startsWith(`${to}/`)
}

function branchActive(item) {
  if (item.children?.length) {
    return item.children.some(branchActive)
  }
  return isItemActive(item.to)
}

const isGroupActive = computed(() => props.items.some(branchActive))

function updatePanelPosition() {
  const el = rootRef.value
  if (!el || !open.value) return
  const r = el.getBoundingClientRect()
  const panelW = 280
  const left = Math.min(Math.max(8, r.left), Math.max(8, window.innerWidth - panelW - 8))
  panelStyle.value = {
    top: `${r.bottom + 6}px`,
    left: `${left}px`,
    width: `${Math.min(panelW, window.innerWidth - 16)}px`,
    maxHeight: `min(70vh, calc(100vh - ${r.bottom + 24}px))`,
    overflowY: 'auto',
  }
}

function toggle() {
  open.value = !open.value
}

function onDocPointerDown(e) {
  if (!open.value) return
  const t = e.target
  if (rootRef.value?.contains(t)) return
  if (panelRef.value?.contains(t)) return
  open.value = false
}

function onWinChange() {
  if (open.value) updatePanelPosition()
}

function onKeydown(e) {
  if (e.key === 'Escape' && open.value) {
    open.value = false
  }
}

watch(open, async (v) => {
  if (v) {
    await nextTick()
    updatePanelPosition()
    document.addEventListener('pointerdown', onDocPointerDown, true)
    document.addEventListener('keydown', onKeydown)
    window.addEventListener('scroll', onWinChange, true)
    window.addEventListener('resize', onWinChange)
  } else {
    document.removeEventListener('pointerdown', onDocPointerDown, true)
    document.removeEventListener('keydown', onKeydown)
    window.removeEventListener('scroll', onWinChange, true)
    window.removeEventListener('resize', onWinChange)
  }
})

onUnmounted(() => {
  document.removeEventListener('pointerdown', onDocPointerDown, true)
  document.removeEventListener('keydown', onKeydown)
  window.removeEventListener('scroll', onWinChange, true)
  window.removeEventListener('resize', onWinChange)
})

watch(
  () => route.path,
  () => {
    open.value = false
  },
)
</script>
