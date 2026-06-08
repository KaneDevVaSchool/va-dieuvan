<template>
  <section
    class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 sm:p-5"
    :aria-label="t('portal.action_center.aria')"
  >
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="min-w-0 sm:col-span-2 lg:col-span-1">
        <p class="text-xs font-medium text-slate-500">{{ t('portal.action_center.waiting_label') }}</p>
        <p class="mt-1 text-sm font-semibold text-slate-900">{{ waitingOnLabel }}</p>
      </div>
      <div v-if="dueLabel" class="min-w-0">
        <p class="text-xs font-medium text-slate-500">{{ t('portal.action_center.due_label') }}</p>
        <p class="mt-1 text-sm font-semibold text-slate-900">{{ dueLabel }}</p>
      </div>
      <div v-if="slaLabel" class="min-w-0">
        <p class="text-xs font-medium text-slate-500">{{ t('portal.action_center.sla_label') }}</p>
        <p
          class="mt-1 text-sm font-semibold"
          :class="slaUrgent ? 'text-rose-700' : 'text-slate-900'"
        >
          {{ slaLabel }}
        </p>
      </div>
      <div class="min-w-0 sm:col-span-2 lg:col-span-1">
        <p class="text-xs font-medium text-slate-500">{{ t('portal.action_center.next_label') }}</p>
        <p class="mt-1 text-sm leading-snug text-slate-800">{{ nextActionText }}</p>
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
