<template>
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div
      v-if="selectedCount > 0"
      class="flex flex-wrap items-center gap-2 border-b border-teal-100 bg-teal-50/80 px-3 py-2 text-sm"
    >
      <span class="font-medium text-teal-900">
        {{ t('tp_attendance_page.selected_n', { n: selectedCount }) }}
      </span>
      <slot name="bulk-actions" />
      <button
        type="button"
        class="ml-auto text-xs text-slate-500 hover:text-slate-800"
        @click="$emit('clear-selection')"
      >
        {{ t('tp_attendance_page.clear_selection') }}
      </button>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full table-fixed text-left text-sm">
        <thead class="border-b border-slate-100 bg-slate-50/90 text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th scope="col" class="w-10 px-3 py-3">
              <input
                type="checkbox"
                class="rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
                :checked="allPageSelected"
                :indeterminate.prop="somePageSelected && !allPageSelected"
                :aria-label="t('tp_attendance_page.select_all_page')"
                @change="$emit('toggle-select-all', rows)"
              />
            </th>
            <th
              v-for="col in headerCols"
              :key="col.id"
              scope="col"
              class="relative px-3 py-3 select-none"
              :style="colWidthStyle(col.id)"
            >
              <button
                type="button"
                class="inline-flex max-w-full items-center gap-1 truncate hover:text-slate-800"
                @click="$emit('sort', col.sortKey || col.id)"
              >
                {{ col.label }}
                <span v-if="sortKey === (col.sortKey || col.id)" class="text-teal-600" aria-hidden="true">
                  {{ sortDir === 'asc' ? '↑' : '↓' }}
                </span>
              </button>
              <span
                class="absolute right-0 top-0 h-full w-1.5 cursor-col-resize hover:bg-teal-300/50"
                @mousedown.prevent="startResize(col.id, $event)"
              />
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="s in rows"
            :key="s.student_id"
            class="border-b border-slate-100 transition-colors last:border-0"
            :class="[rowClass(s), pendingRows.has(s.student_id) ? 'opacity-60' : '']"
          >
            <td class="px-3 py-3">
              <input
                type="checkbox"
                class="rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
                :checked="selectedIds.has(s.student_id)"
                @change="$emit('toggle-select', s.student_id)"
              />
            </td>
            <td v-if="colOn('student')" class="px-3 py-3" :style="colWidthStyle('student')">
              <div class="flex items-center gap-3">
                <div
                  class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                  :class="avatarClass(s)"
                >
                  {{ initials(s.full_name) }}
                </div>
                <div class="min-w-0">
                  <div class="truncate font-semibold text-slate-900">{{ s.full_name }}</div>
                  <div v-if="colOn('code')" class="text-xs text-slate-400">{{ s.code }}</div>
                  <div v-else-if="colOn('parent_phone') && s.parent_phone" class="text-xs text-slate-400">
                    PH: {{ s.parent_phone }}
                  </div>
                  <div v-else class="text-xs text-slate-400">{{ s.code }}</div>
                </div>
              </div>
            </td>
            <td v-if="colOn('class_name')" class="px-3 py-3" :style="colWidthStyle('class_name')">
              <span
                v-if="s.class_name"
                class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700"
              >{{ s.class_name }}</span>
              <span v-else class="text-slate-400">—</span>
            </td>
            <td v-if="colOn('pickup_point')" class="px-3 py-3 text-slate-600" :style="colWidthStyle('pickup_point')">
              {{ s.pickup_point || '—' }}
            </td>
            <td v-if="colOn('boarded_time')" class="px-3 py-3 tabular-nums text-slate-700" :style="colWidthStyle('boarded_time')">
              {{ formatBoardedTime(s.boarded_at) }}
            </td>
            <td v-if="colOn('status')" class="px-3 py-3" :style="colWidthStyle('status')">
              <span :class="statusBadgeClass(s)">{{ statusLabel(s) }}</span>
            </td>
            <td v-if="colOn('parent_phone')" class="px-3 py-3 text-slate-600" :style="colWidthStyle('parent_phone')">
              {{ s.parent_phone || '—' }}
            </td>
            <td v-if="colOn('notes')" class="px-3 py-3" :style="colWidthStyle('notes')">
              <div
                v-if="s.status === 'absent'"
                class="flex w-full min-w-[180px] max-w-[280px] flex-col gap-1.5"
              >
                <select
                  v-if="reasons.length"
                  :value="s.reason_code || ''"
                  :disabled="readOnly || pendingRows.has(s.student_id)"
                  class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-700 focus:border-teal-400 focus:outline-none focus:ring-1 focus:ring-teal-400 disabled:cursor-not-allowed disabled:opacity-60"
                  @change="$emit('reason-change', s, $event.target.value)"
                >
                  <option value="">{{ t('tp_attendance_page.pick_reason') }}</option>
                  <option v-for="r in reasons" :key="r.code" :value="r.code">{{ r.label_vi }}</option>
                </select>
                <input
                  type="text"
                  :value="s.absence_reason || ''"
                  :disabled="readOnly || pendingRows.has(s.student_id)"
                  :placeholder="t('tp_attendance_page.notes_placeholder')"
                  maxlength="500"
                  class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-700 placeholder:text-slate-400 focus:border-teal-400 focus:outline-none focus:ring-1 focus:ring-teal-400 disabled:cursor-not-allowed disabled:opacity-60"
                  @blur="$emit('note-blur', s, $event.target.value)"
                  @keydown.enter.prevent="$event.target?.blur?.()"
                />
              </div>
              <span v-else class="text-slate-300">—</span>
            </td>
            <td v-if="colOn('attendance_toggle')" class="px-3 py-3 text-center" :style="colWidthStyle('attendance_toggle')">
              <div class="flex justify-center">
                <template v-if="pendingRows.has(s.student_id)">
                  <span class="inline-flex h-6 w-11 items-center justify-center">
                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-slate-600" />
                  </span>
                </template>
                <Toggle
                  v-else
                  :model-value="s.status === 'attending'"
                  :disabled="readOnly || saving"
                  @update:model-value="$emit('toggle-present', s)"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="!rows.length" class="flex flex-col items-center gap-2 py-14">
      <UsersIcon class="h-10 w-10 text-slate-300" />
      <p class="text-sm text-slate-500">{{ emptyText }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import { UsersIcon } from '@heroicons/vue/24/outline'
import Toggle from '../../../components/ui/Toggle.vue'
import { attendanceStatusLabel, formatBoardedTime } from '../../../composables/useTpAttendanceList'

const props = defineProps({
  rows: { type: Array, default: () => [] },
  reasons: { type: Array, default: () => [] },
  selectedIds: { type: Object, required: true },
  selectedCount: { type: Number, default: 0 },
  sortKey: { type: String, default: 'student' },
  sortDir: { type: String, default: 'asc' },
  colOn: { type: Function, required: true },
  pendingRows: { type: Object, default: () => new Set() },
  readOnly: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
  emptyText: { type: String, default: '' },
})

defineEmits([
  'sort',
  'toggle-select',
  'toggle-select-all',
  'clear-selection',
  'toggle-present',
  'reason-change',
  'note-blur',
])

const { t } = useI18n()

const defaultWidths = {
  student: 220,
  class_name: 100,
  pickup_point: 120,
  boarded_time: 110,
  status: 120,
  parent_phone: 120,
  notes: 240,
  attendance_toggle: 100,
}
const colWidths = reactive({ ...defaultWidths })

const headerCols = computed(() => {
  const cols = [{ id: 'student', label: t('tp_attendance_page.col_student'), sortKey: 'student' }]
  if (props.colOn('class_name')) cols.push({ id: 'class_name', label: t('tp_attendance_page.col_class'), sortKey: 'class_name' })
  if (props.colOn('pickup_point')) cols.push({ id: 'pickup_point', label: t('tp_attendance_page.col_pickup'), sortKey: 'pickup_point' })
  if (props.colOn('boarded_time')) cols.push({ id: 'boarded_time', label: t('tp_attendance_page.col_boarded_time'), sortKey: 'boarded_time' })
  if (props.colOn('status')) cols.push({ id: 'status', label: t('tp_attendance_page.col_status'), sortKey: 'status' })
  if (props.colOn('parent_phone')) cols.push({ id: 'parent_phone', label: t('tp_attendance_page.col_parent_phone'), sortKey: 'parent_phone' })
  if (props.colOn('notes')) cols.push({ id: 'notes', label: t('tp_attendance_page.col_notes'), sortKey: 'notes' })
  if (props.colOn('attendance_toggle')) cols.push({ id: 'attendance_toggle', label: t('tp_attendance_page.col_toggle'), sortKey: null })
  return cols
})

const allPageSelected = computed(
  () => props.rows.length > 0 && props.rows.every((r) => props.selectedIds.has(r.student_id)),
)
const somePageSelected = computed(() => props.rows.some((r) => props.selectedIds.has(r.student_id)))

function colWidthStyle(id) {
  const w = colWidths[id]
  return w ? { width: `${w}px`, minWidth: `${w}px`, maxWidth: `${w}px` } : {}
}

let resizeId = null
let startX = 0
let startW = 0

function startResize(id, ev) {
  resizeId = id
  startX = ev.clientX
  startW = colWidths[id] || 120
  window.addEventListener('mousemove', onResizeMove)
  window.addEventListener('mouseup', stopResize)
}

function onResizeMove(ev) {
  if (!resizeId) return
  colWidths[resizeId] = Math.max(72, startW + (ev.clientX - startX))
}

function stopResize() {
  resizeId = null
  window.removeEventListener('mousemove', onResizeMove)
  window.removeEventListener('mouseup', stopResize)
}

function statusLabel(s) {
  return attendanceStatusLabel(s, t)
}

function statusBadgeClass(s) {
  if (s.display_status === 'present')
    return 'inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800'
  if (s.display_status === 'excused')
    return 'inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-900'
  return 'inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-800'
}

function rowClass(s) {
  if (s.display_status === 'excused') return 'bg-amber-50/50 hover:bg-amber-50/80'
  if (s.display_status === 'unexcused') return 'bg-rose-50/50 hover:bg-rose-50/80'
  if (s.status === 'absent') return 'bg-rose-50/40 hover:bg-rose-50/70'
  return 'hover:bg-slate-50/60'
}

function avatarClass(s) {
  if (s.status === 'absent') return 'bg-rose-100 text-rose-600'
  return 'bg-sky-100 text-sky-700'
}

function initials(name) {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  return (parts.length >= 2 ? parts[parts.length - 2][0] + parts[parts.length - 1][0] : parts[0][0]).toUpperCase()
}
</script>
