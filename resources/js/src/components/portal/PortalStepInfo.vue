<template>
  <div class="space-y-5">
    <div v-if="requesterDisplay" class="rounded-xl border border-slate-200/80 bg-slate-50/70 px-3 py-3 sm:px-4 sm:py-3.5">
      <p class="text-xs font-medium text-slate-500">{{ t('portal.requester') }}</p>
      <p class="mt-1 text-sm font-medium text-slate-900">{{ requesterDisplay }}</p>
    </div>

    <section class="dw-fieldset">
      <h3 class="dw-section-title">{{ t('portal.create.section_route') }}</h3>
      <div class="grid gap-3 sm:grid-cols-2">
        <label class="block min-w-0">
          <span class="dw-label-text">{{ t('portal.origin') }}</span>
          <input
            v-model="model.origin"
            type="text"
            autocomplete="off"
            name="portal_origin"
            class="dw-input"
          />
        </label>
        <label class="block min-w-0">
          <span class="dw-label-text">{{ t('portal.destination') }}</span>
          <input
            v-model="model.destination"
            type="text"
            autocomplete="off"
            name="portal_destination"
            class="dw-input"
          />
        </label>
      </div>
    </section>

    <section class="dw-fieldset">
      <h3 class="dw-section-title">{{ t('portal.create.section_time') }}</h3>
      <div class="grid gap-3 sm:grid-cols-2">
        <label class="block min-w-0">
          <span class="dw-label-text">{{ t('portal.depart_at') }} <span class="dw-req" aria-hidden="true">*</span></span>
          <input
            v-model="model.departAtLocal"
            type="datetime-local"
            name="portal_depart"
            :min="minLocalDatetime"
            class="dw-input dw-date-input"
          />
          <p v-if="departPreview" class="dw-hint mt-1">{{ departPreview }}</p>
        </label>
        <label class="block min-w-0">
          <span class="dw-label-text">{{ t('portal.arrive_by') }}</span>
          <input
            v-model="model.arriveByLocal"
            type="datetime-local"
            name="portal_arrive"
            :min="minLocalDatetime"
            class="dw-input dw-date-input"
          />
          <p v-if="arrivePreview" class="dw-hint mt-1">{{ arrivePreview }}</p>
        </label>
      </div>
    </section>

    <section class="dw-fieldset">
      <h3 class="dw-section-title">{{ t('portal.create.section_details') }}</h3>
      <div class="space-y-4">
        <label v-if="showPassengerCount" class="block max-w-xs min-w-0">
          <span class="dw-label-text">{{ t('portal.passenger_count') }}</span>
          <input
            v-model.number="model.passengerCount"
            type="number"
            min="1"
            max="999"
            name="portal_passengers"
            class="dw-input"
          />
        </label>
        <label class="block min-w-0">
          <span class="dw-label-text">{{ t('portal.notes') }}</span>
          <textarea
            v-model="model.notes"
            rows="3"
            name="portal_notes"
            class="dw-input min-h-[5.5rem]"
          />
        </label>

        <div class="dw-e-panel dw-e-panel--e11 overflow-hidden">
          <div class="dw-e11-flag">
            <label class="dw-e11-flag__row">
              <input v-model="model.isUrgent" type="checkbox" class="dw-e11-flag__check" />
              <span class="dw-e11-flag__label">{{ t('portal.urgent') }}</span>
            </label>
          </div>
          <div v-if="model.isUrgent" class="border-t border-slate-100 px-4 py-4 sm:px-5">
            <label class="block min-w-0">
              <span class="dw-label-text">{{ t('portal.urgent_reason') }} <span class="dw-req" aria-hidden="true">*</span></span>
              <input
                v-model="model.urgentReason"
                type="text"
                maxlength="500"
                name="portal_urgent_reason"
                class="dw-input"
              />
            </label>
          </div>
        </div>
      </div>
    </section>
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
