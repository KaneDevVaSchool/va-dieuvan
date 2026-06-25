<template>
  <article class="dw-route-card">
    <header class="dw-route-card__head">
      <div class="min-w-0 flex-1">
        <h4 class="dw-route-card__title">
          {{ t('dispatch_wizard.s3.route_card_title', { n: index + 1 }) }}
        </h4>
        <p class="dw-route-card__route truncate">{{ routeEndpointsLabel }}</p>
        <p v-if="routeTimesLabel" class="dw-route-card__times">{{ routeTimesLabel }}</p>
      </div>
      <div class="dw-route-card__actions">
        <button
          type="button"
          class="inline-flex min-h-[28px] items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
          data-testid="dispatch-schedule-duplicate"
          @click="$emit('duplicate')"
        >
          <DocumentDuplicateIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          <span>{{ t('dispatch_wizard.s3.card_duplicate') }}</span>
        </button>
        <button
          v-if="variant !== 'cargo'"
          type="button"
          class="inline-flex min-h-[28px] items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
          :disabled="index === 0"
          data-testid="dispatch-schedule-copy-above"
          @click="$emit('autofill')"
        >
          <ArrowUpIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          <span class="hidden sm:inline">{{ t('dispatch_wizard.s3.card_copy_above') }}</span>
        </button>
        <button
          type="button"
          class="inline-flex min-h-[28px] items-center gap-1 rounded-lg border border-rose-200 bg-white px-2 py-1 text-xs font-medium text-rose-700 transition hover:bg-rose-50"
          data-testid="dispatch-schedule-remove"
          @click="$emit('remove')"
        >
          <TrashIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          <span>{{ t('dispatch_wizard.s3.card_remove') }}</span>
        </button>
      </div>
    </header>

    <div class="dw-route-card__body">
      <div
        v-if="variant === 'passenger'"
        class="col-span-full grid grid-cols-1 gap-2 sm:grid-cols-2"
        role="group"
        :aria-label="t('dispatch_wizard.s3.owner')"
      >
        <BaseInput
          data-testid="dispatch-schedule-pic-name"
          :label="t('dispatch_wizard.s3.owner_name')"
          :model-value="row.person_in_charge"
          :placeholder="t('dispatch_wizard.s3.pic_ph')"
          @update:model-value="(v) => (row.person_in_charge = v)"
        />
        <BaseInput
          data-testid="dispatch-schedule-pic-phone"
          :label="t('dispatch_wizard.s3.owner_phone')"
          inputmode="numeric"
          autocomplete="tel"
          maxlength="11"
          :model-value="row.person_in_charge_phone ?? ''"
          :placeholder="t('dispatch_wizard.create.phone_ph')"
          @update:model-value="(v) => (row.person_in_charge_phone = v)"
        />
      </div>

      <div v-if="variant === 'cargo'" class="col-span-full space-y-2">
        <p class="dw-route-leg__label">{{ t('dispatch_wizard.s3.cargo_info_heading') }}</p>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
          <BaseInput
            class="col-span-2 sm:col-span-1"
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
        </div>
      </div>

      <div class="dw-route-leg">
        <p class="dw-route-leg__label">{{ tripOutLegend }}</p>
        <div class="flex flex-col gap-1">
          <template v-if="index === 0">
            <span class="text-xs font-medium text-slate-700">{{ timeOutLabel }}</span>
            <input
              :value="formattedTripStart"
              type="text"
              readonly
              tabindex="-1"
              :aria-label="timeOutLabel"
              :class="[
                'rounded-lg border px-3 py-2 text-sm',
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
            :label="timeOutLabel"
            :model-value="outboundDepartModel"
            :error="errs.departTime"
            :hint="outboundTimeHint"
            @update:model-value="setOutboundDepart"
          />
        </div>
        <BaseInput
          :label="placeOutLabel"
          :hint="placeOutHint"
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
      </div>

      <div class="dw-route-leg">
        <p class="dw-route-leg__label">{{ tripBackLegend }}</p>
        <div class="flex flex-col gap-1">
          <BaseDateTime
            :label="timeBackLabel"
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
          :hint="placeBackHint"
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
      </div>

      <template v-if="variant === 'business'">
        <BaseInput
          class="col-span-full"
          :label="t('dispatch_wizard.s3.waypoint_col')"
          :model-value="row.waypoint"
          :placeholder="t('dispatch_wizard.s3.waypoint_ph')"
          @update:model-value="(v) => (row.waypoint = v)"
        />
      </template>

      <div v-if="variant !== 'cargo'" class="col-span-full" :class="detailGridClass">
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

    <!-- Cargo: chi phí -->
    <div v-else class="col-span-full">
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
    </div>
    </div>
  </article>
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

const detailGridClass = computed(() => {
  if (isPortalUser.value) {
    return props.variant === 'passenger'
      ? 'grid grid-cols-1 gap-3'
      : 'grid grid-cols-2 gap-4'
  }
  return 'grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5 lg:gap-x-3 lg:gap-y-3'
})

const guestsInputClass = computed(() => {
  if (isPortalUser.value) return 'max-w-[11rem]'
  return 'col-span-2 sm:col-span-1'
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

const placeOutLabel = computed(() => {
  if (props.variant === 'cargo') return t('dispatch_wizard.s3.cargo_pickup_place')
  if (props.variant === 'passenger') return t('dispatch_wizard.s3.place_depart_out')
  return t('dispatch_wizard.s3.place')
})

const placeBackLabel = computed(() => {
  if (props.variant === 'cargo') return t('dispatch_wizard.s3.cargo_delivery_place')
  if (props.variant === 'passenger') return t('dispatch_wizard.s3.place_depart_return')
  return t('dispatch_wizard.s3.place')
})

const placeOutHint = computed(() =>
  props.variant === 'passenger' ? t('dispatch_wizard.s3.place_depart_out_hint') : '',
)

const placeBackHint = computed(() =>
  props.variant === 'passenger' ? t('dispatch_wizard.s3.place_depart_return_hint') : '',
)

const timeOutLabel = computed(() => {
  if (props.variant === 'passenger') return t('dispatch_wizard.s3.time_depart_out')
  return t('dispatch_wizard.s3.time')
})

const timeBackLabel = computed(() => {
  if (props.variant === 'passenger') return t('dispatch_wizard.s3.time_depart_return')
  return t('dispatch_wizard.s3.time')
})

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

const routeEndpointsLabel = computed(() => {
  const a = (pickupModel.value || '').trim() || '—'
  const b = (dropoffModel.value || '').trim() || '—'
  return `${a} → ${b}`
})

const routeTimesLabel = computed(() => {
  const out =
    props.index === 0
      ? String(formattedTripStart.value || '').trim()
      : String(outboundTimeHint.value || '').trim()
  const ret = String(returnTimeHint.value || '').trim()
  if (!out && !ret) return ''
  if (!ret) return out
  if (!out) return ret
  return `${out} → ${ret}`
})

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
      : r.time_required
        ? timeMsg
        : '',
    returnPlace: r.return_place ? t('dispatch_wizard.s3.val_return_place_required') : '',
    passengers: r.passengers ? t('dispatch_wizard.s3.val_guests_min') : '',
  }
})
</script>
