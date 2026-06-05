<template>
  <!-- Dọc: avatar + tên + email → dropdown -->
  <div
    v-if="layout === 'vertical'"
    class="shrink-0 border-t backdrop-blur-sm"
    :class="
      brand
        ? compact
          ? 'min-w-0 overflow-hidden border-white/15 bg-black/15 p-1 md:p-1.5'
          : 'border-white/15 bg-black/15 p-2 md:p-3'
        : compact
          ? 'min-w-0 overflow-hidden border-slate-200/80 bg-white/95 p-1 md:p-1.5 dark:border-slate-700 dark:bg-slate-900/95'
          : 'border-slate-200/80 bg-white/95 p-2 md:p-3 dark:border-slate-700 dark:bg-slate-900/95'
    "
  >
    <div ref="accountMenuRootRef" class="relative flex min-w-0 justify-center">
      <button
        type="button"
        class="flex min-w-0 items-center rounded-lg text-left shadow-sm transition"
        :class="
          brand
            ? compact
              ? 'w-full max-w-[2.75rem] flex-col justify-center gap-0 border-0 bg-transparent p-0.5 shadow-none ring-0 hover:bg-white/10'
              : 'w-full min-w-0 gap-2 border border-white/20 bg-white/10 px-2 py-2 hover:bg-white/15 md:px-2.5 md:py-2.5'
            : compact
              ? 'w-full max-w-[2.75rem] flex-col justify-center gap-0 border-0 bg-transparent p-0.5 shadow-none ring-0 hover:bg-slate-100/80 dark:hover:bg-slate-800/60'
              : 'w-full min-w-0 gap-2 border border-slate-200/80 bg-white/90 px-2 py-2 hover:border-slate-300 hover:bg-slate-50/90 dark:border-slate-600 dark:bg-slate-800/90 dark:hover:border-slate-500 dark:hover:bg-slate-800 md:px-2.5 md:py-2.5'
        "
        :aria-expanded="accountMenuOpen"
        aria-haspopup="menu"
        :aria-controls="accountMenuPanelId"
        :title="auth.user?.name ?? t('app.account')"
        @click.stop="toggleAccountMenu"
      >
        <UserAvatar
          class="shrink-0"
          :name="auth.user?.name"
          :email="auth.user?.email"
          :avatar-url="auth.user?.avatar_url"
          :title="auth.user?.name ?? ''"
          :size="compact ? 'sm' : 'md'"
        />
        <div v-if="!compact" class="min-w-0 flex-1">
          <div
            class="truncate text-sm font-semibold"
            :class="brand ? 'text-white' : 'text-slate-900 dark:text-slate-100'"
          >
            {{ auth.user?.name ?? '—' }}
          </div>
          <div
            class="truncate text-[11px]"
            :class="brand ? 'text-white/75' : 'text-slate-500 dark:text-slate-400'"
          >
            {{ auth.user?.email ?? '' }}
          </div>
        </div>
        <span v-else class="sr-only">{{ auth.user?.name ?? '—' }}</span>
        <ChevronDownIcon
          v-if="!compact"
          class="h-4 w-4 shrink-0 opacity-80 transition-transform"
          :class="[
            accountMenuOpen ? 'rotate-180' : '',
            brand ? 'text-white/80' : 'text-slate-500 dark:text-slate-400',
          ]"
          aria-hidden="true"
        />
      </button>
    </div>
  </div>

  <!-- Ngang: avatar → cùng dropdown -->
  <div
    v-else
    ref="accountMenuRootRef"
    class="relative shrink-0 md:border-l md:border-slate-200/90 md:pl-3 dark:md:border-slate-600/90"
  >
    <button
      type="button"
      class="flex shrink-0 touch-manipulation items-center rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-va-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
      :aria-expanded="accountMenuOpen"
      aria-haspopup="menu"
      :aria-controls="accountMenuPanelId"
      :title="auth.user?.name ?? t('app.account')"
      @click.stop="toggleAccountMenu"
    >
      <UserAvatar
        :name="auth.user?.name"
        :email="auth.user?.email"
        :avatar-url="auth.user?.avatar_url"
        :title="auth.user?.name ?? ''"
        size="lg"
        ring-prominent
      />
    </button>
  </div>

  <Teleport to="body">
    <div
      v-show="accountMenuOpen"
      :id="accountMenuPanelId"
      ref="accountMenuPanelRef"
      role="presentation"
      class="fixed z-[200] min-w-[14rem] max-w-[min(17.5rem,calc(100vw-0.75rem))] overflow-hidden rounded-lg border border-slate-200/90 bg-white py-0.5 text-slate-900 shadow-md ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:ring-white/10"
      :style="accountMenuPanelStyle"
    >
      <div
        role="menu"
        :aria-label="t('app.account')"
        class="scrollbar-hidden"
      >
        <div class="border-b border-slate-100 px-2.5 py-2 dark:border-slate-700/80">
          <p class="truncate text-xs font-semibold leading-tight tracking-tight text-slate-900 dark:text-slate-50">
            {{ auth.user?.name ?? '—' }}
          </p>
          <p class="mt-0.5 truncate text-[10px] leading-snug text-slate-500 dark:text-slate-400">
            {{ auth.user?.email ?? '' }}
          </p>
          <p
            v-if="auth.roleNames?.length"
            class="mt-1 line-clamp-2 text-[10px] text-slate-400 dark:text-slate-500"
          >
            {{ auth.roleNames.join(', ') }}
          </p>
        </div>

        <RouterLink
          role="menuitem"
          :to="profileTo"
          class="flex items-center gap-2 px-2.5 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900 dark:text-slate-200 dark:hover:bg-slate-800/80 dark:hover:text-slate-50"
          @click="accountMenuOpen = false"
        >
          <UserCircleIcon class="h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" aria-hidden="true" />
          {{ t('app.profile') }}
        </RouterLink>

        <div class="border-t border-slate-100 px-2.5 py-2 dark:border-slate-700/80" role="presentation">
          <div class="space-y-2">
            <div>
              <p class="mb-1 text-[10px] font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                {{ t('app.sidebar_layout') }}
              </p>
              <div class="flex rounded-md bg-slate-100/90 p-0.5 dark:bg-slate-800/90">
                <button
                  v-for="opt in axisOptions"
                  :key="opt.value"
                  type="button"
                  class="min-w-0 flex-1 rounded px-1 py-1 text-center text-[10px] font-semibold leading-none transition sm:py-1.5 sm:text-[11px]"
                  :class="ui.sidebarAxisPreference === opt.value ? segmentActive : segmentIdle"
                  :aria-pressed="ui.sidebarAxisPreference === opt.value"
                  @click="requestAxisPreference(opt.value)"
                >
                  {{ t(opt.labelKey) }}
                </button>
              </div>
            </div>
            <div>
              <p class="mb-1 text-[10px] font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                {{ t('app.lang') }}
              </p>
              <div class="flex rounded-md bg-slate-100/90 p-0.5 dark:bg-slate-800/90">
                <button
                  type="button"
                  class="flex-1 rounded px-1.5 py-1 text-center text-[11px] font-semibold transition sm:py-1.5"
                  :class="locale === 'vi' ? segmentActive : segmentIdle"
                  :aria-pressed="locale === 'vi'"
                  @click="onLocale('vi')"
                >
                  VI
                </button>
                <button
                  type="button"
                  class="flex-1 rounded px-1.5 py-1 text-center text-[11px] font-semibold transition sm:py-1.5"
                  :class="locale === 'en' ? segmentActive : segmentIdle"
                  :aria-pressed="locale === 'en'"
                  @click="onLocale('en')"
                >
                  EN
                </button>
              </div>
            </div>
            <ThemeSwitcher />
          </div>
        </div>

        <button
          type="button"
          role="menuitem"
          class="flex w-full items-center gap-2 border-t border-slate-100 px-2.5 py-2 text-left text-xs font-medium text-rose-700 transition hover:bg-rose-50 dark:border-slate-700/80 dark:text-rose-300 dark:hover:bg-rose-950/40"
          @click="openLogoutConfirmFromMenu"
        >
          <ArrowRightOnRectangleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ t('app.logout') }}
        </button>
      </div>
    </div>
  </Teleport>

  <!-- Xác nhận chuyển menu ngang -->
  <Teleport to="body">
    <div
      v-if="horizontalConfirmOpen"
      class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 p-4 backdrop-blur-[2px] sm:items-center"
      role="presentation"
      @click.self="horizontalConfirmOpen = false"
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="sidebar-horizontal-confirm-title"
        class="w-full max-w-md rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xl dark:border-slate-600 dark:bg-slate-900"
      >
        <h2
          id="sidebar-horizontal-confirm-title"
          class="text-base font-semibold text-slate-900 dark:text-slate-50"
        >
          {{ t('app.sidebar_confirm_horizontal_title') }}
        </h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
          {{ t('app.sidebar_confirm_horizontal_body') }}
        </p>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3">
          <button
            type="button"
            class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 sm:w-auto"
            @click="horizontalConfirmOpen = false"
          >
            {{ t('app.cancel') }}
          </button>
          <button
            type="button"
            class="inline-flex w-full items-center justify-center rounded-xl bg-va-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-va-900 dark:bg-va-600 dark:hover:bg-va-500 sm:w-auto"
            @click="confirmHorizontalAxis"
          >
            {{ t('app.sidebar_confirm_horizontal_action') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Xác nhận đăng xuất -->
  <Teleport to="body">
    <div
      v-if="logoutConfirmOpen"
      class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 p-4 backdrop-blur-[2px] sm:items-center"
      role="presentation"
      @click.self="logoutConfirmOpen = false"
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="logout-confirm-title"
        class="w-full max-w-md rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xl dark:border-slate-600 dark:bg-slate-900"
      >
        <h2 id="logout-confirm-title" class="text-base font-semibold text-slate-900 dark:text-slate-50">
          {{ t('app.logout_confirm_title') }}
        </h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
          {{ t('app.logout_confirm_body') }}
        </p>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3">
          <button
            type="button"
            class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 sm:w-auto"
            @click="logoutConfirmOpen = false"
          >
            {{ t('app.cancel') }}
          </button>
          <button
            type="button"
            class="inline-flex w-full items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 dark:bg-rose-500 dark:hover:bg-rose-600 sm:w-auto"
            @click="confirmLogout"
          >
            {{ t('app.logout_confirm_action') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onUnmounted, ref, useId, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowRightOnRectangleIcon, ChevronDownIcon, UserCircleIcon } from '@heroicons/vue/24/outline'
import UserAvatar from '../branding/UserAvatar.vue'
import ThemeSwitcher from './ThemeSwitcher.vue'
import { useAuthStore } from '../../store'
import { useUiStore } from '../../store/ui'
import { profilePathForAuth } from '../../config/dispatchWebBase'
import { setLocale } from '../../i18n'
import { applyRouteDocumentTitle } from '../../util/routeDocumentTitle'
import { useAuthLogout } from '../../composables/useAuthLogout'

const props = defineProps({
  layout: { type: String, required: true, validator: (v) => v === 'vertical' || v === 'horizontal' },
  compact: { type: Boolean, default: false },
  /** Sidebar màu thương hiệu (chỉ áp dụng layout dọc) */
  brand: { type: Boolean, default: false },
})

const { t, locale } = useI18n()
const auth = useAuthStore()
const router = useRouter()
const ui = useUiStore()
const { performLogout } = useAuthLogout()

const profileTo = computed(() => profilePathForAuth(auth.canAccessDispatchWebApp()))

const segmentActive =
  'bg-slate-800 text-white shadow-sm dark:bg-slate-600 dark:text-white'
const segmentIdle =
  'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200'

const axisOptions = [
  { value: 'auto', labelKey: 'app.axis_auto' },
  { value: 'vertical', labelKey: 'app.axis_vertical' },
  { value: 'horizontal', labelKey: 'app.axis_horizontal' },
]

const accountMenuOpen = ref(false)
const accountMenuRootRef = ref(null)
const accountMenuPanelRef = ref(null)
const accountMenuPanelStyle = ref({})
const accountMenuPanelId = useId()
let accountMenuOutsidePointerTimer = null

const horizontalConfirmOpen = ref(false)
const logoutConfirmOpen = ref(false)

function toggleAccountMenu() {
  accountMenuOpen.value = !accountMenuOpen.value
}

async function requestAxisPreference(v) {
  if (v === ui.sidebarAxisPreference) return
  if (v === 'horizontal') {
    accountMenuOpen.value = false
    await nextTick()
    horizontalConfirmOpen.value = true
    return
  }
  ui.setSidebarAxisPreference(v)
  accountMenuOpen.value = false
}

function confirmHorizontalAxis() {
  ui.setSidebarAxisPreference('horizontal')
  horizontalConfirmOpen.value = false
}

function openLogoutConfirmFromMenu() {
  accountMenuOpen.value = false
  logoutConfirmOpen.value = true
}

async function confirmLogout() {
  logoutConfirmOpen.value = false
  accountMenuOpen.value = false
  await performLogout()
}

function onLocale(v) {
  setLocale(v)
  applyRouteDocumentTitle(router.currentRoute.value.meta, router.currentRoute.value.name)
}

function updateAccountMenuPosition() {
  const el = accountMenuRootRef.value
  if (!el || !accountMenuOpen.value) return
  const r = el.getBoundingClientRect()
  const panelW = 276
  const w = Math.min(panelW, window.innerWidth - 16)
  let left = r.right - w
  left = Math.max(8, Math.min(left, window.innerWidth - w - 8))
  const margin = 8
  const vh = window.innerHeight
  const panelEl = accountMenuPanelRef.value
  const measured = panelEl?.getBoundingClientRect().height
  /** Chiều cao dùng để đặt vị trí: ưu tiên đo thật sau khi render */
  const estH =
    measured && measured > 48
      ? measured
      : Math.min(vh * 0.7, 26 * 16)

  if (props.layout === 'vertical') {
    /** Nút ở đáy sidebar: mở lên trên — tránh panel nằm dưới khung nhìn */
    let top = r.top - estH - margin
    if (top < margin) top = margin
    const maxH = Math.min(estH, r.top - margin - top)
    accountMenuPanelStyle.value = {
      top: `${top}px`,
      left: `${left}px`,
      width: `${w}px`,
      maxHeight: maxH > 120 ? `${maxH}px` : `${estH}px`,
    }
  } else {
    let top = r.bottom + margin
    if (top + estH > vh - margin) {
      top = Math.max(margin, r.top - estH - margin)
    }
    accountMenuPanelStyle.value = {
      top: `${top}px`,
      left: `${left}px`,
      width: `${w}px`,
      maxHeight: `${Math.min(estH, vh - top - margin)}px`,
    }
  }
}

function onAccountMenuPointerDown(e) {
  if (!accountMenuOpen.value) return
  /** Modal xác nhận nằm ngoài panel — không xử lý để tránh cắt sự kiện click */
  if (horizontalConfirmOpen.value || logoutConfirmOpen.value) return
  const tgt = e.target
  if (accountMenuRootRef.value?.contains(tgt)) return
  if (accountMenuPanelRef.value?.contains(tgt)) return
  accountMenuOpen.value = false
}

function onAccountMenuKeydown(e) {
  if (e.key === 'Escape' && accountMenuOpen.value) {
    accountMenuOpen.value = false
  }
}

function onAccountMenuWinChange() {
  if (accountMenuOpen.value) updateAccountMenuPosition()
}

function onModalEscape(e) {
  if (e.key !== 'Escape') return
  if (logoutConfirmOpen.value) {
    logoutConfirmOpen.value = false
    return
  }
  if (horizontalConfirmOpen.value) {
    horizontalConfirmOpen.value = false
  }
}

watch(accountMenuOpen, async (open) => {
  if (open) {
    await nextTick()
    updateAccountMenuPosition()
    /** Đo lại sau khi panel đã render (chiều cao thật) */
    requestAnimationFrame(() => {
      updateAccountMenuPosition()
    })
    /** Hoãn gắn listener để cùng một cú bấm mở menu không bị coi là “click ngoài” và đóng ngay */
    accountMenuOutsidePointerTimer = window.setTimeout(() => {
      accountMenuOutsidePointerTimer = null
      document.addEventListener('pointerdown', onAccountMenuPointerDown, true)
    }, 0)
    document.addEventListener('keydown', onAccountMenuKeydown)
    window.addEventListener('scroll', onAccountMenuWinChange, true)
    window.addEventListener('resize', onAccountMenuWinChange)
  } else {
    if (accountMenuOutsidePointerTimer != null) {
      clearTimeout(accountMenuOutsidePointerTimer)
      accountMenuOutsidePointerTimer = null
    }
    document.removeEventListener('pointerdown', onAccountMenuPointerDown, true)
    document.removeEventListener('keydown', onAccountMenuKeydown)
    window.removeEventListener('scroll', onAccountMenuWinChange, true)
    window.removeEventListener('resize', onAccountMenuWinChange)
  }
})

watch([horizontalConfirmOpen, logoutConfirmOpen], ([h, l]) => {
  if (typeof document === 'undefined') return
  document.body.style.overflow = h || l ? 'hidden' : ''
  if (h || l) {
    document.addEventListener('keydown', onModalEscape)
  } else {
    document.removeEventListener('keydown', onModalEscape)
  }
})

watch(
  () => router.currentRoute.value.path,
  () => {
    accountMenuOpen.value = false
  },
)

onUnmounted(() => {
  if (accountMenuOutsidePointerTimer != null) {
    clearTimeout(accountMenuOutsidePointerTimer)
  }
  document.removeEventListener('pointerdown', onAccountMenuPointerDown, true)
  document.removeEventListener('keydown', onAccountMenuKeydown)
  window.removeEventListener('scroll', onAccountMenuWinChange, true)
  window.removeEventListener('resize', onAccountMenuWinChange)
  document.removeEventListener('keydown', onModalEscape)
  if (typeof document !== 'undefined') document.body.style.overflow = ''
})

</script>
