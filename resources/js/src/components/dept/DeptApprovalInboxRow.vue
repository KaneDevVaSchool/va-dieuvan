<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { ClockIcon } from '@heroicons/vue/24/outline'
import Button from '../ui/Button.vue'
import { labelTripType } from '../../util/labels'
import { formatListDateTime } from '../../util/datetime'
import { computeDeptApprovalSla } from '../../composables/useDeptApprovalSla'

const props = defineProps({
  req: { type: Object, required: true },
  selected: { type: Boolean, default: false },
  acting: { type: Boolean, default: false },
  slaHours: { type: Number, default: 24 },
  showActions: { type: Boolean, default: true },
})

defineEmits(['select', 'detail', 'approve', 'reject'])

const { t, locale } = useI18n()

const tripTypeLabel = computed(() => labelTripType(props.req.trip_type) || t('requests_page.empty_trip_type'))

const routeLine = computed(() => {
  const o = (props.req.origin || '').trim() || '—'
  const d = (props.req.destination || '').trim() || '—'
  return `${o} → ${d}`
})

const requesterName = computed(
  () => props.req.requester?.name || t('requests_page.empty_requester'),
)

const createdLabel = computed(() => {
  const v = props.req.created_at
  if (!v) return '—'
  const loc = locale.value === 'en' ? 'en' : 'vi'
  return formatListDateTime(v, loc) || '—'
})

const priceLabel = computed(() => {
  const p = props.req.service_price
  if (p == null || p === '') return null
  const n = Number(p)
  if (!Number.isFinite(n)) return null
  const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  return `${new Intl.NumberFormat(loc).format(n)} ${t('dept.currency_suffix')}`
})

const sla = computed(() => computeDeptApprovalSla(props.req, props.slaHours))

const slaClass = computed(() => {
  if (sla.value.tone === 'red') return 'text-rose-700 bg-rose-50 ring-rose-200/80 dark:text-rose-200 dark:bg-rose-950/50 dark:ring-rose-900/50'
  if (sla.value.tone === 'orange') return 'text-amber-800 bg-amber-50 ring-amber-200/80 dark:text-amber-100 dark:bg-amber-950/40 dark:ring-amber-900/40'
  if (sla.value.tone === 'green') return 'text-emerald-800 bg-emerald-50 ring-emerald-200/80 dark:text-emerald-100 dark:bg-emerald-950/40 dark:ring-emerald-900/40'
  return 'text-slate-600 bg-slate-50 ring-slate-200/80 dark:text-slate-400 dark:bg-slate-800/50 dark:ring-slate-700'
})

const slaText = computed(() => t(sla.value.labelKey, sla.value.labelParams))

const waitingLabel = computed(() => {
  if (props.req.status === 'price_filled') return t('dept.inbox_waiting_dept_head')
  if (props.req.status === 'pending') return t('dept.inbox_waiting_dispatch')
  return t('dept.inbox_waiting_none')
})

const canAct = computed(() => props.showActions && props.req.status === 'price_filled')

const isNewToday = computed(() => {
  const at = props.req.price_filled_at || props.req.created_at
  if (!at) return false
  const d = new Date(at)
  const now = new Date()
  return d.toDateString() === now.toDateString()
})
</script>

