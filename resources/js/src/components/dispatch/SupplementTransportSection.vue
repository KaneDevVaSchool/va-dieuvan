<template>
  <div class="mb-0">
    <div
      class="flex items-center gap-1 rounded-t-2xl border-b border-slate-200/70 bg-slate-50 px-3 py-2.5 dark:border-slate-800 dark:bg-slate-900/40"
      :class="expanded ? '' : 'rounded-b-2xl border-b-0'"
    >
      <button
        type="button"
        class="flex min-w-0 flex-1 items-center gap-2.5 text-left"
        :aria-expanded="expanded"
        @click="toggle"
      >
        <ChevronDownIcon
          class="size-4 shrink-0 text-slate-400 transition-transform duration-200 motion-reduce:transition-none dark:text-slate-500"
          :class="expanded ? '-rotate-180' : ''"
          aria-hidden="true"
        />
        <div
          v-if="icon"
          class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 shadow-inner shadow-slate-900/5 dark:bg-slate-800/90 dark:text-slate-400"
        >
          <component :is="icon" class="size-3.5" aria-hidden="true" />
        </div>
        <span class="text-[12px] font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ title }}</span>
      </button>

      <div class="flex shrink-0 items-center gap-2">
        <template v-if="kind === 'vendor' && canQuickCreate && !disabled">
          <button
            type="button"
            class="whitespace-nowrap rounded-lg border border-[#8B1A1A]/20 px-2 py-1 text-[11px] font-semibold text-[#8B1A1A] hover:bg-[#8B1A1A]/5 disabled:cursor-not-allowed disabled:opacity-45 dark:border-[#e57373]/20 dark:text-[#e57373] dark:hover:bg-[#e57373]/10"
            :disabled="disabled"
            @click.stop="$emit('create-vendor')"
          >
            {{ t('trip_detail.coordination.provider_quick_add_inline') }}
          </button>
        </template>
        <span
          v-if="modelValue.length > 0"
          class="rounded-full bg-slate-200/80 px-2 py-0.5 text-[10px] font-bold tabular-nums text-slate-600 dark:bg-slate-700/90 dark:text-slate-300"
        >
          {{ modelValue.length }}
        </span>
      </div>
    </div>

    <Transition
      enter-active-class="transition duration-200 ease-out motion-reduce:transition-none"
      enter-from-class="opacity-0 -translate-y-0.5"
      leave-active-class="transition duration-150 ease-in motion-reduce:transition-none"
      leave-to-class="opacity-0 -translate-y-0.5"
    >
      <div v-show="expanded" class="rounded-b-2xl bg-white px-3 pb-3 pt-2.5 dark:bg-slate-950/30">
        <div v-if="kind === 'vendor'" class="space-y-2.5" :class="indentBody ? 'ml-[38px]' : ''">
          <div>
            <label
              class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
              for="ncc-supplement-filter"
            >
              {{ nameFieldLabel }}
            </label>
            <input
              id="ncc-supplement-filter"
            v-model="vendorFilter"
            type="search"
            autocomplete="off"
            class="w-full rounded-xl bg-slate-100/90 px-2.5 py-1.5 text-[12px] font-normal text-slate-800 shadow-inner shadow-slate-900/5 outline-none placeholder:text-slate-400 focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:placeholder:text-slate-500 dark:focus:bg-slate-900"
            :placeholder="t('trip_detail.coordination.resource_section_vendor_filter_ph')"
            :disabled="disabled"
            />
          </div>
          <div>
            <label
              class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
              for="ncc-supplement-select"
            >
              {{ t('trip_detail.coordination.resource_section_vendor_title') }}
            </label>
            <select
                id="ncc-supplement-select"
                v-model="selectedVendorId"
                class="w-full appearance-none rounded-xl border border-transparent bg-slate-100/90 py-2 pl-3 pr-8 text-[12px] font-normal text-slate-800 shadow-inner shadow-slate-900/5 outline-none focus:border-slate-300 focus:bg-white focus:shadow-md disabled:cursor-not-allowed disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:focus:border-slate-600 dark:focus:bg-slate-900 dark:focus:shadow-black/35"
                :disabled="disabled"
                :aria-label="nameFieldLabel"
              >
                <option value="">{{ namePlaceholder }}</option>
                <option
                  v-for="opt in filteredVendorSelectOptions"
                  :key="String(opt.id)"
                  :value="String(opt.id)"
                >
                  {{ opt.label }}{{ opt.sublabel ? ` · ${opt.sublabel}` : '' }}
                </option>
              </select>
          </div>
          <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <div>
              <label
                class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                for="ncc-supplement-price"
              >
                {{ t('trip_detail.coordination.supplement_vendor_price_aria') }}
              </label>
              <input
                id="ncc-supplement-price"
                :value="priceDraft"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                class="w-full rounded-xl bg-slate-100/90 px-2.5 py-1.5 text-[12px] font-normal tabular-nums text-slate-800 shadow-inner shadow-slate-900/5 outline-none placeholder:text-slate-400 focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:placeholder:text-slate-500 dark:focus:bg-slate-900"
                :placeholder="t('trip_detail.coordination.supplement_vendor_price_ph')"
                :disabled="disabled"
                :aria-invalid="priceDraftInvalid"
                @input="onVendorPriceDraftInput"
              />
              <p
                v-if="priceDraftInvalid"
                class="mt-1 text-[11px] font-medium text-rose-600 dark:text-rose-400"
                role="alert"
              >
                {{ t('trip_detail.coordination.supplement_price_invalid') }}
              </p>
            </div>
            <div>
              <label
                class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                for="ncc-supplement-seats"
              >
                {{ t('trip_detail.coordination.supplement_seats_field_label') }}
              </label>
              <input
                id="ncc-supplement-seats"
                v-model="seatDraft"
                type="number"
                min="1"
                step="1"
                inputmode="numeric"
                class="w-full rounded-xl bg-slate-100/90 px-2.5 py-1.5 text-[12px] font-normal tabular-nums text-slate-800 shadow-inner shadow-slate-900/5 outline-none focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:focus:bg-slate-900"
                :disabled="disabled"
                :aria-invalid="seatsDraftInvalid"
              />
              <p
                v-if="seatsDraftInvalid"
                class="mt-1 text-[11px] font-medium text-rose-600 dark:text-rose-400"
                role="alert"
              >
                {{ t('trip_detail.coordination.supplement_seats_invalid') }}
              </p>
            </div>
          </div>
          <div class="flex justify-end">
            <button
              type="button"
              class="w-full rounded-xl bg-[#8B1A1A] px-3 py-2 text-[12px] font-semibold text-white shadow-md shadow-[#8B1A1A]/20 hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto dark:shadow-[#8B1A1A]/25"
              :disabled="disabled || !canAddSupplement"
              @click="onAdd"
            >
              {{ t('trip_detail.coordination.supplement_add_btn') }}
            </button>
          </div>
        </div>

        <div v-else class="space-y-2.5" :class="indentBody ? 'ml-[38px]' : ''">
          <template v-if="taxiSelectOptions.length > 0">
            <div>
              <label
                class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                for="taxi-supplement-filter"
              >
                {{ nameFieldLabel }}
              </label>
              <input
                id="taxi-supplement-filter"
                v-model="taxiFilter"
                type="search"
                autocomplete="off"
                class="w-full rounded-xl bg-slate-100/90 px-2.5 py-1.5 text-[12px] font-normal text-slate-800 shadow-inner shadow-slate-900/5 outline-none placeholder:text-slate-400 focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:placeholder:text-slate-500 dark:focus:bg-slate-900"
                :placeholder="t('trip_detail.coordination.resource_section_taxi_filter_ph')"
                :disabled="disabled"
              />
            </div>
            <div>
              <label
                class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                for="taxi-supplement-select"
              >
                {{ t('trip_detail.coordination.resource_section_taxi_title') }}
              </label>
              <select
                id="taxi-supplement-select"
                v-model="selectedTaxiId"
                class="w-full appearance-none rounded-xl border border-transparent bg-slate-100/90 py-2 pl-3 pr-8 text-[12px] font-normal text-slate-800 shadow-inner shadow-slate-900/5 outline-none focus:border-slate-300 focus:bg-white focus:shadow-md disabled:cursor-not-allowed disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:focus:border-slate-600 dark:focus:bg-slate-900 dark:focus:shadow-black/35"
                :disabled="disabled"
                :aria-label="nameFieldLabel"
              >
                <option value="">{{ namePlaceholder }}</option>
                <option
                  v-for="opt in filteredTaxiSelectOptions"
                  :key="String(opt.id)"
                  :value="String(opt.id)"
                >
                  {{ opt.label }}{{ opt.sublabel ? ` · ${opt.sublabel}` : '' }}
                </option>
              </select>
            </div>
          </template>
          <div v-else>
            <label
              class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
              for="taxi-supplement-name"
            >
              {{ nameFieldLabel }}
            </label>
            <input
              id="taxi-supplement-name"
              v-model="nameDraft"
              type="text"
              class="w-full rounded-xl bg-slate-100/90 px-2.5 py-1.5 text-[12px] font-normal text-slate-800 shadow-inner shadow-slate-900/5 outline-none placeholder:text-slate-400 focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:placeholder:text-slate-500 dark:focus:bg-slate-900"
              :placeholder="namePlaceholder"
              :disabled="disabled"
              :aria-busy="disabled"
              @keydown.enter.prevent="onAdd"
            />
          </div>
          <div>
            <label
              class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
              for="taxi-supplement-plate"
            >
              {{ t('trip_detail.coordination.supplement_taxi_plate_aria') }}
            </label>
            <input
              id="taxi-supplement-plate"
              v-model="plateDraft"
              type="text"
              class="w-full rounded-xl bg-slate-100/90 px-2.5 py-1.5 text-[12px] font-normal uppercase tracking-wide text-slate-800 shadow-inner shadow-slate-900/5 outline-none placeholder:normal-case placeholder:tracking-normal placeholder:text-slate-400 focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:placeholder:text-slate-500 dark:focus:bg-slate-900"
              :placeholder="t('trip_detail.coordination.supplement_taxi_plate_ph')"
              :disabled="disabled"
            />
          </div>
          <div>
            <label
              class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
              for="taxi-supplement-price"
            >
              {{ t('trip_detail.coordination.supplement_taxi_price_aria') }}
            </label>
            <input
              id="taxi-supplement-price"
              v-model="priceDraft"
              type="number"
              min="0"
              step="1000"
              inputmode="decimal"
              class="w-full rounded-xl bg-slate-100/90 px-2.5 py-1.5 text-[12px] font-normal tabular-nums text-slate-800 shadow-inner shadow-slate-900/5 outline-none placeholder:text-slate-400 focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:placeholder:text-slate-500 dark:focus:bg-slate-900"
              :placeholder="t('trip_detail.coordination.supplement_taxi_price_ph')"
              :disabled="disabled"
              :aria-invalid="priceDraftInvalid"
            />
            <p
              v-if="priceDraftInvalid"
              class="mt-1 text-[11px] font-medium text-rose-600 dark:text-rose-400"
              role="alert"
            >
              {{ t('trip_detail.coordination.supplement_price_invalid') }}
            </p>
          </div>
          <div class="flex flex-wrap items-end gap-2">
            <div class="min-w-[5rem] flex-1">
              <label
                class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                for="taxi-supplement-seats"
              >
                {{ t('trip_detail.coordination.supplement_seats_field_label') }}
              </label>
              <input
                id="taxi-supplement-seats"
                v-model="seatDraft"
                type="number"
                min="1"
                step="1"
                inputmode="numeric"
                class="w-full rounded-xl bg-slate-100/90 px-2.5 py-1.5 text-[12px] font-normal tabular-nums text-slate-800 shadow-inner shadow-slate-900/5 outline-none focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:focus:bg-slate-900"
                :disabled="disabled"
                :aria-invalid="seatsDraftInvalid"
              />
              <p
                v-if="seatsDraftInvalid"
                class="mt-1 text-[11px] font-medium text-rose-600 dark:text-rose-400"
                role="alert"
              >
                {{ t('trip_detail.coordination.supplement_seats_invalid') }}
              </p>
            </div>
            <button
              type="button"
              class="shrink-0 rounded-xl bg-[#8B1A1A] px-3 py-2 text-[12px] font-semibold text-white shadow-md shadow-[#8B1A1A]/20 hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-40 dark:shadow-[#8B1A1A]/25"
              :disabled="disabled || !canAddSupplement"
              @click="onAdd"
            >
              {{ t('trip_detail.coordination.supplement_add_btn') }}
            </button>
          </div>
        </div>

        <div v-if="isLoading" class="mt-2 h-8 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800/70" />

        <div v-if="!isLoading && modelValue.length > 0" class="mt-3 space-y-2">
          <div v-for="(item, idx) in modelValue" :key="String(item.id) + '-' + idx" class="space-y-2">
            <div
              class="flex min-h-[38px] items-center gap-2 rounded-xl bg-slate-100/80 px-3 py-2 shadow-sm shadow-slate-900/5 dark:bg-slate-800/55 dark:shadow-black/20"
            >
              <span class="min-w-0 flex-1 truncate text-[13px] font-medium text-slate-900 dark:text-slate-100">{{
                item.label
              }}</span>
              <span
                v-if="seatBadgeText(item)"
                class="shrink-0 rounded-full bg-emerald-100/95 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-emerald-900 shadow-sm dark:bg-emerald-950/65 dark:text-emerald-100"
              >
                {{ seatBadgeText(item) }}
              </span>
              <span
                v-if="priceBadgeText(item)"
                class="shrink-0 max-w-[8.5rem] truncate rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-amber-900 shadow-sm dark:bg-amber-950/55 dark:text-amber-100"
                :title="priceBadgeText(item)"
              >
                {{ priceBadgeText(item) }}
              </span>
              <button
                type="button"
                class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-slate-200/70 text-[16px] font-normal leading-none text-slate-500 hover:bg-rose-100 hover:text-rose-600 disabled:opacity-35 dark:bg-slate-700/70 dark:text-slate-400 dark:hover:bg-rose-950/70 dark:hover:text-rose-300"
                :disabled="disabled"
                :aria-label="t('trip_detail.coordination.supplement_remove_aria')"
                @click="removeAt(idx)"
              >
                ×
              </button>
            </div>
            <div
              v-if="kind === 'taxi'"
              class="space-y-2 rounded-xl bg-slate-50/80 px-2.5 py-2.5 shadow-inner shadow-slate-900/5 dark:bg-slate-900/35 dark:shadow-black/20"
            >
              <div>
                <label
                  class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                  :for="`taxi-item-plate-${idx}`"
                >
                  {{ t('trip_detail.coordination.supplement_taxi_plate_aria') }}
                </label>
                <input
                  :id="`taxi-item-plate-${idx}`"
                  :value="item.externalVehicleRef ?? ''"
                  type="text"
                  class="w-full rounded-xl bg-white/70 px-2.5 py-1.5 text-[13px] font-normal uppercase tracking-wide text-slate-800 shadow-inner shadow-slate-900/10 outline-none placeholder:normal-case placeholder:tracking-normal focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50"
                  :placeholder="t('trip_detail.coordination.supplement_taxi_plate_ph')"
                  :disabled="disabled"
                  @input="onExternalVehicleInput(idx, $event)"
                />
              </div>
              <div>
                <label
                  class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                  :for="`taxi-item-price-${idx}`"
                >
                  {{ t('trip_detail.coordination.supplement_taxi_price_aria') }}
                </label>
                <input
                  :id="`taxi-item-price-${idx}`"
                  :value="servicePriceDisplay(item)"
                  type="number"
                  min="0"
                  step="1000"
                  inputmode="decimal"
                  class="w-full rounded-xl bg-white/70 px-2.5 py-1.5 text-[13px] font-normal tabular-nums text-slate-800 shadow-inner shadow-slate-900/10 outline-none focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50"
                  :placeholder="t('trip_detail.coordination.supplement_taxi_price_ph')"
                  :disabled="disabled"
                  @input="onServicePriceInput(idx, $event)"
                />
              </div>
            </div>
            <div v-if="kind === 'vendor'" class="space-y-2">
              <div>
                <label
                  class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                  :for="`ncc-item-price-${idx}`"
                >
                  {{ t('trip_detail.coordination.supplement_vendor_price_aria') }}
                </label>
                <input
                  :id="`ncc-item-price-${idx}`"
                  :value="servicePriceDisplayVendor(item)"
                  type="text"
                  inputmode="numeric"
                  autocomplete="off"
                  class="w-full rounded-xl bg-slate-100/90 px-2.5 py-1.5 text-[12px] font-normal tabular-nums text-slate-800 shadow-inner shadow-slate-900/5 outline-none focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50"
                  :placeholder="t('trip_detail.coordination.supplement_vendor_price_ph')"
                  :disabled="disabled"
                  @input="onVendorItemPriceInput(idx, $event)"
                />
              </div>
              <div
                class="overflow-hidden rounded-xl border border-slate-200/70 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900/30"
              >
              <button
                type="button"
                class="flex w-full items-center justify-between gap-2 px-3 py-2.5 text-left text-[11px] font-semibold text-slate-600 disabled:cursor-not-allowed disabled:opacity-50 dark:text-slate-300"
                :aria-expanded="refsOpen[itemId(item)]"
                :disabled="disabled"
                @click="toggleRefs(itemId(item))"
              >
                <span>{{ t('trip_detail.coordination.supplement_ncc_refs_toggle') }}</span>
                <span class="tabular-nums text-slate-400" aria-hidden="true">{{
                  refsOpen[itemId(item)] ? '▾' : '▸'
                }}</span>
              </button>
              <div
                v-show="refsOpen[itemId(item)]"
                class="space-y-2 bg-slate-50/80 px-3 pb-2.5 pt-1 dark:bg-slate-950/40"
              >
                <div>
                  <input
                    :value="item.externalVehicleRef ?? ''"
                    type="text"
                    class="w-full rounded-xl bg-white/70 px-2.5 py-1.5 text-[13px] font-normal text-slate-800 shadow-inner shadow-slate-900/10 outline-none focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50"
                    :placeholder="t('trip_detail.coordination.external_vehicle_ph')"
                    :disabled="disabled"
                    @input="onExternalVehicleInput(idx, $event)"
                  />
                </div>
                <div>
                  <input
                    :value="item.externalDriverRef ?? ''"
                    type="text"
                    class="w-full rounded-xl bg-white/70 px-2.5 py-1.5 text-[13px] font-normal text-slate-800 shadow-inner shadow-slate-900/10 outline-none focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50"
                    :placeholder="t('trip_detail.coordination.external_driver_ph')"
                    :disabled="disabled"
                    @input="onExternalDriverInput(idx, $event)"
                  />
                </div>
                <div>
                  <label
                    class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                    :for="`ncc-contact-notes-${idx}`"
                  >
                    {{ t('trip_detail.coordination.ncc_contact_notes_label') }}
                  </label>
                  <textarea
                    :id="`ncc-contact-notes-${idx}`"
                    :value="item.contactNotes ?? ''"
                    rows="2"
                    maxlength="500"
                    class="w-full resize-y rounded-xl bg-white/70 px-2.5 py-1.5 text-[13px] font-normal leading-snug text-slate-800 shadow-inner shadow-slate-900/10 outline-none placeholder:text-slate-400 focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:placeholder:text-slate-500"
                    :placeholder="t('trip_detail.coordination.ncc_contact_notes_ph')"
                    :disabled="disabled"
                    @input="onContactNotesInput(idx, $event)"
                  />
                </div>
              </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import { formatVnd } from '../../util/labels'
