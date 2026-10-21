<template>
  <div class="min-h-full w-full bg-driver-bg text-driver-ink">
    <header
      class="sticky top-0 z-40 border-b border-white/[0.06] bg-driver-bg/90 backdrop-blur-md"
      :style="{ paddingTop: 'max(0.5rem, env(safe-area-inset-top))' }"
    >
      <div class="mx-auto flex max-w-lg items-center gap-2 px-3 pb-3 pt-2 sm:max-w-2xl">
        <button
          type="button"
          class="flex min-h-[48px] min-w-[48px] shrink-0 items-center justify-center rounded-2xl bg-driver-card ring-1 ring-white/[0.08] active:scale-[0.97]"
          :aria-label="t('checklist.back')"
          @click="goBack"
        >
          <ArrowLeftIcon class="h-6 w-6 text-driver-accent" />
        </button>
        <h1 class="min-w-0 flex-1 truncate text-center text-lg font-bold sm:text-xl">{{ t('checklist.page_title') }}</h1>
        <span class="w-[48px] shrink-0" aria-hidden="true" />
      </div>
    </header>

    <div class="mx-auto max-w-lg space-y-4 px-4 pt-4 sm:max-w-2xl">
      <p class="text-sm leading-snug text-driver-muted">{{ t('checklist.page_intro') }}</p>

      <DriverChecklistTodayCard ref="todayCardRef" />

      <!-- Bắt đầu phiếu mới -->
      <section class="space-y-4 rounded-[1.25rem] bg-driver-card p-4 ring-1 ring-white/[0.06]">
        <h2 class="text-base font-bold">{{ t('checklist.start_title') }}</h2>
        <p v-if="optionsError" class="text-sm text-amber-200">{{ optionsError }}</p>

        <div>
          <p class="text-sm font-semibold text-driver-muted">{{ t('checklist.start_template') }}</p>
          <div class="mt-1.5 grid grid-cols-2 gap-2">
            <button
              v-for="tpl in options.templates"
              :key="tpl.id"
              type="button"
              class="min-h-[52px] rounded-xl px-3 py-2 text-left text-sm font-bold ring-1 transition"
              :class="String(form.template_id) === String(tpl.id) ? 'bg-driver-accent text-driver-bg ring-driver-accent' : 'bg-driver-surface text-driver-ink ring-white/10'"
              :data-testid="`checklist-template-${tpl.code}`"
              @click="form.template_id = tpl.id"
            >
              {{ t(`checklist.freq_${tpl.frequency}`) }}
              <span class="block truncate text-xs font-medium opacity-80">{{ tpl.name }}</span>
            </button>
          </div>
        </div>

        <div>
          <label class="text-sm font-semibold text-driver-muted" for="cl-vehicle">{{ t('checklist.start_vehicle') }}</label>
          <div class="relative mt-1.5">
            <select
              id="cl-vehicle"
              v-model="form.vehicle_id"
              data-testid="checklist-start-vehicle"
              class="flex min-h-[48px] w-full appearance-none rounded-xl border border-white/10 bg-driver-surface px-3 py-2.5 pr-10 text-base font-medium text-driver-ink focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
            >
              <option disabled value="">{{ loadingOptions ? t('checklist.loading') : t('checklist.pick_vehicle') }}</option>
              <option v-for="v in options.vehicles" :key="v.id" :value="String(v.id)">
                {{ v.license_plate }}{{ v.type ? ` · ${v.type}` : '' }}{{ v.mine ? ` ★` : '' }}
              </option>
            </select>
            <ChevronDownIcon class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-driver-muted/70" />
          </div>
          <p class="mt-1 text-xs text-driver-muted/80">{{ t('checklist.vehicle_hint') }}</p>
        </div>

        <div v-if="linkOptions.length">
          <label class="text-sm font-semibold text-driver-muted" for="cl-link">{{ t('checklist.start_link') }}</label>
          <div class="relative mt-1.5">
            <select
              id="cl-link"
              v-model="form.link"
              class="flex min-h-[48px] w-full appearance-none rounded-xl border border-white/10 bg-driver-surface px-3 py-2.5 pr-10 text-base text-driver-ink focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
              @change="onLinkChange"
            >
              <option value="">{{ t('checklist.link_none') }}</option>
              <option v-for="o in linkOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
            <ChevronDownIcon class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-driver-muted/70" />
          </div>
        </div>

        <div>
          <label class="text-sm font-semibold text-driver-muted" for="cl-location">{{ t('checklist.start_location') }}</label>
          <input
            id="cl-location"
            v-model="form.location_text"
            type="text"
            maxlength="255"
            class="mt-1.5 flex min-h-[48px] w-full rounded-xl border border-white/10 bg-driver-surface px-3 py-2.5 text-base text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
            :placeholder="t('checklist.location_ph')"
          />
        </div>

        <p v-if="startError" class="text-sm text-rose-300">{{ startError }}</p>

        <button
          type="button"
          class="flex min-h-[52px] w-full items-center justify-center rounded-xl bg-driver-accent py-3 text-base font-bold text-driver-bg transition hover:brightness-110 disabled:opacity-45 active:scale-[0.99]"
          :disabled="starting || !form.template_id || !form.vehicle_id"
          data-testid="checklist-start-submit"
          @click="start"
        >
          {{ starting ? t('checklist.starting') : t('checklist.start_button') }}
        </button>
      </section>

      <!-- Lịch sử -->
      <section class="rounded-[1.25rem] bg-driver-card p-4 ring-1 ring-white/[0.06]">
        <h2 class="text-base font-bold">{{ t('checklist.history_title') }}</h2>
        <p v-if="historyLoading && !history.length" class="mt-3 text-sm text-driver-muted">{{ t('checklist.loading') }}</p>
        <p v-else-if="!history.length" class="mt-3 text-sm text-driver-muted">{{ t('checklist.history_empty') }}</p>
        <ul class="mt-3 space-y-2">
          <li v-for="s in history" :key="s.id">
            <RouterLink
              :to="{ name: 'driverChecklistForm', params: { id: s.id } }"
              class="flex min-h-[56px] items-center gap-3 rounded-xl bg-driver-surface px-3 py-2.5 ring-1 ring-white/[0.06]"
            >
              <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold">{{ s.code }} · {{ s.vehicle?.license_plate || '—' }}</span>
                <span class="block truncate text-xs text-driver-muted">
                  {{ t(`checklist.freq_${s.period_type}`) }} · {{ formatDateTime(s.submitted_at || s.started_at) }}
                </span>
              </span>
              <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold" :class="rowPill(s)">{{ rowLabel(s) }}</span>
            </RouterLink>
          </li>
        </ul>
        <button
          v-if="historyMeta.current_page < historyMeta.last_page"
          type="button"
          class="mt-3 min-h-[44px] w-full rounded-xl bg-driver-surface text-sm font-semibold text-driver-accent ring-1 ring-white/10"
          :disabled="historyLoading"
          @click="loadHistory(historyMeta.current_page + 1)"
        >
          {{ t('checklist.load_more') }}
        </button>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeftIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'
