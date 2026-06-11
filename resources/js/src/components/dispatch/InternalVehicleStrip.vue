<template>
  <div
    class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900/50"
    data-testid="dispatch-internal-vehicle-strip"
  >
    <div class="border-b border-slate-200/70 bg-slate-50 px-3 py-2.5 dark:border-slate-800 dark:bg-slate-900/40">
      <div class="flex flex-wrap items-start gap-2">
        <div class="flex min-w-0 flex-1 items-center gap-2">
          <div
            class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-800/90 dark:text-slate-400"
          >
            <TruckIcon class="size-3.5" aria-hidden="true" />
          </div>
          <div class="min-w-0">
            <div class="text-[12px] font-semibold tracking-tight text-slate-800 dark:text-slate-100">
              {{ t('trip_detail.coordination.resource_section_internal_title') }}
            </div>
            <p class="mt-0.5 text-[11px] leading-snug text-slate-500 dark:text-slate-400">
              {{ selectionModeHint }}
            </p>
          </div>
        </div>
        <span
          v-if="modelValue.length > 0"
          class="shrink-0 rounded-full bg-[#8B1A1A]/10 px-2.5 py-0.5 text-[10px] font-bold tabular-nums text-[#8B1A1A] dark:bg-[#8B1A1A]/25 dark:text-[#e57373]"
        >
          {{ selectionSummary }}
        </span>
      </div>

      <div
        v-if="!isLoading"
        class="mt-2 flex flex-wrap items-center gap-2"
        role="group"
        :aria-label="t('trip_detail.coordination.internal_vehicle_strip_mode_aria')"
      >
        <button
          type="button"
          class="rounded-lg px-2.5 py-1 text-[11px] font-semibold transition disabled:cursor-not-allowed disabled:opacity-45"
          :class="selectionMode === 'single' ? modeActiveClass : modeIdleClass"
          :disabled="disabled"
          data-testid="dispatch-internal-vehicle-mode-single"
          @click="setSelectionMode('single')"
        >
          {{ t('trip_detail.coordination.internal_vehicle_strip_mode_single') }}
        </button>
        <button
          type="button"
          class="rounded-lg px-2.5 py-1 text-[11px] font-semibold transition disabled:cursor-not-allowed disabled:opacity-45"
          :class="selectionMode === 'multiple' ? modeActiveClass : modeIdleClass"
          :disabled="disabled"
          data-testid="dispatch-internal-vehicle-mode-multiple"
          @click="setSelectionMode('multiple')"
        >
          {{ t('trip_detail.coordination.internal_vehicle_strip_mode_multiple') }}
        </button>
      </div>
    </div>

    <div class="space-y-2 px-3 py-3">
      <div v-if="isLoading" class="h-24 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800/60" />

      <template v-else>
        <div v-if="selectionMode === 'single'" class="space-y-2">
          <label class="sr-only" :for="nativeSelectId">{{ t('trip_detail.coordination.resource_native_pick_vehicle') }}</label>
          <select
            :id="nativeSelectId"
            class="w-full appearance-none rounded-xl border border-transparent bg-slate-100/90 py-2.5 pl-3 pr-8 text-[12px] font-normal text-slate-800 shadow-inner shadow-slate-900/5 outline-none focus:border-slate-300 focus:bg-white focus:shadow-md disabled:cursor-not-allowed disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:focus:border-slate-600 dark:focus:bg-slate-900"
            :disabled="disabled"
            :value="nativeBoundId"
            data-testid="dispatch-internal-vehicle-native-select"
            @change="onNativeSelectChange"
          >
            <option value="">{{ t('trip_detail.coordination.resource_native_pick_vehicle') }}</option>
            <option
              v-for="opt in options"
              :key="opt.id"
              :value="String(opt.id)"
              :disabled="opt.available === false"
            >
              {{ nativeOptionLine(opt) }}
            </option>
          </select>
        </div>

        <div v-else class="relative">
          <label class="sr-only" :for="searchId">{{ t('trip_detail.coordination.resource_section_internal_ph') }}</label>
          <input
            :id="searchId"
            v-model="searchQuery"
            type="search"
            class="w-full rounded-xl border border-transparent bg-slate-100/90 py-2.5 pl-3 pr-2 text-[12px] text-slate-800 shadow-inner shadow-slate-900/5 outline-none placeholder:text-slate-400 focus:border-slate-300 focus:bg-white focus:ring-2 focus:ring-[#8B1A1A]/15 disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:focus:border-slate-600"
            :disabled="disabled"
            :placeholder="t('trip_detail.coordination.resource_section_internal_ph')"
            data-testid="dispatch-internal-vehicle-search"
            autocomplete="off"
            @focus="onSearchFocus"
            @blur="onBlur"
            @keydown.enter.prevent="onEnterKey"
          />

          <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="-translate-y-1 opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="-translate-y-1 opacity-0"
          >
            <div
              v-if="isOpen"
              class="absolute left-0 right-0 top-full z-50 mt-1 max-h-[240px] overflow-y-auto rounded-xl border border-slate-200/80 bg-white py-1 shadow-lg shadow-slate-900/15 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/40"
              role="listbox"
              :aria-label="t('trip_detail.coordination.resource_section_internal_title')"
            >
              <template v-if="filteredOptions.length > 0">
                <button
                  v-for="opt in filteredOptions"
                  :key="opt.id"
                  type="button"
                  role="option"
                  class="mx-0.5 mb-0.5 flex w-[calc(100%-0.25rem)] items-start gap-2 rounded-lg px-2.5 py-2 text-left transition disabled:cursor-not-allowed disabled:opacity-55"
                  :class="dropdownOptionClass(opt)"
                  :disabled="disabled || opt.available === false || isSelected(opt)"
                  :aria-selected="isSelected(opt)"
                  :data-testid="`dispatch-internal-vehicle-${opt.id}`"
                  @mousedown.prevent="selectOption(opt)"
                >
                  <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                      <span class="text-[13px] font-bold tracking-tight text-slate-900 dark:text-slate-50">
                        {{ plateOf(opt) }}
                      </span>
                      <span
                        v-if="seatCountOf(opt) > 0"
                        class="shrink-0 rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold tabular-nums text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                      >
                        {{ t('trip_detail.coordination.seats_n', { n: seatCountOf(opt) }) }}
                      </span>
                    </div>
                    <span class="mt-0.5 block text-[11px] leading-snug text-slate-600 dark:text-slate-400">
                      {{ typeOf(opt) }}
                    </span>
                  </div>
                  <span
                    v-if="opt.available === false"
                    class="shrink-0 rounded-md bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold text-rose-700 dark:bg-rose-900/40 dark:text-rose-300"
                  >
                    {{ t('trip_detail.coordination.resource_busy_badge') }}
                  </span>
                  <span
                    v-else-if="isSelected(opt)"
                    class="shrink-0 text-[13px] font-medium text-[#8B1A1A] dark:text-[#e57373]"
                    aria-hidden="true"
                  >
                    ✓
                  </span>
                </button>
              </template>
              <p
                v-else
                class="rounded-lg bg-slate-50 px-3 py-2.5 text-center text-[12px] leading-snug text-slate-500 dark:bg-slate-800/50 dark:text-slate-400"
              >
                {{ t('trip_detail.coordination.internal_vehicle_strip_empty') }}
              </p>
            </div>
          </Transition>
        </div>

        <div
          v-if="selectionMode === 'multiple' && modelValue.length > 0"
          class="flex flex-wrap gap-1.5 pt-0.5"
        >
          <div
            v-for="item in modelValue"
            :key="item.id"
            class="inline-flex max-w-full items-center gap-1 rounded-xl bg-[#8B1A1A]/[0.08] px-2 py-1 text-[12px] font-medium text-slate-800 dark:bg-[#8B1A1A]/20 dark:text-slate-100"
          >
            <span class="min-w-0 truncate">{{ chipLabel(item) }}</span>
            <button
              type="button"
              class="shrink-0 px-0.5 text-[14px] leading-none text-slate-500 hover:text-[#8B1A1A] disabled:cursor-not-allowed disabled:opacity-45 dark:text-slate-400 dark:hover:text-[#e57373]"
              :disabled="disabled"
              :aria-label="t('trip_detail.coordination.internal_vehicle_strip_remove_aria', { plate: chipLabel(item) })"
              :data-testid="`dispatch-internal-vehicle-remove-${item.id}`"
              @click="remove(item)"
            >
              ×
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, useId, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { TruckIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: { type: Array, required: true },
  options: { type: Array, required: true },
  isLoading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()
const searchId = useId()
const nativeSelectId = useId()
const searchQuery = ref('')
const isOpen = ref(false)
const selectionMode = ref('multiple')

const modeActiveClass =
  'bg-[#8B1A1A]/12 text-[#8B1A1A] ring-1 ring-[#8B1A1A]/25 dark:bg-[#8B1A1A]/25 dark:text-[#e57373] dark:ring-[#a82828]/35'
const modeIdleClass =
  'bg-white text-slate-600 shadow-sm shadow-slate-900/5 hover:bg-slate-50 dark:bg-slate-800/70 dark:text-slate-300 dark:hover:bg-slate-800'

const selectionModeHint = computed(() =>
  selectionMode.value === 'single'
    ? t('trip_detail.coordination.internal_vehicle_strip_hint_single')
    : t('trip_detail.coordination.internal_vehicle_strip_hint_multiple'),
)

function normalizeSearchText(s) {
  return String(s ?? '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[\s·.,\-_/|()]/g, '')
}

const filteredOptions = computed(() => {
  const qRaw = searchQuery.value.trim()
  const list = props.options || []
  if (!qRaw) return list
  const q = normalizeSearchText(qRaw)
  if (!q) return list
  return list.filter((opt) => {
    const label = normalizeSearchText(opt.label)
    const sub = normalizeSearchText(opt.sublabel)
    const plate = normalizeSearchText(plateOf(opt))
    const type = normalizeSearchText(typeOf(opt))
    return label.includes(q) || sub.includes(q) || plate.includes(q) || type.includes(q)
  })
})

const totalSelectedSeats = computed(() =>
  props.modelValue.reduce((acc, v) => acc + seatCountOf(v), 0),
)

const selectionSummary = computed(() =>
  t('trip_detail.coordination.internal_vehicle_strip_summary', {
    n: props.modelValue.length,
    seats: totalSelectedSeats.value,
  }),
)

const nativeBoundId = computed(() => {
  const v = props.modelValue[0]
  return v ? String(v.id) : ''
})

watch(
  () => props.modelValue.length,
  (len) => {
    if (len <= 1 && selectionMode.value === 'multiple' && len === 1) return
    if (len > 1) selectionMode.value = 'multiple'
  },
  { immediate: true },
)

function plateOf(opt) {
  const s = String(opt.label ?? '')
  const i = s.indexOf(' · ')
  return i >= 0 ? s.slice(0, i).trim() : s.trim()
}

function typeOf(opt) {
  const s = String(opt.label ?? '')
  const i = s.indexOf(' · ')
  if (i < 0) return opt.sublabel?.trim() || '—'
  return s.slice(i + 3).replace(/\s*\(\d+\)\s*$/, '').trim() || '—'
}

function seatCountOf(opt) {
  const n = Number(opt.seatCount)
  if (Number.isFinite(n) && n > 0) return Math.floor(n)
  const m = String(opt.label ?? '').match(/\((\d+)\)\s*$/)
  if (m) return Number(m[1]) || 0
  return 0
}

function chipLabel(item) {
  const plate = plateOf(item)
  const seats = seatCountOf(item)
  if (seats > 0) return `${plate} · ${t('trip_detail.coordination.seats_n', { n: seats })}`
  return plate
}

function nativeOptionLine(opt) {
  const plate = plateOf(opt)
  const type = typeOf(opt)
  const seats = seatCountOf(opt)
  const seatPart = seats > 0 ? ` · ${t('trip_detail.coordination.seats_n', { n: seats })}` : ''
  const busy = opt.available === false ? ` (${t('trip_detail.coordination.resource_busy_badge')})` : ''
  const typePart = type && type !== '—' ? ` — ${type}` : ''
  return `${plate}${typePart}${seatPart}${busy}`
}

function isSelected(opt) {
  return props.modelValue.some((v) => String(v.id) === String(opt.id))
}

function dropdownOptionClass(opt) {
  if (opt.available === false) {
    return 'cursor-not-allowed text-slate-400 dark:text-slate-500'
  }
  if (isSelected(opt)) {
    return 'cursor-default bg-[#8B1A1A]/[0.06] dark:bg-[#8B1A1A]/15'
  }
  return 'text-slate-900 hover:bg-slate-100/80 dark:text-slate-100 dark:hover:bg-slate-800/90'
}

function setSelectionMode(mode) {
  if (props.disabled || selectionMode.value === mode) return
  selectionMode.value = mode
  searchQuery.value = ''
  isOpen.value = false
  if (mode === 'single' && props.modelValue.length > 1) {
    emit('update:modelValue', [props.modelValue[0]])
  }
}

function onNativeSelectChange(ev) {
  if (props.disabled) return
  const raw = ev.target.value
  if (!raw) {
    emit('update:modelValue', [])
    return
  }
  const opt = (props.options || []).find((x) => String(x.id) === String(raw))
  if (!opt || opt.available === false) return
  emit('update:modelValue', [{ ...opt }])
}

function selectOption(opt) {
  if (props.disabled || opt.available === false || isSelected(opt)) return
  if (selectionMode.value === 'single') {
    emit('update:modelValue', [{ ...opt }])
  } else {
    emit('update:modelValue', [...props.modelValue, { ...opt }])
  }
  searchQuery.value = ''
  isOpen.value = false
}

function remove(opt) {
  if (props.disabled) return
  emit(
    'update:modelValue',
    props.modelValue.filter((v) => String(v.id) !== String(opt.id)),
  )
}

function onEnterKey() {
  if (props.disabled) return
  const selectable = filteredOptions.value.filter(
    (o) => o.available !== false && !isSelected(o),
  )
  if (selectable.length === 1) selectOption(selectable[0])
}

function onSearchFocus() {
  if (props.disabled) return
  isOpen.value = true
}

function onBlur() {
  setTimeout(() => {
    isOpen.value = false
  }, 150)
}
</script>