import { formatVndWhileTyping, parseMoneyVnd } from '../../util/money'
import type { ResourceItem } from '../../types/dispatch'

const props = defineProps<{
  modelValue: ResourceItem[]
  options: ResourceItem[]
  title: string
  subtitle?: string
  namePlaceholder: string
  nameFieldLabel: string
  isLoading: boolean
  icon?: object | null
  kind: 'taxi' | 'vendor'
  defaultSeat: number
  indentBody?: boolean
  canQuickCreate?: boolean
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: ResourceItem[]]
  'create-vendor': []
}>()

const { t } = useI18n()

const expanded = ref(false)
const nameDraft = ref('')
const plateDraft = ref('')
const priceDraft = ref('')
const seatDraft = ref(String(props.defaultSeat))
const vendorFilter = ref('')
const selectedVendorId = ref('')
const taxiFilter = ref('')
const selectedTaxiId = ref('')
const refsOpen = ref<Record<string, boolean>>({})

const taxiSelectOptions = computed(() => {
  if (props.kind !== 'taxi') return []
  return (props.options ?? []).filter((o) => o.available !== false)
})

const filteredTaxiSelectOptions = computed(() => {
  if (props.kind !== 'taxi') return []
  const q = taxiFilter.value.trim().toLowerCase()
  const list = taxiSelectOptions.value
  if (!q) return list
  return list.filter((o) => {
    const lab = String(o.label ?? '').toLowerCase()
    const sub = String(o.sublabel ?? '').toLowerCase()
    return lab.includes(q) || sub.includes(q)
  })
})

