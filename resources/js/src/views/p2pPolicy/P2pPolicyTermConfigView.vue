<template>
  <div class="p2p-term-page w-full space-y-6 pb-16 text-slate-900 dark:text-slate-100">
    <header class="flex flex-col gap-4 border-b border-slate-200/80 pb-5 dark:border-slate-700/80 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0 space-y-2">
        <RouterLink
          :to="{ name: 'p2pPolicyHub' }"
          class="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 text-base font-medium text-teal-800 transition hover:bg-teal-50 dark:text-teal-300 dark:hover:bg-teal-950/40"
        >
          <ArrowLeftIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          {{ t('p2p_policy_page.back_to_hub') }}
        </RouterLink>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white md:text-3xl">
          {{ t('p2p_policy_page.term_title') }}
        </h1>
      </div>
    </header>

    <P2pPolicyWorkflowBar :current-step="'term'" :term-id="termId" />

    <div class="grid w-full gap-6 xl:grid-cols-12">
      <form
        class="xl:col-span-7 space-y-6"
        @submit.prevent="save"
      >
        <Card>
          <h2 class="mb-4 text-xl font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.section_basic') }}</h2>
          <div class="space-y-5">
            <div>
              <FieldLabel :label="t('p2p_policy_page.field_p2p_term_name')" />
              <input
                v-model="form.name"
                required
                class="p2p-term-input mt-2 w-full"
                :placeholder="t('p2p_policy_page.placeholder_p2p_term_name')"
              />
            </div>
            <div>
              <FieldLabel :label="t('p2p_policy_page.field_academic_term')" />
              <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-stretch">
                <select
                  v-model="form.academic_term_id"
                  required
                  class="p2p-term-input min-w-0 flex-1"
                >
                  <option v-for="at in academicTerms" :key="at.id" :value="at.id">
                    {{ at.academic_year }} — {{ at.name }} ({{ at.term_code }})
                  </option>
                </select>
                <button
                  type="button"
                  class="shrink-0 rounded-lg border border-teal-200 bg-teal-50 px-4 py-2.5 text-base font-medium text-teal-900 transition hover:bg-teal-100 dark:border-teal-800 dark:bg-teal-950/50 dark:text-teal-100 dark:hover:bg-teal-900/40"
                  @click="openAcademicModal"
                >
                  {{ t('p2p_policy_page.add_academic_term_btn') }}
                </button>
              </div>
            </div>

            <div>
              <div class="mt-2 grid gap-5 sm:grid-cols-2">
                <div>
                  <FieldLabel :label="t('p2p_policy_page.field_operating_from')" />
                  <input v-model="form.operating_from" type="date" required class="p2p-term-input mt-2 w-full" />
                </div>
                <div>
                  <FieldLabel :label="t('p2p_policy_page.field_operating_to')" />
                  <input v-model="form.operating_to" type="date" required class="p2p-term-input mt-2 w-full" />
                </div>
              </div>
            </div>
          </div>
        </Card>

        <Card>
          <h2 class="mb-4 text-xl font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.section_schedule') }}</h2>
          <div class="grid gap-5 lg:grid-cols-2">
            <div>
              <FieldLabel :label="t('p2p_policy_page.field_morning')" />
              <div class="mt-2 flex gap-3">
                <input v-model="form.default_morning_start" type="time" class="p2p-term-input w-full" />
                <span class="self-center text-slate-400">—</span>
                <input v-model="form.default_morning_end" type="time" class="p2p-term-input w-full" />
              </div>
            </div>
            <div>
              <FieldLabel :label="t('p2p_policy_page.field_afternoon')" />
              <div class="mt-2 flex gap-3">
                <input v-model="form.default_afternoon_start" type="time" class="p2p-term-input w-full" />
                <span class="self-center text-slate-400">—</span>
                <input v-model="form.default_afternoon_end" type="time" class="p2p-term-input w-full" />
              </div>
            </div>
          </div>

          <label class="mt-5 flex cursor-pointer items-start gap-3 text-base">
            <input v-model="includeWeekend" type="checkbox" class="mt-1 h-5 w-5 rounded border-slate-300" />
            <span class="flex-1">
              <span class="font-medium text-slate-800 dark:text-slate-200">{{ t('p2p_policy_page.include_weekend') }}</span>
            </span>
          </label>
        </Card>

        <div class="flex flex-wrap gap-3">
          <button
            type="submit"
            class="rounded-xl bg-va-800 px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-va-900 disabled:opacity-50"
            :disabled="saving"
          >
            {{ termId ? t('p2p_policy_page.save') : t('p2p_policy_page.create_term') }}
          </button>
          <RouterLink
            v-if="termId"
            :to="p2pStepTo('p2pPolicyRoutes', termId)"
            class="rounded-xl border border-teal-200 bg-teal-50 px-6 py-3 text-base font-semibold text-teal-900 transition hover:bg-teal-100 dark:border-teal-800 dark:bg-teal-950/50 dark:text-teal-100"
          >
            {{ t('p2p_policy_page.workflow_next_routes') }}
          </RouterLink>
          <RouterLink
            :to="p2pStepTo('p2pPolicyHub', termId)"
            class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-base font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            {{ t('p2p_policy_page.back_to_hub') }}
          </RouterLink>
        </div>
        <p v-if="saveSuccess" class="text-sm font-medium text-teal-700 dark:text-teal-400" role="status">
          {{ t('p2p_policy_page.save_success') }}
        </p>
        <p v-if="saveError" class="text-sm font-medium text-rose-600 dark:text-rose-400" role="alert">
          {{ saveError }}
        </p>
      </form>

      <section v-if="termId" class="xl:col-span-5 space-y-5">
        <Card>
          <h2 class="mb-1 flex items-center gap-2 text-xl font-bold text-slate-900 dark:text-white">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-100 dark:bg-rose-950/50">
              <CalendarDaysIcon class="h-5 w-5 text-rose-600 dark:text-rose-400" aria-hidden="true" />
            </span>
            {{ t('p2p_policy_page.fixed_holidays_title') }}
          </h2>
          <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">{{ t('p2p_policy_page.fixed_holidays_desc') }}</p>
          <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200/90 bg-slate-50/80 p-3 dark:border-slate-600 dark:bg-slate-800/40">
            <input
              v-model="excludeFixedHolidays"
              type="checkbox"
              class="mt-1 h-5 w-5 rounded border-slate-300 text-rose-600 focus:ring-rose-500"
            />
            <span class="text-base">{{ t('p2p_policy_page.exclude_fixed_holidays') }}</span>
          </label>
          <p v-if="!form.operating_from || !form.operating_to" class="mt-3 text-sm text-slate-500">
            {{ t('p2p_policy_page.fixed_holidays_need_dates') }}
          </p>
          <ul
            v-else-if="excludeFixedHolidays && fixedHolidayPreview.length"
            class="mt-4 max-h-[min(40vh,240px)] space-y-1.5 overflow-y-auto text-sm"
          >
            <li
              v-for="h in fixedHolidayPreview"
              :key="h.holiday_date"
              class="flex items-center gap-2 rounded-md bg-white/80 px-2.5 py-1.5 dark:bg-slate-900/50"
            >
              <span class="font-medium text-slate-800 dark:text-slate-200">{{ formatHolidayDateDisplay(h.holiday_date) }}</span>
              <span class="text-slate-500 dark:text-slate-400">— {{ h.label }}</span>
            </li>
          </ul>
          <p v-else-if="excludeFixedHolidays && fixedHolidayPreviewLoaded" class="mt-3 text-sm text-slate-500">
            {{ t('p2p_policy_page.fixed_holidays_empty_range') }}
          </p>
        </Card>

        <Card>
          <h2 class="mb-1 flex items-center gap-2 text-xl font-bold text-slate-900 dark:text-white">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-950/50">
              <StarIcon class="h-5 w-5 text-amber-600 dark:text-amber-400" aria-hidden="true" />
            </span>
            {{ t('p2p_policy_page.holidays_title') }}
          </h2>
          <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">{{ t('p2p_policy_page.holidays_desc') }}</p>
          <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <input v-model="holidayDate" type="date" class="p2p-term-input min-w-[10rem] flex-1" />
            <input
              v-model="holidayLabel"
              type="text"
              :placeholder="t('p2p_policy_page.holiday_label')"
              class="p2p-term-input min-w-[12rem] flex-[2]"
            />
            <button
              type="button"
              class="rounded-lg border border-slate-200 px-4 py-2.5 text-base font-medium hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-800"
              @click="addHoliday"
            >
              {{ t('p2p_policy_page.add') }}
            </button>
          </div>
          <ul v-if="holidays.length" class="mt-4 max-h-[min(40vh,240px)] space-y-2 overflow-y-auto text-base">
            <li
              v-for="(h, i) in holidays"
              :key="i"
              class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/60"
            >
              <span>{{ formatHolidayDateDisplay(h.holiday_date) }} · {{ h.label || '—' }}</span>
              <button type="button" class="text-sm text-rose-600 hover:underline" @click="removeHoliday(i)">
                {{ t('p2p_policy_page.remove') }}
              </button>
            </li>
          </ul>
          <p v-else class="mt-4 text-base text-slate-500">{{ t('p2p_policy_page.holidays_empty') }}</p>
        </Card>

        <Card>
          <h2 class="mb-1 flex items-center gap-2 text-xl font-bold text-slate-900 dark:text-white">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-100 dark:bg-violet-950/50">
              <NoSymbolIcon class="h-5 w-5 text-violet-600 dark:text-violet-400" aria-hidden="true" />
            </span>
            {{ t('p2p_policy_page.skip_dates_title') }}
          </h2>
          <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">{{ t('p2p_policy_page.skip_dates_desc') }}</p>
          <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <input v-model="skipDate" type="date" class="p2p-term-input min-w-[10rem] flex-1" />
            <input
              v-model="skipReason"
              type="text"
              :placeholder="t('p2p_policy_page.skip_reason_placeholder')"
              class="p2p-term-input min-w-[12rem] flex-[2]"
            />
            <button
              type="button"
              class="rounded-lg border border-slate-200 px-4 py-2.5 text-base font-medium hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-800"
              @click="addSkipDate"
            >
              {{ t('p2p_policy_page.add') }}
            </button>
          </div>
          <ul v-if="skipDates.length" class="mt-4 max-h-[min(40vh,240px)] space-y-2 overflow-y-auto text-base">
            <li
              v-for="(s, i) in skipDates"
              :key="i"
              class="flex items-center justify-between gap-2 rounded-lg bg-violet-50/80 px-3 py-2 dark:bg-violet-950/25"
            >
              <span>{{ formatHolidayDateDisplay(s.skip_date) }} · {{ s.reason || '—' }}</span>
              <button type="button" class="text-sm text-rose-600 hover:underline" @click="removeSkipDate(i)">
                {{ t('p2p_policy_page.remove') }}
              </button>
            </li>
          </ul>
          <p v-else class="mt-4 text-base text-slate-500">{{ t('p2p_policy_page.skip_dates_empty') }}</p>
        </Card>

        <button
          type="button"
          class="w-full rounded-xl bg-slate-800 px-4 py-3 text-base font-semibold text-white shadow-sm hover:bg-slate-900 disabled:opacity-50 sm:w-auto"
          :disabled="savingCalendar"
          @click="saveCalendar"
        >
          {{ t('p2p_policy_page.save_calendar') }}
        </button>
        <p v-if="calendarSuccess" class="text-sm font-medium text-teal-700 dark:text-teal-400" role="status">
          {{ t('p2p_policy_page.calendar_success') }}
        </p>
        <p v-if="calendarError" class="text-sm font-medium text-rose-600 dark:text-rose-400" role="alert">
          {{ calendarError }}
        </p>
      </section>

      <section v-else class="xl:col-span-5">
        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/80 p-6 text-base text-slate-600 dark:border-slate-600 dark:bg-slate-900/30 dark:text-slate-400">
          {{ t('p2p_policy_page.holidays_after_create') }}
        </div>
      </section>
    </div>

    <dialog
      ref="academicDialog"
      class="w-[min(100vw-2rem,32rem)] max-w-lg rounded-2xl border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-900/40 dark:border-slate-700 dark:bg-slate-900"
    >
      <form class="p-6" @submit.prevent="submitAcademicTerm">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">
          {{ t('p2p_policy_page.add_academic_term_modal_title') }}
        </h2>
        <div class="mt-5 space-y-4">
          <div>
            <FieldLabel :label="t('p2p_policy_page.at_field_year')" />
            <input v-model="academicForm.academic_year" required placeholder="2025-2026" class="p2p-term-input mt-2 w-full" />
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <FieldLabel :label="t('p2p_policy_page.at_field_code')" />
              <input v-model="academicForm.term_code" required placeholder="HK1" class="p2p-term-input mt-2 w-full" />
            </div>
            <div>
              <FieldLabel :label="t('p2p_policy_page.at_field_name')" />
              <input v-model="academicForm.name" required placeholder="Học kỳ 1" class="p2p-term-input mt-2 w-full" />
            </div>
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <FieldLabel :label="t('p2p_policy_page.at_field_starts')" />
              <input v-model="academicForm.starts_on" type="date" required class="p2p-term-input mt-2 w-full" />
            </div>
            <div>
              <FieldLabel :label="t('p2p_policy_page.at_field_ends')" />
              <input v-model="academicForm.ends_on" type="date" required class="p2p-term-input mt-2 w-full" />
            </div>
          </div>
        </div>
        <p v-if="academicError" class="mt-3 text-base text-rose-600">{{ academicError }}</p>
        <div class="mt-6 flex flex-wrap justify-end gap-2">
          <button type="button" class="rounded-lg px-4 py-2.5 text-base text-slate-600 hover:bg-slate-100 dark:text-slate-300" @click="closeAcademicModal">
            {{ t('common.cancel') }}
          </button>
          <button
            type="submit"
            class="rounded-lg bg-va-800 px-5 py-2.5 text-base font-semibold text-white disabled:opacity-50"
            :disabled="academicSaving"
          >
            {{ t('p2p_policy_page.add_academic_term_save') }}
          </button>
        </div>
      </form>
    </dialog>
  </div>
