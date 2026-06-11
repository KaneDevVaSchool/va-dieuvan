<template>
  <div class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <span class="text-sm font-semibold text-slate-500">
        {{ t('dispatch_wizard.s3.card_schedule', { n: index + 1 }) }}
      </span>
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="inline-flex min-h-[32px] items-center gap-1 rounded border border-slate-300 px-2 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
          @click="$emit('duplicate')"
        >
          <DocumentDuplicateIcon class="h-3.5 w-3.5 shrink-0 sm:hidden" aria-hidden="true" />
          <span class="hidden sm:inline">{{ t('dispatch_wizard.s3.card_duplicate') }}</span>
          <span class="sr-only sm:hidden">{{ t('dispatch_wizard.s3.card_duplicate') }}</span>
        </button>
        <button
          type="button"
          class="inline-flex min-h-[32px] items-center gap-1 rounded border border-slate-300 px-2 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
          :disabled="index === 0"
          @click="$emit('autofill')"
        >
          <ArrowUpIcon class="h-3.5 w-3.5 shrink-0 sm:hidden" aria-hidden="true" />
          <span class="hidden sm:inline">{{ t('dispatch_wizard.s3.card_copy_above') }}</span>
          <span class="sr-only sm:hidden">{{ t('dispatch_wizard.s3.card_copy_above') }}</span>
        </button>
        <button
          type="button"
          class="inline-flex min-h-[32px] items-center gap-1 rounded border border-rose-200 px-2 py-1 text-xs font-medium text-rose-600 transition hover:bg-rose-50"
          @click="$emit('remove')"
        >
          <TrashIcon class="h-3.5 w-3.5 shrink-0 sm:hidden" aria-hidden="true" />
          <span class="hidden sm:inline">{{ t('dispatch_wizard.s3.card_remove') }}</span>
          <span class="sr-only sm:hidden">{{ t('dispatch_wizard.s3.card_remove') }}</span>
        </button>
      </div>
    </div>

    <!-- Cargo: thông tin hàng hóa -->
    <section
      v-if="variant === 'cargo'"
      class="rounded-lg border border-slate-200/80 bg-slate-50/60 p-3"
    >
      <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
        {{ t('dispatch_wizard.s3.cargo_info_heading') }}
      </h4>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <BaseInput
          class="col-span-2"
          :label="t('dispatch_wizard.s3.cargo_name')"
          :model-value="row.name"
          :placeholder="t('dispatch_wizard.s3.cargo_name_ph')"
          @update:model-value="(v) => (row.name = v)"
        />
        <BaseInput
          :label="t('dispatch_wizard.s3.qty')"
          :model-value="row.qty"
          :placeholder="t('dispatch_wizard.s3.qty_ph')"
          @update:model-value="(v) => (row.qty = v)"
        />
        <BaseInput
          :label="t('dispatch_wizard.s3.weight')"
          :model-value="row.weight"
          :placeholder="t('dispatch_wizard.s3.weight_ph')"
          @update:model-value="(v) => (row.weight = v)"
        />
        <BaseInput
          class="col-span-2"
          :label="t('dispatch_wizard.s3.dim')"
          :model-value="row.dimensions"
          :placeholder="t('dispatch_wizard.s3.dim_ph')"
          @update:model-value="(v) => (row.dimensions = v)"
        />
        <BaseInput
          class="col-span-2"
          :label="t('dispatch_wizard.s3.item_notes')"
          :model-value="row.item_notes"
          :placeholder="t('dispatch_wizard.s3.item_notes_ph')"
          @update:model-value="(v) => (row.item_notes = v)"
        />
      </div>
    </section>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <fieldset class="flex flex-col gap-2 rounded-lg border border-sky-100 bg-sky-50/50 p-3">
        <legend class="px-1 text-xs font-semibold text-sky-700">
          {{ tripOutLegend }}
        </legend>
        <div class="flex flex-col gap-1">
          <template v-if="index === 0">
            <span class="text-xs font-medium text-slate-700">{{ t('dispatch_wizard.s3.time') }}</span>
            <input
              :value="formattedTripStart"
              type="text"
              readonly
              tabindex="-1"
              :class="[
                'rounded-lg border px-3 py-2 text-sm shadow-sm',
                errs.departTime
                  ? 'border-rose-300 bg-rose-50/50 text-slate-900 ring-1 ring-rose-200'
                  : 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-800',
              ]"
              :placeholder="t('dispatch_wizard.s3.readonly_depart_empty')"
            />
            <span v-if="errs.departTime" class="text-xs font-medium text-rose-600">{{ errs.departTime }}</span>
          </template>
          <BaseDateTime
            v-else
            :label="t('dispatch_wizard.s3.time')"
            :model-value="outboundDepartModel"
            :error="errs.departTime"
            :hint="outboundTimeHint"
            @update:model-value="setOutboundDepart"
          />
        </div>
        <BaseInput
          :label="placeOutLabel"
          :model-value="pickupModel"
          :placeholder="pickupPh"
          @update:model-value="setPickup"
        />
        <div v-if="variant === 'cargo'" class="grid grid-cols-1 gap-2 sm:grid-cols-2">
          <BaseInput
            :label="t('dispatch_wizard.s3.shipper_col')"
            :model-value="row.pickup_contact"
            :placeholder="t('dispatch_wizard.s3.pickup_contact_ph')"
            @update:model-value="(v) => (row.pickup_contact = v)"
          />
          <BaseInput
            :label="t('dispatch_wizard.s3.shipper_phone')"
            inputmode="numeric"
            autocomplete="tel"
            maxlength="11"
            :model-value="row.pickup_contact_phone"
            :placeholder="t('dispatch_wizard.create.phone_ph')"
            @update:model-value="(v) => (row.pickup_contact_phone = v)"
          />
        </div>
      </fieldset>

      <fieldset class="flex flex-col gap-2 rounded-lg border border-emerald-100 bg-emerald-50/40 p-3">
        <legend class="px-1 text-xs font-semibold text-emerald-700">
          {{ tripBackLegend }}
        </legend>
        <div class="flex flex-col gap-1">
          <BaseDateTime
            :label="t('dispatch_wizard.s3.time')"
            :model-value="returnModel"
            :error="errs.returnTime"
            :hint="returnTimeHint"
            @update:model-value="setReturn"
          />
          <p v-if="tripDurationLabel" class="text-xs font-medium text-slate-600">
            {{ tripDurationLabel }}
          </p>
        </div>
        <BaseInput
          :label="placeBackLabel"
          :model-value="dropoffModel"
          :placeholder="dropoffPh"
          :error="errs.returnPlace"
          @update:model-value="setDropoff"
        />
        <div v-if="variant === 'cargo'" class="grid grid-cols-1 gap-2 sm:grid-cols-2">
          <BaseInput
            :label="t('dispatch_wizard.s3.receiver_col')"
            :model-value="row.delivery_contact"
            :placeholder="t('dispatch_wizard.s3.delivery_contact_ph')"
            @update:model-value="(v) => (row.delivery_contact = v)"
          />
          <BaseInput
            :label="t('dispatch_wizard.s3.receiver_phone')"
            inputmode="numeric"
            autocomplete="tel"
            maxlength="11"
            :model-value="row.delivery_contact_phone"
            :placeholder="t('dispatch_wizard.create.phone_ph')"
            @update:model-value="(v) => (row.delivery_contact_phone = v)"
          />
        </div>
      </fieldset>
    </div>

    <template v-if="variant === 'business'">
      <BaseInput
        :label="t('dispatch_wizard.s3.waypoint_col')"
        :model-value="row.waypoint"
        :placeholder="t('dispatch_wizard.s3.waypoint_ph')"
        @update:model-value="(v) => (row.waypoint = v)"
      />
    </template>

    <div v-if="variant !== 'cargo'" :class="detailGridClass">
      <BaseInput
        :class="guestsInputClass"
        type="number"
        min="1"
        :label="t('dispatch_wizard.s3.guests')"
        :model-value="String(row.guests ?? '')"
        :placeholder="t('dispatch_wizard.s3.guests_ph')"
        :error="errs.passengers"
        @update:model-value="(v) => (row.guests = v)"
      />

      <BaseInput
        v-if="variant === 'passenger'"
        :class="picInputClass"
        :label="t('dispatch_wizard.s3.owner')"
        :model-value="row.person_in_charge"
        :placeholder="t('dispatch_wizard.s3.pic_ph')"
        @update:model-value="(v) => (row.person_in_charge = v)"
      />

      <template v-if="!isPortalUser">
        <BaseInput
          :class="moneyFieldClass"
          inputmode="numeric"
          autocomplete="off"
          :label="t('dispatch_wizard.s3.unit_price')"
          :model-value="row.unit_price"
          :placeholder="t('dispatch_wizard.s3.vnd_ph')"
          @update:model-value="(v) => vndRow(row, 'unit_price', v)"
        />
        <BaseInput
          :class="moneyFieldClass"
          inputmode="numeric"
          autocomplete="off"
          :label="t('dispatch_wizard.s3.extra_fee')"
          :model-value="row.extra_fee"
          :placeholder="t('dispatch_wizard.s3.vnd_ph_small')"
          @update:model-value="(v) => vndRow(row, 'extra_fee', v)"
        />
      </template>

      <BaseInput
        v-if="!isPortalUser"
        :class="notesFieldClass"
        :label="t('dispatch_wizard.s3.notes')"
        :model-value="notesModel"
        :placeholder="notesPh"
        @update:model-value="setNotes"
      />
    </div>

    <!-- Cargo: chi phí & ghi chú vận chuyển -->
    <section v-else class="rounded-lg border border-slate-200/80 p-3">
      <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
        {{ t('dispatch_wizard.s3.cargo_cost_heading') }}
      </h4>
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <BaseInput
          :class="costFieldClass"
          inputmode="numeric"
          autocomplete="off"
          :label="t('dispatch_wizard.s3.cost')"
          :model-value="row.cost"
          :placeholder="t('dispatch_wizard.s3.vnd_ph')"
          :disabled="lockCargoRowMoney"
          @update:model-value="(v) => vndRow(row, 'cost', v)"
        />
        <BaseInput
          :label="t('dispatch_wizard.s3.transport_note')"
          :model-value="row.transport_note"
          :placeholder="t('dispatch_wizard.s3.trans_ph')"
          @update:model-value="(v) => (row.transport_note = v)"
        />
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, inject, unref } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowUpIcon,
  DocumentDuplicateIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import BaseInput from '../../../components/base/BaseInput.vue'
