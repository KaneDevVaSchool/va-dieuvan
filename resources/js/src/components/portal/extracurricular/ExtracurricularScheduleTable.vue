<template>
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
        <tr>
          <th class="px-4 py-3">{{ t('portal.extracurricular_table.col_request') }}</th>
          <th class="px-4 py-3">{{ t('portal.extracurricular_table.col_depart') }}</th>
          <th class="px-4 py-3">{{ t('portal.extracurricular_table.col_route') }}</th>
          <th class="px-4 py-3">{{ t('portal.extracurricular_table.col_status') }}</th>
          <th class="px-4 py-3">{{ t('portal.extracurricular_table.col_tracking') }}</th>
          <th class="px-4 py-3 text-right">{{ t('portal.extracurricular_table.col_actions') }}</th>
        </tr>
      </thead>
      <tbody>
        <template v-for="group in grouped" :key="group.key">
          <tr class="bg-indigo-50/60">
            <td colspan="6" class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-indigo-900">
              {{ group.label }}
            </td>
          </tr>
          <tr
            v-for="req in group.items"
            :key="req.id"
            class="border-t border-slate-100 transition hover:bg-slate-50/80"
          >
            <td class="whitespace-nowrap px-4 py-3 font-mono font-semibold text-slate-900">#{{ req.id }}</td>
            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ departFmt(req) }}</td>
            <td class="px-4 py-3 text-slate-800">{{ routeLine(req) }}</td>
            <td class="px-4 py-3">
              <StatusBadge :status="req.status" size="sm" />
            </td>
            <td class="px-4 py-3">
              <StudentCountTrackingBadge
                :tracking-key="row.studentCountTrackingKey(req)"
                i18n-prefix="portal.extracurricular_table"
              />
            </td>
            <td class="px-4 py-3 text-right">
              <RouterLink
                :to="{ name: detailRouteName, params: { id: req.id } }"
                class="text-sm font-semibold text-indigo-700 hover:underline"
              >
                {{ t('portal.extracurricular_table.open_detail') }}
              </RouterLink>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import StatusBadge from '../../requests/StatusBadge.vue'
import StudentCountTrackingBadge from '../../requests/extracurricular/StudentCountTrackingBadge.vue'
import { useAuthStore } from '../../../store'
import { useExtracurricularRequestRow } from '../../../composables/useExtracurricularRequestRow'

const props = defineProps({
  requests: { type: Array, default: () => [] },
  groupBy: { type: String, default: 'day' },
  detailRouteName: { type: String, required: true },
})

const { t, locale } = useI18n()
const auth = useAuthStore()
const row = useExtracurricularRequestRow(auth, computed(() => auth.user))

const grouped = computed(() => {
  const map = new Map()
  for (const req of props.requests) {
    const key = groupKey(req)
    if (!map.has(key)) map.set(key, { key, label: groupLabel(req, key), items: [] })
    map.get(key).items.push(req)
  }
  const arr = [...map.values()]
  arr.sort((a, b) => String(a.key).localeCompare(String(b.key)))
  for (const g of arr) {
    g.items.sort((a, b) => String(a.depart_at || '').localeCompare(String(b.depart_at || '')))
  }
  return arr
})

function groupKey(req) {
  if (props.groupBy === 'route') {
    return `${req.origin || ''}→${req.destination || ''}`
  }
  if (props.groupBy === 'status') {
    return String(req.status || 'pending')
  }
  return String(req.depart_at || '').slice(0, 10)
}

function groupLabel(req, key) {
  if (props.groupBy === 'route') {
    return routeLine(req) || key
  }
  if (props.groupBy === 'status') {
    return key
  }
  try {
    const [y, m, d] = key.split('-').map(Number)
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return new Intl.DateTimeFormat(loc, { weekday: 'short', day: '2-digit', month: '2-digit', year: 'numeric' }).format(
      new Date(y, m - 1, d),
    )
  } catch {
    return key
  }
}

function routeLine(req) {
  const o = req?.origin?.trim()
  const d = req?.destination?.trim()
  if (o && d) return `${o} → ${d}`
  return o || d || '—'
}

function departFmt(req) {
  if (!req?.depart_at) return '—'
  try {
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return new Intl.DateTimeFormat(loc, {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(req.depart_at))
  } catch {
    return req.depart_at
  }
}
</script>
