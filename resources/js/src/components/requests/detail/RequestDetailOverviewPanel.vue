<template>
  <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <div class="rounded-lg border border-slate-200 bg-white p-4 sm:col-span-2 lg:col-span-1">
      <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
        {{ t('request_detail.aside_requester') }}
      </h2>
      <div class="mt-3 flex items-start gap-3">
        <img
          v-if="avatarUrl"
          :src="avatarUrl"
          alt=""
          class="h-10 w-10 shrink-0 rounded-full object-cover"
        />
        <div
          v-else
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700"
        >
          {{ initials }}
        </div>
        <div class="min-w-0">
          <p class="font-semibold text-slate-900">{{ requesterName }}</p>
          <p v-if="requesterSubtitle" class="mt-0.5 text-sm text-slate-600">{{ requesterSubtitle }}</p>
          <dl v-if="requesterFields.length" class="mt-3 space-y-1.5 text-sm">
            <div v-for="row in requesterFields" :key="row.key" class="flex gap-2">
              <dt class="shrink-0 text-slate-500">{{ row.label }}</dt>
              <dd class="min-w-0 text-slate-800">{{ row.value }}</dd>
            </div>
          </dl>
        </div>
      </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-4">
      <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
        {{ t('request_detail.aside_vehicle_request') }}
      </h2>
      <p class="mt-3 text-sm font-semibold text-slate-900">{{ tripTypeLabel }}</p>
      <p class="mt-2 text-sm text-slate-700">{{ loadLine }}</p>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-4">
      <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
        {{ t('request_detail.route_map_heading') }}
      </h2>
      <p class="mt-3 text-sm font-medium text-slate-900">{{ origin }}</p>
      <p class="mt-1 text-xs text-slate-500">→</p>
      <p class="mt-1 text-sm font-medium text-slate-900">{{ destination }}</p>
      <p v-if="scheduleLine" class="mt-3 text-xs text-slate-600">{{ scheduleLine }}</p>
    </div>
  </section>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  avatarUrl: { type: String, default: '' },
  initials: { type: String, default: '?' },
  requesterName: { type: String, default: '—' },
  requesterSubtitle: { type: String, default: '' },
  requesterFields: { type: Array, default: () => [] },
  tripTypeLabel: { type: String, default: '—' },
  loadLine: { type: String, default: '—' },
  origin: { type: String, default: '—' },
  destination: { type: String, default: '—' },
  scheduleLine: { type: String, default: '' },
})

const { t } = useI18n()
</script>
