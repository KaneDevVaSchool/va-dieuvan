<template>
  <!-- Dọc -->
  <div
    v-if="layout === 'vertical'"
    class="shrink-0 border-t border-slate-200/80 bg-white/95 p-2 backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/95 md:p-3"
    :class="compact ? 'min-w-0 overflow-hidden' : ''"
  >
    <div v-if="!compact" class="hidden text-[10px] font-medium uppercase tracking-wide text-slate-400 md:block">
      {{ t('app.account') }}
    </div>
    <div :class="compact ? 'flex flex-col items-center gap-2' : 'mt-0 flex items-center gap-2 md:mt-2 md:gap-3'">
      <UserAvatar
        class="shrink-0"
        :class="compact ? 'mx-auto' : ''"
        :name="auth.user?.name"
        :email="auth.user?.email"
        :avatar-url="auth.user?.avatar_url"
        :title="auth.user?.name ?? ''"
        :size="compact ? 'sm' : 'md'"
      />
      <div v-if="!compact" class="hidden min-w-0 flex-1 md:block">
        <div class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ auth.user?.name ?? '—' }}
        </div>
        <div class="truncate text-xs text-slate-500 dark:text-slate-400">
          {{ auth.user?.email ?? '' }}
        </div>
        <div
          v-if="auth.roleNames?.length"
          class="mt-0.5 line-clamp-2 text-[10px] text-slate-400 dark:text-slate-500 lg:text-[11px]"
        >
          {{ auth.roleNames.join(', ') }}
        </div>
      </div>
    </div>

    <label v-if="!compact" class="mt-2 hidden text-[11px] font-medium text-slate-500 md:block dark:text-slate-400">
      {{ t('app.lang') }}
    </label>
    <div v-if="!compact" class="mt-1.5 hidden md:block">
      <div class="flex rounded-lg bg-slate-100/90 p-0.5 dark:bg-slate-800/90">
        <button
          type="button"
          class="flex-1 rounded-md px-2 py-1.5 text-center text-xs font-semibold transition"
          :class="locale === 'vi' ? segmentActive : segmentIdle"
          @click="onLocale('vi')"
        >
          VI
        </button>
        <button
          type="button"
          class="flex-1 rounded-md px-2 py-1.5 text-center text-xs font-semibold transition"
          :class="locale === 'en' ? segmentActive : segmentIdle"
          @click="onLocale('en')"
        >
          EN
        </button>
      </div>
    </div>
    <div v-if="compact" class="mt-1 w-full min-w-0 max-w-full">
      <div class="flex rounded-lg bg-slate-100/90 p-0.5 dark:bg-slate-800/90">
        <button
          type="button"
          class="flex-1 rounded-md px-2 py-1.5 text-center text-[10px] font-semibold transition"
          :class="locale === 'vi' ? segmentActive : segmentIdle"
          :aria-pressed="locale === 'vi'"
          @click="onLocale('vi')"
        >
          VI
        </button>
        <button
          type="button"
          class="flex-1 rounded-md px-2 py-1.5 text-center text-[10px] font-semibold transition"
          :class="locale === 'en' ? segmentActive : segmentIdle"
          :aria-pressed="locale === 'en'"
          @click="onLocale('en')"
        >
          EN
        </button>
      </div>
    </div>

    <RouterLink
      class="mt-2 flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white text-[11px] font-medium text-slate-800 shadow-sm transition hover:border-va-200 hover:bg-va-50/50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-va-500/40 dark:hover:bg-va-950/30 md:text-sm"
      :class="
        compact
          ? 'aspect-square min-h-0 w-full max-w-full shrink-0 px-0 py-2'
          : 'px-2 py-2 md:py-2.5'
      "
      to="/profile"
      :aria-label="t('app.profile')"
    >
      <UserCircleIcon
        class="h-5 w-5 shrink-0"
        :class="compact ? '' : 'md:hidden'"
        aria-hidden="true"
      />
      <span :class="compact ? 'sr-only' : 'sr-only md:not-sr-only md:inline'">{{ t('app.profile') }}</span>
    </RouterLink>
    <button
      type="button"
      class="mt-1.5 flex w-full items-center justify-center rounded-lg border border-slate-200 text-[11px] font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800 md:mt-2 md:text-sm"
      :class="compact ? 'aspect-square max-w-full px-0 py-2' : 'px-2 py-2 md:py-2.5'"
      @click="openLogoutConfirm"
    >
      <ArrowRightOnRectangleIcon v-if="compact" class="h-5 w-5 shrink-0" aria-hidden="true" />
      <span v-if="compact" class="sr-only">{{ t('app.logout') }}</span>
      <template v-else>{{ t('app.logout') }}</template>
    </button>
  </div>

  <!-- Ngang: chỉ avatar → dropdown tài khoản -->
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
      @click="accountMenuOpen = !accountMenuOpen"
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

    <Teleport to="body">
      <div
        v-show="accountMenuOpen"
        :id="accountMenuPanelId"
        ref="accountMenuPanelRef"
        role="presentation"
        class="fixed z-[70] min-w-[16.5rem] max-w-[min(19rem,calc(100vw-1rem))] overflow-hidden rounded-xl border border-slate-200/90 bg-white py-1 shadow-xl ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900 dark:ring-white/10"
        :style="accountMenuPanelStyle"
      >
        <div
          role="menu"
          :aria-label="t('app.account')"
          class="max-h-[min(70vh,28rem)] overflow-y-auto"
        >
          <div class="border-b border-slate-100 px-3 py-2.5 dark:border-slate-700">
            <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-50">
              {{ auth.user?.name ?? '—' }}
            </p>
            <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
              {{ auth.user?.email ?? '' }}
            </p>
          </div>

          <RouterLink
            role="menuitem"
            to="/profile"
            class="flex items-center gap-2.5 px-3 py-2.5 text-sm text-slate-700 transition hover:bg-va-50 hover:text-va-900 dark:text-slate-200 dark:hover:bg-va-950/40 dark:hover:text-va-100"
            @click="accountMenuOpen = false"
          >
            <UserCircleIcon class="h-5 w-5 shrink-0 text-slate-500 dark:text-slate-400" aria-hidden="true" />
            {{ t('app.profile') }}
          </RouterLink>

          <div class="border-t border-slate-100 px-3 py-2.5 dark:border-slate-700" role="presentation">
            <p class="mb-2 text-[11px] font-medium text-slate-500 dark:text-slate-400">
              {{ t('app.sidebar_layout') }}
            </p>
            <div class="flex rounded-lg bg-slate-100/90 p-0.5 dark:bg-slate-800/90">
              <button
                v-for="opt in axisOptions"
                :key="opt.value"
                type="button"
                class="min-w-0 flex-1 rounded-md px-1 py-1.5 text-center text-[10px] font-semibold leading-tight transition sm:px-1.5 sm:text-xs"
                :class="ui.sidebarAxisPreference === opt.value ? segmentActive : segmentIdle"
                :aria-pressed="ui.sidebarAxisPreference === opt.value"
                @click="requestAxisPreference(opt.value)"
              >
                {{ t(opt.labelKey) }}
              </button>
            </div>
          </div>

          <div class="border-t border-slate-100 px-3 py-2.5 dark:border-slate-700" role="presentation">
            <p class="mb-2 text-[11px] font-medium text-slate-500 dark:text-slate-400">
              {{ t('app.lang') }}
            </p>
            <div class="flex rounded-lg bg-slate-100/90 p-0.5 dark:bg-slate-800/90">
              <button
                type="button"
                class="flex-1 rounded-md px-2 py-1.5 text-center text-xs font-semibold transition"
                :class="locale === 'vi' ? segmentActive : segmentIdle"
                :aria-pressed="locale === 'vi'"
                @click="onLocale('vi')"
              >
                VI
              </button>
              <button
                type="button"
                class="flex-1 rounded-md px-2 py-1.5 text-center text-xs font-semibold transition"
                :class="locale === 'en' ? segmentActive : segmentIdle"
                :aria-pressed="locale === 'en'"
                @click="onLocale('en')"
              >
                EN
              </button>
            </div>
          </div>

          <button
            type="button"
            role="menuitem"
            class="flex w-full items-center gap-2.5 border-t border-slate-100 px-3 py-2.5 text-left text-sm font-medium text-rose-700 transition hover:bg-rose-50 dark:border-slate-700 dark:text-rose-300 dark:hover:bg-rose-950/40"
            @click="openLogoutConfirmFromMenu"
          >
            <ArrowRightOnRectangleIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
            {{ t('app.logout') }}
          </button>
        </div>
      </div>
    </Teleport>
  </div>

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
import { nextTick, onUnmounted, ref, useId, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowRightOnRectangleIcon, UserCircleIcon } from '@heroicons/vue/24/outline'
import UserAvatar from '../branding/UserAvatar.vue'
import { useAuthStore } from '../../store'
import { useUiStore } from '../../store/ui'
import { setLocale } from '../../i18n'

