<template>
  <div
    class="mb-0"
  >
    <div
      class="flex items-center justify-between gap-2 rounded-t-2xl border-b border-slate-200/70 bg-slate-50 px-3 py-2.5 dark:border-slate-800 dark:bg-slate-900/40"
    >
      <div class="flex min-w-0 flex-1 items-center gap-2.5">
        <div
          v-if="icon"
          class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-800/90 dark:text-slate-400"
        >
          <component :is="icon" class="size-3.5" aria-hidden="true" />
        </div>
        <div class="min-w-0">
          <div class="text-[12px] font-semibold tracking-tight text-slate-800 dark:text-slate-100">{{ title }}</div>
          <p
            v-if="subtitle"
            class="mt-0.5 text-[11px] font-normal leading-snug text-slate-500 dark:text-slate-400"
          >
            {{ subtitle }}
          </p>
        </div>
      </div>
      <span
        v-if="modelValue.length > 0"
        class="shrink-0 rounded-full bg-slate-200/80 px-2 py-0.5 text-[10px] font-bold tabular-nums text-slate-600 dark:bg-slate-700/80 dark:text-slate-300"
      >
        {{ modelValue.length }}
      </span>
    </div>

    <div class="space-y-2 rounded-b-2xl bg-white px-3 pb-2.5 pt-2 dark:bg-slate-950/30">
      <slot v-if="bodySlotBeforeNative" name="body-before-search" />

      <div
        v-if="showNativeSelect && !isLoading"
        class="space-y-2"
        :class="indentSearch ? 'ml-[38px]' : ''"
      >
        <label class="sr-only" :for="nativeSelectId">{{ nativeSelectAriaLabel }}</label>
        <select
          :id="nativeSelectId"
          class="w-full appearance-none rounded-xl border border-transparent bg-slate-100/90 py-2 pl-3 pr-8 text-[12px] font-normal text-slate-800 shadow-inner shadow-slate-900/5 outline-none focus:border-slate-300 focus:bg-white focus:shadow-md disabled:cursor-not-allowed disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:focus:border-slate-600 dark:focus:bg-slate-900 dark:focus:shadow-black/35"
          :disabled="disabled"
          :value="nativeBoundId"
          :aria-label="nativeSelectAriaLabel"
          @change="onNativeSelectChange"
        >
          <option value="">{{ effectiveNativePlaceholder }}</option>
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

      <slot
        v-if="!bodySlotBeforeNative && showNativeSelect"
        name="body-before-search"
      />

      <div
        v-if="!showNativeSelect && !isLoading"
        class="relative flex gap-2"
        :class="indentSearch ? 'ml-[38px]' : ''"
      >
        <div class="relative min-w-0 flex-1">
          <input
            v-model="searchQuery"
            type="text"
            class="w-full rounded-xl bg-slate-100/90 py-2 pl-3 pr-2 text-[12px] font-normal text-slate-800 shadow-inner shadow-slate-900/5 outline-none placeholder:text-slate-400 focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50 dark:focus:bg-slate-900 dark:focus:shadow-black/35"
            :disabled="disabled"
            :placeholder="placeholder"
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
              class="absolute left-0 right-0 top-full z-50 mt-1 max-h-[240px] overflow-y-auto rounded-xl bg-white py-1 shadow-lg shadow-slate-900/15 dark:bg-slate-900 dark:shadow-black/40"
            >
              <template v-if="filteredOptions.length > 0">
                <button
                  v-for="opt in filteredOptions"
                  :key="opt.id"
                  type="button"
                  class="mx-0.5 mb-0.5 flex w-full items-start gap-2 rounded-lg px-2.5 py-2 text-left text-slate-900 hover:bg-slate-100/80 disabled:cursor-not-allowed disabled:opacity-55 dark:text-slate-100 dark:hover:bg-slate-800/90"
                  :class="isSelected(opt) ? 'cursor-default bg-rose-50/80 dark:bg-rose-950/30' : ''"
                  :disabled="disabled || opt.available === false || isSelected(opt)"
                  @mousedown.prevent="select(opt)"
                >
                  <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                      <span
                        class="text-[13px] font-medium"
                        :class="optionLabelClass(opt)"
                      >{{ opt.label }}</span>
                      <span
                        v-if="opt.sublabel"
                        class="shrink-0 text-[11px] text-slate-500 dark:text-slate-400"
                      >{{ opt.sublabel }}</span>
                    </div>
                    <slot name="option-extra" :option="opt" />
                  </div>
                  <span
                    v-if="opt.available === false"
                    class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300"
                  >
                    {{ busyLabel }}
                  </span>
                  <span v-if="isSelected(opt)" class="text-[13px] font-medium text-[#8B1A1A] dark:text-[#e57373]">✓</span>
                </button>
              </template>
              <div v-else class="rounded-lg bg-slate-50 px-3 py-2.5 text-center text-[12px] leading-snug text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                {{ emptySearchLabel }}
              </div>
            </div>
          </Transition>
        </div>

        <input
          v-if="showSupplementSeats"
          v-model="seatDraft"
          type="number"
          min="0"
          step="1"
          class="w-[4.25rem] shrink-0 rounded-xl bg-slate-100/90 px-1 py-1.5 text-center text-[12px] font-normal tabular-nums text-slate-900 shadow-inner shadow-slate-900/5 outline-none focus:bg-white focus:shadow-md disabled:opacity-55 dark:bg-slate-800/85 dark:text-slate-50"
          :disabled="disabled"
          :aria-label="supplementSeatsAria"
          :placeholder="supplementSeatsPlaceholder"
          inputmode="numeric"
        />
      </div>

      <div v-if="isLoading" class="h-8 animate-pulse rounded-lg bg-slate-100 dark:bg-slate-800/60" />

      <div v-if="modelValue.length > 0 && !isLoading" class="flex flex-wrap gap-1.5 pt-0.5">
        <div
          v-for="item in modelValue"
          :key="item.id"
          class="inline-flex max-w-full items-center gap-1 rounded-xl bg-slate-100/95 px-2 py-1 text-[12px] font-medium text-slate-800 shadow-sm dark:bg-slate-800/80 dark:text-slate-100"
        >
          <span class="min-w-0 truncate">{{ chipLabel(item) }}</span>
          <button
            type="button"
            class="shrink-0 px-0.5 text-[14px] leading-none text-slate-500 hover:text-[#8B1A1A] disabled:cursor-not-allowed disabled:opacity-45 dark:text-slate-400 dark:hover:text-[#e57373]"
            :disabled="disabled"
            :aria-label="t('trip_detail.coordination.supplement_remove_aria')"
            @click="remove(item)"
          >
            ×
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, useId } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  modelValue: { type: Array, required: true },
  options: { type: Array, required: true },
  title: { type: String, required: true },
  subtitle: { type: String, required: true },
  placeholder: { type: String, required: true },
  isLoading: { type: Boolean, default: false },
  icon: { type: [Object, Function], default: null },
  multiple: { type: Boolean, default: true },
  allowCustomEntry: { type: Boolean, default: false },
  optionLabelClassFn: { type: Function, default: null },
  indentSearch: { type: Boolean, default: false },
  /** Ô số chỗ khi chọn xe (mỗi dòng có thêm bổ sung chỗ). */
  showSupplementSeats: { type: Boolean, default: false },
  /** Hiển thị thẻ select đủ lựa chọn (phân bổ xe/tài xế nội bộ). */
  showNativeSelect: { type: Boolean, default: false },
  nativeSelectPlaceholder: { type: String, default: '' },
  /** false: với select nội bộ, slot (vd. nút lịch tài xế) nằm dưới select */
  bodySlotBeforeNative: { type: Boolean, default: true },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()
