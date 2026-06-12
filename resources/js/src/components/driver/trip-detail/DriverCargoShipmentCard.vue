<template>
  <div class="overflow-hidden rounded-2xl bg-driver-card ring-1 ring-white/[0.06]">
    <div class="flex items-center justify-between gap-2 px-4 pb-2 pt-3.5">
      <h2 class="flex min-w-0 items-center gap-2 text-base font-semibold text-driver-ink sm:text-lg">
        <TruckIcon class="h-5 w-5 shrink-0 text-driver-muted/80" aria-hidden="true" />
        {{ t('driver_trip_detail.cargo_section_title') }}
      </h2>
      <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold sm:text-sm" :class="statusBadgeClass">
        {{ t(`driver_trip_detail.${statusKey}`) }}
      </span>
    </div>

    <div class="space-y-2.5 px-4 pb-3">
      <!-- Người gửi / người nhận -->
      <div class="grid grid-cols-2 gap-2">
        <div class="rounded-xl bg-driver-surface/60 px-3 py-2.5">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-driver-muted/70">
            {{ t('driver_trip_detail.cargo_sender') }}
          </p>
          <p class="mt-0.5 break-words text-sm font-medium leading-snug text-driver-ink sm:text-base">
            {{ shipment.sender_name || '—' }}
          </p>
        </div>
        <div class="rounded-xl bg-driver-surface/60 px-3 py-2.5">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-driver-muted/70">
            {{ t('driver_trip_detail.cargo_receiver') }}
          </p>
          <p class="mt-0.5 break-words text-sm font-medium leading-snug text-driver-ink sm:text-base">
            {{ shipment.receiver_name || '—' }}
          </p>
        </div>
      </div>

      <!-- Mốc thời gian -->
      <div v-if="pickedAtLabel || deliveredAtLabel" class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-driver-muted sm:text-sm">
        <span v-if="pickedAtLabel" class="inline-flex items-center gap-1">
          <CheckCircleIcon class="h-4 w-4 text-emerald-400" aria-hidden="true" />
          {{ t('driver_trip_detail.cargo_picked_at', { t: pickedAtLabel }) }}
        </span>
        <span v-if="deliveredAtLabel" class="inline-flex items-center gap-1">
          <CheckCircleIcon class="h-4 w-4 text-emerald-400" aria-hidden="true" />
          {{ t('driver_trip_detail.cargo_delivered_at', { t: deliveredAtLabel }) }}
        </span>
      </div>

      <!-- Nút nhận/giao -->
      <div v-if="canAct && (showPickup || showDeliver)" class="flex flex-wrap gap-2 pt-0.5">
        <button
          v-if="showPickup"
          type="button"
          :disabled="busy"
          class="flex min-h-[48px] flex-1 basis-[calc(50%-0.25rem)] items-center justify-center gap-2 rounded-xl bg-[#7fdcc8] px-3 text-base font-bold text-driver-bg transition active:scale-[0.97] disabled:opacity-50"
          @click="emit('set-status', 'picked_up')"
        >
          <CheckIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          {{ t('driver_trip_detail.cargo_btn_pickup') }}
        </button>
        <button
          v-if="showDeliver"
          type="button"
          :disabled="busy"
          class="flex min-h-[48px] flex-1 basis-[calc(50%-0.25rem)] items-center justify-center gap-2 rounded-xl bg-emerald-500 px-3 text-base font-bold text-white transition active:scale-[0.97] disabled:opacity-50"
          @click="emit('set-status', 'delivered')"
        >
          <CheckCircleIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          {{ t('driver_trip_detail.cargo_btn_deliver') }}
        </button>
      </div>

      <!-- Ảnh minh chứng -->
      <div class="rounded-xl bg-driver-surface/40 px-3 py-3">
        <div class="flex items-center justify-between gap-2">
          <p class="flex items-center gap-1.5 text-sm font-semibold text-driver-ink">
            <CameraIcon class="h-4 w-4 text-driver-muted/80" aria-hidden="true" />
            {{ t('driver_trip_detail.cargo_proof_title') }}
          </p>
          <label
            v-if="canAct"
            class="inline-flex min-h-[40px] cursor-pointer items-center gap-1.5 rounded-lg bg-driver-elevated px-3 text-sm font-semibold text-driver-ink transition active:scale-[0.97]"
            :class="busy ? 'pointer-events-none opacity-50' : ''"
          >
            <PlusIcon class="h-4 w-4" aria-hidden="true" />
            {{ t('driver_trip_detail.cargo_proof_add') }}
            <input type="file" accept="image/*" class="hidden" :disabled="busy" @change="onPick" />
          </label>
        </div>

        <div v-if="proofImages.length" class="mt-3 grid grid-cols-3 gap-2">
          <a
            v-for="img in proofImages"
            :key="img.id"
            :href="img.url"
            target="_blank"
            rel="noopener"
            class="block aspect-square overflow-hidden rounded-lg ring-1 ring-white/10"
          >
            <img :src="img.url" alt="" class="h-full w-full object-cover" loading="lazy" />
          </a>
        </div>
        <p v-else class="mt-2 text-xs text-driver-muted/80 sm:text-sm">
          {{ t('driver_trip_detail.cargo_proof_empty') }}
        </p>
      </div>

      <p v-if="error" class="rounded-lg bg-rose-950/40 px-3 py-2 text-sm text-rose-200" role="alert">
        {{ error }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  CameraIcon,
  CheckCircleIcon,
  CheckIcon,
  PlusIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  shipment: { type: Object, required: true },
  busy: { type: Boolean, default: false },
  error: { type: String, default: '' },
  canAct: { type: Boolean, default: false },
})

const emit = defineEmits(['set-status', 'upload-pod'])

const { t, locale } = useI18n()

const STATUS_KEYS = {
  pending: 'cargo_status_pending',
  picked_up: 'cargo_status_picked_up',
  in_transit: 'cargo_status_in_transit',
  delivered: 'cargo_status_delivered',
  failed: 'cargo_status_failed',
  cancelled: 'cargo_status_cancelled',
}

const status = computed(() => String(props.shipment?.status ?? 'pending'))

const statusKey = computed(() => STATUS_KEYS[status.value] ?? 'cargo_status_pending')

const statusBadgeClass = computed(() => {
  switch (status.value) {
    case 'delivered':
      return 'bg-emerald-500/20 text-emerald-300'
    case 'picked_up':
    case 'in_transit':
      return 'bg-sky-500/20 text-sky-200'
    case 'failed':
    case 'cancelled':
      return 'bg-rose-500/20 text-rose-200'
    default:
      return 'bg-driver-elevated text-driver-muted'
  }
})

const showPickup = computed(() => status.value === 'pending')
const showDeliver = computed(() => ['pending', 'picked_up', 'in_transit'].includes(status.value))

const proofImages = computed(() => {
  const list = Array.isArray(props.shipment?.attachments) ? props.shipment.attachments : []
  return list.filter((a) => a && a.url)
})

function fmt(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
  return d.toLocaleString(loc, { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

const pickedAtLabel = computed(() => fmt(props.shipment?.picked_up_at))
const deliveredAtLabel = computed(() => fmt(props.shipment?.delivered_at))

function onPick(e) {
  const file = e.target.files?.[0]
  e.target.value = ''
  if (file) emit('upload-pod', file)
}
</script>
