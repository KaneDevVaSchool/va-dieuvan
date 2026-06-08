<template>
  <section
    class="rounded-2xl border border-slate-200/90 bg-gradient-to-br from-va-50/40 via-slate-50/80 to-white p-5 shadow-sm ring-1 ring-slate-900/5"
  >
    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t('portal.status_hint_title') }}</p>
    <p class="mt-2 text-sm leading-relaxed text-slate-800">{{ hintText }}</p>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  req: { type: Object, required: true },
})

const { t } = useI18n()

const hintKey = computed(() => {
  const r = props.req
  const st = r.status
  const tripSt = r.trip?.status
  if (st === 'pending') return 'portal.next_step_hint.pending'
  if (st === 'price_filled') return 'portal.next_step_hint.price_filled'
  if (st === 'rejected') return 'portal.next_step_hint.rejected'
  if (st === 'cancelled') return 'portal.next_step_hint.cancelled'
  if (st === 'draft') return 'portal.next_step_hint.draft'
  if (st === 'approved') {
    if (!tripSt || tripSt === 'approved') return 'portal.next_step_hint.approved_dispatch'
    if (tripSt === 'assigned' || tripSt === 'driver_confirmed') return 'portal.next_step_hint.dispatched'
    if (tripSt === 'in_progress') return 'portal.next_step_hint.running'
    if (tripSt === 'completed') return 'portal.next_step_hint.completed'
    return 'portal.next_step_hint.approved_dispatch'
  }
  return 'portal.next_step_hint.generic'
})

const hintText = computed(() => t(hintKey.value))
</script>