const nativeSelectId = useId()
const supplementSeatsPlaceholder = computed(() => t('trip_detail.coordination.supplement_seats_ph'))

const searchQuery = ref('')
const seatDraft = ref('')
const isOpen = ref(false)

const busyLabel = computed(() => t('trip_detail.coordination.resource_busy_badge'))
const emptySearchLabel = computed(() => t('trip_detail.coordination.resource_search_empty'))
const nativeBoundId = computed(() => {
  const v = props.modelValue[0]
  return v ? String(v.id) : ''
})
const effectiveNativePlaceholder = computed(() => {
  const p = props.nativeSelectPlaceholder?.trim()
  if (p) return p
  return t('trip_detail.coordination.resource_native_pick_default')
})
const nativeSelectAriaLabel = computed(() => props.title)
const customAddHint = computed(() =>
  t('trip_detail.coordination.resource_press_enter_add', { name: searchQuery.value.trim() }),
)

/** Gom ký tự để tìm biển số / tên dễ khớp (51F-123.45 ↔ 51f 12345). */
function normalizeSearchText(s) {
  return String(s ?? '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[\s·.,\-_/|]/g, '')
}

function optionLabelClass(opt) {
  const fn = props.optionLabelClassFn
  if (typeof fn !== 'function') return undefined
  return fn(opt) || undefined
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
    return label.includes(q) || sub.includes(q)
  })
})

