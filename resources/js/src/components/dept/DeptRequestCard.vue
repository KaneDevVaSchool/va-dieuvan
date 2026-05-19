<template>
  <article
    class="overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:shadow-md"
    :class="
      emphasize
        ? 'border-rose-200/80 bg-rose-50/30 ring-1 ring-rose-100/80'
        : 'border-slate-200/70 bg-white'
    "
  >
    <div class="px-4 py-4 sm:px-5 sm:py-5">
      <div class="flex flex-wrap items-center gap-2">
        <span class="text-base font-bold text-[#800020]">{{ t('dept.request_code_short', { id: req.id }) }}</span>
        <span
          class="rounded-full bg-slate-100/90 px-3 py-1 text-sm font-semibold text-slate-700"
        >
          {{ tripTypeLabel }}
        </span>
        <span
          class="rounded-full px-3 py-1 text-sm font-semibold"
          :class="statusBadgeClass"
        >
          {{ statusLabel }}
        </span>
      </div>
      <h3 class="mt-2.5 text-lg font-bold leading-snug text-slate-900 sm:text-xl">
        {{ cardTitle }}
      </h3>
      <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-base text-slate-600">
        <span class="inline-flex items-center gap-1.5">
          <UserIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
          {{ req.requester?.name ?? '—' }}
        </span>
        <span class="inline-flex items-center gap-1.5">
          <CalendarDaysIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
          {{ departLabel }}
        </span>
        <span v-if="priceLabel" class="inline-flex items-center gap-1.5 font-semibold text-slate-800">
          <BanknotesIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
          {{ priceLabel }}
        </span>
      </div>
      <p v-if="req.rejection_reason && showRejection" class="mt-2 text-base text-rose-700">
        {{ req.rejection_reason }}
      </p>
      <div class="mt-4 flex flex-wrap gap-2">
        <button
          type="button"
          class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-slate-200/80 bg-white px-4 text-base font-semibold text-slate-800 hover:bg-slate-50"
          @click="$emit('detail', req.id)"
        >
          {{ t('dept.view_detail') }}
        </button>
        <template v-if="showActions">
          <Button
            class="min-h-[44px] flex-1 min-w-[8rem] justify-center !bg-[#800020] hover:!bg-[#6b0a1f]"
            :loading="acting"
            :disabled="acting"
            @click="$emit('approve', req.id)"
          >
            <CheckCircleIcon class="mr-1.5 h-5 w-5" aria-hidden="true" />
            {{ t('dept.approve_btn') }}
          </Button>
          <Button
            variant="danger"
            class="min-h-[44px] flex-1 min-w-[8rem] justify-center"
            :loading="acting"
            :disabled="acting"
            @click="$emit('reject', req.id)"
          >
            {{ t('dept.reject_btn') }}
          </Button>
        </template>
      </div>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  BanknotesIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  UserIcon,
} from '@heroicons/vue/24/outline'
import Button from '../ui/Button.vue'
import { labelTripType, labelRequestStatus } from '../../util/labels'

const props = defineProps({
  req: { type: Object, required: true },
  /** Phiếu đã có giá — nhấn mạnh viền trái như mockup */
  emphasize: { type: Boolean, default: false },
  showActions: { type: Boolean, default: false },
  acting: { type: Boolean, default: false },
  showRejection: { type: Boolean, default: false },
})

defineEmits(['detail', 'approve', 'reject'])

const { t, locale } = useI18n()

const tripTypeLabel = computed(() => {
  const k = props.req.trip_type
  if (!k) return '—'
  return labelTripType(k)
})

const statusLabel = computed(() => {
  const s = props.req.status
  if (s === 'price_filled') return t('dept.badge_new_pending')
  if (s === 'pending') return t('dept.badge_waiting_price')
  return labelRequestStatus(s)
})

const statusBadgeClass = computed(() => {
  const s = props.req.status
  if (s === 'price_filled') return 'bg-rose-100 text-rose-800'
  if (s === 'pending') return 'bg-amber-100 text-amber-900'
  if (s === 'approved') return 'bg-emerald-100 text-emerald-800'
  if (s === 'rejected') return 'bg-rose-50 text-rose-800'
  return 'bg-slate-100 text-slate-700'
})

const cardTitle = computed(() => {
  const o = (props.req.origin || '').trim()
  const d = (props.req.destination || '').trim()
  if (o && d) return `${o} — ${d}`
  return o || d || props.req.notes?.slice(0, 120) || t('dept.request_code_short', { id: props.req.id })
})

const departLabel = computed(() => {
  const raw = props.req.depart_at
  if (!raw) return '—'
  try {
    const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
    return new Date(raw).toLocaleString(loc, {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    })
  } catch {
    return '—'
  }
})

const priceLabel = computed(() => {
  const p = props.req.service_price
  if (p == null || p === '') return null
  const n = Number(p)
  if (!Number.isFinite(n)) return null
  const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  const num = new Intl.NumberFormat(loc).format(n)
  return `${num} ${t('dept.currency_suffix')}`
})
</script>
