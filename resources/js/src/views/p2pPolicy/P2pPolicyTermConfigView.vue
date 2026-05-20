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
        <p class="max-w-3xl text-base leading-relaxed text-slate-600 dark:text-slate-400">
          {{ t('p2p_policy_page.term_subtitle') }}
        </p>
      </div>
    </header>

    <div class="grid w-full gap-6 xl:grid-cols-12">
      <form
        class="xl:col-span-7 space-y-6"
        @submit.prevent="save"
      >
        <Card :hint="t('p2p_policy_page.tip_section_basic')">
          <h2 class="mb-4 text-xl font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.section_basic') }}</h2>
          <div class="space-y-5">
            <div>
              <FieldLabel :label="t('p2p_policy_page.field_academic_term')" :hint="t('p2p_policy_page.tip_academic_term')" />
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

            <div class="grid gap-5 sm:grid-cols-2">
              <div>
                <FieldLabel :label="t('p2p_policy_page.field_operating_from')" :hint="t('p2p_policy_page.tip_operating_dates')" />
                <input v-model="form.operating_from" type="date" required class="p2p-term-input mt-2 w-full" />
              </div>
              <div>
                <FieldLabel :label="t('p2p_policy_page.field_operating_to')" :hint="t('p2p_policy_page.tip_operating_dates')" />
                <input v-model="form.operating_to" type="date" required class="p2p-term-input mt-2 w-full" />
              </div>
            </div>
          </div>
        </Card>

        <Card :hint="t('p2p_policy_page.tip_section_schedule')">
          <h2 class="mb-4 text-xl font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.section_schedule') }}</h2>
          <div class="grid gap-5 lg:grid-cols-2">
            <div>
              <FieldLabel :label="t('p2p_policy_page.field_morning')" :hint="t('p2p_policy_page.tip_morning')" />
              <div class="mt-2 flex gap-3">
                <input v-model="form.default_morning_start" type="time" class="p2p-term-input w-full" />
                <span class="self-center text-slate-400">—</span>
                <input v-model="form.default_morning_end" type="time" class="p2p-term-input w-full" />
              </div>
            </div>
            <div>
              <FieldLabel :label="t('p2p_policy_page.field_afternoon')" :hint="t('p2p_policy_page.tip_afternoon')" />
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
              {{ t('p2p_policy_page.include_weekend') }}
              <FieldHintInline class="ml-1 align-middle" :hint="t('p2p_policy_page.tip_weekend')" />
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
            :to="{ name: 'p2pPolicyHub' }"
            class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-base font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            {{ t('p2p_policy_page.back_to_hub') }}
          </RouterLink>
        </div>
      </form>

      <section v-if="termId" class="xl:col-span-5">
        <Card :hint="t('p2p_policy_page.tip_holidays')">
          <h2 class="mb-4 text-xl font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.holidays_title') }}</h2>
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
          <ul v-if="holidays.length" class="mt-4 max-h-[min(50vh,320px)] space-y-2 overflow-y-auto text-base">
            <li
              v-for="(h, i) in holidays"
              :key="i"
              class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/60"
            >
              <span>{{ formatHolidayDate(h.holiday_date) }} — {{ h.label || '—' }}</span>
              <button type="button" class="text-sm text-rose-600 hover:underline" @click="removeHoliday(i)">
                {{ t('p2p_policy_page.remove') }}
              </button>
            </li>
          </ul>
          <p v-else class="mt-4 text-base text-slate-500">{{ t('p2p_policy_page.holidays_empty') }}</p>
          <button
            type="button"
            class="mt-4 w-full rounded-lg bg-slate-800 px-4 py-2.5 text-base font-medium text-white hover:bg-slate-900 sm:w-auto"
            @click="saveCalendar"
          >
            {{ t('p2p_policy_page.save_calendar') }}
          </button>
        </Card>
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
        <p class="mt-2 text-base text-slate-600 dark:text-slate-400">
          {{ t('p2p_policy_page.add_academic_term_modal_hint') }}
        </p>
        <div class="mt-5 space-y-4">
          <div>
            <FieldLabel :label="t('p2p_policy_page.at_field_year')" :hint="t('p2p_policy_page.at_tip_year')" />
            <input v-model="academicForm.academic_year" required placeholder="2025-2026" class="p2p-term-input mt-2 w-full" />
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <FieldLabel :label="t('p2p_policy_page.at_field_code')" :hint="t('p2p_policy_page.at_tip_code')" />
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
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'
import { computed, defineComponent, h, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { InformationCircleIcon } from '@heroicons/vue/24/outline'
import Card from '../../components/ui/Card.vue'
import {
  createAcademicTerm,
  createP2pPolicyTerm,
  getP2pPolicyTerm,
  listAcademicTerms,
  syncP2pPolicyTermCalendar,
  updateP2pPolicyTerm,
} from '../../api/p2pPolicy'

const FieldHintInline = defineComponent({
  name: 'FieldHintInline',
  props: { hint: { type: String, required: true } },
  setup(props) {
    return () =>
      h(
        'span',
        {
          class: 'inline-flex cursor-help align-middle text-slate-400 hover:text-teal-600 dark:hover:text-teal-400',
          title: props.hint,
          tabindex: 0,
          role: 'img',
          'aria-label': props.hint,
        },
        [h(InformationCircleIcon, { class: 'h-5 w-5', 'aria-hidden': 'true' })],
      )
  },
})

const FieldLabel = defineComponent({
  name: 'FieldLabel',
  props: {
    label: { type: String, required: true },
    hint: { type: String, default: '' },
  },
  setup(props) {
    return () =>
      h('div', { class: 'flex items-center gap-2 text-base font-semibold text-slate-800 dark:text-slate-200' }, [
        h('span', props.label),
        props.hint ? h(FieldHintInline, { hint: props.hint }) : null,
      ])
  },
})

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const academicTerms = ref([])
const termId = computed(() => (route.query.id ? Number(route.query.id) : null))
const saving = ref(false)
const includeWeekend = ref(false)

const form = reactive({
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

function formatHolidayDate(d) {
  if (!d) return '—'
  const s = String(d)
  return s.length >= 10 ? s.slice(0, 10) : s
}

async function loadAcademicTerms() {
  academicTerms.value = (await listAcademicTerms({ per_page: 100 })).items ?? []
}

async function load() {
  await loadAcademicTerms()
  if (termId.value) {
    const term = await getP2pPolicyTerm(termId.value)
    form.academic_term_id = term.academic_term_id
    form.operating_from = term.operating_from?.slice(0, 10) ?? ''
    form.operating_to = term.operating_to?.slice(0, 10) ?? ''
    form.default_morning_start = (term.default_morning_start ?? '06:00:00').slice(0, 5)
    form.default_morning_end = (term.default_morning_end ?? '07:00:00').slice(0, 5)
    form.default_afternoon_start = (term.default_afternoon_start ?? '15:30:00').slice(0, 5)
    form.default_afternoon_end = (term.default_afternoon_end ?? '16:30:00').slice(0, 5)
    includeWeekend.value = (term.weekdays_mask & 96) !== 0
    holidays.value = (term.holidays ?? []).map((h) => ({
      holiday_date: formatHolidayDate(h.holiday_date),
      label: h.label ?? '',
    }))
  } else if (academicTerms.value[0]) {
    form.academic_term_id = academicTerms.value[0].id
  }
}

async function save() {
  saving.value = true
  form.weekdays_mask = includeWeekend.value ? 127 : 31
  try {
    if (termId.value) {
      await updateP2pPolicyTerm(termId.value, { ...form })
    } else {
      const created = await createP2pPolicyTerm({ ...form })
      await router.replace({ query: { id: created.id } })
    }
    await load()
  } finally {
    saving.value = false
  }
}

function addHoliday() {
  if (!holidayDate.value) return
  holidays.value.push({ holiday_date: holidayDate.value, label: holidayLabel.value })
  holidayDate.value = ''
  holidayLabel.value = ''
}

function removeHoliday(index) {
  holidays.value.splice(index, 1)
}

async function saveCalendar() {
  if (!termId.value) return
  await syncP2pPolicyTermCalendar(termId.value, { holidays: holidays.value })
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
