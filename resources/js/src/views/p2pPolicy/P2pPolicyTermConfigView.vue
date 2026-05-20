<template>
  <div class="mx-auto max-w-3xl space-y-6 pb-12">
    <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.term_title') }}</h1>
    <form class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50" @submit.prevent="save">
      <label class="block text-sm">
        <span class="text-slate-600 dark:text-slate-400">{{ t('p2p_policy_page.field_academic_term') }}</span>
        <select v-model="form.academic_term_id" required class="mt-1 w-full rounded-md border px-3 py-2 dark:border-slate-600 dark:bg-slate-800">
          <option v-for="at in academicTerms" :key="at.id" :value="at.id">{{ at.academic_year }} — {{ at.name }}</option>
        </select>
      </label>
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="block text-sm">
          <span>{{ t('p2p_policy_page.field_operating_from') }}</span>
          <input v-model="form.operating_from" type="date" required class="mt-1 w-full rounded-md border px-3 py-2 dark:border-slate-600 dark:bg-slate-800" />
        </label>
        <label class="block text-sm">
          <span>{{ t('p2p_policy_page.field_operating_to') }}</span>
          <input v-model="form.operating_to" type="date" required class="mt-1 w-full rounded-md border px-3 py-2 dark:border-slate-600 dark:bg-slate-800" />
        </label>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="block text-sm">
          <span>{{ t('p2p_policy_page.field_morning') }}</span>
          <div class="mt-1 flex gap-2">
            <input v-model="form.default_morning_start" type="time" class="w-full rounded-md border px-2 py-2 dark:border-slate-600 dark:bg-slate-800" />
            <input v-model="form.default_morning_end" type="time" class="w-full rounded-md border px-2 py-2 dark:border-slate-600 dark:bg-slate-800" />
          </div>
        </label>
        <label class="block text-sm">
          <span>{{ t('p2p_policy_page.field_afternoon') }}</span>
          <div class="mt-1 flex gap-2">
            <input v-model="form.default_afternoon_start" type="time" class="w-full rounded-md border px-2 py-2 dark:border-slate-600 dark:bg-slate-800" />
            <input v-model="form.default_afternoon_end" type="time" class="w-full rounded-md border px-2 py-2 dark:border-slate-600 dark:bg-slate-800" />
          </div>
        </label>
      </div>
      <label class="flex items-center gap-2 text-sm">
        <input v-model="includeWeekend" type="checkbox" class="rounded" />
        {{ t('p2p_policy_page.include_weekend') }}
      </label>
      <button type="submit" class="rounded-lg bg-va-800 px-4 py-2 text-sm font-medium text-white" :disabled="saving">
        {{ termId ? t('p2p_policy_page.save') : t('p2p_policy_page.create_term') }}
      </button>
    </form>

    <section v-if="termId" class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
      <h2 class="font-semibold">{{ t('p2p_policy_page.holidays_title') }}</h2>
      <div class="mt-2 flex flex-wrap gap-2">
        <input v-model="holidayDate" type="date" class="rounded-md border px-2 py-1 text-sm dark:border-slate-600 dark:bg-slate-800" />
        <input v-model="holidayLabel" type="text" :placeholder="t('p2p_policy_page.holiday_label')" class="rounded-md border px-2 py-1 text-sm dark:border-slate-600 dark:bg-slate-800" />
        <button type="button" class="rounded-md border px-3 py-1 text-sm" @click="addHoliday">{{ t('p2p_policy_page.add') }}</button>
      </div>
      <ul class="mt-3 space-y-1 text-sm">
        <li v-for="(h, i) in holidays" :key="i">{{ h.holiday_date }} — {{ h.label || '—' }}</li>
      </ul>
      <button type="button" class="mt-3 text-sm text-teal-700" @click="saveCalendar">{{ t('p2p_policy_page.save_calendar') }}</button>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import {
  createP2pPolicyTerm,
  getP2pPolicyTerm,
  listAcademicTerms,
  syncP2pPolicyTermCalendar,
  updateP2pPolicyTerm,
} from '../../api/p2pPolicy'

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

async function load() {
  academicTerms.value = (await listAcademicTerms({ per_page: 50 })).items ?? []
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
    holidays.value = [...(term.holidays ?? [])]
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

async function saveCalendar() {
  if (!termId.value) return
  await syncP2pPolicyTermCalendar(termId.value, { holidays: holidays.value })
}

onMounted(load)
</script>