const filteredVendorSelectOptions = computed(() => {
  if (props.kind !== 'vendor') return []
  const q = vendorFilter.value.trim().toLowerCase()
  const list = props.options ?? []
  return list.filter((o) => {
    if (o.available === false) return false
    if (!q) return true
    const lab = String(o.label ?? '').toLowerCase()
    const sub = String(o.sublabel ?? '').toLowerCase()
    return lab.includes(q) || sub.includes(q)
  })
})

watch(
  () => props.options,
  () => {
    if (props.kind === 'vendor') {
      const id = selectedVendorId.value.trim()
      if (!id) return
      const ok = props.options.some((o) => String(o.id) === id && o.available !== false)
      if (!ok) selectedVendorId.value = ''
      return
    }
    if (props.kind === 'taxi') {
      const id = selectedTaxiId.value.trim()
      if (!id) return
      const ok = props.options.some((o) => String(o.id) === id && o.available !== false)
      if (!ok) selectedTaxiId.value = ''
    }
  },
  { deep: true },
)

watch(
  () => props.modelValue.length,
  (n) => {
    if (n > 0) expanded.value = true
  },
  { immediate: true },
)

function resetAddFormDrafts() {
  priceDraft.value = ''
  seatDraft.value = String(props.defaultSeat)
  if (props.kind === 'vendor') {
    selectedVendorId.value = ''
    vendorFilter.value = ''
  } else {
    selectedTaxiId.value = ''
    taxiFilter.value = ''
    nameDraft.value = ''
    plateDraft.value = ''
  }
}