function isSelected(opt) {
  return props.modelValue.some((v) => String(v.id) === String(opt.id))
}

function parseSeatDraft() {
  if (!props.showSupplementSeats) return undefined
  const n = Number(String(seatDraft.value).trim())
  if (!Number.isFinite(n) || n <= 0) return undefined
  return Math.floor(n)
}

function enrichItem(raw) {
  const seats = parseSeatDraft()
  const next = { ...raw }
  if (seats != null) next.supplementSeats = seats
  else delete next.supplementSeats
  return next
}

function nativeOptionLine(opt) {
  const main = String(opt.label ?? '')
  const sub = opt.sublabel ? ` — ${opt.sublabel}` : ''
  const busy = opt.available === false ? ` (${busyLabel.value})` : ''
  return `${main}${sub}${busy}`
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
  emit('update:modelValue', [enrichItem(opt)])
}

function chipLabel(item) {
  const n = Number(item.supplementSeats)
  if (Number.isFinite(n) && n > 0)
    return `${item.label} — ${t('trip_detail.coordination.seats_n', { n })}`
  return String(item.label ?? '')
}

function select(opt) {
  if (props.disabled) return
  if (opt.available === false || isSelected(opt)) return
  const toAdd = enrichItem(opt)
  if (props.multiple) emit('update:modelValue', [...props.modelValue, toAdd])
  else emit('update:modelValue', [toAdd])
  searchQuery.value = ''
  seatDraft.value = ''
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
  const q = searchQuery.value.trim()
  if (q) {
    const selectable = filteredOptions.value.filter(
      (o) => o.available !== false && !isSelected(o),
    )
    if (selectable.length === 1) {
      select(selectable[0])
      return
    }
  }
  if (!props.allowCustomEntry) return
  if (!q) return
  const qNorm = normalizeSearchText(q)
  const exactLabel = filteredOptions.value.filter(
    (o) =>
      o.available !== false &&
      !isSelected(o) &&
      normalizeSearchText(String(o.label ?? '')) === qNorm,
  )
  if (exactLabel.length === 1) {
    select(exactLabel[0])
    return
  }
  const qLower = q.toLowerCase()
  if (
    props.modelValue.some((v) => v.isCustom === true && String(v.label).toLowerCase() === qLower)
  ) {
    searchQuery.value = ''
    isOpen.value = false
    return
  }
  const id = `custom:${Date.now()}-${Math.random().toString(36).slice(2, 9)}`
  const seats = parseSeatDraft()
  const item = { id, label: q, available: true, isCustom: true }
  if (seats != null) item.supplementSeats = seats
  if (props.multiple) emit('update:modelValue', [...props.modelValue, item])
  else emit('update:modelValue', [item])
  searchQuery.value = ''
  seatDraft.value = ''
  isOpen.value = false
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
