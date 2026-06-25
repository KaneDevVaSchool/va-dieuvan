<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { getDispatchRequest } from '../../api/requests'
import { formatApiError } from '../../api/http'
import DeptApprovalStepTimeline from './DeptApprovalStepTimeline.vue'
import { computeDeptApprovalSla } from '../../composables/useDeptApprovalSla'
import { labelTripType } from '../../util/labels'
import { formatListDateTime } from '../../util/datetime'
import Button from '../ui/Button.vue'
import { ClockIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  requestId: { type: Number, default: null },
  slaHours: { type: Number, default: 24 },
  acting: { type: Boolean, default: false },
})

const emit = defineEmits(['approve', 'reject', 'open-full'])

const { t, locale } = useI18n()

const loading = ref(false)
const loadError = ref('')
const req = ref(null)

const canAct = computed(() => req.value?.status === 'price_filled')

const sla = computed(() => (req.value ? computeDeptApprovalSla(req.value, props.slaHours) : null))

const slaClass = computed(() => {
  const tone = sla.value?.tone
  if (tone === 'red') return 'text-rose-700 bg-rose-50 ring-rose-200/80'
  if (tone === 'orange') return 'text-amber-800 bg-amber-50 ring-amber-200/80'
  if (tone === 'green') return 'text-emerald-800 bg-emerald-50 ring-emerald-200/80'
  return 'text-slate-600 bg-slate-50 ring-slate-200/80'
})

async function load(id) {
  if (!id) {
    req.value = null
    return
  }
  loading.value = true
  loadError.value = ''
  try {
    req.value = await getDispatchRequest(id)
  } catch (e) {
    loadError.value = formatApiError(e, t('dept.load_error'))
    req.value = null
  } finally {
    loading.value = false
  }
}

watch(
  () => props.requestId,
  (id) => {
    load(id)
  },
  { immediate: true },
)

function fmtDate(v) {
  if (!v) return '—'
  const loc = locale.value === 'en' ? 'en' : 'vi'
  return formatListDateTime(v, loc) || '—'
}

const priceLabel = computed(() => {
  const p = req.value?.service_price
  if (p == null) return '—'
  const n = Number(p)
  if (!Number.isFinite(n)) return '—'
  const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  return `${new Intl.NumberFormat(loc).format(n)} ${t('dept.currency_suffix')}`
})
</script>

<template>
  <div
    class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
    data-testid="dept-approval-detail-panel"
  >
    <div v-if="!requestId" class="flex flex-1 items-center justify-center p-8 text-sm text-slate-500">
      {{ t('dept.inbox_detail_placeholder') }}
    </div>
    <div v-else-if="loading" class="flex flex-1 items-center justify-center p-8 text-sm text-slate-500">
      {{ t('dept.loading') }}
    </div>
    <div v-else-if="loadError" class="p-4 text-sm text-rose-700">{{ loadError }}</div>
    <template v-else-if="req">
      <div class="shrink-0 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div class="min-w-0">
            <p class="font-mono text-lg font-bold text-slate-900 dark:text-slate-50">
              {{ t('dept.request_code_short', { id: req.id }) }}
            </p>
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">
              {{ labelTripType(req.trip_type) }}
            </p>
          </div>
          <span
            v-if="sla && sla.tone !== 'muted'"
            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold ring-1"
            :class="slaClass"
          >
            <ClockIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
            {{ t(sla.labelKey, sla.labelParams) }}
          </span>
        </div>
      </div>

      <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-4 py-4">
        <section>
          <h3 class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-500">
            {{ t('dept.inbox_timeline_section') }}
          </h3>
          <DeptApprovalStepTimeline :status="req.status" />
        </section>

        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
          <div>
            <dt class="text-[11px] font-semibold uppercase text-slate-500">{{ t('trips_page.col_origin') }}</dt>
            <dd class="font-medium text-slate-900 dark:text-slate-100">{{ req.origin || '—' }}</dd>
          </div>
          <div>
            <dt class="text-[11px] font-semibold uppercase text-slate-500">{{ t('trips_page.col_destination') }}</dt>
            <dd class="font-medium text-slate-900 dark:text-slate-100">{{ req.destination || '—' }}</dd>
          </div>
          <div>
            <dt class="text-[11px] font-semibold uppercase text-slate-500">{{ t('requests_page.col_requester') }}</dt>
            <dd class="font-medium">{{ req.requester?.name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-[11px] font-semibold uppercase text-slate-500">{{ t('requests_page.meta_created') }}</dt>
            <dd class="tabular-nums">{{ fmtDate(req.created_at) }}</dd>
          </div>
          <div>
            <dt class="text-[11px] font-semibold uppercase text-slate-500">{{ t('dept.inbox_label_price') }}</dt>
            <dd class="font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ priceLabel }}</dd>
          </div>
          <div v-if="req.notes">
            <dt class="text-[11px] font-semibold uppercase text-slate-500">{{ t('dept.inbox_label_notes') }}</dt>
            <dd class="whitespace-pre-wrap text-slate-700 dark:text-slate-300">{{ req.notes }}</dd>
          </div>
        </dl>
      </div>

      <div
        class="sticky bottom-0 flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-white/95 px-4 py-3 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95"
      >
        <button
          type="button"
          class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200"
          data-testid="dept-detail-open-full"
          @click="emit('open-full', req.id)"
        >
          {{ t('dept.view_detail') }}
        </button>
        <template v-if="canAct">
          <Button variant="danger" :loading="acting" :disabled="acting" @click="emit('reject', req.id)">
            {{ t('dept.reject_btn') }}
          </Button>
          <Button :loading="acting" :disabled="acting" @click="emit('approve', req.id)">
            {{ t('dept.approve_btn') }}
          </Button>
        </template>
      </div>
    </template>
  </div>
</template>
