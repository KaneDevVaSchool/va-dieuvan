<template>
  <div class="space-y-4">
    <RouterLink :to="{ name: 'expenses' }" class="inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:underline dark:text-teal-400">
      <ArrowLeftIcon class="h-4 w-4" /> {{ t('expense.back_to_list') }}
    </RouterLink>

    <section v-if="form" class="space-y-5 rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
      <div>
        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('expense.settings_title') }}</h2>
        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ t('expense.settings_subtitle') }}</p>
      </div>

      <div class="grid gap-3 sm:grid-cols-2">
        <label v-for="f in companyFields" :key="f" class="text-xs text-slate-600 dark:text-slate-400">
          {{ t(`expense.set_${f}`) }}
          <input v-model="form[f]" type="text" maxlength="255" :class="inputCls" />
        </label>
      </div>

      <div>
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ t('expense.set_form_codes') }}</h3>
        <div class="mt-2 grid gap-3 sm:grid-cols-2">
          <div v-for="key in formCodeKeys" :key="key" class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ key === 'checklist' ? t('checklist.page_title') : t(`expense.type_${key === 'payment_request' ? 'payment' : key}`) }}</p>
            <div class="mt-1 grid grid-cols-3 gap-2">
              <input v-model="form.form_codes[key].code" type="text" maxlength="64" :placeholder="t('expense.set_code')" :class="inputCls" />
              <input v-model="form.form_codes[key].issued_on" type="text" maxlength="32" :placeholder="t('expense.set_issued_on')" :class="inputCls" />
              <input v-model="form.form_codes[key].revision" type="text" maxlength="16" :placeholder="t('expense.set_revision')" :class="inputCls" />
            </div>
          </div>
        </div>
      </div>

      <div>
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ t('expense.set_signatories') }}</h3>
        <p class="text-xs text-slate-500">{{ t('expense.set_signatories_hint') }}</p>
        <div class="mt-2 grid gap-3 lg:grid-cols-3">
          <div v-for="key in signatoryKeys" :key="key" class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ t(`expense.type_${key === 'payment_request' ? 'payment' : key}`) }}</p>
            <div v-for="(s, i) in form.default_signatories[key]" :key="i" class="mt-1 grid grid-cols-2 gap-2">
              <input v-model="s.role" type="text" maxlength="120" :placeholder="t('expense.set_role')" :class="inputCls" />
              <input v-model="s.name" type="text" maxlength="120" :placeholder="t('expense.set_name')" :class="inputCls" />
            </div>
          </div>
        </div>
      </div>

      <button type="button" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50" :disabled="saving" @click="save">
        {{ saving ? t('expense.saving') : t('checklist.save') }}
      </button>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'
import { getExpenseSettings, updateExpenseSettings } from '../../api/expenses'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

const inputCls = 'mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100'
const companyFields = ['company_name', 'tax_code', 'company_address', 'purchasing_place', 'city_label', 'default_department_label']
const formCodeKeys = ['proposal', 'payment_request', 'no_invoice_statement', 'checklist']
const signatoryKeys = ['proposal', 'payment_request', 'no_invoice_statement']

const { t } = useI18n()
const form = ref(null)
const saving = ref(false)

async function load() {
  try {
    const data = await getExpenseSettings()
    const codes = data.form_codes ?? {}
    const sigs = data.default_signatories ?? {}
    form.value = {
      ...data,
      form_codes: Object.fromEntries(formCodeKeys.map((k) => [k, { code: '', issued_on: '', revision: '', ...(codes[k] ?? {}) }])),
      default_signatories: Object.fromEntries(signatoryKeys.map((k) => [k, (sigs[k] ?? []).map((s) => ({ ...s }))])),
    }
  } catch (err) {
    showAppErrorFromApi(err, t('expense.load_error'))
  }
}

async function save() {
  saving.value = true
  try {
    const payload = Object.fromEntries([...companyFields, 'form_codes', 'default_signatories'].map((k) => [k, form.value[k]]))
    await updateExpenseSettings(payload)
    showAppSuccess(t('expense.settings_saved'))
  } catch (err) {
    showAppErrorFromApi(err, t('expense.save_error'))
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
