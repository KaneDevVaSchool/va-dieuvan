<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  status: { type: String, default: '' },
  compact: { type: Boolean, default: false },
  highlightYou: { type: Boolean, default: true },
})

const { t } = useI18n()

const steps = computed(() => {
  const st = props.status
  const items = [
    { key: 'requester', label: t('dept.inbox_timeline_requester') },
    { key: 'dispatch', label: t('dept.inbox_timeline_dispatch') },
    { key: 'dept', label: props.highlightYou ? t('dept.inbox_timeline_dept_you') : t('dept.inbox_timeline_dept') },
    { key: 'done', label: t('dept.inbox_timeline_done') },
  ]

  let active = 0
  if (st === 'pending') active = 1
  else if (st === 'price_filled') active = 2
  else if (st === 'approved') active = 3
  else if (st === 'rejected') active = 2

  return items.map((item, i) => {
    let state = 'upcoming'
    if (st === 'rejected' && item.key === 'dept') state = 'rejected'
    else if (i < active) state = 'done'
    else if (i === active) state = 'current'
    return { ...item, state }
  })
})

const connectorDoneThrough = computed(() => {
  const list = steps.value
  const cur = list.findIndex((s) => s.state === 'current' || s.state === 'rejected')
  return cur >= 0 ? cur : list.length
})
</script>

<template>
  <ol
    class="flex min-w-0 items-center gap-0"
    :class="compact ? 'text-[10px]' : 'text-xs'"
    :aria-label="t('dept.inbox_timeline_aria')"
  >
    <template v-for="(step, idx) in steps" :key="step.key">
      <li class="flex min-w-0 flex-1 flex-col items-center gap-0.5">
        <span
          class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[9px] font-bold ring-2"
          :class="{
            'bg-emerald-500 text-white ring-emerald-200 dark:ring-emerald-800': step.state === 'done',
            'bg-[color:var(--va-brand)] text-white ring-[color:var(--va-brand)]/30': step.state === 'current',
            'bg-rose-500 text-white ring-rose-200 dark:ring-rose-900': step.state === 'rejected',
            'bg-slate-100 text-slate-400 ring-slate-200 dark:bg-slate-800 dark:text-slate-500 dark:ring-slate-700':
              step.state === 'upcoming',
          }"
        >
          <span v-if="step.state === 'done'" aria-hidden="true">✓</span>
          <span v-else-if="step.state === 'rejected'" aria-hidden="true">×</span>
          <span v-else>{{ idx + 1 }}</span>
        </span>
        <span
          class="max-w-full truncate text-center font-medium leading-tight"
          :class="
            step.state === 'current'
              ? 'text-[color:var(--va-brand)] dark:text-rose-200'
              : 'text-slate-500 dark:text-slate-400'
          "
        >
          {{ step.label }}
        </span>
      </li>
      <div
        v-if="idx < steps.length - 1"
        class="mx-0.5 mt-2.5 h-px min-w-[0.5rem] flex-1 self-start"
        :class="idx < connectorDoneThrough ? 'bg-emerald-400' : 'bg-slate-200 dark:bg-slate-700'"
        aria-hidden="true"
      />
    </template>
  </ol>
</template>
