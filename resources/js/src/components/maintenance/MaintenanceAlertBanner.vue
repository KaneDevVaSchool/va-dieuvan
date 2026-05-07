<template>
  <div
    v-if="visible && urgentItem"
    class="relative mb-3 flex items-start gap-3 overflow-hidden rounded-2xl border-l-4 border-[#ff6b6b] bg-[#2a1010] px-4 py-3 shadow-lg ring-1 ring-[#ff6b6b]/20"
  >
    <!-- Warning icon -->
    <div class="mt-0.5 shrink-0">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
        class="h-6 w-6 text-[#ff6b6b]" aria-hidden="true">
        <path fill-rule="evenodd"
          d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
          clip-rule="evenodd" />
      </svg>
    </div>

    <div class="min-w-0 flex-1">
      <p class="text-[17px] font-bold leading-snug text-white">
        {{ t('driver_maintenance.alert_title') }} {{ urgentItem.name }}
      </p>
      <p class="mt-0.5 text-sm text-[#ff9999]">
        {{ t('driver_maintenance.alert_subtitle', {
          days: urgentItem.days_remaining ?? '—',
          date: formatDate(urgentItem.expiry_date)
        }) }}
      </p>
      <button
        type="button"
        class="mt-2.5 inline-flex min-h-[36px] items-center rounded-full bg-[#1dbc5e] px-4 py-1.5 text-sm font-semibold text-white active:scale-95"
        @click="$emit('cta')"
      >
        {{ t('driver_maintenance.alert_cta') }}
      </button>
    </div>

    <!-- Dismiss -->
    <button
      type="button"
      class="absolute right-3 top-3 flex h-6 w-6 items-center justify-center rounded-full text-[#ff9999]/70 hover:text-white"
      :aria-label="t('driver_maintenance.alert_dismiss')"
      @click="visible = false"
    >
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
      </svg>
    </button>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  items: { type: Array, default: () => [] },
})

defineEmits(['cta'])

const visible = ref(true)

const urgentItem = computed(() =>
  props.items.find((i) => i.status === 'urgent') ?? null,
)

function formatDate(ymd) {
  if (!ymd) return '—'
  const [y, m, d] = String(ymd).split('-')
  return `${d}/${m}/${y}`
}
</script>