import BaseDateTime from '../../../components/base/BaseDateTime.vue'
import { dispatchScheduleRowErrors } from '../../../composables/dispatchScheduleRowErrors'
import { formatVndWhileTyping } from '../../../util/money'
import { formatDatetimeLocalAmPm } from '../../../util/datetime'
import { DISPATCH_WIZARD_KEY } from './injectionKeys'

const props = defineProps({
  row: { type: Object, required: true },
  index: { type: Number, required: true },
  variant: {
    type: String,
    required: true,
    validator: (v) => ['passenger', 'business', 'cargo'].includes(v),
  },
})

defineEmits(['remove', 'duplicate', 'autofill'])

const { t } = useI18n()

const wizard = inject(DISPATCH_WIZARD_KEY, null)

const isPortalUser = computed(() => Boolean(wizard?.isPortal))

const detailGridClass = computed(() =>
  isPortalUser.value
    ? 'grid grid-cols-2 gap-4'
    : 'grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5 lg:gap-x-3 lg:gap-y-3',
)

const guestsInputClass = computed(() => {
  if (!isPortalUser.value) return 'col-span-2 sm:col-span-1'
  return 'col-span-1'
})

const picInputClass = computed(() => {
  if (!isPortalUser.value) return 'col-span-2 sm:col-span-2'
  return 'col-span-1'
})

