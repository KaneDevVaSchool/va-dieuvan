<template>
  <div v-if="alert || budgetAlert" class="space-y-3">
    <div
      v-if="alert"
      role="status"
      class="rounded-2xl border border-amber-200/90 bg-amber-50 px-4 py-3 text-sm text-amber-950 shadow-sm ring-1 ring-amber-600/10"
    >
      <p class="font-semibold">
        {{
          alert.severity === 'exceeded'
            ? t('request_detail.package_cost_alert_exceeded_title')
            : t('request_detail.package_cost_alert_warning_title')
        }}
      </p>
      <p class="mt-1 text-xs leading-relaxed text-amber-900/90">
        {{
          t('request_detail.package_cost_alert_body', {
            label: alert.package_label,
            used: alert.sessions_used,
            total: alert.total_sessions,
            remaining: alert.sessions_remaining,
            threshold: alert.threshold_sessions,
          })
        }}
      </p>
      <p class="mt-2 text-xs text-amber-800/90">{{ t('request_detail.package_cost_alert_note') }}</p>
    </div>
    <div
      v-if="budgetAlert"
      role="status"
      class="rounded-2xl border border-rose-200/90 bg-rose-50 px-4 py-3 text-sm text-rose-950 shadow-sm ring-1 ring-rose-600/10"
    >
      <p class="font-semibold">{{ t('request_detail.package_budget_alert_title') }}</p>
      <p class="mt-1 text-xs leading-relaxed text-rose-900/90">
        {{
          t('request_detail.package_budget_alert_body', {
            label: budgetAlert.package_label,
            month: budgetAlert.month,
            cost: formatVnd(budgetAlert.monthly_cost),
            budget: formatVnd(budgetAlert.monthly_budget),
          })
        }}
      </p>
      <p class="mt-2 text-xs text-rose-800/90">{{ t('request_detail.package_budget_alert_note') }}</p>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  /** @type {{ severity: 'warning'|'exceeded', package_label: string, sessions_used: number, total_sessions: number, sessions_remaining: number, threshold_sessions: number }|null|undefined} */
  alert: { type: Object, default: null },
  /** @type {{ severity: string, package_label: string, month: number, monthly_cost: number, monthly_budget: number }|null|undefined} */
  budgetAlert: { type: Object, default: null },
})

const { t } = useI18n()

function formatVnd(n) {
  const num = Number(n)
  if (!Number.isFinite(num)) return '—'
  return `${new Intl.NumberFormat('vi-VN').format(num)} VND`
}
</script>