function toggle() {
  const next = !expanded.value
  if (!next) resetAddFormDrafts()
  expanded.value = next
}

const priceDraftInvalid = computed(() => {
  const raw = String(priceDraft.value).trim()
  return raw !== '' && parseServicePrice() === null
})

const seatsDraftInvalid = computed(() => {
  const raw = String(seatDraft.value).trim()
  return raw !== '' && parseSeats() === null
})

const canAddSupplement = computed(() => {
  if (props.disabled) return false
  if (parseSeats() === null) return false
  if (priceDraftInvalid.value) return false
  if (props.kind === 'vendor') return selectedVendorId.value.trim() !== ''
  if (selectedTaxiId.value.trim() !== '') return true
  if (taxiSelectOptions.value.length === 0) return nameDraft.value.trim() !== ''
  return false
})

function itemId(item: ResourceItem) {
  return String(item.id)
}

watch(
  () => props.defaultSeat,
  (n) => {
    const s = String(n)
    if (!nameDraft.value.trim()) seatDraft.value = s
  },
)

watch(
  () => props.modelValue,
  (list) => {
    const next = { ...refsOpen.value }
    for (const it of list) {
      const id = itemId(it)
      const has =
        String(it.externalVehicleRef ?? '').trim() !== '' ||
        String(it.externalDriverRef ?? '').trim() !== '' ||
        String(it.contactNotes ?? '').trim() !== ''
      if (props.kind === 'vendor' && has && next[id] === undefined) next[id] = true
    }
    refsOpen.value = next
  },
  { deep: true, immediate: true },
)

