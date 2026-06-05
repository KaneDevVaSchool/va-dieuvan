<template>
  <Teleport to="body">
    <div
      v-if="state.open"
      class="fixed inset-0 z-[200] overflow-y-auto bg-black/40 p-4"
      role="alertdialog"
      aria-modal="true"
      :aria-labelledby="titleId"
      @click.self="dismissModal"
    >
      <div class="flex min-h-full items-center justify-center">
      <div
        class="flex w-full max-h-[min(90dvh,calc(100dvh-2rem))] flex-col overflow-hidden rounded-xl border shadow-xl"
        :class="[panelClass, state.apiDetails ? 'max-w-2xl' : 'max-w-lg']"
      >
        <div class="flex shrink-0 items-start justify-between gap-3 border-b px-4 py-3 sm:px-5" :class="headerBorderClass">
          <div :id="titleId" class="pr-2 text-sm font-semibold leading-snug" :class="titleClass">{{ state.title }}</div>
          <button
            type="button"
            class="shrink-0 rounded px-2 py-1 text-xs transition"
            :class="closeBtnClass"
            @click="dismissModal"
          >
            {{ t('message_modal.close') }}
          </button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-4 py-3 sm:px-5">
          <div
            v-if="state.variant === 'success'"
            class="flex gap-4 rounded-xl border border-emerald-100/90 bg-gradient-to-br from-emerald-50/90 via-white to-teal-50/40 p-4 dark:border-emerald-900/40 dark:from-emerald-950/40 dark:via-slate-900 dark:to-teal-950/30"
          >
            <div
              class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-md shadow-emerald-900/20"
              aria-hidden="true"
            >
              <CheckCircleIcon class="h-9 w-9" />
            </div>
            <p class="min-w-0 flex-1 whitespace-pre-wrap break-words text-sm font-medium leading-relaxed text-slate-800 dark:text-slate-100">
              {{ state.body }}
            </p>
          </div>
          <p
            v-else
            class="whitespace-pre-wrap break-words text-sm leading-relaxed"
            :class="bodyClass"
          >
            {{ state.body }}
          </p>
          <div
            v-if="state.variant === 'error' && state.apiDetails"
            class="mt-4 rounded-lg border border-slate-200/90 bg-slate-50 p-3 text-left dark:border-slate-600 dark:bg-slate-800/80"
          >
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('message_modal.tech_details') }}</p>
            <dl class="mt-2 space-y-1.5 font-mono text-[11px] text-slate-700 dark:text-slate-300">
              <div v-if="state.apiDetails.status != null" class="flex flex-wrap gap-x-2 gap-y-0.5">
                <dt class="shrink-0 text-slate-500 dark:text-slate-500">{{ t('message_modal.http') }}</dt>
                <dd>{{ state.apiDetails.status }}</dd>
              </div>
              <div class="flex flex-wrap gap-x-2 gap-y-0.5 break-all">
                <dt class="shrink-0 text-slate-500 dark:text-slate-500">{{ t('message_modal.request') }}</dt>
                <dd>{{ state.apiDetails.method }} {{ state.apiDetails.path }}</dd>
              </div>
              <div v-if="state.apiDetails.configMissing" class="text-slate-600 dark:text-slate-400">
                {{ t('message_modal.config_missing') }}
              </div>
              <div v-if="state.apiDetails.axiosCode" class="flex flex-wrap gap-x-2 gap-y-0.5">
                <dt class="shrink-0 text-slate-500 dark:text-slate-500">{{ t('message_modal.error_code') }}</dt>
                <dd>{{ state.apiDetails.axiosCode }}</dd>
              </div>
              <div v-if="state.apiDetails.axiosMessage" class="pt-1">
                <dt class="text-slate-500 dark:text-slate-500">{{ t('message_modal.axios_msg') }}</dt>
                <dd class="mt-0.5 whitespace-pre-wrap break-words text-slate-800 dark:text-slate-200">
                  {{ state.apiDetails.axiosMessage }}
                </dd>
              </div>
              <div v-if="state.apiDetails.retryAfter" class="flex flex-wrap gap-x-2 gap-y-0.5">
                <dt class="shrink-0 text-slate-500 dark:text-slate-500">{{ t('message_modal.retry_after') }}</dt>
                <dd>{{ state.apiDetails.retryAfter }} {{ t('message_modal.retry_after_hint') }}</dd>
              </div>
              <div v-if="state.apiDetails.serverRaw" class="pt-1">
                <dt class="text-slate-500 dark:text-slate-500">{{ t('message_modal.server_raw') }}</dt>
                <dd class="mt-0.5 whitespace-pre-wrap break-words text-slate-800 dark:text-slate-200">{{ state.apiDetails.serverRaw }}</dd>
              </div>
              <div v-if="state.apiDetails.networkHint" class="text-slate-600 dark:text-slate-400">
                {{ state.apiDetails.networkHint }}
              </div>
            </dl>
            <p
              v-if="state.apiDetails?.status === 429"
              class="mt-2 text-[10px] leading-relaxed text-slate-500 dark:text-slate-500"
            >
              {{ t('message_modal.rate_limit_hint') }}
            </p>
          </div>
        </div>
        <div class="flex shrink-0 justify-end border-t px-4 py-3 sm:px-5" :class="footerBorderClass">
          <button
            type="button"
            class="rounded-md px-4 py-2 text-sm font-medium text-white transition focus:outline-none focus:ring-2 focus:ring-offset-1 dark:focus:ring-offset-slate-900"
            :class="primaryBtnClass"
            @click="dismissModal"
          >
            {{ primaryButtonLabel }}
          </button>
        </div>
      </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onUnmounted, watch, watchEffect } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { CheckCircleIcon } from '@heroicons/vue/24/solid'
