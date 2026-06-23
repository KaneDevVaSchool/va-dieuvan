<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-[202] flex items-center justify-center bg-slate-900/45 p-4 backdrop-blur-[3px]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="submit-result-modal-title"
        data-testid="dispatch-submit-result-modal"
        @click.self="emit('close')"
      >
        <div
          class="w-full max-w-[480px] overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-black/5"
          @click.stop
        >
          <div
            class="relative border-b border-slate-100 px-5 pb-5 pt-5"
            :class="
              ok
                ? 'bg-gradient-to-br from-emerald-50/90 via-white to-slate-50/80'
                : 'bg-gradient-to-br from-rose-50/90 via-white to-slate-50/80'
            "
          >
            <div
              class="pointer-events-none absolute -right-8 -top-10 h-32 w-32 rounded-full opacity-40 blur-2xl"
              :class="ok ? 'bg-emerald-200/60' : 'bg-rose-200/60'"
              aria-hidden="true"
            />
            <div class="relative flex gap-4">
              <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-inner ring-1 ring-white/80"
                :class="
                  ok
                    ? 'bg-gradient-to-br from-emerald-100 to-emerald-50 text-emerald-700 shadow-emerald-900/5'
                    : 'bg-gradient-to-br from-rose-100 to-rose-50 text-rose-700 shadow-rose-900/5'
                "
              >
                <CheckCircleIcon v-if="ok" class="h-6 w-6" aria-hidden="true" />
                <XCircleIcon v-else class="h-6 w-6" aria-hidden="true" />
              </div>
              <div class="min-w-0 flex-1 pt-0.5">
                <h3 id="submit-result-modal-title" class="text-base font-semibold leading-snug text-slate-900">
                  {{
                    ok
                      ? t('dispatch_wizard.confirm.submit_modal_success_title')
                      : t('dispatch_wizard.confirm.submit_modal_fail_title')
                  }}
                </h3>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                  {{
                    ok
                      ? t('dispatch_wizard.confirm.submit_modal_success_body')
                      : detail || t('dispatch_wizard.confirm.submit_modal_fail_body')
                  }}
                </p>
              </div>
            </div>

            <div
              v-if="ok && created?.id"
              class="relative mt-4 overflow-hidden rounded-xl border border-slate-200/80 bg-white/90 shadow-sm shadow-slate-900/[0.04] ring-1 ring-slate-100"
            >
              <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">
                  {{ t('dispatch_wizard.confirm.submit_modal_success_code_hint') }}
                </p>
                <p
                  class="mt-1 font-mono text-lg font-semibold tracking-tight text-va-900"
                  data-testid="dispatch-submit-result-ref-code"
                >
                  {{ refCode }}
                </p>
              </div>
              <dl class="grid grid-cols-1 gap-3 px-4 py-3.5 text-sm sm:grid-cols-2">
                <div class="min-w-0">
                  <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                    {{ t('dispatch_wizard.confirm.submit_modal_meta_trip') }}
                  </dt>
                  <dd class="mt-0.5 font-medium text-slate-900">{{ tripTypeLabel }}</dd>
                </div>
                <div class="min-w-0">
                  <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                    {{ t('dispatch_wizard.confirm.submit_modal_meta_status') }}
                  </dt>
                  <dd class="mt-0.5">
                    <span
                      class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1"
                      :class="statusBadgeClass"
                    >
                      {{ statusLabel }}
                    </span>
                  </dd>
                </div>
                <div class="min-w-0 sm:col-span-2">
                  <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                    {{ t('dispatch_wizard.confirm.submit_modal_meta_depart') }}
                  </dt>
                  <dd class="mt-0.5 font-medium text-slate-900">{{ departDisplay }}</dd>
                </div>
                <div v-if="routeDisplay" class="min-w-0 sm:col-span-2">
                  <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                    {{ t('dispatch_wizard.confirm.submit_modal_meta_route') }}
                  </dt>
                  <dd class="mt-0.5 text-slate-800 leading-snug">{{ routeDisplay }}</dd>
                </div>
              </dl>
            </div>

            <p
              v-if="ok && detail?.trim()"
              class="relative mt-3 rounded-lg bg-amber-50 px-3 py-2.5 text-xs font-medium leading-relaxed text-amber-950 ring-1 ring-amber-100"
              role="status"
            >
              {{ detail }}
            </p>
          </div>

          <div class="flex flex-col gap-2 bg-slate-50/90 px-4 py-4 sm:flex-row sm:flex-wrap sm:justify-end sm:gap-3">
            <template v-if="ok && created?.id">
              <button
                type="button"
                class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-va-800/25 focus-visible:ring-offset-2 sm:w-auto"
                data-testid="dispatch-submit-result-view"
                @click="emit('view-request')"
              >
                {{ t('dispatch_wizard.confirm.submit_modal_view_request') }}
              </button>
              <button
                type="button"
                class="inline-flex w-full items-center justify-center rounded-xl border border-va-200 bg-va-50/80 px-4 py-2.5 text-sm font-semibold text-va-900 shadow-sm transition hover:bg-va-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-va-800/25 focus-visible:ring-offset-2 sm:w-auto"
                data-testid="dispatch-submit-result-new"
                @click="emit('new-request')"
              >
                {{ t('dispatch_wizard.confirm.submit_modal_new_request') }}
              </button>
            </template>
            <button
              type="button"
              class="inline-flex w-full items-center justify-center rounded-xl bg-va-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-va-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-va-800/30 focus-visible:ring-offset-2 sm:w-auto"
              data-testid="dispatch-submit-result-close"
              @click="emit('close')"
            >
              {{ t('dispatch_wizard.confirm.submit_modal_close') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline'
import { DISPATCH_WIZARD_KEY } from './injectionKeys'
import { formatDispatchRequestRefCode } from '../../../util/portalRequestFormat'
import { formatDatetimeLocalAmPm } from '../../../util/datetime'

const props = defineProps({
  open: { type: Boolean, default: false },
  ok: { type: Boolean, default: false },
  detail: { type: String, default: '' },
  /** @type {import('vue').Prop<{ id?: number, trip_type?: string, status?: string, depart_at?: string, origin?: string, destination?: string, created_at?: string } | null>} */
  created: { type: Object, default: null },
})

const emit = defineEmits(['close', 'view-request', 'new-request'])

const wizard = inject(DISPATCH_WIZARD_KEY, null)
const { t } = useI18n()

const refCode = computed(() => formatDispatchRequestRefCode(props.created) || '—')

const tripTypeLabel = computed(() => {
  const tt = props.created?.trip_type ?? wizard?.form?.value?.trip_type
  if (!tt) return '—'
  const key = `dispatch_wizard.trip_short.${tt}`
  const translated = t(key)
  return translated !== key ? translated : String(tt)
})

const departDisplay = computed(() => {
  const raw =
    props.created?.depart_at ??
    wizard?.computedDepartAt?.value ??
    wizard?.form?.value?.date_needed
  if (!raw) return '—'
  const ampm = formatDatetimeLocalAmPm(raw)
  if (ampm) return ampm
  try {
    const d = new Date(raw)
    if (Number.isNaN(d.getTime())) return '—'
    return d.toLocaleString('vi-VN')
  } catch {
    return '—'
  }
})

const routeDisplay = computed(() => {
  const origin = String(props.created?.origin ?? '').trim()
  const dest = String(props.created?.destination ?? '').trim()
  if (!origin && !dest) return ''
  if (origin && dest) return `${origin} → ${dest}`
  return origin || dest
})

const statusLabel = computed(() => {
  const s = props.created?.status ?? 'pending'
  const key = `dispatch_wizard.request_status.${s}`
  const translated = t(key)
  return translated !== key ? translated : s
})

const statusBadgeClass = computed(() => {
  const s = props.created?.status ?? 'pending'
  if (s === 'approved') return 'bg-emerald-50 text-emerald-800 ring-emerald-200/80'
  if (s === 'rejected') return 'bg-rose-50 text-rose-800 ring-rose-200/80'
  if (s === 'returned') return 'bg-amber-50 text-amber-900 ring-amber-200/80'
  return 'bg-sky-50 text-sky-900 ring-sky-200/80'
})
</script>