function toggleRefs(id: string) {
  if (props.disabled) return
  refsOpen.value = { ...refsOpen.value, [id]: !refsOpen.value[id] }
}

function onExternalVehicleInput(idx: number, e: Event) {
  const el = e.target as HTMLInputElement
  patchItem(idx, 'externalVehicleRef', el.value)
}

function onExternalDriverInput(idx: number, e: Event) {
  const el = e.target as HTMLInputElement
  patchItem(idx, 'externalDriverRef', el.value)
}

function onContactNotesInput(idx: number, e: Event) {
  const el = e.target as HTMLTextAreaElement
  patchItem(idx, 'contactNotes', el.value)
}

function onVendorPriceDraftInput(e: Event) {
  const el = e.target as HTMLInputElement
  priceDraft.value = formatVndWhileTyping(el.value)
}

function onServicePriceInput(idx: number, e: Event) {
  const el = e.target as HTMLInputElement
  const raw = el.value.trim()
  if (!raw) {
    patchItem(idx, 'servicePrice', null)
    return
  }
  const n = Number(raw)
  if (!Number.isFinite(n) || n < 0) return
  patchItem(idx, 'servicePrice', n)
}

function onVendorItemPriceInput(idx: number, e: Event) {
  const el = e.target as HTMLInputElement
  const formatted = formatVndWhileTyping(el.value)
  el.value = formatted
  if (!formatted.trim()) {
    patchItem(idx, 'servicePrice', null)
    return
  }
  const n = parseMoneyVnd(formatted)
  if (!Number.isFinite(n) || n < 0) return
  patchItem(idx, 'servicePrice', Math.floor(n))
}

