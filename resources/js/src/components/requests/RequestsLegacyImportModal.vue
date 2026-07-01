<template>
  <Modal
    :open="open"
    :title="t('requests_page.legacy_import_modal_title')"
    :description="t('requests_page.legacy_import_modal_lead')"
    wide
    extra-wide
    :z-index="195"
    @close="emit('close')"
  >
    <div class="space-y-5">
      <!-- Stepper — ngôn ngữ đơn giản -->
      <ol class="grid gap-2 sm:grid-cols-4">
        <li
          v-for="(phase, i) in phases"
          :key="phase.key"
          class="flex items-center gap-2 rounded-lg border px-2.5 py-2 text-xs"
          :class="step === i ? 'border-va-400 bg-va-50/80' : step > i ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200 bg-white'"
        >
          <span
            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold"
            :class="step > i ? 'bg-emerald-500 text-white' : step === i ? 'bg-va-800 text-white' : 'bg-slate-100 text-slate-400'"
          >
            <CheckIcon v-if="step > i" class="h-3.5 w-3.5" aria-hidden="true" />
            <span v-else>{{ i + 1 }}</span>
          </span>
          <span class="font-medium leading-snug text-slate-800">{{ phase.label }}</span>
        </li>
      </ol>

      <!-- Bước 1: Chọn file -->
      <div v-if="step === 0" class="grid gap-4 lg:grid-cols-5">
        <div class="space-y-4 lg:col-span-3">
          <div class="rounded-xl border border-sky-100 bg-sky-50/70 px-4 py-3 text-sm text-sky-950">
            <p class="font-semibold">{{ t('requests_page.legacy_import_help_title') }}</p>
            <p class="mt-1 text-xs leading-relaxed text-sky-900/90">{{ t('requests_page.legacy_import_help_body') }}</p>
          </div>

          <div
            class="flex flex-col items-center rounded-xl border-2 border-dashed px-4 py-8 text-center transition"
            :class="dragging ? 'border-va-500 bg-va-50/40' : 'border-slate-200 bg-slate-50/30'"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
          >
            <CloudArrowUpIcon class="h-10 w-10 text-va-600" aria-hidden="true" />
            <p class="mt-2 text-sm font-medium text-slate-800">{{ t('requests_page.legacy_import_drop_title') }}</p>
            <p class="mt-1 max-w-md text-xs text-slate-500">{{ t('requests_page.legacy_import_drop_hint') }}</p>
            <div v-if="file" class="mt-3 inline-flex max-w-full items-center gap-2 rounded-lg bg-white px-3 py-1.5 text-xs text-slate-700 ring-1 ring-slate-200">
              <DocumentIcon class="h-4 w-4 shrink-0 text-emerald-600" aria-hidden="true" />
              <span class="max-w-[16rem] truncate">{{ file.name }}</span>
              <button type="button" class="shrink-0 text-slate-400 hover:text-rose-600" data-testid="requests-legacy-import-clear-file" @click="clearFile">
                <XMarkIcon class="h-4 w-4" aria-hidden="true" />
              </button>
            </div>
            <Button class="mt-4" variant="secondary" data-testid="requests-legacy-import-pick-file" @click="fileInputRef?.click()">
              {{ t('requests_page.legacy_import_pick_file') }}
            </Button>
            <input
              ref="fileInputRef"
              type="file"
              accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
              class="hidden"
              data-testid="requests-legacy-import-file-input"
              @change="onFileInput"
            />
          </div>
        </div>

        <div class="space-y-3 lg:col-span-2">
          <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h3 class="text-sm font-semibold text-slate-900">{{ t('requests_page.legacy_import_sheets_title') }}</h3>
            <p class="mt-1 text-xs text-slate-500">{{ t('requests_page.legacy_import_sheets_plain') }}</p>
            <ul class="mt-3 space-y-2">
              <li v-for="sh in sheetOptions" :key="sh.key">
                <label class="flex cursor-pointer gap-2 rounded-lg border border-slate-200 p-2.5 text-xs hover:border-va-300">
                  <input v-model="selectedSheets" type="checkbox" :value="sh.key" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-va-800" />
                  <span>
                    <span class="font-medium text-slate-800">{{ sh.label }}</span>
                    <span class="mt-0.5 block text-slate-500">{{ sh.plain }}</span>
                  </span>
                </label>
              </li>
            </ul>
          </div>
          <button
            type="button"
            class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-700 hover:border-va-300"
            :disabled="templateDownloading"
            data-testid="requests-legacy-import-download-template"
            @click="doDownloadTemplate"
          >
            <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" />
            {{ templateDownloading ? t('requests_page.legacy_import_template_busy') : t('requests_page.legacy_import_download_template') }}
          </button>
        </div>
      </div>

      <!-- Bước 2: Đang kiểm tra -->
      <div v-else-if="step === 1" class="flex flex-col items-center rounded-xl border border-slate-200 bg-white px-6 py-14 text-center">
        <div class="h-10 w-10 animate-spin rounded-full border-2 border-va-200 border-t-va-800" role="status" />
        <p class="mt-4 text-sm font-medium text-slate-800">{{ t('requests_page.legacy_import_checking') }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ t('requests_page.legacy_import_checking_hint') }}</p>
      </div>

      <!-- Bước 3: Kết quả kiểm tra -->
      <div v-else-if="step === 2 && batch" class="space-y-4">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 px-4 py-3">
          <p class="text-sm font-semibold text-emerald-950">{{ t('requests_page.legacy_import_preview_ok_title') }}</p>
          <p class="mt-1 text-xs text-emerald-900/90">{{ t('requests_page.legacy_import_preview_ok_body') }}</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="card in summaryCards" :key="card.key" class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ card.label }}</div>
            <div class="mt-1 font-display text-2xl tabular-nums text-slate-900">{{ card.value }}</div>
            <p class="mt-1 text-[11px] leading-snug text-slate-500">{{ card.hint }}</p>
          </div>
        </div>

        <div v-if="issuePreview.length" class="rounded-xl border border-amber-200 bg-amber-50/50">
          <div class="flex flex-wrap items-center justify-between gap-2 border-b border-amber-100 px-4 py-2.5">
            <div>
              <h3 class="text-sm font-semibold text-amber-950">{{ t('requests_page.legacy_import_issues_title') }}</h3>
              <p class="text-xs text-amber-900/80">{{ t('requests_page.legacy_import_issues_lead') }}</p>
            </div>
            <button
              v-if="batch.has_error_report"
              type="button"
              class="inline-flex items-center gap-1 rounded-lg border border-amber-300 bg-white px-2.5 py-1.5 text-xs font-medium text-amber-900 hover:bg-amber-50"
              data-testid="requests-legacy-import-download-report"
              @click="downloadReport(batch.id)"
            >
              <ArrowDownTrayIcon class="h-3.5 w-3.5" aria-hidden="true" />
              {{ t('requests_page.legacy_import_download_report') }}
            </button>
          </div>
          <div class="max-h-52 overflow-y-auto">
            <table class="w-full text-left text-xs">
              <thead class="sticky top-0 bg-amber-100/90 text-amber-950">
                <tr>
                  <th class="px-3 py-2 font-medium">{{ t('requests_page.legacy_import_col_row') }}</th>
                  <th class="px-3 py-2 font-medium">{{ t('requests_page.legacy_import_col_level') }}</th>
                  <th class="px-3 py-2 font-medium">{{ t('requests_page.legacy_import_col_message') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-amber-100/80 bg-white/80">
                <tr v-for="(row, ri) in issuePreview" :key="ri">
                  <td class="whitespace-nowrap px-3 py-2 text-slate-600">{{ row.sheet }} · {{ row.row }}</td>
                  <td class="px-3 py-2">
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase" :class="levelClass(row.level)">
                      {{ levelLabel(row.level) }}
                    </span>
                  </td>
                  <td class="px-3 py-2 text-slate-700">{{ row.message }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <details v-if="history.length" class="rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-xs">
          <summary class="cursor-pointer font-semibold text-slate-700">{{ t('requests_page.legacy_import_history') }}</summary>
          <ul class="mt-2 space-y-1.5">
            <li v-for="h in history" :key="h.id" class="flex flex-wrap gap-x-3 gap-y-0.5 text-slate-600">
              <span class="font-medium text-slate-800">{{ h.original_filename }}</span>
              <span>{{ h.status }}</span>
              <span>{{ formatWhen(h.created_at) }}</span>
            </li>
          </ul>
        </details>
      </div>

      <!-- Bước 4: Đang nhập / xong -->
      <div v-else-if="step === 3" class="space-y-4">
        <div v-if="executing" class="flex flex-col items-center rounded-xl border border-slate-200 bg-white px-6 py-12 text-center">
          <div class="h-10 w-10 animate-spin rounded-full border-2 border-va-200 border-t-va-800" role="status" />
          <p class="mt-4 text-sm font-medium text-slate-800">{{ t('requests_page.legacy_import_running') }}</p>
        </div>
        <template v-else-if="batch">
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-center">
            <CheckCircleIcon class="mx-auto h-10 w-10 text-emerald-600" aria-hidden="true" />
            <p class="mt-2 text-base font-semibold text-emerald-950">{{ t('requests_page.legacy_import_done_title') }}</p>
            <p class="mt-1 text-sm text-emerald-900/90">{{ t('requests_page.legacy_import_done_body') }}</p>
          </div>
          <div class="grid gap-3 sm:grid-cols-3">
            <div v-for="card in doneCards" :key="card.key" class="rounded-xl border border-slate-200 bg-white p-3 text-center">
              <div class="text-2xl font-semibold tabular-nums text-slate-900">{{ card.value }}</div>
              <div class="mt-0.5 text-xs text-slate-500">{{ card.label }}</div>
            </div>
          </div>
          <div class="flex flex-wrap justify-end gap-2">
            <Button v-if="batch.has_error_report" variant="secondary" data-testid="requests-legacy-import-done-report" @click="downloadReport(batch.id)">
              {{ t('requests_page.legacy_import_download_report') }}
            </Button>
            <Button data-testid="requests-legacy-import-close-done" @click="emit('completed'); emit('close')">
              {{ t('requests_page.legacy_import_close') }}
            </Button>
          </div>
        </template>
      </div>

      <!-- Footer actions -->
      <div v-if="step === 0" class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
        <Button variant="secondary" data-testid="requests-legacy-import-cancel" @click="emit('close')">
          {{ t('requests_page.bulk_confirm_cancel') }}
        </Button>
        <Button :disabled="!file || !selectedSheets.length || busy" data-testid="requests-legacy-import-analyze" @click="runAnalyze">
          {{ t('requests_page.legacy_import_btn_check') }}
        </Button>
      </div>
      <div v-else-if="step === 2 && batch" class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
        <Button variant="secondary" data-testid="requests-legacy-import-back" @click="step = 0">
          {{ t('requests_page.legacy_import_back') }}
        </Button>
        <Button :disabled="busy || !canExecute" data-testid="requests-legacy-import-execute" @click="runExecute">
          {{ t('requests_page.legacy_import_btn_import') }}
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowDownTrayIcon,
  CheckCircleIcon,
  CheckIcon,
  CloudArrowUpIcon,
  DocumentIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import Modal from '../ui/Modal.vue'
import Button from '../ui/Button.vue'
import {
  downloadLegacyDispatchImportErrorReport,
  downloadLegacyDispatchImportTemplate,
  executeLegacyDispatchImportBatch,
  listLegacyImportBatches,
  uploadLegacyDispatchImport,
} from '../../api/requests'
import { showAppErrorFromApi } from '../../composables/appMessage'

const props = defineProps({
  open: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'completed'])

const { t } = useI18n()

const phases = computed(() => [
  { key: 'file', label: t('requests_page.legacy_import_phase_file') },
  { key: 'check', label: t('requests_page.legacy_import_phase_check') },
  { key: 'review', label: t('requests_page.legacy_import_phase_review') },
  { key: 'done', label: t('requests_page.legacy_import_phase_done') },
])

const step = ref(0)
const file = ref(null)
const fileInputRef = ref(null)
const dragging = ref(false)
const busy = ref(false)
const executing = ref(false)
const templateDownloading = ref(false)
/** @type {import('vue').Ref<Record<string, unknown> | null>} */
const batch = ref(null)
const history = ref([])

const sheetOptions = computed(() => [
  { key: 'vehicles', label: t('requests_page.legacy_import_sheet_vehicles'), plain: t('requests_page.legacy_import_sheet_vehicles_plain') },
  { key: 'passenger', label: t('requests_page.legacy_import_sheet_passenger'), plain: t('requests_page.legacy_import_sheet_passenger_plain') },
  { key: 'cargo', label: t('requests_page.legacy_import_sheet_cargo'), plain: t('requests_page.legacy_import_sheet_cargo_plain') },
  { key: 'costs', label: t('requests_page.legacy_import_sheet_costs'), plain: t('requests_page.legacy_import_sheet_costs_plain') },
  { key: 'odometer', label: t('requests_page.legacy_import_sheet_odometer'), plain: t('requests_page.legacy_import_sheet_odometer_plain') },
])

const selectedSheets = ref(['vehicles', 'passenger', 'cargo'])

watch(
  () => props.open,
  (v) => {
    if (v) resetWizard()
  },
)

async function resetWizard() {
  step.value = 0
  busy.value = false
  executing.value = false
  batch.value = null
  try {
    const res = await listLegacyImportBatches()
    history.value = res.items ?? []
  } catch {
    history.value = []
  }
}

function clearFile() {
  file.value = null
}

function onFileInput(e) {
  const f = e.target.files?.[0]
  if (f) file.value = f
  e.target.value = ''
}

function onDrop(e) {
  dragging.value = false
  const f = e.dataTransfer?.files?.[0]
  if (f) file.value = f
}

async function doDownloadTemplate() {
  templateDownloading.value = true
  try {
    await downloadLegacyDispatchImportTemplate()
  } catch (e) {
    showAppErrorFromApi(e, t('requests_page.legacy_import_template_fail'))
  } finally {
    templateDownloading.value = false
  }
}

async function runAnalyze() {
  if (!file.value || !selectedSheets.value.length || busy.value) return
  busy.value = true
  step.value = 1
  try {
    batch.value = await uploadLegacyDispatchImport(file.value, { sheets: selectedSheets.value })
    step.value = 2
  } catch (e) {
    step.value = 0
    showAppErrorFromApi(e, t('requests_page.legacy_import_fail'))
  } finally {
    busy.value = false
  }
}

const summary = computed(() => batch.value?.summary ?? {})

const summaryCards = computed(() => [
  {
    key: 'ok',
    label: t('requests_page.legacy_import_card_ok'),
    value: summary.value.will_import_requests ?? 0,
    hint: t('requests_page.legacy_import_card_ok_hint'),
  },
  {
    key: 'skip',
    label: t('requests_page.legacy_import_card_skip'),
    value: summary.value.rows_skipped ?? 0,
    hint: t('requests_page.legacy_import_card_skip_hint'),
  },
  {
    key: 'warn',
    label: t('requests_page.legacy_import_card_warn'),
    value: batch.value?.issue_count ?? 0,
    hint: t('requests_page.legacy_import_card_warn_hint'),
  },
  {
    key: 'veh',
    label: t('requests_page.legacy_import_card_veh'),
    value: summary.value.vehicles_upserted ?? 0,
    hint: t('requests_page.legacy_import_card_veh_hint'),
  },
])

const issuePreview = computed(() => (batch.value?.issues ?? []).slice(0, 15))

const canExecute = computed(() => (summary.value.will_import_requests ?? 0) > 0 || selectedSheets.value.includes('costs'))

async function runExecute() {
  if (!batch.value?.id || busy.value) return
  busy.value = true
  executing.value = true
  step.value = 3
  try {
    batch.value = await executeLegacyDispatchImportBatch(batch.value.id)
    executing.value = false
    emit('completed')
  } catch (e) {
    executing.value = false
    step.value = 2
    showAppErrorFromApi(e, t('requests_page.legacy_import_fail'))
  } finally {
    busy.value = false
  }
}

const doneCards = computed(() => {
  const s = batch.value?.execute_stats ?? batch.value?.analyze_stats ?? {}
  const p = s.passenger ?? {}
  const c = s.cargo ?? {}
  return [
    { key: 'req', label: t('requests_page.legacy_import_stat_created_requests'), value: (p.created_requests ?? 0) + (c.created_requests ?? 0) },
    { key: 'trip', label: t('requests_page.legacy_import_stat_created_trips'), value: (p.created_trips ?? 0) + (c.created_trips ?? 0) },
    { key: 'dup', label: t('requests_page.legacy_import_stat_duplicates'), value: (p.duplicates ?? 0) + (c.duplicates ?? 0) },
  ]
})

async function downloadReport(batchId) {
  try {
    await downloadLegacyDispatchImportErrorReport(batchId)
  } catch (e) {
    showAppErrorFromApi(e, t('requests_page.legacy_import_template_fail'))
  }
}

function levelLabel(level) {
  if (level === 'error') return t('requests_page.legacy_import_level_error')
  if (level === 'warning') return t('requests_page.legacy_import_level_warning')
  return t('requests_page.legacy_import_level_skip')
}

function levelClass(level) {
  if (level === 'error') return 'bg-rose-100 text-rose-800'
  if (level === 'warning') return 'bg-amber-100 text-amber-900'
  return 'bg-slate-100 text-slate-600'
}

function formatWhen(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString('vi-VN')
  } catch {
    return iso
  }
}
</script>