import { appMessageState as state, closeAppMessage } from '../../composables/appMessage'

const router = useRouter()
const { t } = useI18n()
const titleId = 'app-message-modal-title'

const primaryButtonLabel = computed(() => state.primaryLabel || t('app.close'))

function dismissModal() {
  const to = state.navigateTo
  closeAppMessage()
  if (to) router.push(to)
}

const panelClass = computed(() => {
  if (state.variant === 'success') {
    return 'border-emerald-200 bg-white dark:border-emerald-900/60 dark:bg-slate-900'
  }
  if (state.variant === 'info') {
    return 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900'
  }
  return 'border-rose-200 bg-white dark:border-rose-900/50 dark:bg-slate-900'
})

const headerBorderClass = computed(() =>
  state.variant === 'error'
    ? 'border-rose-200/80 dark:border-rose-900/50'
    : state.variant === 'success'
      ? 'border-emerald-200/80 dark:border-emerald-900/50'
      : 'border-slate-200 dark:border-slate-700',
)

const footerBorderClass = headerBorderClass

const titleClass = computed(() =>
  state.variant === 'error'
    ? 'text-rose-900 dark:text-rose-100'
    : state.variant === 'success'
      ? 'text-emerald-900 dark:text-emerald-100'
      : 'text-slate-900 dark:text-slate-100',
)

const bodyClass = computed(() =>
  state.variant === 'error'
    ? 'text-slate-800 dark:text-slate-200'
    : 'text-slate-700 dark:text-slate-300',
)

const closeBtnClass = computed(() =>
  state.variant === 'error'
    ? 'text-rose-700 hover:bg-rose-50 dark:text-rose-200 dark:hover:bg-rose-950/40'
    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
)

const primaryBtnClass = computed(() =>
  state.variant === 'error'
    ? 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500'
    : state.variant === 'success'
      ? 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500'
      : 'bg-slate-700 hover:bg-slate-800 focus:ring-slate-500 dark:bg-slate-600',
)

watch(
  () => state.open,
  (open) => {
    if (typeof document === 'undefined') return
    document.body.style.overflow = open ? 'hidden' : ''
  },
)

watchEffect((onCleanup) => {
  if (!state.open || typeof window === 'undefined') return
  const onKey = (e) => {
    if (e.key === 'Escape') dismissModal()
  }
  window.addEventListener('keydown', onKey)
  onCleanup(() => window.removeEventListener('keydown', onKey))
})

onUnmounted(() => {
  if (typeof document !== 'undefined') document.body.style.overflow = ''
})
</script>
