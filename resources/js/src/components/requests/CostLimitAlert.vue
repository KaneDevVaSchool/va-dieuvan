<template>
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
</template>

<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  /** @type {{ severity: 'warning'|'exceeded', package_label: string, sessions_used: number, total_sessions: number, sessions_remaining: number, threshold_sessions: number }|null|undefined} */
  alert: { type: Object, default: null },
})

const { t } = useI18n()
</script>