import { getChecklistOptions, listMyChecklists, startChecklist } from '../../api/checklists'
import DriverChecklistTodayCard from '../../components/driver/checklist/DriverChecklistTodayCard.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const todayCardRef = ref(null)
const options = ref({ templates: [], vehicles: [], trips: [], tp_days: [] })
const loadingOptions = ref(false)
const optionsError = ref('')
const form = ref({ template_id: '', vehicle_id: '', link: '', location_text: '' })
const starting = ref(false)
const startError = ref('')

const history = ref([])
const historyMeta = ref({ current_page: 1, last_page: 1 })
const historyLoading = ref(false)

const linkOptions = computed(() => [
  ...(options.value.trips ?? []).map((tr) => ({ value: `trip:${tr.id}`, label: `${t('checklist.link_trip')} ${tr.label}`, vehicle_id: tr.vehicle_id })),
  ...(options.value.tp_days ?? []).map((d) => ({ value: `tp:${d.id}`, label: `${t('checklist.link_tp')} ${d.label}`, vehicle_id: d.vehicle_id })),
])

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push({ name: 'driverHome' })
}

function formatDateTime(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

function rowLabel(s) {
  if (s.status === 'draft') return t('checklist.state_draft')
  return s.result === 'fail' ? t('checklist.state_fail') : t('checklist.state_pass')
}

function rowPill(s) {
  if (s.status === 'draft') return 'bg-amber-500/20 text-amber-200'
  return s.result === 'fail' ? 'bg-rose-500/20 text-rose-200' : 'bg-emerald-500/20 text-emerald-200'
}

function onLinkChange() {
  const opt = linkOptions.value.find((o) => o.value === form.value.link)
  if (opt?.vehicle_id) form.value.vehicle_id = String(opt.vehicle_id)
}

async function loadOptions() {
  loadingOptions.value = true
  optionsError.value = ''
  try {
    options.value = await getChecklistOptions()
    const fromQuery = String(route.query.start || route.query.template || '')
    const templates = options.value.templates ?? []
    form.value.template_id = templates.find((x) => String(x.id) === fromQuery)?.id ?? templates[0]?.id ?? ''
    const mine = (options.value.vehicles ?? []).filter((v) => v.mine)
    if (mine.length === 1) form.value.vehicle_id = String(mine[0].id)
  } catch (err) {
    optionsError.value = err?.response?.data?.message || t('checklist.options_error')
  } finally {
    loadingOptions.value = false
  }
}

async function loadHistory(page = 1) {
  historyLoading.value = true
  try {
    const res = await listMyChecklists({ page, per_page: 15 })
    history.value = page === 1 ? res.items : [...history.value, ...res.items]
    historyMeta.value = res.meta
  } catch {
    if (page === 1) history.value = []
  } finally {
    historyLoading.value = false
  }
}

async function start() {
  if (starting.value) return
  starting.value = true
  startError.value = ''
  try {
    const [kind, id] = String(form.value.link || '').split(':')
    const created = await startChecklist({
      template_id: Number(form.value.template_id),
      vehicle_id: Number(form.value.vehicle_id),
      trip_id: kind === 'trip' ? Number(id) : null,
      tp_program_day_id: kind === 'tp' ? Number(id) : null,
      location_text: form.value.location_text?.trim() || null,
    })
    await router.push({ name: 'driverChecklistForm', params: { id: created.id } })
  } catch (err) {
    startError.value = err?.response?.data?.message || t('checklist.start_error')
  } finally {
    starting.value = false
  }
}

onMounted(() => {
  void loadOptions()
  void loadHistory(1)
})
</script>
