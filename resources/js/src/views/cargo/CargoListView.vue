<template>
  <div class="space-y-4">
    <Card :title="t('cargo_page.quick_create_title')">
      <form class="grid gap-3 md:grid-cols-2" @submit.prevent="create">
        <Input v-model="form.pickup_address" :label="t('cargo_page.pickup')" />
        <Input v-model="form.delivery_address" :label="t('cargo_page.delivery')" />
        <Input v-model="form.sender_name" :label="t('cargo_page.sender')" />
        <Input v-model="form.receiver_name" :label="t('cargo_page.receiver')" />
        <div class="flex flex-wrap items-center gap-2 md:col-span-2">
          <Button :loading="creating" type="submit">{{ t('cargo_page.create') }}</Button>
          <span v-if="msg" class="text-sm text-slate-600 dark:text-slate-400">{{ msg }}</span>
        </div>
      </form>
    </Card>

    <AppFilterBar>
      <div class="flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
        <AppFilterFunnelMenu ref="filterMenuRef" :badge-count="activeFilterCount">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ t('filter_bar.active_title') }}
          </p>
          <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
            <li v-if="filters.status" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.status') }}</span>
              <span class="font-medium">{{ labelCargoStatus(filters.status) }}</span>
            </li>
            <li v-if="filters.from || filters.to" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.created_range') }}</span>
              <span class="text-right font-medium">{{ filters.from || '…' }} → {{ filters.to || '…' }}</span>
            </li>
            <li v-if="filters.q?.trim()" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.search') }}</span>
              <span class="max-w-[10rem] truncate font-medium">{{ filters.q.trim() }}</span>
            </li>
            <li v-if="filters.per_page !== 20" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.per_page') }}</span>
              <span class="font-medium">{{ filters.per_page }}</span>
            </li>
            <li v-if="activeFilterCount === 0" class="text-slate-400 dark:text-slate-500">{{ t('filter_bar.empty') }}</li>
          </ul>
          <button
            type="button"
            class="mt-3 w-full rounded-lg border border-slate-200 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
            @click="resetFilters(); closeFilterMenu()"
          >
            {{ t('filter_bar.clear_all') }}
          </button>
        </AppFilterFunnelMenu>

        <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
          <AppFilterDropdown
            :label="t('filter_bar.status')"
            :summary-text="filters.status ? labelCargoStatus(filters.status) : t('filter_bar.all')"
            summary-text-class="max-w-[10rem]"
            panel-class="min-w-[220px] py-1"
          >
            <ul class="max-h-[min(60vh,320px)] space-y-0.5 overflow-y-auto px-1 py-1">
              <li v-for="opt in statusOptions" :key="opt.value === '' ? '_all' : opt.value">
                <button
                  type="button"
                  class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                  :class="
                    filters.status === opt.value
                      ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                      : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                  "
                  @click="applyFilterPatch($event, { status: opt.value })"
                >
                  {{ opt.label }}
                </button>
              </li>
            </ul>
          </AppFilterDropdown>

          <AppFilterDropdown
            :label="t('filter_bar.created_range')"
            :summary-text="dateRangeSummary"
            full-width-summary
            panel-class="w-[min(100vw-1.5rem,320px)] p-3 sm:w-max"
          >
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
              <input
                v-model="filters.from"
                type="date"
                class="h-9 w-full rounded-md border-0 bg-white px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-auto dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                @change="onFilterDropdownChange"
              />
              <span class="hidden text-slate-300 dark:text-slate-600 sm:inline">—</span>
              <input
                v-model="filters.to"
                type="date"
                class="h-9 w-full rounded-md border-0 bg-white px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-auto dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                @change="onFilterDropdownChange"
              />
            </div>
          </AppFilterDropdown>
        </div>

        <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
            :title="t('filter_bar.clear_icon')"
            @click="resetFilters"
          >
            <span class="relative inline-flex">
              <FunnelIcon class="h-5 w-5" />
              <XMarkIcon
                class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/40"
              />
            </span>
          </button>
          <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />
          <button
            type="button"
            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-white/70 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-slate-100"
            :aria-expanded="extraFiltersOpen"
            @click="extraFiltersOpen = !extraFiltersOpen"
          >
            {{ t('filter_bar.more') }}
            <PlusCircleIcon class="h-5 w-5 text-teal-600 dark:text-teal-400" aria-hidden="true" />
          </button>
        </div>
      </div>

      <div
        v-show="extraFiltersOpen"
        class="mt-3 flex flex-col gap-3 border-t border-violet-100/80 pt-3 dark:border-violet-900/30 sm:flex-row sm:flex-wrap sm:items-end"
      >
        <label class="flex min-w-0 flex-1 flex-col gap-1 sm:max-w-md">
          <span class="text-sm text-slate-600 dark:text-slate-400">{{ t('filter_bar.search') }}</span>
          <input
            v-model="filters.q"
            type="search"
            :placeholder="t('filter_bar.search_placeholder')"
            class="h-9 w-full rounded-md border-0 bg-white/90 px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            @keydown.enter.prevent="onFilterChange"
          />
        </label>
        <label class="inline-flex items-center gap-2">
          <span class="text-sm text-slate-600 dark:text-slate-400">{{ t('filter_bar.per_page') }}</span>
          <select
            v-model.number="filters.per_page"
            class="h-9 rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            @change="onFilterChange"
          >
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </label>
      </div>
    </AppFilterBar>

    <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('cargo_page.date_filter_hint') }}</p>

    <Card :title="t('cargo_page.list_title')">
      <div v-if="loading" class="text-sm text-slate-500 dark:text-slate-400">{{ t('cargo_page.loading') }}</div>
      <div v-else class="space-y-2">
        <div
          v-for="s in items"
          :key="s.id"
          class="space-y-3 rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-600"
        >
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
              <div class="font-semibold text-slate-900 dark:text-slate-100">
                <span v-if="s.tracking_code" class="mr-2 font-mono text-xs text-slate-600 dark:text-slate-400">{{
                  s.tracking_code
                }}</span>
                #{{ s.id }} · {{ labelCargoStatus(s.status) }}
              </div>
              <div class="mt-1 text-slate-600 dark:text-slate-300">{{ s.pickup_address }} → {{ s.delivery_address }}</div>
              <div v-if="s.sla_due_at" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                SLA: {{ fmt(s.sla_due_at) }}
              </div>
            </div>
            <Button variant="secondary" class="shrink-0 text-xs" @click="toggleTimeline(s.id)">
              {{ expandedId === s.id ? t('cargo_page.hide_timeline') : t('cargo_page.show_timeline') }}
            </Button>
          </div>

          <div v-if="expandedId === s.id" class="border-t border-slate-100 pt-3 dark:border-slate-700">
            <div v-if="timelineLoading[s.id]" class="text-xs text-slate-500 dark:text-slate-400">
              {{ t('cargo_page.timeline_loading') }}
            </div>
            <ul
              v-else-if="(timelineCache[s.id] ?? []).length"
              class="relative ml-2 space-y-4 border-l-2 border-slate-200 pl-4 dark:border-slate-600"
            >
              <li v-for="(ev, idx) in timelineCache[s.id]" :key="`${s.id}-${idx}-${ev.at}`" class="relative">
                <span
                  class="absolute -left-[calc(0.5rem+5px)] top-1.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-va-600 dark:border-slate-900"
                />
                <div class="flex flex-wrap justify-between gap-2 text-xs">
                  <span class="font-medium text-slate-800 dark:text-slate-200">{{ timelineTitle(ev) }}</span>
                  <span class="text-slate-500 dark:text-slate-400">{{ fmt(ev.at) }}</span>
                </div>
                <div v-if="timelineSubtitle(ev)" class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                  {{ timelineSubtitle(ev) }}
                </div>
                <div v-if="ev.kind === 'audit' && ev.actor?.name" class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                  {{ ev.actor.name }}
                </div>
              </li>
            </ul>
            <p v-else class="text-xs text-slate-500 dark:text-slate-400">{{ t('cargo_page.timeline_empty') }}</p>
          </div>

          <div v-if="(s.attachments ?? []).length" class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/40">
            <div class="text-xs font-medium text-slate-600 dark:text-slate-300">
              {{ t('cargo_page.pod_count', { n: s.attachments.length }) }}
            </div>
            <ul class="mt-2 space-y-3">
              <li
                v-for="pod in s.attachments"
                :key="pod.id"
                class="flex flex-wrap items-start gap-3 border-b border-slate-200 pb-3 last:border-0 last:pb-0 dark:border-slate-600"
              >
                <div class="min-w-0 flex-1">
                  <a
                    v-if="pod.url"
                    :href="pod.url"
                    target="_blank"
                    rel="noopener"
                    class="break-all text-sm font-medium text-slate-900 underline dark:text-slate-100"
                  >
                    {{ pod.original_name || t('cargo_page.open_pod') }}
                  </a>
                  <div v-if="pod.mime_type" class="text-[11px] text-slate-400">{{ pod.mime_type }}</div>
                </div>
                <img
                  v-if="pod.mime_type?.startsWith('image/') && pod.url"
                  :src="pod.url"
                  alt=""
                  class="max-h-28 max-w-[200px] rounded border object-cover"
                />
              </li>
            </ul>
          </div>
          <div v-else class="text-xs text-slate-400 dark:text-slate-500">{{ t('cargo_page.no_pod') }}</div>

          <FileUpload
            :key="`pod-${s.id}`"
            :label="t('cargo_page.upload_pod')"
            :hint="t('cargo_page.upload_pod_hint')"
            :upload-fn="(file, onProgress) => uploadCargoPod(s.id, file, onProgress)"
            @uploaded="reload"
          />
        </div>
        <div v-if="!items.length" class="text-slate-500 dark:text-slate-400">{{ t('cargo_page.empty') }}</div>
      </div>

      <div class="mt-4 flex items-center justify-between text-sm">
        <span class="text-slate-500 dark:text-slate-400">{{ t('cargo_page.total', { n: meta.total ?? 0 }) }}</span>
        <div class="flex gap-2">
          <Button variant="secondary" :disabled="(meta.current_page ?? 1) <= 1" @click="page(-1)">
            {{ t('cargo_page.prev') }}
          </Button>
          <Button variant="secondary" :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1)" @click="page(1)">
            {{ t('cargo_page.next') }}
          </Button>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { FunnelIcon, PlusCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import FileUpload from '../../components/ui/FileUpload.vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import AppFilterFunnelMenu from '../../components/filters/AppFilterFunnelMenu.vue'
import { uploadCargoPod } from '../../api/attachments'
import { createCargoShipment, getCargoShipmentTimeline, listCargoShipments } from '../../api/cargo'
import { labelCargoStatus } from '../../util/labels'

const { t, te } = useI18n()

const loading = ref(false)
const items = ref([])
const meta = ref({})
const filters = reactive({ status: '', from: '', to: '', q: '', page: 1, per_page: 20 })
const filterMenuRef = ref(null)
const extraFiltersOpen = ref(false)

const form = ref({ pickup_address: '', delivery_address: '', sender_name: '', receiver_name: '' })
const creating = ref(false)
const msg = ref('')

const expandedId = ref(null)
const timelineCache = reactive({})
const timelineLoading = reactive({})

const CARGO_STATUS_VALUES = ['pending', 'picked_up', 'in_transit', 'delivered', 'failed', 'cancelled']

const statusOptions = computed(() => [
  { value: '', label: t('filter_bar.all') },
  ...CARGO_STATUS_VALUES.map((s) => ({ value: s, label: labelCargoStatus(s) })),
])

const dateRangeSummary = computed(() => {
  if (!filters.from && !filters.to) return t('filter_bar.all')
  return `${filters.from || '…'} → ${filters.to || '…'}`
})

const activeFilterCount = computed(() => {
  let n = 0
  if (filters.status) n++
  if (filters.from || filters.to) n++
  if (filters.q?.trim()) n++
  if (filters.per_page !== 20) n++
  return n
})

function fmt(v) {
  return v ? new Date(v).toLocaleString('vi-VN') : ''
}

function timelineTitle(ev) {
  if (ev.kind === 'milestone') {
    const key = `labels.cargo_timeline.${ev.code}`
    return te(key) ? t(key) : ev.code
  }
  const key = `labels.cargo_audit.${ev.code}`
  return te(key) ? t(key) : ev.code
}

function timelineSubtitle(ev) {
  if (ev.kind === 'milestone' && ev.detail) return ev.detail
  if (ev.kind === 'audit' && ev.code === 'cargo.status_change' && ev.after?.status) {
    const st = ev.after.status
    const key = `labels.cargo_status.${st}`
    return te(key) ? t(key) : st
  }
  return ''
}

async function toggleTimeline(id) {
  if (expandedId.value === id) {
    expandedId.value = null
    return
  }
  expandedId.value = id
  if (timelineCache[id]) return
  timelineLoading[id] = true
  try {
    const data = await getCargoShipmentTimeline(id)
    timelineCache[id] = data.items ?? []
  } catch {
    timelineCache[id] = []
  } finally {
    timelineLoading[id] = false
  }
}

function closeParentDetails(ev) {
  const el = ev?.target
  if (!el || typeof el.closest !== 'function') return
  const d = el.closest('details')
  if (d) d.open = false
}

function onFilterChange() {
  filters.page = 1
  reload()
}

function applyFilterPatch(ev, patch) {
  Object.assign(filters, patch)
  closeParentDetails(ev)
  onFilterChange()
}

function onFilterDropdownChange(ev) {
  closeParentDetails(ev)
  onFilterChange()
}

function closeFilterMenu() {
  filterMenuRef.value?.close?.()
}

function resetFilters() {
  filters.status = ''
  filters.from = ''
  filters.to = ''
  filters.q = ''
  filters.per_page = 20
  filters.page = 1
  reload()
}

async function reload() {
  loading.value = true
  try {
    const p = { ...filters }
    if (typeof p.q === 'string') p.q = p.q.trim()
    Object.keys(p).forEach((k) => (p[k] === '' ? delete p[k] : null))
    const res = await listCargoShipments(p)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
  } finally {
    loading.value = false
  }
}

function page(d) {
  filters.page = (meta.value.current_page ?? 1) + d
  reload()
}

async function create() {
  msg.value = ''
  creating.value = true
  try {
    await createCargoShipment(form.value)
    msg.value = t('cargo_page.created_ok')
    filters.page = 1
    await reload()
  } catch (e) {
    msg.value = e?.response?.data?.message ?? t('cargo_page.create_error')
  } finally {
    creating.value = false
  }
}

onMounted(reload)
</script>
