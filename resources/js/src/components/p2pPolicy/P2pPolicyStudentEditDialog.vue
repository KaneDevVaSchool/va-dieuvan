<template>
  <dialog
    ref="dialogEl"
    class="p2p-student-edit-dialog w-[min(100vw-1.5rem,36rem)] max-h-[min(92vh,720px)] overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-0 shadow-2xl shadow-slate-900/10 ring-1 ring-slate-900/5 backdrop:bg-slate-900/50 dark:border-slate-600 dark:bg-slate-900 dark:shadow-black/40"
    @close="onDialogClose"
    @cancel.prevent="close"
  >
    <form v-if="form" class="flex max-h-[min(92vh,720px)] flex-col" @submit.prevent="submit">
      <header class="border-b border-teal-100/80 bg-gradient-to-r from-teal-50/90 via-white to-slate-50/80 px-6 py-5 dark:border-teal-900/40 dark:from-teal-950/40 dark:via-slate-900 dark:to-slate-900">
        <h2 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
          {{ t('p2p_policy_page.students_edit_title') }}
        </h2>
        <p class="mt-1.5 max-w-md text-sm leading-relaxed text-slate-600 dark:text-slate-400">
          {{ t('p2p_policy_page.students_edit_hint') }}
        </p>
      </header>

      <div class="flex-1 space-y-6 overflow-y-auto px-6 py-5">
        <section class="space-y-3">
          <h3 class="text-[11px] font-bold uppercase tracking-wider text-teal-800/90 dark:text-teal-300/90">
            {{ t('p2p_policy_page.students_edit_section_route') }}
          </h3>
          <div class="rounded-xl border border-slate-200/90 bg-slate-50/50 p-4 dark:border-slate-700 dark:bg-slate-800/40">
            <P2pPolicyFieldLabel compact :label="t('p2p_policy_page.filter_route')" required />
            <select v-model="form.policy_route_id" required class="p2p-student-field-input mt-2 w-full">
              <option v-for="r in routes" :key="r.id" :value="r.id">{{ r.name }}</option>
            </select>
          </div>
        </section>

        <section class="space-y-3">
          <h3 class="text-[11px] font-bold uppercase tracking-wider text-teal-800/90 dark:text-teal-300/90">
            {{ t('p2p_policy_page.students_edit_section_identity') }}
          </h3>
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
              <P2pPolicyFieldLabel compact :label="t('p2p_policy_page.col_name')" required />
              <input v-model="form.student_name" required class="p2p-student-field-input mt-2 w-full" />
            </div>
            <div>
              <P2pPolicyFieldLabel compact :label="t('p2p_policy_page.col_code')" required />
              <input
                v-model="form.student_code"
                required
                class="p2p-student-field-input mt-2 w-full font-mono text-sm tracking-wide"
              />
            </div>
            <div>
              <P2pPolicyFieldLabel compact :label="t('p2p_policy_page.col_class')" />
              <input v-model="form.class_name" class="p2p-student-field-input mt-2 w-full" />
            </div>
          </div>
        </section>

        <section class="space-y-3">
          <h3 class="text-[11px] font-bold uppercase tracking-wider text-teal-800/90 dark:text-teal-300/90">
            {{ t('p2p_policy_page.students_edit_section_policy') }}
          </h3>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <P2pPolicyFieldLabel compact :label="t('p2p_policy_page.col_direction')" />
              <select v-model="form.direction" class="p2p-student-field-input mt-2 w-full">
                <option v-for="d in P2P_DIRECTION_VALUES" :key="d" :value="d">{{ directionLabel(t, d) }}</option>
              </select>
            </div>
            <div>
              <P2pPolicyFieldLabel compact :label="t('p2p_policy_page.col_policy')" required />
              <select v-model="form.policy_type" required class="p2p-student-field-input mt-2 w-full">
                <option v-for="pt in P2P_POLICY_TYPE_VALUES" :key="pt" :value="pt">{{ policyTypeLabel(t, pt) }}</option>
              </select>
            </div>
            <div>
              <P2pPolicyFieldLabel compact :label="t('p2p_policy_page.field_effective_from')" required />
              <input v-model="form.effective_from" type="date" required class="p2p-student-field-input mt-2 w-full" />
            </div>
            <div>
              <P2pPolicyFieldLabel compact :label="t('p2p_policy_page.field_effective_to')" />
              <input v-model="form.effective_to" type="date" class="p2p-student-field-input mt-2 w-full" />
            </div>
          </div>
          <label
            class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200/90 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm transition hover:border-teal-200/80 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-200 dark:hover:border-teal-800"
          >
            <input
              v-model="form.is_active"
              type="checkbox"
              class="h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-500"
            />
            {{ t('p2p_policy_page.active_yes') }}
          </label>
        </section>

        <p
          v-if="error"
          class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200"
          role="alert"
        >
          {{ error }}
        </p>
      </div>

      <footer
        class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50/95 px-6 py-4 sm:flex-row sm:justify-end dark:border-slate-700 dark:bg-slate-800/80"
      >
        <button
          type="button"
          class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
          @click="close"
        >
          {{ t('p2p_policy_page.cancel') }}
        </button>
        <button
          type="submit"
          class="rounded-xl bg-va-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-va-900 disabled:opacity-50"
          :disabled="saving"
        >
          {{ saving ? t('common.processing') : t('p2p_policy_page.save') }}
        </button>
      </footer>
    </form>
  </dialog>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import P2pPolicyFieldLabel from './P2pPolicyFieldLabel.vue'
