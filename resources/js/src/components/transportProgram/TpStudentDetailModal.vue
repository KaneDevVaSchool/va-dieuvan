<template>
  <Modal
    :open="open"
    wide
    :title="modalTitle"
    :description="modalDescription"
    @close="$emit('close')"
  >
    <div v-if="loading" class="flex items-center justify-center gap-2 py-16 text-sm text-slate-500">
      <span class="h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-teal-600" />
      {{ t('tp_attendance_page.student_detail_loading') }}
    </div>

    <div v-else-if="loadError" class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-6 text-center text-sm text-rose-800">
      {{ t('tp_attendance_page.student_detail_load_error') }}
      <div class="mt-4">
        <Button variant="secondary" @click="$emit('close')">{{ t('tp_attendance_page.student_detail_close') }}</Button>
      </div>
    </div>

    <div v-else-if="student" class="space-y-6">
      <div class="flex flex-col gap-4 rounded-2xl border border-slate-100 bg-gradient-to-br from-slate-50 to-white p-4 sm:flex-row sm:items-center">
        <div
          class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-lg font-bold text-white shadow-sm"
          :style="{ backgroundColor: avatarColor(student.full_name) }"
        >
          {{ initials(student.full_name) }}
        </div>
        <div class="min-w-0 flex-1">
          <h2 class="text-lg font-bold tracking-tight text-slate-900">{{ student.full_name }}</h2>
          <p class="mt-0.5 font-mono text-sm" :class="student.code ? 'text-slate-500' : 'italic text-slate-400'">
            {{ student.code?.trim() ? student.code : t('tp_student_page.empty_code') }}
          </p>
          <div class="mt-2 flex flex-wrap gap-2">
            <span :class="profileStatusClass(student.status)">{{ profileStatusLabel(student.status) }}</span>
            <span :class="transportBadgeClass(student.transport_status)">{{ transportLabel(student.transport_status) }}</span>
          </div>
        </div>
      </div>

      <section v-if="attendanceRow" class="space-y-3 rounded-xl border border-teal-100 bg-teal-50/40 p-4">
        <h3 class="flex items-center gap-2 text-sm font-semibold text-teal-900">
          <ClipboardDocumentCheckIcon class="h-4 w-4" />
          {{ t('tp_attendance_page.student_detail_session') }}
        </h3>
        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <DetailItem :label="t('tp_attendance_page.col_status')" :value="sessionStatusLabel" :empty-text="t('tp_student_page.empty_not_available')" />
          <DetailItem :label="t('tp_attendance_page.col_boarded_time')" :value="formatBoardedTime(attendanceRow.boarded_at)" :empty-text="t('tp_student_page.empty_not_available')" />
          <DetailItem :label="t('tp_attendance_page.col_pickup')" :value="attendanceRow.pickup_point" :empty-text="t('tp_student_page.empty_pickup')" />
          <DetailItem v-if="attendanceRow.display_status !== 'present'" :label="t('tp_attendance_page.student_detail_absence_reason')" :value="absenceReasonText" :empty-text="t('tp_student_page.empty_not_available')" />
          <div v-if="attendanceRow.absence_reason" class="sm:col-span-2">
            <DetailItem
              :label="t('tp_attendance_page.col_notes')"
              :value="attendanceRow.absence_reason"
            />
          </div>
          <div v-if="attendanceRow.driver_notes" class="sm:col-span-2">
            <DetailItem
              :label="t('tp_attendance_page.driver_note_label')"
              :value="attendanceRow.driver_notes"
            />
          </div>
        </dl>
      </section>

      <section class="space-y-3">
        <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-800">
          <AcademicCapIcon class="h-4 w-4 text-va-700" />
          {{ t('tp_attendance_page.student_detail_section_student') }}
        </h3>
        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <DetailItem :label="t('tp_attendance_page.student_detail_grade')" :value="student.grade" :empty-text="t('tp_student_page.empty_grade')" />
          <DetailItem :label="t('tp_attendance_page.col_class')" :value="student.class_name" :empty-text="t('tp_student_page.empty_class')" />
          <DetailItem :label="t('tp_attendance_page.student_detail_gender')" :value="genderLabel(student.gender)" :empty-text="t('tp_student_page.empty_gender')" />
          <DetailItem :label="t('tp_attendance_page.student_detail_dob')" :value="formatDate(student.date_of_birth)" :empty-text="t('tp_student_page.empty_dob')" />
          <DetailItem :label="t('tp_attendance_page.student_detail_age')" :value="ageText" :empty-text="t('tp_student_page.empty_not_available')" />
        </dl>
      </section>

      <section class="space-y-3 border-t border-slate-100 pt-5">
        <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-800">
          <UsersIcon class="h-4 w-4 text-va-700" />
          {{ t('tp_attendance_page.student_detail_section_family') }}
        </h3>
        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <DetailItem :label="t('tp_attendance_page.student_detail_father')" :value="student.father_name" :empty-text="t('tp_student_page.empty_father_name')" />
          <DetailItem :label="t('tp_attendance_page.student_detail_father_phone')" :value="student.father_phone" :href="telHref(student.father_phone)" :empty-text="t('tp_student_page.empty_phone')" />
          <DetailItem :label="t('tp_attendance_page.student_detail_mother')" :value="student.mother_name" :empty-text="t('tp_student_page.empty_mother_name')" />
          <DetailItem :label="t('tp_attendance_page.student_detail_mother_phone')" :value="student.mother_phone" :href="telHref(student.mother_phone)" :empty-text="t('tp_student_page.empty_phone')" />
          <DetailItem :label="t('tp_attendance_page.student_detail_primary_contact')" :value="student.parent_name" :empty-text="t('tp_student_page.empty_parent_name')" />
          <DetailItem :label="t('tp_attendance_page.col_parent_phone')" :value="student.parent_phone" :href="telHref(student.parent_phone)" :empty-text="t('tp_student_page.empty_phone')" />
        </dl>
      </section>

      <section class="space-y-3 border-t border-slate-100 pt-5">
        <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-800">
          <MapPinIcon class="h-4 w-4 text-va-700" />
          {{ t('tp_attendance_page.student_detail_section_address') }}
        </h3>
        <dl class="grid grid-cols-1 gap-3">
          <DetailItem :label="t('tp_attendance_page.student_detail_home_address')" :value="student.address" :empty-text="t('tp_student_page.empty_address')" />
          <DetailItem :label="t('tp_attendance_page.col_pickup')" :value="student.pickup_point" :empty-text="t('tp_student_page.empty_pickup')" />
        </dl>
      </section>

      <section v-if="student.note" class="space-y-2 border-t border-slate-100 pt-5">
        <h3 class="text-sm font-semibold text-slate-800">{{ t('tp_attendance_page.student_detail_note') }}</h3>
        <p class="whitespace-pre-wrap rounded-lg bg-slate-50 px-3 py-2.5 text-sm leading-relaxed text-slate-700">{{ student.note }}</p>
      </section>

      <section class="space-y-3 border-t border-slate-100 pt-5">
        <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-800">
          <TruckIcon class="h-4 w-4 text-va-700" />
          {{ t('tp_attendance_page.student_detail_section_program') }}
        </h3>
        <template v-if="student.program">
          <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <DetailItem :label="t('tp_attendance_page.student_detail_program_name')" :value="student.program.name" :empty-text="t('tp_student_page.empty_not_available')" />
            <DetailItem :label="t('tp_attendance_page.student_detail_program_code')" :value="student.program.code" :empty-text="t('tp_student_page.empty_code')" />
            <DetailItem :label="t('tp_attendance_page.student_detail_program_status')" :value="programStatusLabel(student.program.status)" :empty-text="t('tp_student_page.empty_not_available')" />
            <DetailItem :label="t('tp_attendance_page.student_detail_program_start')" :value="formatDate(student.program.start_date)" :empty-text="t('tp_student_page.empty_date')" />
          </dl>
        </template>
        <p v-else class="text-sm italic text-slate-400">{{ t('tp_attendance_page.student_detail_no_program') }}</p>
      </section>

      <div class="flex justify-end border-t border-slate-100 pt-4">
        <Button variant="secondary" @click="$emit('close')">{{ t('tp_attendance_page.student_detail_close') }}</Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed, defineComponent, h, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  AcademicCapIcon,
  ClipboardDocumentCheckIcon,
  MapPinIcon,
  TruckIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline'
