<template>
  <div class="space-y-5">
    <div
      v-if="requesterDisplay"
      class="flex items-start gap-3 rounded-2xl border border-slate-200/90 bg-gradient-to-r from-va-50/90 via-white to-slate-50/80 px-4 py-3.5 shadow-sm shadow-slate-900/[0.04] ring-1 ring-slate-900/[0.03]"
    >
      <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-va-800/12 text-va-900 shadow-inner ring-1 ring-va-800/10">
        <UserCircleIcon class="h-7 w-7 text-va-800" aria-hidden="true" />
      </span>
      <div class="min-w-0 pt-0.5">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t('portal.requester') }}</p>
        <p class="mt-0.5 text-sm font-medium text-slate-900">{{ requesterDisplay }}</p>
      </div>
    </div>

    <!-- Section: Route -->
    <section class="rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-4 shadow-inner shadow-slate-900/[0.02] ring-1 ring-slate-900/[0.03] sm:px-5 sm:py-5">
      <div class="mb-4 flex items-center gap-2.5">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 shadow-sm ring-1 ring-indigo-200/60">
          <MapPinIcon class="h-5 w-5" aria-hidden="true" />
        </span>
        <h3 class="text-sm font-bold tracking-tight text-slate-800">{{ t('portal.create.section_route') }}</h3>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
          <span class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('portal.origin') }}</span>
          <input
            v-model="model.origin"
            type="text"
            autocomplete="off"
            name="portal_origin"
            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-900 shadow-sm transition-colors focus:border-va-800 focus:outline-none focus:ring-2 focus:ring-va-800/25"
          />
        </label>
        <label class="block">
          <span class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('portal.destination') }}</span>
          <input
            v-model="model.destination"
            type="text"
            autocomplete="off"
            name="portal_destination"
            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-900 shadow-sm transition-colors focus:border-va-800 focus:outline-none focus:ring-2 focus:ring-va-800/25"
          />
        </label>
      </div>
    </section>

    <!-- Section: Time -->
    <section class="rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-4 shadow-inner shadow-slate-900/[0.02] ring-1 ring-slate-900/[0.03] sm:px-5 sm:py-5">
      <div class="mb-4 flex items-center gap-2.5">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-100 text-violet-700 shadow-sm ring-1 ring-violet-200/60">
          <ClockIcon class="h-5 w-5" aria-hidden="true" />
        </span>
        <h3 class="text-sm font-bold tracking-tight text-slate-800">{{ t('portal.create.section_time') }}</h3>
      </div>
      <div class="space-y-4">
        <label class="block rounded-xl border border-va-800/15 bg-white/80 p-3 shadow-sm ring-1 ring-va-800/10 sm:p-4">
          <span class="mb-1.5 flex items-center gap-1.5 text-sm font-semibold text-va-900">
            {{ t('portal.depart_at') }}
            <span class="text-rose-600">*</span>
          </span>
          <input
            v-model="model.departAtLocal"
            type="datetime-local"
            name="portal_depart"
            :min="minLocalDatetime"
            class="w-full rounded-xl border border-va-200/80 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-900 shadow-sm transition-colors focus:border-va-800 focus:outline-none focus:ring-2 focus:ring-va-800/25"
          />
          <p v-if="departPreview" class="mt-2 text-xs font-medium text-slate-600">{{ departPreview }}</p>
        </label>
        <label class="block">
          <span class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('portal.arrive_by') }}</span>
          <input
            v-model="model.arriveByLocal"
            type="datetime-local"
            name="portal_arrive"
            :min="minLocalDatetime"
            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-900 shadow-sm transition-colors focus:border-va-800 focus:outline-none focus:ring-2 focus:ring-va-800/25"
          />
          <p v-if="arrivePreview" class="mt-2 text-xs text-slate-600">{{ arrivePreview }}</p>
        </label>
      </div>
    </section>

    <!-- Section: Details & urgency -->
    <section class="rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-4 shadow-inner shadow-slate-900/[0.02] ring-1 ring-slate-900/[0.03] sm:px-5 sm:py-5">
      <div class="mb-4 flex items-center gap-2.5">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-100 text-teal-700 shadow-sm ring-1 ring-teal-200/60">
          <DocumentTextIcon class="h-5 w-5" aria-hidden="true" />
        </span>
        <h3 class="text-sm font-bold tracking-tight text-slate-800">{{ t('portal.create.section_details') }}</h3>
      </div>
      <div class="space-y-4">
        <label v-if="showPassengerCount" class="block max-w-xs">
          <span class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('portal.passenger_count') }}</span>
          <input
            v-model.number="model.passengerCount"
            type="number"
            min="1"
            max="999"
            name="portal_passengers"
            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-900 shadow-sm transition-colors focus:border-va-800 focus:outline-none focus:ring-2 focus:ring-va-800/25"
          />
        </label>
        <label class="block">
          <span class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('portal.notes') }}</span>
          <textarea
            v-model="model.notes"
            rows="3"
            name="portal_notes"
            class="min-h-[5.5rem] w-full resize-y rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-900 shadow-sm transition-colors focus:border-va-800 focus:outline-none focus:ring-2 focus:ring-va-800/25"
          />
        </label>

        <div
          class="rounded-2xl border border-rose-100/90 bg-gradient-to-br from-rose-50/90 via-white to-white p-4 shadow-sm ring-1 ring-rose-100/80"
        >
          <label class="flex min-h-[44px] cursor-pointer items-start gap-3">
            <input v-model="model.isUrgent" type="checkbox" class="mt-1 h-4 w-4 rounded border-rose-300 text-rose-600 focus:ring-rose-500" />
            <span class="text-sm font-semibold text-slate-900">{{ t('portal.urgent') }}</span>
          </label>
          <label v-if="model.isUrgent" class="mt-4 block">
            <span class="mb-1.5 flex items-center gap-1.5 text-sm font-semibold text-rose-900">
              {{ t('portal.urgent_reason') }}
              <span class="text-rose-600">*</span>
            </span>
            <input
              v-model="model.urgentReason"
              type="text"
              maxlength="500"
              name="portal_urgent_reason"
              class="w-full rounded-xl border border-rose-200/90 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-900 shadow-sm transition-colors focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-400/30"
            />
          </label>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ClockIcon, DocumentTextIcon, MapPinIcon, UserCircleIcon } from '@heroicons/vue/24/outline'

defineProps({
  requesterDisplay: { type: String, default: '' },
  showPassengerCount: { type: Boolean, default: true },
})

const model = defineModel({
  type: Object,
  required: true,
})

const { t } = useI18n()

const minLocalDatetime = ref('')

function pad2(n) {
  return String(n).padStart(2, '0')
}

function toDatetimeLocalString(d) {
  return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}T${pad2(d.getHours())}:${pad2(d.getMinutes())}`
}

onMounted(() => {
  const d = new Date()
  d.setMinutes(d.getMinutes() - 1)
  minLocalDatetime.value = toDatetimeLocalString(d)
})

function formatDatetimePreview(v) {
  if (!v || typeof v !== 'string') return ''
  try {
    const dt = new Date(v)
    if (Number.isNaN(dt.getTime())) return ''
    return dt.toLocaleString('vi-VN', {
      weekday: 'short',
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return ''
  }
}

const departPreview = computed(() => formatDatetimePreview(model.value.departAtLocal))
const arrivePreview = computed(() => formatDatetimePreview(model.value.arriveByLocal))
</script>
