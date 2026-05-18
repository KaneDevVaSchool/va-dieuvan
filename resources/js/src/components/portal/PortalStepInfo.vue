<template>
  <div class="space-y-4">
    <div v-if="requesterDisplay" class="rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-700">
      <span class="font-medium text-slate-800">{{ t('portal.requester') }}:</span>
      {{ requesterDisplay }}
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('portal.origin') }}</span>
        <input
          v-model="model.origin"
          type="text"
          autocomplete="off"
          name="portal_origin"
          class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-inner focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800"
        />
      </label>
      <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('portal.destination') }}</span>
        <input
          v-model="model.destination"
          type="text"
          autocomplete="off"
          name="portal_destination"
          class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-inner focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800"
        />
      </label>
    </div>

    <label class="block">
      <span class="mb-1 block text-xs font-medium text-slate-600">
        {{ t('portal.depart_at') }}
        <span class="text-rose-600">*</span>
      </span>
      <input
        v-model="model.departAtLocal"
        type="datetime-local"
        name="portal_depart"
        :min="minLocalDatetime"
        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-inner focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800"
      />
      <p v-if="departPreview" class="mt-1 text-xs text-slate-500">{{ departPreview }}</p>
    </label>

    <label class="block">
      <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('portal.arrive_by') }}</span>
      <input
        v-model="model.arriveByLocal"
        type="datetime-local"
        name="portal_arrive"
        :min="minLocalDatetime"
        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-inner focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800"
      />
      <p v-if="arrivePreview" class="mt-1 text-xs text-slate-500">{{ arrivePreview }}</p>
    </label>

    <label v-if="showPassengerCount" class="block">
      <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('portal.passenger_count') }}</span>
      <input
        v-model.number="model.passengerCount"
        type="number"
        min="1"
        max="999"
        name="portal_passengers"
        class="w-full max-w-[12rem] rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-inner focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800"
      />
    </label>

    <label class="block">
      <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('portal.notes') }}</span>
      <textarea
        v-model="model.notes"
        rows="3"
        name="portal_notes"
        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-inner focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800"
      />
    </label>

    <label class="flex min-h-[44px] cursor-pointer items-start gap-3 rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-3">
      <input v-model="model.isUrgent" type="checkbox" class="mt-0.5 rounded border-slate-300 text-va-800" />
      <span class="text-sm text-slate-800">{{ t('portal.urgent') }}</span>
    </label>
    <label v-if="model.isUrgent" class="block">
      <span class="mb-1 block text-xs font-medium text-slate-600">
        {{ t('portal.urgent_reason') }}
        <span class="text-rose-600">*</span>
      </span>
      <input
        v-model="model.urgentReason"
        type="text"
        maxlength="500"
        name="portal_urgent_reason"
        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-inner focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800"
      />
    </label>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

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
    const d = new Date(v)
    if (Number.isNaN(d.getTime())) return ''
    return d.toLocaleString('vi-VN', {
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
