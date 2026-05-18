<template>
  <div>
    <!-- Desktop -->
    <div class="hidden overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
      <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        <thead class="bg-slate-50/90 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="whitespace-nowrap px-4 py-3">{{ t('portal.table_code') }}</th>
            <th class="min-w-[12rem] px-4 py-3">{{ t('portal.table_route') }}</th>
            <th class="whitespace-nowrap px-4 py-3">{{ t('portal.table_time') }}</th>
            <th class="whitespace-nowrap px-4 py-3">{{ t('portal.table_status') }}</th>
            <th class="w-14 px-4 py-3 text-right" :aria-label="t('portal.table_action')"><span class="sr-only">{{ t('portal.table_action') }}</span></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="req in requests" :key="req.id" class="group/row transition hover:bg-indigo-50/40">
            <td class="whitespace-nowrap px-4 py-3 font-mono text-sm font-semibold text-slate-900">#{{ req.id }}</td>
            <td class="px-4 py-3">
              <p class="font-medium text-slate-800">{{ routeLine(req) }}</p>
              <p v-if="tripTypeLabel(req)" class="mt-0.5 text-xs text-slate-500">{{ tripTypeLabel(req) }}</p>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">
              {{ timeCell(req) }}
            </td>
            <td class="px-4 py-3">
              <StatusBadge :status="req.status" size="sm" />
            </td>
            <td class="px-4 py-3 text-right">
              <RouterLink
                :to="{ name: 'portalRequestDetail', params: { id: String(req.id) } }"
                class="inline-flex min-h-[40px] min-w-[40px] items-center justify-center rounded-xl text-slate-400 transition group-hover/row:bg-indigo-50 group-hover/row:text-indigo-500 hover:text-indigo-600"
                :aria-label="t('portal.open_request', { id: req.id })"
              >
                <ChevronRightIcon class="h-5 w-5" />
              </RouterLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile -->
    <div class="space-y-3 md:hidden">
      <RouterLink
        v-for="req in requests"
        :key="req.id"
        :to="{ name: 'portalRequestDetail', params: { id: String(req.id) } }"
        class="group/card flex gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-indigo-200/80 hover:bg-indigo-50/30"
      >
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-2">
            <span class="font-mono text-sm font-semibold text-slate-900">#{{ req.id }}</span>
            <StatusBadge :status="req.status" size="sm" />
          </div>
          <p class="mt-2 text-sm font-medium text-slate-800">{{ routeLine(req) }}</p>
          <p v-if="tripTypeLabel(req)" class="mt-1 text-xs text-slate-500">{{ tripTypeLabel(req) }}</p>
          <p class="mt-2 text-xs text-slate-600">{{ timeCell(req) }}</p>
        </div>
        <ChevronRightIcon class="h-5 w-5 shrink-0 text-slate-400 transition group-hover/card:text-indigo-500" aria-hidden="true" />
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { ChevronRightIcon } from '@heroicons/vue/24/outline'
import StatusBadge from '../ui/StatusBadge.vue'

defineProps({
  requests: { type: Array, required: true },
})

const { t, locale } = useI18n()

const localeTag = computed(() => (locale.value === 'vi' ? 'vi-VN' : 'en-US'))

function routeLine(req) {
  const o = (req.origin || '').trim()
  const d = (req.destination || '').trim()
  if (o || d) return `${o || '…'} → ${d || '…'}`.trim()
  return t('portal.card_no_route')
}

function tripTypeLabel(req) {
  const tt = req.trip_type
  if (!tt) return ''
  return t(`dispatch_wizard.trip_short.${tt}`)
}

function departFmt(req) {
  const raw = req.depart_at
  if (!raw) return ''
  try {
    const d = new Date(raw)
    const opts = {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }
    if (d.getFullYear() !== new Date().getFullYear()) {
      opts.year = 'numeric'
    }
    return d.toLocaleString(localeTag.value, opts)
  } catch {
    return ''
  }
}

function arriveFmt(req) {
  const raw = req.arrive_by
  if (!raw) return ''
  try {
    const dArrive = new Date(raw)
    return dArrive.toLocaleString(localeTag.value, { hour: '2-digit', minute: '2-digit' })
  } catch {
    return ''
  }
}

function timeCell(req) {
  const dep = departFmt(req)
  const arr = arriveFmt(req)
  if (dep && arr) return `${dep} — ${arr}`
  return dep || arr || '—'
}
</script>
