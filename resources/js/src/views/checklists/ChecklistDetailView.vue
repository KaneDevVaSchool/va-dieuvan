<template>
  <div class="space-y-3 sm:space-y-4">
    <RouterLink :to="{ name: 'checklists' }" class="inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:underline dark:text-teal-400">
      <ArrowLeftIcon class="h-4 w-4" /> {{ t('checklist.back_to_list') }}
    </RouterLink>

    <p v-if="loadError" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">{{ loadError }}</p>

    <template v-if="sub">
      <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ sub.code }} · {{ sub.template?.name }}</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
              {{ sub.driver?.full_name }} ({{ sub.driver?.phone }}) · <span class="font-mono">{{ sub.vehicle?.license_plate }}</span> · {{ sub.period_label }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
              {{ t('checklist.started_at') }}: {{ dateTime(sub.started_at) }} · {{ t('checklist.col_submitted') }}: {{ dateTime(sub.submitted_at) || '—' }}
              <span v-if="sub.location_text"> · {{ sub.location_text }}</span>
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-600 dark:text-slate-200" :disabled="busy" @click="download('pdf')">
              <ArrowDownTrayIcon class="h-4 w-4" /> PDF
            </button>
            <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-600 dark:text-slate-200" :disabled="busy" @click="download('xlsx')">
              <ArrowDownTrayIcon class="h-4 w-4" /> Excel
            </button>
          </div>
        </div>

        <div
          class="mt-4 rounded-lg px-4 py-3 text-sm font-semibold ring-1"
          :class="sub.status === 'draft' ? CHECKLIST_STATE_PILL.draft : sub.result === 'fail' ? CHECKLIST_STATE_PILL.fail : CHECKLIST_STATE_PILL.pass"
        >
          <template v-if="sub.status === 'draft'">{{ t('checklist.draft_in_progress', { done: doneCount, total: sub.items.length }) }}</template>
          <template v-else-if="sub.result === 'fail'">{{ t('checklist.result_fail_desc', { count: sub.critical_ng_count }) }}</template>
          <template v-else>{{ t('checklist.result_pass_desc') }}</template>
        </div>

        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-4">
          <div><dt class="text-xs text-slate-500">{{ t('checklist.info_route') }}</dt><dd>{{ sub.trip_info?.campus_route || '—' }}</dd></div>
          <div><dt class="text-xs text-slate-500">{{ t('checklist.info_departure') }}</dt><dd>{{ sub.trip_info?.departure_time || '—' }}</dd></div>
          <div><dt class="text-xs text-slate-500">{{ t('checklist.info_attendant') }}</dt><dd>{{ sub.trip_info?.attendant || '—' }}</dd></div>
          <div><dt class="text-xs text-slate-500">{{ t('checklist.inspector') }}</dt><dd>{{ sub.inspector_name || '—' }}</dd></div>
          <div v-if="sub.note" class="sm:col-span-4"><dt class="text-xs text-slate-500">{{ t('checklist.general_note') }}</dt><dd>{{ sub.note }}</dd></div>
        </dl>
      </section>

      <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
        <label class="mb-3 inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
          <input v-model="onlyNg" type="checkbox" class="h-4 w-4 rounded accent-teal-600" /> {{ t('checklist.only_ng') }}
        </label>
        <div class="overflow-x-auto">
          <table class="w-full min-w-[760px] border-separate border-spacing-0 text-left text-sm">
            <thead>
              <tr class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('checklist.col_group') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('checklist.col_item') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 text-center dark:border-slate-700">{{ t('checklist.col_result') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('checklist.col_note') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('checklist.col_time') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="i in visibleItems" :key="i.id" :class="i.result === 'ng' ? 'bg-rose-50/60 dark:bg-rose-950/20' : ''">
                <td class="px-3 py-2 align-top text-xs text-slate-500">{{ i.group_name }}</td>
                <td class="px-3 py-2 align-top">
                  <div class="font-medium text-slate-900 dark:text-slate-100">
                    {{ i.title }}
                    <span v-if="i.severity === 'critical'" class="ml-1 rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">Critical</span>
                  </div>
                  <div class="text-xs text-slate-500">{{ i.requirement }}</div>
                  <div v-if="i.result === 'ng' && i.ng_action" class="mt-1 text-xs text-rose-700 dark:text-rose-300">→ {{ i.ng_action }}</div>
                </td>
                <td class="px-3 py-2 text-center align-top">
                  <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold" :class="resultPill(i.result)">{{ i.result ? t(`checklist.result_${i.result}`) : '—' }}</span>
                </td>
                <td class="px-3 py-2 align-top">
                  <div class="text-xs">{{ i.note }}</div>
                  <div v-if="i.attachments?.length" class="mt-1.5 flex flex-wrap gap-1.5">
                    <AuthAttachmentThumb v-for="a in i.attachments" :key="a.id" :attachment="a" size="sm" />
                  </div>
                </td>
                <td class="whitespace-nowrap px-3 py-2 align-top text-xs tabular-nums text-slate-500">{{ timeOnly(i.checked_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="sub.status !== 'draft'" class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ t('checklist.review_title') }}</h3>
        <p v-if="sub.status === 'reviewed'" class="mt-1 text-xs text-slate-500">
          {{ t('checklist.reviewed_by', { name: sub.reviewer?.name || '—', time: dateTime(sub.reviewed_at) }) }}
        </p>
        <textarea v-model="reviewNote" rows="2" maxlength="2000" :placeholder="t('checklist.review_note_ph')" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
        <button type="button" class="mt-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50" :disabled="busy" @click="review">
          {{ sub.status === 'reviewed' ? t('checklist.review_update') : t('checklist.review_mark') }}
        </button>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { ArrowDownTrayIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'
import { downloadChecklistPdf, downloadChecklistXlsx, getChecklist, reviewChecklist } from '../../api/checklists'
import { CHECKLIST_STATE_PILL } from '../../constants/checklistExpense'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import AuthAttachmentThumb from '../../components/shared/AuthAttachmentThumb.vue'

const { t } = useI18n()
const route = useRoute()

const sub = ref(null)
const loadError = ref('')
const busy = ref(false)
const onlyNg = ref(false)
const reviewNote = ref('')

const doneCount = computed(() => (sub.value?.items ?? []).filter((i) => i.result).length)
const visibleItems = computed(() => (sub.value?.items ?? []).filter((i) => !onlyNg.value || i.result === 'ng'))

function dateTime(iso) {
  return iso ? new Date(iso).toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : ''
}
function timeOnly(iso) {
  return iso ? new Date(iso).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) : ''
}
function resultPill(r) {
  return {
    ok: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    ng: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
    na: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
  }[r] || 'text-slate-400'
}

async function load() {
  try {
    sub.value = await getChecklist(route.params.id)
    reviewNote.value = sub.value.review_note || ''
  } catch (err) {
    loadError.value = err?.response?.data?.message || t('checklist.load_error')
  }
}

async function review() {
  busy.value = true
  try {
    sub.value = await reviewChecklist(sub.value.id, reviewNote.value)
    showAppSuccess(t('checklist.review_done'))
  } catch (err) {
    showAppErrorFromApi(err, t('checklist.submit_error'))
  } finally {
    busy.value = false
  }
}

async function download(format) {
  busy.value = true
  try {
    if (format === 'pdf') await downloadChecklistPdf(sub.value, 'staff')
    else await downloadChecklistXlsx(sub.value)
  } catch (err) {
    showAppErrorFromApi(err, t('checklist.download_error'))
  } finally {
    busy.value = false
  }
}

onMounted(load)
</script>