import { formatApiError } from '../../api/http'
import { updatePolicyStudent } from '../../api/p2pPolicy'
import {
  directionLabel,
  P2P_DIRECTION_VALUES,
  P2P_POLICY_TYPE_VALUES,
  policyTypeLabel,
} from '../../utils/p2pPolicyStudentLabels'

defineProps({
  routes: { type: Array, default: () => [] },
})

const emit = defineEmits(['saved'])

const { t } = useI18n()
const dialogEl = ref(null)
const form = ref(null)
const saving = ref(false)
const error = ref('')
let rowId = null

function toDateInput(val) {
  if (!val) return ''
  const s = String(val)
  return s.length >= 10 ? s.slice(0, 10) : s
}

function open(row) {
  rowId = row.id
  error.value = ''
  form.value = {
    policy_route_id: row.policy_route?.id ?? row.policy_route_id,
    student_code: row.student_code ?? '',
    student_name: row.student_name ?? '',
    class_name: row.class_name ?? '',
    direction: row.direction ?? 'two_way',
    policy_type: row.policy_type ?? 'internal',
    effective_from: toDateInput(row.effective_from),
    effective_to: toDateInput(row.effective_to),
    is_active: row.is_active !== false,
  }
  dialogEl.value?.showModal()
}

function close() {
  dialogEl.value?.close()
}

function onDialogClose() {
  form.value = null
  rowId = null
  error.value = ''
}

async function submit() {
  if (!form.value || rowId == null) return
  saving.value = true
  error.value = ''
  try {
    const payload = {
      policy_route_id: Number(form.value.policy_route_id),
      student_code: form.value.student_code.trim(),
      student_name: form.value.student_name.trim(),
      class_name: form.value.class_name?.trim() || null,
      direction: form.value.direction,
      policy_type: form.value.policy_type,
      effective_from: form.value.effective_from,
      effective_to: form.value.effective_to || null,
      is_active: form.value.is_active,
    }
    await updatePolicyStudent(rowId, payload)
    emit('saved')
    close()
  } catch (e) {
    error.value = formatApiError(e)
  } finally {
    saving.value = false
  }
}

defineExpose({ open, close })
</script>

<style scoped>
.p2p-student-field-input {
  @apply block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-slate-900/[0.04] transition placeholder:text-slate-400 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:ring-white/5 dark:focus:border-teal-600;
}
</style>
