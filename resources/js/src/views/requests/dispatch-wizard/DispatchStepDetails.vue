<template>
  <div class="dw-schedule-details flex flex-col gap-6">
    <div
      v-if="rows.length === 0"
      class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-slate-200/70 bg-slate-50/80 px-5 py-14 text-center sm:py-16"
    >
      <div class="text-4xl text-slate-300" aria-hidden="true">📅</div>
      <p class="text-sm text-slate-600">{{ t('dispatch_wizard.s3.empty_schedules') }}</p>
      <button
        type="button"
        class="mt-1 rounded-xl bg-va-800 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-va-900"
        @click="addFirstRow"
      >
        {{ t('dispatch_wizard.s3.empty_add_first') }}
      </button>
    </div>

    <template v-else>
      <DispatchItemCard
        v-for="(row, idx) in rows"
        :key="row.id ?? idx"
        :row="row"
        :index="idx"
        :variant="variant"
        @remove="removeAt(idx)"
        @duplicate="duplicateAt(idx)"
        @autofill="autofillAt(idx)"
      />
    </template>

    <DispatchToolbar
      v-if="rows.length > 0"
      :total="grandTotal"
      :item-count="rows.length"
      :locale-tag="localeTag"
      @add="appendRow"
    />

    <div v-if="$slots['toolbar-extra'] && rows.length > 0" class="-mt-2">
      <slot name="toolbar-extra" />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import DispatchItemCard from './DispatchItemCard.vue'
import DispatchToolbar from './DispatchToolbar.vue'
import {
  appendScheduleRow,
  autoFillScheduleRowFromPrevious,
  duplicateScheduleRow,
  ensureScheduleRowIds,
  removeScheduleRowAt,
} from '../../../composables/useDispatchItems'
import { rowsHaveNoInlineErrors } from '../../../composables/dispatchScheduleRowErrors'
import { parseMoneyVnd } from '../../../util/money'

const props = defineProps({
  modelValue: { type: Array, required: true },
  variant: {
    type: String,
    required: true,
    validator: (v) => ['passenger', 'business', 'cargo'].includes(v),
  },
})

const emit = defineEmits(['update:modelValue', 'update:valid'])

const { t, locale } = useI18n()

const rows = computed(() => props.modelValue)

const localeTag = computed(() => (locale.value === 'en' ? 'en-US' : 'vi-VN'))

const grandTotal = computed(() => {
  const list = props.modelValue
  if (props.variant === 'cargo') {
    return list.reduce((s, r) => s + parseMoneyVnd(r.cost), 0)
  }
  return list.reduce((s, r) => s + parseMoneyVnd(r.unit_price) + parseMoneyVnd(r.extra_fee), 0)
})

/** Danh sách rỗng không chặn Next (door có thể chỉ điền e.1 hoặc chỉ e.2); có dòng thì phải hợp lệ. */
const schedulesValid = computed(() => {
  if (!props.modelValue.length) return true
  return rowsHaveNoInlineErrors(props.modelValue, props.variant)
})

watch(
  schedulesValid,
  (v) => {
    emit('update:valid', v)
  },
  { immediate: true },
)

watch(
  () => props.modelValue,
  () => ensureScheduleRowIds(props.modelValue),
  { deep: true },
)

onMounted(() => {
  ensureScheduleRowIds(props.modelValue)
})

function emitRows() {
  emit('update:modelValue', props.modelValue)
}

function addFirstRow() {
  appendScheduleRow(props.modelValue, props.variant)
  emitRows()
}

function appendRow() {
  appendScheduleRow(props.modelValue, props.variant)
  emitRows()
}

function removeAt(idx) {
  removeScheduleRowAt(props.modelValue, idx)
  emitRows()
}

function duplicateAt(idx) {
  duplicateScheduleRow(props.modelValue, idx)
  emitRows()
}

function autofillAt(idx) {
  autoFillScheduleRowFromPrevious(props.modelValue, idx, props.variant)
  emitRows()
}
</script>