<template>
  <article
    class="group relative flex max-h-[120px] min-h-[7rem] cursor-pointer flex-col overflow-hidden rounded-lg border bg-white shadow-sm transition dark:bg-slate-900/60"
    :class="
      selected
        ? 'border-[color:var(--va-brand)]/50 ring-2 ring-[color:var(--va-brand)]/25'
        : 'border-slate-200/90 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600'
    "
    :data-testid="`dept-inbox-row-${req.id}`"
    @click="$emit('select', req.id)"
  >
    <div class="flex min-h-0 flex-1 gap-2 px-3 py-2 sm:items-stretch sm:gap-3 sm:pr-2">
      <div class="min-w-0 flex-1 space-y-0.5">
        <div class="flex flex-wrap items-center gap-1.5">
          <span class="font-mono text-xs font-bold tabular-nums text-slate-900 dark:text-slate-100">
            {{ t('dept.request_code_short', { id: req.id }) }}
          </span>
          <span
            v-if="req.is_urgent"
            class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-900 bg-amber-100 dark:bg-amber-950/60 dark:text-amber-100"
          >
            {{ t('dept.inbox_badge_urgent') }}
          </span>
          <span
            v-if="isNewToday && req.status === 'price_filled'"
            class="rounded px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-200"
          >
            {{ t('dept.inbox_badge_new') }}
          </span>
          <span
            v-if="sla.tone !== 'muted'"
            class="inline-flex items-center gap-0.5 rounded px-1.5 py-0.5 text-[10px] font-semibold tabular-nums ring-1"
            :class="slaClass"
          >
            <ClockIcon class="h-3 w-3 shrink-0" aria-hidden="true" />
            {{ slaText }}
          </span>
        </div>
        <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
          {{ tripTypeLabel }}
        </p>
        <p class="truncate text-xs text-slate-600 dark:text-slate-400">
          {{ routeLine }}
        </p>
        <div class="flex flex-wrap gap-x-3 gap-y-0 text-[11px] text-slate-500 dark:text-slate-400">
          <span class="truncate">
            <span class="font-medium text-slate-600 dark:text-slate-300">{{ t('dept.inbox_label_requester') }}:</span>
            {{ requesterName }}
          </span>
          <span class="hidden tabular-nums sm:inline">
            <span class="font-medium text-slate-600 dark:text-slate-300">{{ t('dept.inbox_label_created') }}:</span>
            {{ createdLabel }}
          </span>
          <span v-if="priceLabel" class="font-semibold tabular-nums text-slate-800 dark:text-slate-200">
            {{ priceLabel }}
          </span>
        </div>
        <p class="truncate text-[10px] text-slate-500 dark:text-slate-400">
          {{ t('dept.inbox_waiting_prefix') }} {{ waitingLabel }}
        </p>
      </div>

      <div
        class="hidden shrink-0 flex-col items-end justify-center gap-1.5 border-l border-slate-100 pl-2 dark:border-slate-800 sm:flex"
        @click.stop
      >
        <button
          type="button"
          class="rounded-lg border border-slate-200 px-2.5 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
          :data-testid="`dept-inbox-detail-${req.id}`"
          @click="$emit('detail', req.id)"
        >
          {{ t('dept.view_detail') }}
        </button>
        <template v-if="canAct">
          <Button
            class="!min-h-0 !px-2.5 !py-1 !text-[11px]"
            :loading="acting"
            :disabled="acting"
            :data-testid="`dept-inbox-approve-${req.id}`"
            @click="$emit('approve', req.id)"
          >
            {{ t('dept.approve_btn_short') }}
          </Button>
          <Button
            variant="danger"
            class="!min-h-0 !px-2.5 !py-1 !text-[11px]"
            :loading="acting"
            :disabled="acting"
            :data-testid="`dept-inbox-reject-${req.id}`"
            @click="$emit('reject', req.id)"
          >
            {{ t('dept.reject_btn') }}
          </Button>
        </template>
      </div>
    </div>

    <div
      v-if="canAct"
      class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/90 px-2 py-1.5 dark:border-slate-800 dark:bg-slate-900/80 sm:hidden"
      @click.stop
    >
      <button
        type="button"
        class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-semibold text-slate-700"
        @click="$emit('detail', req.id)"
      >
        {{ t('dept.view_detail') }}
      </button>
      <Button class="!min-h-0 flex-1 !py-1.5 !text-xs" :loading="acting" @click="$emit('approve', req.id)">
        {{ t('dept.approve_btn_short') }}
      </Button>
      <Button variant="danger" class="!min-h-0 flex-1 !py-1.5 !text-xs" :loading="acting" @click="$emit('reject', req.id)">
        {{ t('dept.reject_btn') }}
      </Button>
    </div>
  </article>
</template>
