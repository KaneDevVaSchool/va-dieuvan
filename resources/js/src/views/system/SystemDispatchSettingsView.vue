<template>
  <div class="min-w-0 w-full space-y-8 pb-10 pt-1">
    <header class="space-y-4">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
          {{ t('dispatch_settings.title') }}
        </h1>
        <div
          class="mt-4 rounded-2xl border border-slate-200/90 border-l-[3px] border-l-violet-500 bg-gradient-to-r from-violet-50/80 via-white to-slate-50/30 px-4 py-4 text-sm leading-relaxed text-slate-600 shadow-sm dark:border-slate-700 dark:border-l-violet-400 dark:from-violet-950/40 dark:via-slate-900 dark:to-slate-900 dark:text-slate-300 sm:px-5 sm:py-4"
        >
          <p class="m-0">{{ t('dispatch_settings.lead') }}</p>
        </div>
      </div>
    </header>

    <div
      v-if="loadErr"
      class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 shadow-sm dark:border-amber-800/60 dark:bg-amber-950/35 dark:text-amber-100"
      role="alert"
    >
      {{ loadErr }}
    </div>

    <section
      class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-md shadow-slate-900/[0.06] ring-1 ring-slate-900/[0.04] dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/20 dark:ring-white/[0.06]"
    >
      <form class="space-y-8 p-5 sm:p-7" @submit.prevent="onSave">
        <fieldset class="space-y-6 border-0 p-0">
          <legend class="mb-1 text-base font-semibold tracking-tight text-slate-900 dark:text-slate-50">
            {{ t('dispatch_settings.section_urgency') }}
          </legend>
          <div class="grid gap-6 sm:grid-cols-2 sm:gap-8">
            <label class="block min-w-0 space-y-2">
              <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">
                {{ t('dispatch_settings.passenger_hours') }}
              </span>
              <input
                v-model.number="passengerHours"
                type="number"
                min="1"
                max="8760"
                required
                inputmode="numeric"
                class="mt-1 min-h-[2.75rem] w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-base tabular-nums text-slate-900 shadow-sm outline-none ring-va-800/15 transition placeholder:text-slate-400 focus:border-violet-400/80 focus:ring-2 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500/50"
                :aria-invalid="passengerHours != null && (passengerHours < 1 || passengerHours > 8760)"
              />
            </label>
            <label class="block min-w-0 space-y-2">
              <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">
                {{ t('dispatch_settings.cargo_hours') }}
              </span>
              <input
                v-model.number="cargoHours"
                type="number"
                min="1"
                max="8760"
                required
                inputmode="numeric"
                class="mt-1 min-h-[2.75rem] w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-base tabular-nums text-slate-900 shadow-sm outline-none ring-va-800/15 transition placeholder:text-slate-400 focus:border-violet-400/80 focus:ring-2 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500/50"
                :aria-invalid="cargoHours != null && (cargoHours < 1 || cargoHours > 8760)"
              />
            </label>
          </div>
        </fieldset>

        <fieldset class="space-y-4 border-t border-slate-100 pt-8 dark:border-slate-700/80">
          <legend class="mb-1 text-base font-semibold tracking-tight text-slate-900 dark:text-slate-50">
            {{ t('dispatch_settings.section_reference') }}
          </legend>
          <label class="block min-w-0 space-y-2">
            <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">
              {{ t('dispatch_settings.reference_pricing_url') }}
            </span>
            <input
              v-model.trim="referencePricingUrl"
              type="text"
              inputmode="url"
              autocomplete="off"
              maxlength="2048"
              class="mt-1 min-h-[2.75rem] w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-base text-slate-900 shadow-sm outline-none ring-va-800/15 transition placeholder:text-slate-400 focus:border-violet-400/80 focus:ring-2 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500/50"
              placeholder="https://..."
            />
            <span class="block text-xs leading-snug text-slate-500 dark:text-slate-400">{{
              t('dispatch_settings.reference_pricing_hint')
            }}</span>
            <a
              v-if="referencePricingPreviewHref"
              :href="referencePricingPreviewHref"
              target="_blank"
              rel="noopener noreferrer"
              class="mt-2 inline-flex w-fit items-center rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-sm font-semibold text-teal-900 shadow-sm transition hover:bg-teal-100 dark:border-teal-800 dark:bg-teal-950/45 dark:text-teal-100 dark:hover:bg-teal-950/70"
            >
              {{ t('dispatch_settings.reference_pricing_try_open') }}
            </a>
          </label>
        </fieldset>

        <div
          class="flex flex-col gap-4 border-t border-slate-100 pt-6 dark:border-slate-700/80 sm:flex-row sm:items-center sm:justify-between sm:gap-6"
        >
          <div class="min-h-[1.75rem] space-y-1">
            <p
              v-if="saveOk"
              class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200/90 dark:bg-emerald-950/45 dark:text-emerald-200 dark:ring-emerald-800/50"
              role="status"
            >
              <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-xs font-bold text-white dark:bg-emerald-600" aria-hidden="true">✓</span>
              {{ t('dispatch_settings.saved_hint') }}
            </p>
            <p v-if="saveErr" class="text-sm font-medium leading-snug text-rose-600 dark:text-rose-400">{{ saveErr }}</p>
          </div>

          <Button
            type="submit"
            variant="primary"
            class="h-11 min-w-[8.5rem] shrink-0 px-6 text-[15px] font-semibold shadow-md shadow-va-900/15 sm:self-end"
            :disabled="loading || passengerHours == null || cargoHours == null"
          >
            <span v-if="loading">{{ t('dispatch_settings.saving') }}</span>
            <span v-else>{{ t('dispatch_settings.save') }}</span>
          </Button>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
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
const referencePricingUrl = ref('')

function normalizeExternalUrl(raw) {
  const s = String(raw ?? '').trim()
  if (!s) return ''
  if (/^https?:\/\//i.test(s)) return s
  return `https://${s}`
}

const referencePricingPreviewHref = computed(() => normalizeExternalUrl(referencePricingUrl.value))

watch([passengerHours, cargoHours, referencePricingUrl], () => {
  saveOk.value = false
})

onMounted(async () => {
  loadErr.value = ''
  try {
    const s = await getAdminDispatchSettings()
    passengerHours.value = Number(s.passenger_urgent_threshold_hours) || 72
    cargoHours.value = Number(s.cargo_urgent_threshold_hours) || 24
    referencePricingUrl.value = s.reference_pricing_url != null ? String(s.reference_pricing_url) : ''
  } catch (e) {
    loadErr.value = formatApiError(e, t('dispatch_settings.load_error'))
  }
})

async function onSave() {
  saveErr.value = ''
  saveOk.value = false
  loading.value = true
  try {
    const urlRaw = referencePricingUrl.value.trim()
    await updateDispatchSettings({
      passenger_urgent_threshold_hours: Math.round(passengerHours.value),
      cargo_urgent_threshold_hours: Math.round(cargoHours.value),
      reference_pricing_url: urlRaw === '' ? null : normalizeExternalUrl(urlRaw),
    })
    saveOk.value = true
  } catch (e) {
    saveErr.value = formatApiError(e, t('dispatch_settings.save_error'))
  } finally {
    loading.value = false
  }
}
</script>
