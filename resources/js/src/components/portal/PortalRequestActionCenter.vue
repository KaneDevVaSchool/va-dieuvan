<template>
  <section
    class="overflow-hidden rounded-xl border border-slate-200 bg-white"
    :aria-label="t('portal.action_center.aria')"
  >
    <div class="border-b border-va-100 bg-va-50/80 px-4 py-3 sm:px-5">
      <h2 class="text-sm font-bold text-va-900">{{ t('portal.action_center.heading') }}</h2>
    </div>
    <div class="grid gap-0 sm:grid-cols-2 lg:grid-cols-4">
      <div class="border-b border-slate-100 px-4 py-4 sm:border-b-0 sm:border-r lg:col-span-1">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t('portal.action_center.waiting_label') }}</p>
        <p class="mt-1.5 text-sm font-bold text-slate-900">{{ waitingOnLabel }}</p>
      </div>
      <div v-if="dueLabel" class="border-b border-slate-100 px-4 py-4 sm:border-b-0 sm:border-r">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t('portal.action_center.due_label') }}</p>
        <p class="mt-1.5 text-sm font-bold text-slate-900">{{ dueLabel }}</p>
      </div>
      <div v-if="slaLabel" class="border-b border-slate-100 px-4 py-4 sm:border-b-0 sm:border-r lg:border-b-0">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t('portal.action_center.sla_label') }}</p>
        <p
          class="mt-1.5 text-sm font-bold"
          :class="slaUrgent ? 'text-rose-700' : 'text-slate-900'"
        >
          {{ slaLabel }}
        </p>
      </div>
      <div class="px-4 py-4 sm:col-span-2 lg:col-span-1">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t('portal.action_center.next_label') }}</p>
        <p class="mt-1.5 text-sm font-medium leading-snug text-slate-800">{{ nextActionText }}</p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  waitingOnLabel: { type: String, required: true },
  dueLabel: { type: String, default: '' },
  slaLabel: { type: String, default: '' },
  nextActionText: { type: String, required: true },
  hoursUntilDepart: { type: Number, default: null },
  urgentThresholdHours: { type: Number, default: 24 },
})

const { t } = useI18n()

const slaUrgent = computed(() => {
  const h = props.hoursUntilDepart
  if (h == null) return false
  return h >= 0 && h <= (props.urgentThresholdHours ?? 24)
})
</script>
