<template>
  <div
    v-if="showDeptHeadPanel"
    :class="
      variant === 'bm03'
        ? 'rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-4 sm:px-4'
        : 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900'
    "
  >
    <div
      :class="
        variant === 'bm03'
          ? 'mb-3 flex items-center gap-2'
          : 'flex items-center gap-2.5 border-b border-slate-100 pb-3 dark:border-slate-800'
      "
    >
      <span
        :class="
          variant === 'bm03'
            ? 'inline-flex items-center justify-center rounded bg-slate-800 px-2 py-0.5 text-xs font-bold text-white'
            : 'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700 dark:bg-teal-950/40 dark:text-teal-400'
        "
      >
        <ShieldCheckIcon v-if="variant === 'staff'" class="h-4 w-4" aria-hidden="true" />
        <span v-else aria-hidden="true">TB</span>
      </span>
      <h2
        :class="
          variant === 'bm03'
            ? 'text-[11px] font-bold uppercase tracking-wide text-slate-700'
            : 'text-sm font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300'
        "
      >
        {{ t('request_detail.assign_dept_head_preset_label') }}
      </h2>
    </div>

    <div :class="variant === 'staff' ? 'mt-4' : 'mt-1'">
      <p
        v-if="showAssignedDeptHeadOnForm"
        :class="
          variant === 'bm03'
            ? 'text-sm font-semibold text-slate-900'
            : 'rounded-xl border border-slate-200/80 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100'
        "
      >
        {{ deptHeadDisplayLine }}
      </p>
      <div
        v-else
        class="rounded-xl border border-amber-200 bg-amber-50/80 px-3 py-2.5 dark:border-amber-900/50 dark:bg-amber-950/25"
      >
        <p class="text-xs font-medium text-amber-900 dark:text-amber-100">
          {{ t('request_detail.assign_dept_head_missing_staff_title') }}
        </p>
      </div>
      <p v-if="deptHeadsLoadErr" class="mt-2 text-xs font-medium text-rose-600">{{ deptHeadsLoadErr }}</p>
    </div>
  </div>
</template>

<script setup>
import { toRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { ShieldCheckIcon } from '@heroicons/vue/24/outline'
import { useAssignedDeptHeadDisplay } from '../../composables/useAssignedDeptHeadDisplay'

const props = defineProps({
  req: { type: Object, default: null },
  variant: {
    type: String,
    default: 'staff',
    validator: (v) => ['staff', 'bm03'].includes(v),
  },
})

const { t } = useI18n()

const {
  showDeptHeadPanel,
  showAssignedDeptHeadOnForm,
  deptHeadDisplayLine,
  deptHeadsLoadErr,
} = useAssignedDeptHeadDisplay(toRef(props, 'req'))
</script>
