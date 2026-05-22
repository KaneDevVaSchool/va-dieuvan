<template>
  <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <header class="flex items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/80 px-3 py-2">
      <h2 class="text-xs font-bold uppercase tracking-wide text-slate-700">
        {{ t('request_detail.audit_timeline_heading') }}
      </h2>
      <button
        v-if="!loading && items.length"
        type="button"
        class="text-[11px] font-semibold text-teal-700 hover:underline"
        @click="expanded = !expanded"
      >
        {{ expanded ? t('request_detail.audit_timeline_collapse') : t('request_detail.audit_timeline_expand') }}
      </button>
    </header>
    <div class="px-3 py-2">
      <p v-if="loading" class="py-3 text-center text-xs text-slate-500">{{ t('request_detail.audit_timeline_loading') }}</p>
      <p v-else-if="error" class="py-2 text-xs text-rose-700">{{ error }}</p>
      <p v-else-if="!items.length" class="py-3 text-center text-xs text-slate-500">{{ t('request_detail.audit_timeline_empty') }}</p>
      <ul v-else class="max-h-64 space-y-2 overflow-y-auto" :class="expanded ? '' : 'max-h-40'">
        <li
          v-for="row in visibleItems"
          :key="row.id"
          class="rounded-md border border-slate-100 bg-slate-50/50 px-2.5 py-2 text-xs"
        >
          <p class="font-semibold text-slate-800">{{ eventLabel(row.event) }}</p>
          <p class="mt-0.5 text-[11px] text-slate-600">
            {{ row.actor?.name ?? '—' }}
            <span class="text-slate-400"> · {{ formatDateTime(row.created_at) }}</span>
          </p>
        </li>
      </ul>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
  formatDateTime: { type: Function, required: true },
})

const { t } = useI18n()
const expanded = ref(false)

const visibleItems = computed(() => {
  if (expanded.value) return props.items
  return props.items.slice(0, 8)
})

function eventLabel(event) {
  const key = `request_detail.audit_event_${String(event || '').replace(/\./g, '_')}`
  const translated = t(key)
  return translated !== key ? translated : event || '—'
}
</script>