import Modal from '../ui/Modal.vue'
import Button from '../ui/Button.vue'
import { getStudent } from '../../api/transportProgram'
import { attendanceStatusLabel, formatBoardedTime } from '../../composables/useTpAttendanceList'

const props = defineProps({
  open: { type: Boolean, default: false },
  studentId: { type: [Number, String], default: null },
  attendanceRow: { type: Object, default: null },
  reasons: { type: Array, default: () => [] },
})

defineEmits(['close'])

const { t } = useI18n()

const loading = ref(false)
const loadError = ref(false)
const student = ref(null)

const modalTitle = computed(() => {
  if (student.value?.full_name) return student.value.full_name
  if (props.attendanceRow?.full_name) return props.attendanceRow.full_name
  return t('tp_attendance_page.student_detail_title')
})

const modalDescription = computed(() => {
  const code = student.value?.code || props.attendanceRow?.code
  return code ? t('tp_attendance_page.student_detail_desc', { code }) : t('tp_attendance_page.student_detail_title_hint')
})

const sessionStatusLabel = computed(() => {
  if (!props.attendanceRow) return ''
  return attendanceStatusLabel(props.attendanceRow, t)
})

const absenceReasonText = computed(() => {
  const row = props.attendanceRow
  if (!row || row.display_status === 'present') return ''
  const fromCatalog = props.reasons.find((r) => r.code === row.reason_code)
  if (fromCatalog?.label_vi) return fromCatalog.label_vi
  return row.reason_code || ''
})

