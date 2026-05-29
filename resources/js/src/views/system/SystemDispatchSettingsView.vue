<template>
  <div class="mx-auto min-w-0 w-full max-w-5xl space-y-6 pb-10 pt-1 sm:space-y-7">
    <header class="space-y-3">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0 space-y-1">
          <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
            {{ t('dispatch_settings.title') }}
          </h1>
        </div>
        <p
          v-if="lastUpdatedLabel"
          class="shrink-0 rounded-lg bg-slate-100/90 px-2.5 py-1 text-[11px] font-medium tabular-nums text-slate-600 dark:bg-slate-800/80 dark:text-slate-400"
        >
          {{ lastUpdatedLabel }}
        </p>
      </div>
    </header>

    <div
      v-if="loadErr"
      class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-950 ring-1 ring-amber-200/90 dark:bg-amber-950/35 dark:text-amber-100 dark:ring-amber-800/50"
      role="alert"
    >
      {{ loadErr }}
    </div>

    <form class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,17rem)] lg:items-start lg:gap-7" @submit.prevent="onSave">
      <div class="space-y-5">
        <Card :title="t('dispatch_settings.section_urgency')" :hint="t('dispatch_settings.section_urgency_hint')">
          <div class="grid gap-5 sm:grid-cols-2">
            <div class="min-w-0 space-y-2">
              <Input
                :model-value="passengerHours ?? ''"
                type="number"
                :label="t('dispatch_settings.passenger_hours')"
                :hint="t('dispatch_settings.passenger_hours_hint')"
                min="1"
                max="8760"
                required
                :disabled="loading"
                @update:model-value="onPassengerHoursInput"
              />
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="h in PASSENGER_PRESETS"
                  :key="'p-' + h"
                  type="button"
                  class="rounded-full px-2.5 py-1 text-xs font-medium transition"
                  :class="
                    passengerHours === h
                      ? 'bg-teal-100 text-teal-900 ring-1 ring-teal-300/80 dark:bg-teal-950/60 dark:text-teal-100 dark:ring-teal-700'
                      : 'bg-slate-100 text-slate-600 hover:bg-slate-200/90 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                  "
                  @click="passengerHours = h"
                >
                  {{ h }}h
                </button>
              </div>
            </div>
            <div class="min-w-0 space-y-2">
              <Input
                :model-value="cargoHours ?? ''"
                type="number"
                :label="t('dispatch_settings.cargo_hours')"
                :hint="t('dispatch_settings.cargo_hours_hint')"
                min="1"
                max="8760"
                required
                :disabled="loading"
                @update:model-value="onCargoHoursInput"
              />
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="h in CARGO_PRESETS"
                  :key="'c-' + h"
                  type="button"
                  class="rounded-full px-2.5 py-1 text-xs font-medium transition"
                  :class="
                    cargoHours === h
                      ? 'bg-teal-100 text-teal-900 ring-1 ring-teal-300/80 dark:bg-teal-950/60 dark:text-teal-100 dark:ring-teal-700'
                      : 'bg-slate-100 text-slate-600 hover:bg-slate-200/90 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                  "
                  @click="cargoHours = h"
                >
                  {{ h }}h
                </button>
              </div>
            </div>
          </div>
        </Card>

        <Card :title="t('dispatch_settings.section_reference')" :hint="t('dispatch_settings.section_reference_hint')">
          <div class="space-y-3">
            <Input
              v-model="referencePricingUrl"
              type="text"
              :label="t('dispatch_settings.reference_pricing_url')"
              :hint="t('dispatch_settings.reference_pricing_hint')"
              placeholder="https://..."
              maxlength="2048"
              autocomplete="off"
              :disabled="loading"
            />
            <a
              v-if="referencePricingPreviewHref"
              :href="referencePricingPreviewHref"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex w-fit items-center gap-1.5 rounded-lg bg-teal-50 px-3 py-2 text-sm font-semibold text-teal-900 transition hover:bg-teal-100 dark:bg-teal-950/45 dark:text-teal-100 dark:hover:bg-teal-950/70"
            >
              <ArrowTopRightOnSquareIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ t('dispatch_settings.reference_pricing_try_open') }}
            </a>
          </div>
        </Card>

        <div
          class="flex flex-col gap-3 rounded-xl bg-slate-50/80 px-4 py-4 dark:bg-slate-900/50 sm:flex-row sm:items-center sm:justify-between sm:px-5"
        >
          <div class="min-h-[1.75rem] space-y-1">
            <p
              v-if="saveOk"
              class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200/90 dark:bg-emerald-950/45 dark:text-emerald-200 dark:ring-emerald-800/50"
              role="status"
            >
              <CheckCircleIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-400" aria-hidden="true" />
              {{ t('dispatch_settings.saved_hint') }}
            </p>
            <p v-if="saveErr" class="text-sm font-medium leading-snug text-rose-600 dark:text-rose-400">{{ saveErr }}</p>
          </div>
          <Button
            type="submit"
            variant="primary"
            class="h-11 min-w-[8.5rem] shrink-0 px-6 text-[15px] font-semibold shadow-md shadow-va-900/15 sm:self-end"
            :disabled="loading || !formValid"
          >
            <span v-if="loading">{{ t('dispatch_settings.saving') }}</span>
            <span v-else>{{ t('dispatch_settings.save') }}</span>
          </Button>
        </div>
      </div>

      <aside class="space-y-5 lg:sticky lg:top-4">
        <Card :title="t('dispatch_settings.section_preview')" :hint="t('dispatch_settings.section_preview_hint')">
          <div class="space-y-4">
            <label class="block">
              <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('dispatch_settings.preview_trip_type') }}
              </span>
              <select
                v-model="previewTripType"
                class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-600"
              >
                <option value="passenger">{{ t('dispatch_settings.preview_trip_type_passenger') }}</option>
                <option value="cargo">{{ t('dispatch_settings.preview_trip_type_cargo') }}</option>
              </select>
            </label>
            <Input
              v-model="previewDepartAt"
              type="datetime-local"
              :label="t('dispatch_settings.preview_depart_label')"
              :hint="t('dispatch_settings.preview_depart_hint')"
            />
            <div
              class="rounded-lg px-3 py-3 text-sm"
              :class="
                previewResult?.isUrgent
                  ? 'bg-rose-50 text-rose-950 ring-1 ring-rose-200/90 dark:bg-rose-950/40 dark:text-rose-100 dark:ring-rose-900/50'
                  : 'bg-emerald-50 text-emerald-950 ring-1 ring-emerald-200/90 dark:bg-emerald-950/35 dark:text-emerald-100 dark:ring-emerald-900/40'
              "
            >
              <p class="font-semibold">
                {{
                  previewResult?.isUrgent
                    ? t('dispatch_settings.preview_result_urgent')
                    : t('dispatch_settings.preview_result_not_urgent')
                }}
              </p>
              <p v-if="previewResult?.detail" class="mt-1 text-xs leading-snug opacity-90">
                {{ previewResult.detail }}
              </p>
              <p v-else-if="!previewDepartAt" class="mt-1 text-xs leading-snug opacity-75">
                {{ t('dispatch_settings.preview_pick_datetime') }}
              </p>
            </div>
            <p class="text-[11px] leading-snug text-slate-500 dark:text-slate-400">
              {{ t('dispatch_settings.preview_rule_note') }}
            </p>
          </div>
        </Card>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/90 dark:bg-slate-900/80 dark:ring-slate-800">
          <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
            {{ t('dispatch_settings.impact_title') }}
          </h2>
          <ul class="mt-3 space-y-2.5 text-xs leading-snug text-slate-600 dark:text-slate-400">
            <li class="flex gap-2">
              <ClipboardDocumentListIcon class="mt-0.5 h-4 w-4 shrink-0 text-teal-600 dark:text-teal-400" aria-hidden="true" />
              <span>{{ t('dispatch_settings.impact_item_form') }}</span>
            </li>
            <li class="flex gap-2">
              <QueueListIcon class="mt-0.5 h-4 w-4 shrink-0 text-teal-600 dark:text-teal-400" aria-hidden="true" />
              <span>{{ t('dispatch_settings.impact_item_list') }}</span>
            </li>
            <li class="flex gap-2">
              <CurrencyDollarIcon class="mt-0.5 h-4 w-4 shrink-0 text-teal-600 dark:text-teal-400" aria-hidden="true" />
              <span>{{ t('dispatch_settings.impact_item_pricing') }}</span>
            </li>
          </ul>
        </div>
      </aside>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  CheckCircleIcon,
  ArrowTopRightOnSquareIcon,
  ClipboardDocumentListIcon,
  QueueListIcon,
  CurrencyDollarIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import Card from '../../components/ui/Card.vue'
