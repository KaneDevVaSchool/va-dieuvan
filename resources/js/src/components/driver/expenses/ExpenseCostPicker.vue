<template>
  <div v-if="open" class="fixed inset-0 z-[70] flex items-end justify-center bg-black/60 sm:items-center" @click.self="$emit('close')">
    <div
      class="flex max-h-[85dvh] w-full max-w-lg flex-col rounded-t-[1.5rem] bg-driver-card pb-[env(safe-area-inset-bottom)] ring-1 ring-white/10 sm:rounded-[1.5rem]"
      role="dialog"
      aria-modal="true"
      :aria-label="t('expense.picker_title')"
    >
      <div class="flex items-center justify-between gap-2 border-b border-white/10 px-4 py-3">
        <h2 class="text-base font-bold">{{ t('expense.picker_title') }}</h2>
        <button type="button" class="flex h-10 w-10 items-center justify-center rounded-xl ring-1 ring-white/10" :aria-label="t('expense.close')" @click="$emit('close')">
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>
      <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3">
        <p v-if="loading" class="text-sm text-driver-muted">{{ t('expense.loading') }}</p>
        <p v-else-if="!candidates.length" class="text-sm text-driver-muted">{{ t('expense.picker_empty') }}</p>
        <ul class="space-y-2">
          <li v-for="c in candidates" :key="c.id">
            <label class="flex min-h-[56px] items-center gap-3 rounded-xl bg-driver-surface px-3 py-2 ring-1 ring-white/[0.06]">
              <input v-model="selected" type="checkbox" :value="c.id" class="h-5 w-5 shrink-0 accent-[#7fdcc8]" />
              <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold">{{ t(`expense.cost_type_${c.type}`, c.type) }} · {{ c.license_plate || '—' }}</span>
                <span class="block truncate text-xs text-driver-muted">{{ formatDate(c.reported_on) }} {{ c.description ? `· ${c.description}` : '' }}</span>
              </span>
              <span class="shrink-0 text-sm font-bold tabular-nums">{{ formatVndCurrency(c.amount) }}</span>
            </label>
          </li>
        </ul>
      </div>
      <div class="border-t border-white/10 px-4 py-3">
        <button
          type="button"
          class="flex min-h-[52px] w-full items-center justify-center rounded-xl bg-driver-accent text-base font-bold text-driver-bg disabled:opacity-45"
          :disabled="!selected.length"
          @click="confirm"
        >
          {{ t('expense.picker_add', { n: selected.length }) }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { listExpenseCostCandidates } from '../../../api/expenses'
import { formatVndCurrency } from '../../../util/money'

/**
 * Chọn chi phí đã khai (trip_costs) chưa nằm trong hồ sơ cùng loại để đưa vào hồ sơ.
 */
const props = defineProps({
  open: { type: Boolean, default: false },
  docType: { type: String, required: true },
  vehicleId: { type: [Number, String], default: null },
  documentId: { type: [Number, String], default: null },
  excludeIds: { type: Array, default: () => [] },
})
const emit = defineEmits(['close', 'pick'])
const { t } = useI18n()

const loading = ref(false)
const candidates = ref([])
const selected = ref([])

function formatDate(ymd) {
  return ymd ? new Date(`${ymd}T00:00:00`).toLocaleDateString('vi-VN') : ''
}

async function load() {
  loading.value = true
  selected.value = []
  try {
    const res = await listExpenseCostCandidates({
      doc_type: props.docType,
      vehicle_id: props.vehicleId || undefined,
      document_id: props.documentId || undefined,
    })
    const skip = new Set(props.excludeIds.map(Number))
    candidates.value = (res.items ?? []).filter((c) => !skip.has(Number(c.id)))
  } catch {
    candidates.value = []
  } finally {
    loading.value = false
  }
}

function confirm() {
  const picked = candidates.value.filter((c) => selected.value.includes(c.id))
  emit('pick', picked)
  emit('close')
}

watch(
  () => props.open,
  (v) => {
    if (v) void load()
  },
)
</script>