const ageText = computed(() => {
  const age = student.value?.age
  if (age == null || age === '') return ''
  return t('tp_attendance_page.student_detail_age_years', { n: age })
})

const DetailItem = defineComponent({
  name: 'DetailItem',
  props: {
    label: { type: String, required: true },
    value: { type: [String, Number], default: '' },
    href: { type: String, default: '' },
    emptyText: { type: String, default: '' },
  },
  setup(itemProps) {
    const { t } = useI18n()
    const empty = computed(() => {
      const v = itemProps.value
      if (v == null) return true
      return String(v).trim() === ''
    })
    const emptyLabel = computed(
      () => itemProps.emptyText || t('tp_student_page.empty_not_available'),
    )
    return () =>
      h('div', { class: 'min-w-0 rounded-lg border border-slate-100 bg-white px-3 py-2.5' }, [
        h('dt', { class: 'text-[11px] font-semibold uppercase tracking-wide text-slate-400' }, itemProps.label),
        h('dd', { class: 'mt-1 text-sm' }, [
          empty.value
            ? h('span', { class: 'italic text-slate-400' }, emptyLabel.value)
            : itemProps.href
              ? h(
                  'a',
                  {
                    href: itemProps.href,
                    class: 'font-medium text-sky-700 hover:underline',
                  },
                  String(itemProps.value),
                )
              : h('span', { class: 'font-medium text-slate-900 break-words' }, String(itemProps.value)),
        ]),
      ])
  },
})

watch(
  () => [props.open, props.studentId],
  async ([isOpen, id]) => {
    if (!isOpen || id == null || id === '') {
      student.value = null
      loadError.value = false
      return
    }
    loading.value = true
    loadError.value = false
    student.value = null
    try {
      student.value = await getStudent(id)
    } catch {
      loadError.value = true
    } finally {
      loading.value = false
    }
  },
  { immediate: true },
)

function initials(name) {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  return (parts.length >= 2 ? parts[parts.length - 2][0] + parts[parts.length - 1][0] : parts[0][0]).toUpperCase()
}

function avatarColor(name) {
  if (!name) return '#64748b'
  let hash = 0
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
  const hues = [200, 168, 142, 262, 320, 24]
  return `hsl(${hues[Math.abs(hash) % hues.length]} 55% 42%)`
}

function genderLabel(g) {
  const map = {
    male: t('tp_attendance_page.student_detail_gender_male'),
    female: t('tp_attendance_page.student_detail_gender_female'),
    other: t('tp_attendance_page.student_detail_gender_other'),
  }
  return map[g] ?? g ?? ''
}

function profileStatusLabel(s) {
  const map = {
    active: t('tp_attendance_page.student_detail_profile_active'),
    inactive: t('tp_attendance_page.student_detail_profile_inactive'),
    transferred: t('tp_attendance_page.student_detail_profile_transferred'),
    graduated: t('tp_attendance_page.student_detail_profile_graduated'),
  }
  return map[s] ?? s ?? ''
}

function profileStatusClass(s) {
  const base = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium'
  if (s === 'active') return `${base} bg-emerald-100 text-emerald-800`
  if (s === 'graduated') return `${base} bg-sky-100 text-sky-800`
  if (s === 'transferred') return `${base} bg-amber-100 text-amber-900`
  return `${base} bg-slate-100 text-slate-600`
}

function transportLabel(s) {
  const map = {
    transporting: t('tp_attendance_page.student_detail_transport_active'),
    pending: t('tp_attendance_page.student_detail_transport_pending'),
    paused: t('tp_attendance_page.student_detail_transport_paused'),
    unregistered: t('tp_attendance_page.student_detail_transport_none'),
  }
  return map[s] ?? s ?? ''
}

function transportBadgeClass(s) {
  const base = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium'
  if (s === 'transporting') return `${base} bg-sky-100 text-sky-800`
  if (s === 'pending') return `${base} bg-amber-100 text-amber-900`
  if (s === 'paused') return `${base} bg-slate-200 text-slate-700`
  return `${base} bg-slate-100 text-slate-500`
}

function programStatusLabel(s) {
  const map = {
    active: t('tp_attendance_page.student_detail_program_active'),
    draft: t('tp_attendance_page.student_detail_program_draft'),
    paused: t('tp_attendance_page.student_detail_program_paused'),
    completed: t('tp_attendance_page.student_detail_program_completed'),
  }
  return map[s] ?? s ?? ''
}

function formatDate(iso) {
  if (!iso) return ''
  try {
    const d = new Date(iso.includes('T') ? iso : `${iso}T12:00:00`)
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(d)
  } catch {
    return iso
  }
}

function telHref(phone) {
  if (!phone) return ''
  const digits = String(phone).replace(/\s/g, '')
  return digits ? `tel:${digits}` : ''
}
</script>