defineProps({
  layout: { type: String, required: true, validator: (v) => v === 'vertical' || v === 'horizontal' },
  compact: { type: Boolean, default: false },
})

const { t, locale } = useI18n()
const auth = useAuthStore()
const router = useRouter()
const ui = useUiStore()

const segmentActive =
  'bg-white text-va-900 shadow-sm dark:bg-slate-700 dark:text-va-100'
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

const horizontalConfirmOpen = ref(false)
const logoutConfirmOpen = ref(false)

function requestAxisPreference(v) {
  if (v === ui.sidebarAxisPreference) return
  if (v === 'horizontal') {
    horizontalConfirmOpen.value = true
    return
  }
  ui.setSidebarAxisPreference(v)
}

function confirmHorizontalAxis() {
  ui.setSidebarAxisPreference('horizontal')
  horizontalConfirmOpen.value = false
}

function openLogoutConfirm() {
  logoutConfirmOpen.value = true
}

function openLogoutConfirmFromMenu() {
  accountMenuOpen.value = false
  logoutConfirmOpen.value = true
}

async function confirmLogout() {
  logoutConfirmOpen.value = false
  accountMenuOpen.value = false
  await auth.logout()
  await router.push({ name: 'login' })
}

function onLocale(v) {
  setLocale(v)
}

