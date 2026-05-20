<template>
  <dialog
    ref="dialogEl"
    class="w-[min(100%,56rem)] max-h-[90vh] overflow-hidden rounded-2xl border border-slate-200 bg-white p-0 shadow-xl dark:border-slate-700 dark:bg-slate-900"
    @cancel.prevent="close"
  >
    <div class="flex max-h-[90vh] flex-col">
      <header class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.import_modal_title') }}</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ t('p2p_policy_page.import_modal_subtitle') }}</p>
      </header>

      <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
        <p v-if="!p2pPolicyTermId" class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
          {{ t('p2p_policy_page.import_need_term') }}
        </p>

        <div v-if="step === 'upload'" class="space-y-3">
          <label
            class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 px-4 py-8 text-center dark:border-slate-600"
          >
            <span class="text-base font-medium text-slate-700 dark:text-slate-200">{{ t('p2p_policy_page.import_pick_file') }}</span>
            <span class="mt-1 text-sm text-slate-500">.xlsx</span>
            <input type="file" class="sr-only" accept=".xlsx,.xls" @change="onFilePicked" />
          </label>
          <p v-if="fileName" class="text-sm text-slate-600">{{ fileName }}</p>
        </div>

        <div v-else-if="step === 'preview'" class="space-y-3">
          <div class="flex flex-wrap gap-3 text-sm">
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
              {{ t('p2p_policy_page.import_summary_create', { n: summary.create ?? 0 }) }}
            </span>
            <span class="rounded-full bg-sky-100 px-3 py-1 text-sky-900 dark:bg-sky-950 dark:text-sky-200">
              {{ t('p2p_policy_page.import_summary_update', { n: summary.update ?? 0 }) }}
            </span>
            <span class="rounded-full bg-rose-100 px-3 py-1 text-rose-900 dark:bg-rose-950 dark:text-rose-200">
              {{ t('p2p_policy_page.import_summary_error', { n: summary.error ?? 0 }) }}
            </span>
          </div>
          <p v-if="warning" class="text-sm text-amber-700 dark:text-amber-300">{{ warning }}</p>
          <p v-if="truncated" class="text-xs text-slate-500">{{ t('p2p_policy_page.import_preview_truncated') }}</p>
          <div class="max-h-64 overflow-auto rounded-lg border border-slate-200 dark:border-slate-700">
            <table class="min-w-full text-xs">
              <thead class="sticky top-0 bg-slate-50 dark:bg-slate-800">
                <tr>
                  <th class="px-2 py-1.5 text-left">{{ t('p2p_policy_page.import_col_row') }}</th>
                  <th class="px-2 py-1.5 text-left">{{ t('p2p_policy_page.col_code') }}</th>
                  <th class="px-2 py-1.5 text-left">{{ t('p2p_policy_page.col_route') }}</th>
                  <th class="px-2 py-1.5 text-left">{{ t('p2p_policy_page.import_col_action') }}</th>
                  <th class="px-2 py-1.5 text-left">{{ t('p2p_policy_page.import_col_message') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(r, idx) in previewRows" :key="idx" class="border-t border-slate-100 dark:border-slate-800">
                  <td class="px-2 py-1">{{ r.row }}</td>
                  <td class="px-2 py-1 font-mono">{{ r.student_code }}</td>
                  <td class="px-2 py-1">{{ r.route_name }}</td>
                  <td class="px-2 py-1">
                    <span
                      class="rounded px-1.5 py-0.5"
                      :class="actionClass(r.action)"
                    >
                      {{ actionLabel(r.action) }}
                    </span>
                  </td>
                  <td class="px-2 py-1 text-rose-600">{{ (r.messages || []).join('; ') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-else-if="step === 'done'" class="space-y-2 text-sm">
          <p>{{ t('p2p_policy_page.import_done', { created: result.imported ?? 0, updated: result.updated ?? 0 }) }}</p>
          <ul v-if="result.errors?.length" class="list-disc pl-5 text-rose-600">
            <li v-for="(e, i) in result.errors" :key="i">{{ t('p2p_policy_page.import_col_row') }} {{ e.row }}: {{ e.message }}</li>
          </ul>
        </div>

        <p v-if="error" class="text-sm text-rose-600">{{ error }}</p>
      </div>

      <footer class="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-700">
        <button type="button" class="rounded-lg border px-4 py-2 text-sm" @click="close">
          {{ step === 'done' ? t('p2p_policy_page.close') : t('p2p_policy_page.cancel') }}
        </button>
        <button
          v-if="step === 'upload'"
          type="button"
          class="rounded-lg bg-va-800 px-4 py-2 text-sm text-white disabled:opacity-50"
          :disabled="!file || !p2pPolicyTermId || loading"
          @click="runPreview"
        >
          {{ t('p2p_policy_page.import_preview_btn') }}
        </button>
        <button
          v-if="step === 'preview'"
          type="button"
          class="rounded-lg bg-va-800 px-4 py-2 text-sm text-white disabled:opacity-50"
          :disabled="loading || !previewId || !(summary.create + summary.update)"
          @click="runCommit"
        >
          {{ t('p2p_policy_page.import_confirm_btn') }}
        </button>
      </footer>
    </div>
  </dialog>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { commitPolicyStudentsImport, previewPolicyStudentsImport } from '../../api/p2pPolicy'
import { formatApiError } from '../../api/http'

const props = defineProps({
  p2pPolicyTermId: { type: [String, Number], default: '' },
})

const emit = defineEmits(['committed'])

const { t } = useI18n()
const dialogEl = ref(null)
const step = ref('upload')
const file = ref(null)
const fileName = ref('')
const loading = ref(false)
const error = ref('')
const previewId = ref('')
const previewRows = ref([])
const summary = reactive({ create: 0, update: 0, error: 0, total: 0 })
const warning = ref('')
const truncated = ref(false)
const result = reactive({ imported: 0, updated: 0, errors: [] })

function reset() {
  step.value = 'upload'
  file.value = null
  fileName.value = ''
  error.value = ''
  previewId.value = ''
  previewRows.value = []
  summary.create = 0
  summary.update = 0
  summary.error = 0
  summary.total = 0
  warning.value = ''
  truncated.value = false
  result.imported = 0
  result.updated = 0
  result.errors = []
}

function open() {
  reset()
  dialogEl.value?.showModal()
}

function close() {
  dialogEl.value?.close()
  if (step.value === 'done') {
    emit('committed')
  }
}

function onFilePicked(ev) {
  const f = ev.target.files?.[0]
  file.value = f ?? null
  fileName.value = f?.name ?? ''
  ev.target.value = ''
}

function actionClass(action) {
  if (action === 'create') return 'bg-emerald-100 text-emerald-800'
  if (action === 'update') return 'bg-sky-100 text-sky-800'
  return 'bg-rose-100 text-rose-800'
}

function actionLabel(action) {
  if (action === 'create') return t('p2p_policy_page.import_action_create')
  if (action === 'update') return t('p2p_policy_page.import_action_update')
  return t('p2p_policy_page.import_action_error')
}

async function runPreview() {
  if (!file.value || !props.p2pPolicyTermId) return
  loading.value = true
  error.value = ''
  try {
    const fd = new FormData()
    fd.append('file', file.value)
    fd.append('p2p_policy_term_id', String(props.p2pPolicyTermId))
    const data = await previewPolicyStudentsImport(fd)
    previewId.value = data.preview_id
    previewRows.value = data.rows ?? []
    summary.create = data.summary?.create ?? 0
    summary.update = data.summary?.update ?? 0
    summary.error = data.summary?.error ?? 0
    summary.total = data.summary?.total ?? 0
    warning.value = data.warning ?? ''
    truncated.value = !!data.truncated
    step.value = 'preview'
  } catch (e) {
    error.value = formatApiError(e)
  } finally {
    loading.value = false
  }
}

async function runCommit() {
  if (!previewId.value) return
  loading.value = true
  error.value = ''
  try {
    const data = await commitPolicyStudentsImport(previewId.value)
    result.imported = data.imported ?? 0
    result.updated = data.updated ?? 0
    result.errors = data.errors ?? []
    step.value = 'done'
  } catch (e) {
    error.value = formatApiError(e)
  } finally {
    loading.value = false
  }
}

defineExpose({ open })
</script>
