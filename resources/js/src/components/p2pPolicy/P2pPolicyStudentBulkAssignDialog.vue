<template>
  <dialog
    ref="dialogEl"
    class="w-[min(100vw-2rem,28rem)] max-h-[90vh] overflow-hidden rounded-2xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-900/40 dark:border-slate-700 dark:bg-slate-900"
    @cancel.prevent="close"
  >
    <form class="flex max-h-[90vh] flex-col" @submit.prevent="submit">
      <header class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.students_bulk_assign_title') }}</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
          {{ t('p2p_policy_page.students_bulk_assign_hint', { count }) }}
        </p>
      </header>

      <div class="flex-1 overflow-y-auto px-5 py-4">
        <P2pPolicyFieldLabel :label="t('p2p_policy_page.filter_route')" required />
        <select v-model="policyRouteId" required class="p2p-term-input mt-2 w-full">
          <option value="" disabled>{{ t('p2p_policy_page.filter_any') }}</option>
          <option v-for="r in routes" :key="r.id" :value="r.id">{{ r.name }}</option>
        </select>
        <p v-if="error" class="mt-4 text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>
      </div>

      <footer class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-700">
        <button type="button" class="rounded-lg border px-4 py-2 text-sm dark:border-slate-600" @click="close">
          {{ t('p2p_policy_page.cancel') }}
        </button>
        <button
          type="submit"
          class="rounded-lg bg-va-800 px-4 py-2 text-sm font-medium text-white hover:bg-va-900 disabled:opacity-50"
          :disabled="saving || !policyRouteId"
        >
          {{ t('p2p_policy_page.students_bulk_assign') }}
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
import { bulkAssignPolicyStudents } from '../../api/p2pPolicy'

defineProps({
  routes: { type: Array, default: () => [] },
  count: { type: Number, default: 0 },
})

const emit = defineEmits(['done'])

const { t } = useI18n()
const dialogEl = ref(null)
const policyRouteId = ref('')
const saving = ref(false)
const error = ref('')
let studentIds = []

function open(ids) {
  studentIds = [...ids]
  policyRouteId.value = ''
  error.value = ''
  dialogEl.value?.showModal()
}

function close() {
  dialogEl.value?.close()
  studentIds = []
}

async function submit() {
  if (!policyRouteId.value || !studentIds.length) return
  saving.value = true
  error.value = ''
  try {
    await bulkAssignPolicyStudents({
      ids: studentIds,
      policy_route_id: Number(policyRouteId.value),
    })
    emit('done')
    close()
  } catch (e) {
    error.value = formatApiError(e)
  } finally {
    saving.value = false
  }
}

defineExpose({ open, close })
</script>
