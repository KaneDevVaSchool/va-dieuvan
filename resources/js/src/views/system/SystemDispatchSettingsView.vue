<template>
  <div class="mx-auto max-w-2xl space-y-6 pb-8">
    <div>
      <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
        {{ t('dispatch_settings.title') }}
      </h1>
      <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
        {{ t('dispatch_settings.lead') }}
      </p>
    </div>

    <div
      v-if="loadErr"
      class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800/70 dark:bg-amber-950/40 dark:text-amber-100"
      role="alert"
    >
      {{ loadErr }}
    </div>

    <div
      class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-6"
    >
      <form class="space-y-5" @submit.prevent="onSave">
        <label class="block">
          <span class="text-sm font-medium text-slate-800 dark:text-slate-200">
            {{ t('dispatch_settings.passenger_hours') }}
          </span>
          <input
            v-model.number="passengerHours"
            type="number"
            min="1"
            max="8760"
            required
            class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm outline-none ring-va-800/20 focus:border-va-800/35 focus:ring-2 dark:border-slate-600 dark:bg-slate-950"
          />
        </label>
        <label class="block">
          <span class="text-sm font-medium text-slate-800 dark:text-slate-200">
            {{ t('dispatch_settings.cargo_hours') }}
          </span>
          <input
            v-model.number="cargoHours"
            type="number"
            min="1"
            max="8760"
            required
            class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm outline-none ring-va-800/20 focus:border-va-800/35 focus:ring-2 dark:border-slate-600 dark:bg-slate-950"
          />
        </label>

        <p v-if="saveOk" class="text-sm font-medium text-emerald-700 dark:text-emerald-400">
          {{ t('dispatch_settings.saved_hint') }}
        </p>
        <p v-if="saveErr" class="text-sm font-medium text-rose-600 dark:text-rose-400">{{ saveErr }}</p>

        <Button type="submit" class="w-full sm:w-auto" :disabled="loading || passengerHours == null || cargoHours == null">
          <span v-if="loading">{{ t('dispatch_settings.saving') }}</span>
          <span v-else>{{ t('dispatch_settings.save') }}</span>
        </Button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import Button from '../../components/ui/Button.vue'
import { formatApiError } from '../../api/http'
import { getAdminDispatchSettings, updateDispatchSettings } from '../../api/dispatchSettings'

const { t } = useI18n()

const loading = ref(false)
const loadErr = ref('')
const saveErr = ref('')
const saveOk = ref(false)

const passengerHours = ref(null)
const cargoHours = ref(null)

onMounted(async () => {
  loadErr.value = ''
  try {
    const s = await getAdminDispatchSettings()
    passengerHours.value = Number(s.passenger_urgent_threshold_hours) || 72
    cargoHours.value = Number(s.cargo_urgent_threshold_hours) || 24
  } catch (e) {
    loadErr.value = formatApiError(e, t('dispatch_settings.load_error'))
  }
})

async function onSave() {
  saveErr.value = ''
  saveOk.value = false
  loading.value = true
  try {
    await updateDispatchSettings({
      passenger_urgent_threshold_hours: Math.round(passengerHours.value),
      cargo_urgent_threshold_hours: Math.round(cargoHours.value),
    })
    saveOk.value = true
  } catch (e) {
    saveErr.value = formatApiError(e, t('dispatch_settings.save_error'))
  } finally {
    loading.value = false
  }
}
</script>