function servicePriceDisplay(item: ResourceItem) {
  const n = Number(item.servicePrice)
  if (!Number.isFinite(n) || n < 0) return ''
  return String(n)
}

function servicePriceDisplayVendor(item: ResourceItem) {
  const n = Number(item.servicePrice)
  if (!Number.isFinite(n) || n < 0) return ''
  return formatVndWhileTyping(String(Math.floor(n)))
}

function patchItem(
  idx: number,
  key: 'externalVehicleRef' | 'externalDriverRef' | 'contactNotes' | 'servicePrice',
  value: string | number | null,
) {
  if (props.disabled) return
  const next = props.modelValue.map((it, i) => {
    if (i !== idx) return it
    const copy: ResourceItem = { ...it }
    if (key === 'servicePrice') {
      if (value == null || value === '') delete copy.servicePrice
      else copy.servicePrice = Number(value)
    } else if (value == null || value === '') {
      delete copy[key]
    } else {
      copy[key] = String(value)
    }
    return copy
  })
  emit('update:modelValue', next)
}

function taxiExtrasFromDrafts(): Pick<ResourceItem, 'externalVehicleRef' | 'servicePrice'> {
  const plate = plateDraft.value.trim()
  const price = parseServicePrice()
  const out: Pick<ResourceItem, 'externalVehicleRef' | 'servicePrice'> = {}
  if (plate) out.externalVehicleRef = plate.slice(0, 255)
  if (price != null) out.servicePrice = price
  return out
}

