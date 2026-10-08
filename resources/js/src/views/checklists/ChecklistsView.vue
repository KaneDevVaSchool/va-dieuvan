<template>
  <div class="space-y-4">
    <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('checklist.staff_title') }}</h2>
          <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ t('checklist.staff_subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <div class="inline-flex rounded-lg border border-slate-200 p-0.5 dark:border-slate-600" role="tablist">
            <button
              v-for="tab in ['compliance', 'list']"
              :key="tab"
              type="button"
              role="tab"
              :aria-selected="activeTab === tab"
              class="rounded-md px-3 py-1.5 text-sm font-medium"
              :class="activeTab === tab ? 'bg-teal-600 text-white' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'"
              @click="activeTab = tab"
            >
              {{ t(`checklist.tab_${tab}`) }}
            </button>
          </div>
          <RouterLink
            v-if="auth.hasPermission('checklist.manage')"
            :to="{ name: 'checklistSettings' }"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            <Cog6ToothIcon class="h-4 w-4" /> {{ t('checklist.settings_title') }}
          </RouterLink>
        </div>
      </div>

      <!-- Ma trận tuân thủ -->
      <template v-if="activeTab === 'compliance'">
        <div class="mt-4 grid gap-3 sm:grid-cols-4">
          <select v-model="matrixFilters.template_id" :aria-label="t('checklist.filter_template')" class="rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" @change="onTemplateChange">
            <option v-for="tpl in templates" :key="tpl.id" :value="tpl.id">{{ tpl.name }}</option>
          </select>
          <input v-model="matrixFilters.date_from" type="date" :aria-label="t('checklist.filter_from')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
          <input v-model="matrixFilters.date_to" type="date" :aria-label="t('checklist.filter_to')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
          <button type="button" class="rounded-lg bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50" :disabled="matrixLoading" @click="loadMatrix">
            {{ t('checklist.apply') }}
          </button>
        </div>

        <div v-if="matrix" class="mt-4 flex flex-wrap gap-2">
          <span v-for="k in ['pass', 'fail', 'draft', 'todo']" :key="k" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1" :class="CHECKLIST_STATE_PILL[k]">
            {{ t(`checklist.state_${k}`) }} <span class="tabular-nums">{{ matrix.totals?.[k] ?? 0 }}</span>
          </span>
        </div>

        <div class="mt-4 overflow-x-auto">
          <table v-if="matrix" class="w-full border-separate border-spacing-0 text-left text-sm">
            <thead>
              <tr class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                <th class="sticky left-0 z-10 min-w-[180px] border-b border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800">{{ t('checklist.col_driver') }}</th>
                <th v-for="p in matrix.periods" :key="p.key" class="min-w-[92px] whitespace-nowrap border-b border-slate-200 px-2 py-2 text-center dark:border-slate-700">{{ shortPeriod(p) }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in matrix.rows" :key="row.driver.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                <td class="sticky left-0 z-10 border-b border-slate-100 bg-white px-3 py-2 dark:border-slate-800 dark:bg-slate-900">
                  <div class="font-medium text-slate-900 dark:text-slate-100">{{ row.driver.full_name }}</div>
                  <div class="text-xs text-slate-500">{{ row.driver.phone }}</div>
                </td>
                <td v-for="p in matrix.periods" :key="p.key" class="border-b border-slate-100 px-1.5 py-1.5 text-center dark:border-slate-800">
                  <component
                    :is="row.cells[p.key]?.submission_id ? 'RouterLink' : 'span'"
                    :to="row.cells[p.key]?.submission_id ? { name: 'checklistDetail', params: { id: row.cells[p.key].submission_id } } : undefined"
                    class="inline-flex min-w-[76px] flex-col items-center rounded-md px-1.5 py-1 text-[11px] font-semibold ring-1"
                    :class="CHECKLIST_STATE_PILL[row.cells[p.key]?.state || 'todo']"
                    :title="row.cells[p.key]?.license_plate || ''"
                  >
                    {{ t(`checklist.state_${row.cells[p.key]?.state || 'todo'}`) }}
                    <span v-if="row.cells[p.key]?.submitted_at" class="font-normal tabular-nums opacity-80">{{ timeOf(row.cells[p.key].submitted_at) }}</span>
                  </component>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-if="matrixLoading" class="mt-3 text-sm text-slate-500">{{ t('checklist.loading') }}</p>
          <p v-else-if="matrix && !matrix.rows.length" class="mt-3 text-sm text-slate-500">{{ t('checklist.no_drivers') }}</p>
        </div>
      </template>

      <!-- Danh sách phiếu -->
      <template v-else>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
          <input v-model="listFilters.q" type="search" :placeholder="t('checklist.search_ph')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 lg:col-span-2" @keyup.enter="loadList(1)" />
          <select v-model="listFilters.template_id" :aria-label="t('checklist.filter_template')" class="rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
            <option value="">{{ t('checklist.all_templates') }}</option>
            <option v-for="tpl in templates" :key="tpl.id" :value="tpl.id">{{ tpl.name }}</option>
          </select>
          <select v-model="listFilters.result" :aria-label="t('checklist.filter_result')" class="rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
            <option value="">{{ t('checklist.all_results') }}</option>
            <option value="pass">{{ t('checklist.state_pass') }}</option>
            <option value="fail">{{ t('checklist.state_fail') }}</option>
          </select>
          <input v-model="listFilters.date_from" type="date" :aria-label="t('checklist.filter_from')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
          <input v-model="listFilters.date_to" type="date" :aria-label="t('checklist.filter_to')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
        </div>
        <div class="mt-3 flex flex-wrap justify-end gap-2">
          <button type="button" class="rounded-lg bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700" @click="loadList(1)">{{ t('checklist.apply') }}</button>
          <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium dark:border-slate-600 dark:text-slate-200" :disabled="exporting" @click="exportList">
            <ArrowDownTrayIcon class="h-4 w-4" /> {{ t('checklist.export_excel') }}
          </button>
        </div>

        <div class="mt-4 overflow-x-auto">
          <table class="w-full min-w-[820px] border-separate border-spacing-0 text-left text-sm">
            <thead>
              <tr class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('checklist.col_code') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('checklist.col_driver') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('checklist.col_vehicle') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('checklist.col_type') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('checklist.col_submitted') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('checklist.col_result') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="s in list" :key="s.id" class="cursor-pointer hover:bg-slate-50/60 dark:hover:bg-slate-800/40" @click="router.push({ name: 'checklistDetail', params: { id: s.id } })">
                <td class="px-3 py-2 font-mono text-xs text-teal-700 dark:text-teal-400">{{ s.code }}</td>
                <td class="px-3 py-2">{{ s.driver?.full_name }}</td>
                <td class="px-3 py-2 font-mono text-xs">{{ s.vehicle?.license_plate }}</td>
                <td class="px-3 py-2 text-xs">{{ t(`checklist.freq_${s.period_type}`) }} · {{ s.period_label }}</td>
                <td class="whitespace-nowrap px-3 py-2 text-xs">{{ dateTime(s.submitted_at || s.started_at) }}</td>
                <td class="px-3 py-2">
                  <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1" :class="CHECKLIST_STATE_PILL[stateOf(s)]">{{ t(`checklist.state_${stateOf(s)}`) }}</span>
                  <span v-if="s.critical_ng_count" class="ml-1 text-xs text-rose-600">{{ s.critical_ng_count }} Critical NG</span>
                  <span v-if="s.status === 'reviewed'" class="ml-1 text-xs text-slate-500">· {{ t('checklist.reviewed') }}</span>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-if="listLoading" class="mt-3 text-sm text-slate-500">{{ t('checklist.loading') }}</p>
          <p v-else-if="!list.length" class="mt-3 text-sm text-slate-500">{{ t('checklist.history_empty') }}</p>
        </div>
        <div v-if="listMeta.last_page > 1" class="mt-4 flex items-center justify-between gap-2 text-xs text-slate-600 dark:text-slate-400">
          <span>{{ t('checklist.page_info', { page: listMeta.current_page, last: listMeta.last_page, total: listMeta.total }) }}</span>
          <div class="flex gap-2">
            <button type="button" class="rounded-md border border-slate-200 px-3 py-1 disabled:opacity-40 dark:border-slate-600" :disabled="listMeta.current_page <= 1" @click="loadList(listMeta.current_page - 1)">‹</button>
            <button type="button" class="rounded-md border border-slate-200 px-3 py-1 disabled:opacity-40 dark:border-slate-600" :disabled="listMeta.current_page >= listMeta.last_page" @click="loadList(listMeta.current_page + 1)">›</button>
          </div>
        </div>
      </template>
    </section>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { ArrowDownTrayIcon, Cog6ToothIcon } from '@heroicons/vue/24/outline'
import { exportChecklists, getChecklistCompliance, getChecklistSettings, listChecklists } from '../../api/checklists'
import { CHECKLIST_STATE_PILL } from '../../constants/checklistExpense'
import { showAppErrorFromApi } from '../../composables/appMessage'
import { useAuthStore } from '../../store'
import { toLocalDateKey } from '../../util/dates'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const activeTab = ref('compliance')
const templates = ref([])

const matrix = ref(null)
const matrixLoading = ref(false)
const matrixFilters = reactive({ template_id: '', date_from: '', date_to: '' })

const list = ref([])
const listMeta = ref({ current_page: 1, last_page: 1, total: 0 })
const listLoading = ref(false)
const exporting = ref(false)
const listFilters = reactive({ q: '', template_id: '', result: '', date_from: '', date_to: '' })

function daysAgo(n) {
  const d = new Date()
  d.setDate(d.getDate() - n)
  return toLocalDateKey(d)
}

function onTemplateChange() {
  const tpl = templates.value.find((x) => String(x.id) === String(matrixFilters.template_id))
  matrixFilters.date_from = daysAgo(tpl?.frequency === 'weekly' ? 55 : 6)
  matrixFilters.date_to = daysAgo(0)
  void loadMatrix()
}

function shortPeriod(p) {
  if (/^\d{4}-\d{2}-\d{2}$/.test(p.key)) {
    const d = new Date(`${p.key}T00:00:00`)
    return d.toLocaleDateString('vi-VN', { weekday: 'short', day: '2-digit', month: '2-digit' })
  }
  return p.key.replace(/^\d{4}-/, '')
}

function timeOf(iso) {
  return new Date(iso).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}

function dateTime(iso) {
  return iso ? new Date(iso).toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : ''
}

function stateOf(s) {
  if (s.status === 'draft') return 'draft'
  return s.result === 'fail' ? 'fail' : 'pass'
}

function clean(obj) {
  return Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== '' && v != null))
}

