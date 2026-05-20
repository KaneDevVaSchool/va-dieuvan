<template>
  <nav
    class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-900/[0.03] dark:border-slate-700 dark:bg-slate-900/70 dark:ring-white/5"
    :aria-label="t('p2p_policy_page.workflow_bar_aria')"
  >
    <ol class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
      <li class="flex min-w-0 flex-1 flex-wrap items-center gap-1 sm:gap-2">
        <template v-for="(step, idx) in P2P_WORKFLOW_STEPS" :key="step.key">
          <RouterLink
            v-if="stepLink(step)"
            :to="stepLink(step)"
            class="inline-flex max-w-full items-center gap-2 rounded-lg px-2 py-1.5 text-sm transition"
            :class="stepClass(step.key)"
          >
            <span
              class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-xs font-bold"
              :class="stepBadgeClass(step.key)"
            >
              {{ idx + 1 }}
            </span>
            <span class="truncate font-medium">{{ t(step.titleKey) }}</span>
          </RouterLink>
          <span
            v-else
            class="inline-flex max-w-full items-center gap-2 rounded-lg px-2 py-1.5 text-sm opacity-50"
            :class="stepClass(step.key)"
          >
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-slate-200 text-xs font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-300">
              {{ idx + 1 }}
            </span>
            <span class="truncate font-medium">{{ t(step.titleKey) }}</span>
          </span>
          <ChevronRightIcon
            v-if="idx < P2P_WORKFLOW_STEPS.length - 1"
            class="hidden h-4 w-4 shrink-0 text-slate-300 sm:inline dark:text-slate-600"
            aria-hidden="true"
          />
        </template>
      </li>
      <li v-if="showHandoff" class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
        <RouterLink
          v-if="prevStep"
          :to="p2pStepTo(prevStep.routeName, termId)"
          class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
        >
          {{ t('p2p_policy_page.workflow_prev') }}
        </RouterLink>
        <RouterLink
          v-if="nextStep && nextStepLink"
          :to="nextStepLink"
          class="rounded-lg bg-teal-700 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-800"
        >
          {{ nextLabel }}
        </RouterLink>
        <span
          v-else-if="nextStep && !nextStepLink"
          class="rounded-lg bg-slate-200 px-3 py-2 text-sm font-medium text-slate-500 dark:bg-slate-700 dark:text-slate-400"
          :title="t('p2p_policy_page.workflow_need_term')"
        >
          {{ nextLabel }}
        </span>
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { ChevronRightIcon } from '@heroicons/vue/24/outline'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  P2P_WORKFLOW_STEPS,
  p2pStepTo,
  p2pWorkflowStepIndex,
} from '../../composables/useP2pPolicyWorkflow'

const props = defineProps({
  currentStep: {
    type: String,
    required: true,
    validator: (v) => P2P_WORKFLOW_STEPS.some((s) => s.key === v),
  },
  termId: {
    type: [Number, String, null],
    default: null,
  },
  showHandoff: {
    type: Boolean,
    default: true,
  },
})

const { t } = useI18n()

const stepIndex = computed(() => p2pWorkflowStepIndex(props.currentStep))

const prevStep = computed(() => {
  const i = stepIndex.value
  if (i <= 0) return null
  return P2P_WORKFLOW_STEPS[i - 1]
})

const nextStep = computed(() => {
  const i = stepIndex.value
  if (i < 0 || i >= P2P_WORKFLOW_STEPS.length - 1) return null
  return P2P_WORKFLOW_STEPS[i + 1]
})

const nextStepLink = computed(() => {
  if (!nextStep.value) return null
  if (nextStep.value.key === 'routes' || nextStep.value.key === 'students' || nextStep.value.key === 'activate') {
    if (!props.termId) return null
  }
  return p2pStepTo(nextStep.value.routeName, props.termId)
})

const nextLabel = computed(() => {
  const key = nextStep.value?.key
  if (key === 'routes') return t('p2p_policy_page.workflow_next_routes')
  if (key === 'students') return t('p2p_policy_page.workflow_next_students')
  if (key === 'activate') return t('p2p_policy_page.workflow_next_activate')
  return t('p2p_policy_page.workflow_next')
})

function stepLink(step) {
  if (step.key === 'term') {
    return p2pStepTo(step.routeName, props.termId)
  }
  if (!props.termId) return null
  return p2pStepTo(step.routeName, props.termId)
}

function stepClass(stepKey) {
  if (stepKey === props.currentStep) {
    return 'bg-teal-50 text-teal-900 ring-1 ring-teal-200/80 dark:bg-teal-950/40 dark:text-teal-100 dark:ring-teal-800/50'
  }
  return 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800/60'
}

function stepBadgeClass(stepKey) {
  if (stepKey === props.currentStep) {
    return 'bg-teal-600 text-white'
  }
  return 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
}
</script>
