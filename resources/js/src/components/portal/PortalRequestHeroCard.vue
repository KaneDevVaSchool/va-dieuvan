<template>
  <section
    class="rounded-xl border p-4 sm:p-6 transition-colors"
    :class="
      isUrgent
        ? 'border-rose-200/90 bg-rose-50/45 ring-1 ring-inset ring-rose-100/90'
        : 'border-slate-200 bg-white'
    "
  >
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div class="flex min-w-0 items-start gap-3">
        <RouterLink
          :to="{ name: backRoute }"
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border text-slate-600 transition hover:text-slate-900"
          :class="
            isUrgent
              ? 'border-rose-200/80 bg-white/80 hover:border-rose-300 hover:bg-white'
              : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
          "
          :aria-label="t('portal.back_list')"
        >
          <ArrowLeftIcon class="h-5 w-5" aria-hidden="true" />
        </RouterLink>
        <div class="min-w-0 flex-1">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            {{ t('portal.detail_hero.ref_label') }}
          </p>
          <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-2">
            <h1 class="font-mono text-2xl font-bold tracking-tight text-va-900 sm:text-3xl">
              {{ refCode }}
            </h1>
            <StatusBadge :status="req.status" />
            <span
              v-if="isUrgent"
              class="inline-flex items-center gap-1 rounded-md bg-rose-100/90 px-2 py-0.5 text-xs font-semibold text-rose-800 ring-1 ring-rose-200/90"
            >
              <BoltIcon class="h-3.5 w-3.5" aria-hidden="true" />
              {{ priorityLabel || t('portal.badge_urgent') }}
            </span>
            <span
              v-else-if="priorityLabel"
              class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
            >
              {{ priorityLabel }}
            </span>
          </div>
          <dl class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-600">
            <div class="flex gap-1.5">
              <dt class="text-slate-500">{{ t('portal.detail_hero.created') }}</dt>
              <dd
                class="font-medium"
                :class="createdFmt ? 'text-slate-800' : 'italic text-slate-400'"
              >
                {{ createdFmt || t('portal.list_empty.created_at') }}
              </dd>
            </div>
            <div v-if="requesterName" class="flex gap-1.5">
              <dt class="text-slate-500">{{ t('portal.requester') }}</dt>
              <dd class="font-medium text-slate-800">{{ requesterName }}</dd>
            </div>
            <div v-if="pollingRefreshing" class="inline-flex items-center gap-1.5 text-teal-700">
              <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-teal-600" aria-hidden="true" />
              {{ t('portal.auto_refresh_indicator') }}
            </div>
          </dl>
        </div>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
        <button
          v-if="canWithdraw"
          type="button"
          class="inline-flex min-h-[40px] w-full items-center justify-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-4 text-sm font-medium text-rose-800 transition hover:bg-rose-100 disabled:opacity-50 sm:w-auto"
          :disabled="withdrawBusy"
          data-testid="portal-request-withdraw"
          @click="$emit('withdraw')"
        >
          <TrashIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ withdrawBusy ? t('portal.withdraw_pending_busy') : t('portal.withdraw_pending') }}
        </button>
        <button
          v-if="canPrint"
          type="button"
          class="inline-flex min-h-[40px] w-full items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50 sm:w-auto"
          @click="$emit('print')"
        >
          <PrinterIcon class="h-4 w-4 shrink-0 text-slate-500" aria-hidden="true" />
          {{ t('portal.detail_hero.print') }}
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowLeftIcon, BoltIcon, PrinterIcon, TrashIcon } from '@heroicons/vue/24/outline'
import StatusBadge from '../ui/StatusBadge.vue'
import { formatDispatchRequestRefCode } from '../../util/portalRequestFormat.js'

const props = defineProps({
  req: { type: Object, required: true },
  backRoute: { type: String, required: true },
  priorityLabel: { type: String, default: '' },
  pollingRefreshing: { type: Boolean, default: false },
  canPrint: { type: Boolean, default: false },
  canWithdraw: { type: Boolean, default: false },
  withdrawBusy: { type: Boolean, default: false },
})

defineEmits(['print', 'withdraw'])

const { t } = useI18n()

const isUrgent = computed(() => !!props.req?.is_urgent)

const refCode = computed(() => formatDispatchRequestRefCode(props.req) || t('portal.list_empty.ref_code'))

const requesterName = computed(() => {
  const r = props.req
  return (r.requester_name || r.requester?.name || r.user?.name || '').trim()
})

const createdFmt = computed(() => {
  const iso = props.req?.created_at
  if (!iso) return ''
  try {
    return new Date(iso).toLocaleString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return ''
  }
})
</script>