async function loadMatrix() {
  if (!matrixFilters.template_id) return
  matrixLoading.value = true
  try {
    matrix.value = await getChecklistCompliance(clean({ ...matrixFilters }))
  } catch (err) {
    showAppErrorFromApi(err, t('checklist.load_error'))
  } finally {
    matrixLoading.value = false
  }
}

async function loadList(page = 1) {
  listLoading.value = true
  try {
    const res = await listChecklists(clean({ ...listFilters, page, per_page: 30 }))
    list.value = res.items
    listMeta.value = res.meta
  } catch (err) {
    showAppErrorFromApi(err, t('checklist.load_error'))
  } finally {
    listLoading.value = false
  }
}

async function exportList() {
  exporting.value = true
  try {
    await exportChecklists(clean({ ...listFilters }))
  } catch (err) {
    showAppErrorFromApi(err, t('checklist.download_error'))
  } finally {
    exporting.value = false
  }
}

watch(activeTab, (tab) => {
  if (tab === 'list' && !list.value.length) void loadList(1)
})

onMounted(async () => {
  try {
    const res = await getChecklistSettings()
    templates.value = res.templates ?? []
  } catch {
    templates.value = []
  }
  matrixFilters.template_id = templates.value[0]?.id ?? ''
  onTemplateChange()
})
</script>
