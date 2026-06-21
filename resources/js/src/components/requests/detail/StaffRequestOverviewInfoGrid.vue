<template>
  <section
    class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
    :aria-label="t('request_detail.overview_grid_aria')"
    data-testid="staff-request-overview-grid"
  >
    <div class="space-y-8">
      <div v-for="group in groups" :key="group.key" class="min-w-0">
        <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          {{ group.title }}
        </h2>
        <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="field in group.fields" :key="field.key" class="min-w-0">
            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
              {{ field.label }}
            </dt>
            <dd class="mt-1 text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100">
              <RouterLink
                v-if="field.to"
                :to="field.to"
                class="font-mono text-va-800 underline decoration-va-300 underline-offset-2 hover:decoration-va-600 dark:text-va-300 dark:decoration-va-700"
              >
                {{ field.value || t('request_detail.ops_no_data') }}
              </RouterLink>
              <span v-else :class="field.muted ? 'font-normal italic text-slate-400 dark:text-slate-500' : ''">
                {{ field.value || t('request_detail.ops_no_data') }}
              </span>
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </section>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'

defineProps({
  groups: { type: Array, required: true },
})

const { t } = useI18n()
</script>