const lockDetailPricing = computed(() => isPortalUser.value && props.variant !== 'cargo')

const lockCargoRowMoney = computed(() => isPortalUser.value && props.variant === 'cargo')

const moneyFieldClass = computed(() => {
  const base = 'col-span-2 sm:col-span-1 lg:col-span-1'
  return lockDetailPricing.value ? `${base} opacity-50 pointer-events-none` : base
})

const notesFieldClass = computed(() => {
  if (isPortalUser.value) return 'col-span-2 opacity-50 pointer-events-none'
  return 'col-span-2 sm:col-span-3 lg:col-span-1'
})

const costFieldClass = computed(() =>
  lockCargoRowMoney.value ? 'opacity-50 pointer-events-none' : '',
)

const tripOutLegend = computed(() =>
  props.variant === 'cargo'
    ? t('dispatch_wizard.s3.cargo_pickup_legend')
    : t('dispatch_wizard.s3.trip_out'),
)

const tripBackLegend = computed(() =>
  props.variant === 'cargo'
    ? t('dispatch_wizard.s3.cargo_delivery_legend')
    : t('dispatch_wizard.s3.trip_back'),
)

const placeOutLabel = computed(() =>
  props.variant === 'cargo'
    ? t('dispatch_wizard.s3.cargo_pickup_place')
    : t('dispatch_wizard.s3.place'),
)

const placeBackLabel = computed(() =>
  props.variant === 'cargo'
    ? t('dispatch_wizard.s3.cargo_delivery_place')
    : t('dispatch_wizard.s3.place'),
)