function parseSeats(): number | null {
  const n = Number(String(seatDraft.value).trim())
  if (!Number.isFinite(n) || n < 1) return null
  return Math.floor(n)
}

function parseServicePrice(): number | null {
  const raw = String(priceDraft.value).trim()
  if (!raw) return null
  if (props.kind === 'vendor') {
    const digits = raw.replace(/\D/g, '')
    if (!digits) return null
    const n = parseMoneyVnd(raw)
    if (!Number.isFinite(n) || n < 0) return null
    return Math.floor(n)
  }
  const n = Number(raw)
  if (!Number.isFinite(n) || n < 0) return null
  return n
}

function tryResolveOption(name: string): ResourceItem | null {
  const q = name.trim().toLowerCase()
  if (!q) return null
  const list = props.options ?? []
  const exact = list.find(
    (o) => o.available !== false && String(o.label).toLowerCase() === q,
  )
  return exact ?? null
}

function seatBadgeText(item: ResourceItem) {
  const n = Number(item.supplementSeats)
  if (!Number.isFinite(n) || n < 1) return ''
  return t('trip_detail.coordination.seats_n', { n })
}

function priceBadgeText(item: ResourceItem) {
  const n = Number(item.servicePrice)
  if (!Number.isFinite(n) || n < 0) return ''
  return formatVnd(n)
}

