<template>
  <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4 sm:px-8">
      <h2 class="text-base font-bold text-slate-900">{{ t('portal.recurring_edit.title') }}</h2>
      <p class="mt-1 text-sm text-slate-600">{{ t('portal.recurring_edit.lead') }}</p>
    </div>

    <form class="grid gap-8 p-5 sm:p-8 lg:grid-cols-2" @submit.prevent="save">
      <fieldset class="space-y-4 lg:col-span-2">
        <legend class="text-xs font-bold uppercase tracking-wide text-slate-500">
          {{ t('portal.recurring_edit.sec_trip') }}
        </legend>
        <div class="flex flex-wrap items-center gap-2 text-sm">
          <span class="font-mono font-bold text-slate-900">#{{ req.id }}</span>
          <StatusBadge :status="req.status" size="sm" />
          <StudentCountTrackingBadge
            :tracking-key="row.studentCountTrackingKey(req)"
            i18n-prefix="portal.extracurricular_table"
          />
        </div>
      </fieldset>

      <fieldset class="space-y-3">
        <legend class="text-xs font-bold uppercase tracking-wide text-slate-500">
          {{ t('portal.recurring_edit.sec_time') }}
        </legend>
        <label class="block">
          <span class="text-sm font-medium text-slate-700">{{ t('portal.recurring_edit.depart_at') }}</span>
          <input
            v-model="draft.departAtLocal"
            type="datetime-local"
            class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
            :disabled="locked"
          />
        </label>
        <label class="block">
          <span class="text-sm font-medium text-slate-700">{{ t('portal.recurring_edit.arrive_by') }}</span>
          <input
            v-model="draft.arriveByLocal"
            type="datetime-local"
            class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
            :disabled="locked"
          />
        </label>
      </fieldset>

      <fieldset class="space-y-3">
        <legend class="text-xs font-bold uppercase tracking-wide text-slate-500">
          {{ t('portal.recurring_edit.sec_route') }}
        </legend>
        <label class="block">
          <span class="text-sm font-medium text-slate-700">{{ t('dispatch_wizard.s3.pickup_ph') }}</span>
          <input
            v-model="draft.origin"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
            :disabled="locked"
          />
        </label>
        <label class="block">
          <span class="text-sm font-medium text-slate-700">{{ t('dispatch_wizard.s3.dropoff_ph') }}</span>
          <input
            v-model="draft.destination"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
            :disabled="locked"
          />
        </label>
      </fieldset>

      <fieldset class="space-y-3">
        <legend class="text-xs font-bold uppercase tracking-wide text-slate-500">
          {{ t('portal.recurring_edit.sec_students') }}
        </legend>
        <label class="block">
          <span class="text-sm font-medium text-slate-700">{{ t('portal.extracurricular_table.col_actual') }}</span>
          <input
            v-model.number="draft.studentCount"
            type="number"
            min="1"
            max="999"
            class="mt-1 w-full max-w-[8rem] rounded-lg border border-slate-200 px-3 py-2 text-sm tabular-nums"
            :disabled="locked"
          />
        </label>
      </fieldset>

      <fieldset class="space-y-3">
        <legend class="text-xs font-bold uppercase tracking-wide text-slate-500">
          {{ t('portal.recurring_edit.sec_notes') }}
        </legend>
        <textarea
          v-model="draft.notes"
          rows="4"
          class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
          :disabled="locked"
        />
      </fieldset>

      <fieldset class="space-y-2 lg:col-span-2">
        <legend class="text-xs font-bold uppercase tracking-wide text-slate-500">
          {{ t('portal.recurring_edit.sec_dispatch') }}
        </legend>
        <p class="text-sm text-slate-600">{{ t('portal.recurring_edit.dispatch_hint') }}</p>
        <p v-if="req.trip" class="text-sm font-medium text-slate-800">
          {{ t('portal.recurring_edit.trip_status') }}: {{ req.trip.status }}
        </p>
      </fieldset>
    </form>

    <p v-if="formError" class="px-5 pb-2 text-sm text-rose-600 sm:px-8">{{ formError }}</p>

    <div
      class="sticky bottom-0 flex flex-wrap gap-3 border-t border-slate-100 bg-white/95 px-5 py-4 backdrop-blur sm:px-8"
    >
      <button
        type="button"
        class="rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900 disabled:opacity-50"
        :disabled="locked || saving"
        @click="save"
      >
        {{ saving ? t('portal.extracurricular_table.update_count_busy') : t('portal.extracurricular_table.update_count') }}
      </button>
      <button
        type="button"
        class="rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-500 disabled:opacity-50"
        :disabled="locked || saving || submitting || !canSubmit"
        @click="submitToDispatch"
      >
        {{
          submitting
            ? t('portal.extracurricular_table.submit_dispatch_busy')
            : t('portal.extracurricular_table.submit_dispatch')
        }}
      </button>
      <p v-if="locked" class="self-center text-xs text-amber-800">{{ t('portal.extracurricular_table.depart_soon') }}</p>
    </div>
  </section>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import StatusBadge from '../../components/requests/StatusBadge.vue'