function updateAccountMenuPosition() {
  const el = accountMenuRootRef.value
  if (!el || !accountMenuOpen.value) return
  const r = el.getBoundingClientRect()
  const panelW = 304
  const w = Math.min(panelW, window.innerWidth - 16)
  let left = r.right - w
  left = Math.max(8, Math.min(left, window.innerWidth - w - 8))
  accountMenuPanelStyle.value = {
    top: `${r.bottom + 8}px`,
    left: `${left}px`,
    width: `${w}px`,
  }
}

function onAccountMenuPointerDown(e) {
  if (!accountMenuOpen.value) return
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
    document.addEventListener('pointerdown', onAccountMenuPointerDown, true)
    document.addEventListener('keydown', onAccountMenuKeydown)
    window.addEventListener('scroll', onAccountMenuWinChange, true)
    window.addEventListener('resize', onAccountMenuWinChange)
  } else {
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
  document.removeEventListener('pointerdown', onAccountMenuPointerDown, true)
  document.removeEventListener('keydown', onAccountMenuKeydown)
  window.removeEventListener('scroll', onAccountMenuWinChange, true)
  window.removeEventListener('resize', onAccountMenuWinChange)
  document.removeEventListener('keydown', onModalEscape)
  if (typeof document !== 'undefined') document.body.style.overflow = ''
})

</script>
