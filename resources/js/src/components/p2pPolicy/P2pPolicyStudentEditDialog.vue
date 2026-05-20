<template>
  <dialog
    ref="dialogEl"
    class="w-[min(100vw-2rem,32rem)] max-w-lg rounded-2xl border p-0 shadow-2xl backdrop:bg-slate-900/40 dark:border-slate-700 dark:bg-slate-900"
    @close="onDialogClose"
  >
    <form v-if="form" class="max-h-[min(90vh,640px)] overflow-y-auto p-6" @submit.prevent="submit">
      <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.students_edit_title') }}</h3>
      <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.students_edit_hint') }}</p>

      <div class="mt-5 space-y-4">
        <div>
          <P2pPolicyFieldLabel :label="t('p2p_policy_page.filter_assigned_group')" required />
          <select v-model="form.policy_route_id" required class="p2p-term-input mt-2 w-full">
            <option v-for="r in routes" :key="r.id" :value="r.id">{{ r.name }}</option>
          </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <P2pPolicyFieldLabel :label="t('p2p_policy_page.col_code')" required />
            <input v-model="form.student_code" required class="p2p-term-input mt-2 w-full font-mono text-sm" />
          </div>
          <div>
            <P2pPolicyFieldLabel :label="t('p2p_policy_page.col_class')" />
            <input v-model="form.class_name" class="p2p-term-input mt-2 w-full" />
          </div>
        </div>
        <div>
          <P2pPolicyFieldLabel :label="t('p2p_policy_page.col_name')" required />
          <input v-model="form.student_name" required class="p2p-term-input mt-2 w-full" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <P2pPolicyFieldLabel :label="t('p2p_policy_page.col_direction')" />
            <select v-model="form.direction" class="p2p-term-input mt-2 w-full">
              <option v-for="d in P2P_DIRECTION_VALUES" :key="d" :value="d">{{ directionLabel(t, d) }}</option>
            </select>
          </div>
          <div>
            <P2pPolicyFieldLabel :label="t('p2p_policy_page.col_policy')" required />
            <select v-model="form.policy_type" required class="p2p-term-input mt-2 w-full">
              <option v-for="pt in P2P_POLICY_TYPE_VALUES" :key="pt" :value="pt">{{ policyTypeLabel(t, pt) }}</option>
            </select>
          </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <P2pPolicyFieldLabel :label="t('p2p_policy_page.field_effective_from')" required />
            <input v-model="form.effective_from" type="date" required class="p2p-term-input mt-2 w-full" />
          </div>
          <div>
            <P2pPolicyFieldLabel :label="t('p2p_policy_page.field_effective_to')" />
            <input v-model="form.effective_to" type="date" class="p2p-term-input mt-2 w-full" />
          </div>
        </div>
        <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
          <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-teal-600" />
          {{ t('p2p_policy_page.active_yes') }}
        </label>
      </div>

      <p v-if="error" class="mt-4 text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>

      <div class="mt-6 flex justify-end gap-2">
        <button type="button" class="rounded-lg border px-4 py-2 text-sm dark:border-slate-600" @click="close">
          {{ t('p2p_policy_page.cancel') }}
        </button>
        <button type="submit" class="rounded-lg bg-va-800 px-4 py-2 text-sm text-white hover:bg-va-900 disabled:opacity-50" :disabled="saving">
          {{ t('p2p_policy_page.save') }}
        </button>
      </div>
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
