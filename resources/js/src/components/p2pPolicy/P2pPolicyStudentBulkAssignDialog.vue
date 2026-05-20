<template>
  <dialog
    ref="dialogEl"
    class="p2p-student-bulk-dialog w-[min(100vw-1.5rem,28rem)] max-h-[90vh] overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-0 shadow-2xl shadow-slate-900/10 ring-1 ring-slate-900/5 backdrop:bg-slate-900/50 dark:border-slate-600 dark:bg-slate-900"
    @cancel.prevent="close"
  >
    <form class="flex max-h-[90vh] flex-col" @submit.prevent="submit">
      <header class="border-b border-teal-100/80 bg-gradient-to-r from-teal-50/90 via-white to-slate-50/80 px-6 py-5 dark:border-teal-900/40 dark:from-teal-950/40 dark:via-slate-900 dark:to-slate-900">
        <h2 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
          {{ t('p2p_policy_page.students_bulk_assign_title') }}
        </h2>
        <p class="mt-1.5 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
          {{ t('p2p_policy_page.students_bulk_assign_hint', { count }) }}
        </p>
      </header>

      <div class="flex-1 overflow-y-auto px-6 py-5">
        <div class="rounded-xl border border-slate-200/90 bg-slate-50/50 p-4 dark:border-slate-700 dark:bg-slate-800/40">
          <P2pPolicyFieldLabel compact :label="t('p2p_policy_page.filter_route')" required />
          <select v-model="policyRouteId" required class="p2p-student-field-input mt-2 w-full">
            <option value="" disabled>{{ t('p2p_policy_page.filter_any') }}</option>
            <option v-for="r in routes" :key="r.id" :value="r.id">{{ r.name }}</option>
          </select>
        </div>
        <p
          v-if="error"
          class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200"
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
          class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          @click="close"
        >
          {{ t('p2p_policy_page.cancel') }}
        </button>
        <button
          type="submit"
          class="rounded-xl bg-va-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-va-900 disabled:opacity-50"
          :disabled="saving || !policyRouteId"
        >
          {{ saving ? t('common.processing') : t('p2p_policy_page.students_bulk_assign') }}
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

<style scoped>
.p2p-student-field-input {
  @apply block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-slate-900/[0.04] transition focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-teal-600;
}
</style>