</template>

<script setup>
import { ArrowLeftIcon, CalendarDaysIcon, NoSymbolIcon, StarIcon } from '@heroicons/vue/24/outline'
import { computed, defineComponent, h, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Card from '../../components/ui/Card.vue'
import P2pPolicyWorkflowBar from '../../components/p2pPolicy/P2pPolicyWorkflowBar.vue'
import { p2pStepTo, p2pWorkflowQuery, resolveP2pTermIdFromRoute } from '../../composables/useP2pPolicyWorkflow'
import {
  createAcademicTerm,
  createP2pPolicyTerm,
  getP2pPolicyTerm,
  listAcademicTerms,
  listP2pPolicyFixedHolidays,
  syncP2pPolicyTermCalendar,
  updateP2pPolicyTerm,
} from '../../api/p2pPolicy'
import { formatIsoDate } from '../../util/datetime'

const FieldLabel = defineComponent({
  name: 'FieldLabel',
  props: {
    label: { type: String, required: true },
  },
  setup(props) {
    return () =>
      h('div', { class: 'text-base font-semibold text-slate-800 dark:text-slate-200' }, [h('span', props.label)])
  },
})

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()

const academicTerms = ref([])
const termId = computed(() => resolveP2pTermIdFromRoute(route))
const saving = ref(false)
const saveError = ref('')
const saveSuccess = ref(false)
const savingCalendar = ref(false)
const calendarError = ref('')
const calendarSuccess = ref(false)
const includeWeekend = ref(false)
const excludeFixedHolidays = ref(true)

const form = reactive({
  name: '',
  academic_term_id: '',
  operating_from: '',
  operating_to: '',
  default_morning_start: '06:00',
  default_morning_end: '07:00',
  default_afternoon_start: '15:30',
  default_afternoon_end: '16:30',
  weekdays_mask: 31,
})

const holidays = ref([])
const holidayDate = ref('')
const holidayLabel = ref('')

const skipDates = ref([])
const skipDate = ref('')
const skipReason = ref('')

const fixedHolidayPreview = ref([])
const fixedHolidayPreviewLoaded = ref(false)

const academicDialog = ref(null)
const academicSaving = ref(false)
const academicError = ref('')
const academicForm = reactive({
  academic_year: '',
  term_code: 'HK1',
  name: '',
  starts_on: '',
  ends_on: '',
  is_active: true,
})

function normalizeIsoDateOnly(d) {
  if (!d) return ''
  const s = String(d)
  return s.length >= 10 ? s.slice(0, 10) : s
}

function formatHolidayDateDisplay(d) {
  if (!d) return '—'
  const loc = locale.value === 'en' ? 'en' : 'vi'
  return formatIsoDate(d, loc)
}

async function loadAcademicTerms() {
  academicTerms.value = (await listAcademicTerms({ per_page: 100 })).items ?? []
}

async function load() {
  await loadAcademicTerms()
  if (termId.value) {
    const term = await getP2pPolicyTerm(termId.value)
    form.name = term.name ?? ''
    form.academic_term_id = term.academic_term_id
    form.operating_from = term.operating_from?.slice(0, 10) ?? ''
    form.operating_to = term.operating_to?.slice(0, 10) ?? ''
    form.default_morning_start = (term.default_morning_start ?? '06:00:00').slice(0, 5)
    form.default_morning_end = (term.default_morning_end ?? '07:00:00').slice(0, 5)
    form.default_afternoon_start = (term.default_afternoon_start ?? '15:30:00').slice(0, 5)
    form.default_afternoon_end = (term.default_afternoon_end ?? '16:30:00').slice(0, 5)
    includeWeekend.value = (term.weekdays_mask & 96) !== 0
    excludeFixedHolidays.value = term.exclude_fixed_holidays !== false
    holidays.value = (term.holidays ?? []).map((h) => ({
      holiday_date: normalizeIsoDateOnly(h.holiday_date),
      label: h.label ?? '',
    }))
    skipDates.value = (term.skip_dates ?? term.skipDates ?? []).map((s) => ({
      skip_date: normalizeIsoDateOnly(s.skip_date),
      reason: s.reason ?? '',
    }))
    await refreshFixedHolidayPreview()
  } else if (academicTerms.value[0]) {
    form.academic_term_id = academicTerms.value[0].id
  }
}

async function save() {
  saving.value = true
  saveError.value = ''
  saveSuccess.value = false
  form.weekdays_mask = includeWeekend.value ? 127 : 31

  if (form.operating_from && form.operating_to && form.operating_to < form.operating_from) {
    saveError.value = t('p2p_policy_page.date_range_error')
    saving.value = false
    return
  }
  if (
    form.default_morning_start &&
    form.default_morning_end &&
    form.default_morning_end <= form.default_morning_start
  ) {
    saveError.value = t('p2p_policy_page.time_range_morning_error')
    saving.value = false
    return
  }
  if (
    form.default_afternoon_start &&
    form.default_afternoon_end &&
    form.default_afternoon_end <= form.default_afternoon_start
  ) {
    saveError.value = t('p2p_policy_page.time_range_afternoon_error')
    saving.value = false
    return
  }

  const { academic_term_id: _academicId, ...updateFields } = form
  const payload = termId.value
    ? { ...updateFields, exclude_fixed_holidays: excludeFixedHolidays.value }
    : { ...form, exclude_fixed_holidays: excludeFixedHolidays.value }

  try {
    if (termId.value) {
      await updateP2pPolicyTerm(termId.value, payload)
    } else {
      const created = await createP2pPolicyTerm(payload)
      await router.replace({ query: p2pWorkflowQuery(created.id, { id: String(created.id) }) })
    }
    saveSuccess.value = true
    await load()
  } catch (e) {
    const status = e?.response?.status
    if (status === 409) {
      saveError.value = t('p2p_policy_page.save_error_not_draft')
    } else {
      saveError.value = e?.response?.data?.message ?? t('p2p_policy_page.save_error')
    }
  } finally {
    saving.value = false
  }
}

async function refreshFixedHolidayPreview() {
  fixedHolidayPreviewLoaded.value = false
  fixedHolidayPreview.value = []
  if (!form.operating_from || !form.operating_to) return
  try {
    const res = await listP2pPolicyFixedHolidays({
      from: form.operating_from,
      to: form.operating_to,
    })
    fixedHolidayPreview.value = res.items ?? []
  } finally {
    fixedHolidayPreviewLoaded.value = true
  }
}

watch(
  () => [form.operating_from, form.operating_to],
  () => refreshFixedHolidayPreview(),
)

function addHoliday() {
  if (!holidayDate.value) return
  holidays.value.push({ holiday_date: holidayDate.value, label: holidayLabel.value })
  holidayDate.value = ''
  holidayLabel.value = ''
}

function removeHoliday(index) {
  holidays.value.splice(index, 1)
}

function addSkipDate() {
  if (!skipDate.value) return
  skipDates.value.push({ skip_date: skipDate.value, reason: skipReason.value })
  skipDate.value = ''
  skipReason.value = ''
}

function removeSkipDate(index) {
  skipDates.value.splice(index, 1)
}

async function saveCalendar() {
  if (!termId.value) return
  savingCalendar.value = true
  calendarError.value = ''
  calendarSuccess.value = false
  try {
    await syncP2pPolicyTermCalendar(termId.value, {
      holidays: holidays.value,
      skip_dates: skipDates.value,
    })
    calendarSuccess.value = true
    await load()
  } catch (e) {
    const status = e?.response?.status
    if (status === 409) {
      calendarError.value = t('p2p_policy_page.save_error_not_draft')
    } else {
      calendarError.value = e?.response?.data?.message ?? t('p2p_policy_page.calendar_error')
    }
  } finally {
    savingCalendar.value = false
  }
}

function openAcademicModal() {
  academicError.value = ''
  academicDialog.value?.showModal()
}

function closeAcademicModal() {
  academicDialog.value?.close()
}

async function submitAcademicTerm() {
  academicSaving.value = true
  academicError.value = ''
  try {
    const created = await createAcademicTerm({ ...academicForm })
    await loadAcademicTerms()
    form.academic_term_id = created.id
    closeAcademicModal()
  } catch (e) {
    academicError.value = e?.response?.data?.message ?? t('p2p_policy_page.add_academic_term_error')
  } finally {
    academicSaving.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.p2p-term-input {
  @apply rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-base text-slate-900 shadow-sm ring-1 ring-slate-900/5 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100;
}
</style>