import StudentCountTrackingBadge from '../../components/requests/extracurricular/StudentCountTrackingBadge.vue'
import { patchPortalRecurringInstance, submitPortalRecurringInstance } from '../../api/requests'
import { formatApiError } from '../../api/http'
import { useAuthStore } from '../../store'
import { useExtracurricularRequestRow } from '../../composables/useExtracurricularRequestRow'
import { confirmAction } from '../../composables/useConfirm'

const props = defineProps({
  req: { type: Object, required: true },
})

const emit = defineEmits(['saved'])

const { t } = useI18n()
const auth = useAuthStore()
const row = useExtracurricularRequestRow(auth, computed(() => auth.user))

const saving = ref(false)
const submitting = ref(false)
const formError = ref('')

const draft = reactive({
  departAtLocal: '',
  arriveByLocal: '',
  origin: '',
  destination: '',
  studentCount: null,
  notes: '',
})

const locked = computed(() => row.passengerDepartLocked(props.req))
const canSubmit = computed(() => row.canSubmitStudentCount(props.req))

function toLocalInput(iso) {
  if (!iso) return ''
  try {
    const d = new Date(iso)
    const pad = (n) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
  } catch {
    return ''
  }
}

function fromLocalInput(local) {
  if (!local) return null
  try {
    return new Date(local).toISOString()
  } catch {
    return null
  }
}

function syncFromReq(r) {
  draft.departAtLocal = toLocalInput(r.depart_at)
  draft.arriveByLocal = toLocalInput(r.arrive_by)
  draft.origin = r.origin || ''
  draft.destination = r.destination || ''
  draft.studentCount =
    r.student_count_actual != null ? Number(r.student_count_actual) : r.passenger_count != null ? Number(r.passenger_count) : null
  draft.notes = r.notes || ''
}

watch(
  () => props.req,
  (r) => {
    if (r) syncFromReq(r)
  },
  { immediate: true, deep: true },
)

async function save() {
  if (locked.value || saving.value) return
  saving.value = true
  formError.value = ''
  try {
    const payload = {
      origin: draft.origin.trim(),
      destination: draft.destination.trim(),
      notes: draft.notes,
    }
    const dep = fromLocalInput(draft.departAtLocal)
    const arr = fromLocalInput(draft.arriveByLocal)
    if (dep) payload.depart_at = dep
    if (arr) payload.arrive_by = arr
    if (draft.studentCount != null && draft.studentCount !== '') {
      payload.student_count_actual = Math.round(Number(draft.studentCount))
    }
    await patchPortalRecurringInstance(props.req.id, payload)
    emit('saved')
  } catch (e) {
    formError.value = formatApiError(e, t('portal.extracurricular_table.save_fail'))
  } finally {
    saving.value = false
  }
}

async function submitToDispatch() {
  if (!canSubmit.value || locked.value || submitting.value) return
  const n = Math.round(Number(draft.studentCount) || 0)
  const ok = await confirmAction({
    title: t('portal.extracurricular_table.submit_confirm_title'),
    message: t('portal.extracurricular_table.submit_confirm_message', { count: n }),
    confirmLabel: t('portal.extracurricular_table.submit_dispatch'),
  })
  if (!ok) return
  submitting.value = true
  formError.value = ''
  try {
    await save()
    await submitPortalRecurringInstance(props.req.id)
    emit('saved')
  } catch (e) {
    formError.value = formatApiError(e, t('portal.extracurricular_table.submit_fail'))
  } finally {
    submitting.value = false
  }
}
</script>