const formattedTripStart = computed(() => wizard?.formattedRequestedDateTime?.value ?? '')

const outboundDepartKey = computed(() => (props.variant === 'cargo' ? 'pickup_at' : 'depart_at'))

const outboundDepartModel = computed(() => String(props.row[outboundDepartKey.value] ?? ''))

function setOutboundDepart(v) {
  props.row[outboundDepartKey.value] = v
}

const outboundDepartRaw = computed(() => {
  if (props.index === 0) {
    return wizard?.requestedDateTime?.value?.trim() ?? ''
  }
  return outboundDepartModel.value.trim()
})

const returnModel = computed(() =>
  props.variant === 'cargo' ? props.row.delivery_at ?? '' : props.row.return_at ?? '',
)

/** Hiển thị đồng bộ dd/mm/yyyy h:mm AM/PM dưới ô datetime. */
const outboundTimeHint = computed(() => formatDatetimeLocalAmPm(outboundDepartModel.value))

const returnTimeHint = computed(() => formatDatetimeLocalAmPm(String(returnModel.value ?? '')))

const tripDurationLabel = computed(() => {
  const startRaw = outboundDepartRaw.value
  const retRaw = String(returnModel.value ?? '').trim()
  if (!startRaw || !retRaw) return ''
  const a = new Date(startRaw).getTime()
  const b = new Date(retRaw).getTime()
  if (!Number.isFinite(a) || !Number.isFinite(b) || b <= a) return ''
  const ms = b - a
  const h = Math.floor(ms / 3600000)
  const m = Math.round((ms % 3600000) / 60000)
  return t('dispatch_wizard.s3.trip_duration_hint', { hours: h, minutes: m })
})

function vndRow(row, key, raw) {
  row[key] = formatVndWhileTyping(raw)
}

const pickupPh = computed(() => {
  if (props.variant === 'business') return t('dispatch_wizard.s3.biz_pickup_ph')
  if (props.variant === 'cargo') return t('dispatch_wizard.s3.pickup_place_ph')
  return t('dispatch_wizard.s3.pickup_ph')
})

const dropoffPh = computed(() => {
  if (props.variant === 'business') return t('dispatch_wizard.s3.biz_drop_ph')
  if (props.variant === 'cargo') return t('dispatch_wizard.s3.delivery_place_ph')
  return t('dispatch_wizard.s3.dropoff_ph')
})

const notesPh = computed(() =>
  props.variant === 'business'
    ? t('dispatch_wizard.s3.row_notes_biz_ph')
    : t('dispatch_wizard.s3.row_notes_ph'),
)

const pickupModel = computed(() =>
  props.variant === 'cargo' ? props.row.pickup_place ?? '' : props.row.pickup ?? '',
)

const dropoffModel = computed(() =>
  props.variant === 'cargo' ? props.row.delivery_place ?? '' : props.row.dropoff ?? '',
)

const notesModel = computed(() => props.row.notes ?? '')

function setReturn(v) {
  if (props.variant === 'cargo') props.row.delivery_at = v
  else props.row.return_at = v
}

function setPickup(v) {
  if (props.variant === 'cargo') props.row.pickup_place = v
  else props.row.pickup = v
}

function setDropoff(v) {
  if (props.variant === 'cargo') props.row.delivery_place = v
  else props.row.dropoff = v
}

function setNotes(v) {
  props.row.notes = v
}

const scheduleRowErrorOptions = computed(() => {
  if (
    props.variant === 'passenger' &&
    wizard?.wantsRecurringTemplate &&
    unref(wizard.wantsRecurringTemplate)
  ) {
    return { timesFromRecurringTemplate: true }
  }
  return undefined
})

const rawErrors = computed(() =>
  dispatchScheduleRowErrors(props.row, props.variant, scheduleRowErrorOptions.value),
)

const errs = computed(() => {
  const r = rawErrors.value
  const timeMsg = t('dispatch_wizard.s3.val_time_required')
  return {
    departTime: r.time_required ? timeMsg : '',
    returnTime: r.return_time
      ? t('dispatch_wizard.s3.val_return_order')
      : r.return_time_required
        ? t('dispatch_wizard.s3.val_return_time_required')
        : r.time_required
          ? timeMsg
          : '',
    returnPlace: r.return_place ? t('dispatch_wizard.s3.val_return_place_required') : '',
    passengers: r.passengers ? t('dispatch_wizard.s3.val_guests_min') : '',
  }
})
</script>
