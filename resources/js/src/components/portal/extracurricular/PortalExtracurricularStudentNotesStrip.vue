<template>
  <section
    class="rounded-2xl border border-violet-200/90 bg-gradient-to-br from-violet-50/80 via-white to-white p-4 shadow-sm ring-1 ring-violet-100/80 sm:p-5"
  >
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h3 class="text-sm font-bold text-violet-950">{{ t('portal.recurring_edit.bm03_sec_g') }}</h3>
      <StudentCountTrackingBadge
        :tracking-key="row.studentCountTrackingKey(req)"
        i18n-prefix="portal.extracurricular_table"
      />
    </div>
    <p class="mt-1 text-xs text-slate-600">{{ t('portal.recurring_edit.student_strip_lead') }}</p>
    <div class="mt-4 flex flex-wrap items-end gap-6">
      <div>
        <p class="text-[11px] font-semibold uppercase text-slate-500">{{ t('portal.extracurricular_table.col_plan') }}</p>
        <p class="mt-1 text-lg font-bold tabular-nums text-slate-800">{{ row.planStudentCount(req) ?? '—' }}</p>
      </div>
      <div class="min-w-[12rem] flex-1">
        <StudentCountCell
          :req="req"
          :draft="draftCount"
          :saving="savingCount"
          :error="countError"
          :can-edit="canEdit"
          :lock-hint="lockHint"
          :show-submit="false"
          :save-label-key="'portal.extracurricular_table.update_count'"
          :save-busy-label-key="'portal.extracurricular_table.update_count_busy'"
          @update:draft="draftCount = $event"
          @save="saveCount"
        />
      </div>
    </div>
    <label class="mt-4 block">
      <span class="text-xs font-semibold text-slate-700">{{ t('portal.recurring_edit.sec_notes') }}</span>
      <textarea
        v-model="notesLocal"
        rows="3"
        class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-500/20"
        :disabled="!canEdit"
        @blur="saveNotes"
      />
    </label>
    <p v-if="notesError" class="mt-1 text-xs text-rose-600">{{ notesError }}</p>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import StudentCountCell from '../../requests/extracurricular/StudentCountCell.vue'
import StudentCountTrackingBadge from '../../requests/extracurricular/StudentCountTrackingBadge.vue'
import { patchPortalRecurringInstance } from '../../../api/requests'
import { formatApiError } from '../../../api/http'
import { useAuthStore } from '../../../store'
import { useExtracurricularRequestRow } from '../../../composables/useExtracurricularRequestRow'

const props = defineProps({
  req: { type: Object, required: true },
})

const emit = defineEmits(['saved'])

const { t } = useI18n()
const auth = useAuthStore()
const row = useExtracurricularRequestRow(auth, computed(() => auth.user))

const draftCount = ref(1)
const notesLocal = ref('')
const savingCount = ref(false)
const countError = ref('')
const notesError = ref('')

const canEdit = computed(() => {
  const r = props.req
  if (!r || r.student_count_submitted_at) return false
  if (!row.isRequester(r)) return false
  return ['pending', 'price_filled'].includes(String(r.status || ''))
})

const lockHint = computed(() => {
  const k = row.lockHintKey(props.req)
  return k ? t(`portal.extracurricular_table.${k}`) : ''
})

function syncFromReq(r) {
  draftCount.value = row.actualStudentCount(r)
  notesLocal.value = r.notes || ''
}

watch(() => props.req, syncFromReq, { immediate: true, deep: true })

async function saveCount() {
  if (!canEdit.value || savingCount.value) return
  const n = Math.round(Number(draftCount.value))
  if (!Number.isFinite(n) || n < 1 || n > 999) {
    countError.value = t('portal.extracurricular_table.invalid_count')
    return
  }
  savingCount.value = true
  countError.value = ''
  try {
    await patchPortalRecurringInstance(props.req.id, { student_count_actual: n })
    emit('saved')
  } catch (e) {
    countError.value = formatApiError(e, t('portal.extracurricular_table.save_fail'))
  } finally {
    savingCount.value = false
  }
}

async function saveNotes() {
  if (!canEdit.value) return
  notesError.value = ''
  try {
    await patchPortalRecurringInstance(props.req.id, { notes: notesLocal.value })
    emit('saved')
  } catch (e) {
    notesError.value = formatApiError(e, t('portal.extracurricular_table.save_fail'))
  }
}
</script>