import Input from '../../components/ui/Input.vue'
import { formatApiError } from '../../api/http'
import { getAdminDispatchSettings, updateDispatchSettings } from '../../api/dispatchSettings'

const { t, locale } = useI18n()

const PASSENGER_PRESETS = [48, 72, 96]
const CARGO_PRESETS = [12, 24, 48]

const loading = ref(false)
const loadErr = ref('')
const saveErr = ref('')
const saveOk = ref(false)
const lastUpdatedAt = ref(null)

const passengerHours = ref(null)
const cargoHours = ref(null)
const referencePricingUrl = ref('')

const previewTripType = ref('passenger')
const previewDepartAt = ref('')

function onPassengerHoursInput(v) {
  passengerHours.value = v === '' || v == null ? null : Number(v)
}

function onCargoHoursInput(v) {
  cargoHours.value = v === '' || v == null ? null : Number(v)
}

function normalizeExternalUrl(raw) {
  const s = String(raw ?? '').trim()
  if (!s) return ''
  if (/^https?:\/\//i.test(s)) return s
  return `https://${s}`
}

const referencePricingPreviewHref = computed(() => normalizeExternalUrl(referencePricingUrl.value))

const formValid = computed(() => {
  const p = passengerHours.value
  const c = cargoHours.value
  return (
    p != null &&
    c != null &&
    p >= 1 &&
    p <= 8760 &&
    c >= 1 &&
    c <= 8760
  )
})

const lastUpdatedLabel = computed(() => {
  if (!lastUpdatedAt.value) return ''
  try {
    const d = new Date(lastUpdatedAt.value)
    if (Number.isNaN(d.getTime())) return ''
    const fmt = new Intl.DateTimeFormat(locale.value === 'en' ? 'en-GB' : 'vi-VN', {
      dateStyle: 'medium',
      timeStyle: 'short',
    })
    return t('dispatch_settings.last_updated', { when: fmt.format(d) })
  } catch {
    return ''
  }
})

function thresholdForType(tripType) {
  const raw = tripType === 'cargo' ? cargoHours.value : passengerHours.value
  const n = Number(raw)
  return Number.isFinite(n) && n >= 1 ? n : tripType === 'cargo' ? 24 : 72
}

const previewResult = computed(() => {
  const raw = previewDepartAt.value?.trim()
  if (!raw) return null
  const departMs = new Date(raw).getTime()
  if (!Number.isFinite(departMs)) return { isUrgent: false, detail: '' }
  const threshold = thresholdForType(previewTripType.value)
  const hoursUntil = (departMs - Date.now()) / (3600 * 1000)
  if (hoursUntil < 0) {
    return {
      isUrgent: false,
      detail: t('dispatch_settings.preview_past_depart'),
    }
  }
  const isUrgent = hoursUntil <= threshold
  const hoursRounded = Math.round(hoursUntil * 10) / 10
  return {
    isUrgent,
    detail: isUrgent
      ? t('dispatch_settings.preview_detail_urgent', { hours: hoursRounded, threshold })
      : t('dispatch_settings.preview_detail_ok', { hours: hoursRounded, threshold }),
  }
})

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
    if (s.updated_at) lastUpdatedAt.value = s.updated_at
    const now = new Date()
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
    previewDepartAt.value = now.toISOString().slice(0, 16)
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
    const saved = await updateDispatchSettings({
      passenger_urgent_threshold_hours: Math.round(passengerHours.value),
      cargo_urgent_threshold_hours: Math.round(cargoHours.value),
      reference_pricing_url: urlRaw === '' ? null : normalizeExternalUrl(urlRaw),
    })
    if (saved?.updated_at) lastUpdatedAt.value = saved.updated_at
    saveOk.value = true
  } catch (e) {
    saveErr.value = formatApiError(e, t('dispatch_settings.save_error'))
  } finally {
    loading.value = false
  }
}
</script>