function isCustomDuplicate(name: string) {
  const q = name.toLowerCase()
  return props.modelValue.some((v) => {
    const isC = v.isCustom === true || String(v.id).startsWith('custom:')
    return isC && String(v.label).toLowerCase() === q
  })
}

function onAdd() {
  if (props.disabled) return
  const seats = parseSeats()
  if (seats == null) return

  if (props.kind === 'vendor') {
    const idRaw = selectedVendorId.value.trim()
    if (!idRaw) return
    const resolved = props.options.find((o) => String(o.id) === idRaw && o.available !== false)
    if (!resolved) return
    if (props.modelValue.some((v) => String(v.id) === String(resolved.id))) {
      selectedVendorId.value = ''
      return
    }
    const price = parseServicePrice()
    const enriched: ResourceItem = {
      ...resolved,
      supplementSeats: seats,
      ...(price != null ? { servicePrice: price } : {}),
    }
    emit('update:modelValue', [...props.modelValue, enriched])
    selectedVendorId.value = ''
    priceDraft.value = ''
    seatDraft.value = String(props.defaultSeat)
    return
  }

  const idRaw = selectedTaxiId.value.trim()
  if (idRaw) {
    const resolved = props.options.find((o) => String(o.id) === idRaw && o.available !== false)
    if (!resolved) return
    if (props.modelValue.some((v) => String(v.id) === String(resolved.id))) {
      selectedTaxiId.value = ''
      return
    }
    const enriched: ResourceItem = {
      ...resolved,
      supplementSeats: seats,
      ...taxiExtrasFromDrafts(),
    }
    emit('update:modelValue', [...props.modelValue, enriched])
    selectedTaxiId.value = ''
    taxiFilter.value = ''
    plateDraft.value = ''
    priceDraft.value = ''
    seatDraft.value = String(props.defaultSeat)
    return
  }

  const name = nameDraft.value.trim()
  if (!name) return

  if (isCustomDuplicate(name)) {
    nameDraft.value = ''
    return
  }

  const resolved = tryResolveOption(name)
  if (resolved) {
    if (props.modelValue.some((v) => String(v.id) === String(resolved.id))) {
      nameDraft.value = ''
      return
    }
    const enriched: ResourceItem = {
      ...resolved,
      supplementSeats: seats,
      ...taxiExtrasFromDrafts(),
    }
    emit('update:modelValue', [...props.modelValue, enriched])
    nameDraft.value = ''
    plateDraft.value = ''
    priceDraft.value = ''
    seatDraft.value = String(props.defaultSeat)
    return
  }

  const id = `custom:${Date.now()}-${Math.random().toString(36).slice(2, 9)}`
  const item: ResourceItem = {
    id,
    label: name,
    available: true,
    isCustom: true,
    supplementSeats: seats,
    ...taxiExtrasFromDrafts(),
  }
  emit('update:modelValue', [...props.modelValue, item])
  nameDraft.value = ''
  plateDraft.value = ''
  priceDraft.value = ''
  seatDraft.value = String(props.defaultSeat)
}

function removeAt(index: number) {
  if (props.disabled) return
  const removed = props.modelValue[index]
  const next = props.modelValue.filter((_, i) => i !== index)
  if (removed) {
    const id = itemId(removed)
    const ro = { ...refsOpen.value }
    delete ro[id]
    refsOpen.value = ro
  }
  emit('update:modelValue', next)
}
</script>
