<template>
  <div>
    <div v-if="canEdit" class="flex flex-wrap items-center gap-2" :class="mobile ? 'w-full' : ''">
      <label class="sr-only" :for="inputId">{{ t('request_detail.passenger_count_actual_label') }}</label>
      <input
        :id="inputId"
        :value="draft"
        type="number"
        min="1"
        max="999"
        class="h-9 w-20 rounded-lg border border-violet-200 bg-white px-2 text-sm font-semibold tabular-nums text-slate-900 shadow-sm ring-1 ring-violet-100 focus:outline-none focus:ring-2 focus:ring-teal-500/40"
        :disabled="saving"
        @input="$emit('update:draft', Math.round(Number($event.target.value) || 0))"
        @keydown.enter.prevent="$emit('save')"
      />
      <button
        type="button"
        class="inline-flex h-9 items-center rounded-lg bg-teal-600 px-3 text-xs font-semibold text-white shadow-sm hover:bg-teal-500 disabled:opacity-50"
        :disabled="saving"
        @click="$emit('save')"
      >
        <span
          v-if="saving"
          class="mr-1.5 inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"
          aria-hidden="true"
        />
        {{ saving ? saveBusyText : saveText }}
      </button>
    </div>
    <div v-else>
      <p class="text-sm font-semibold tabular-nums text-slate-800">
        {{ displayCount }}
      </p>
      <p v-if="lockHint" class="mt-0.5 text-[11px] leading-snug text-slate-500">{{ lockHint }}</p>
    </div>
    <p v-if="error" class="mt-1 text-xs font-medium text-rose-600">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  req: { type: Object, required: true },
  draft: { type: Number, default: 1 },
  saving: { type: Boolean, default: false },
  error: { type: String, default: '' },
  canEdit: { type: Boolean, default: false },
  lockHint: { type: String, default: '' },
  mobile: { type: Boolean, default: false },
  saveLabelKey: { type: String, default: '' },
  saveBusyLabelKey: { type: String, default: '' },
})

defineEmits(['update:draft', 'save'])

const { t } = useI18n()

const saveText = computed(() =>
  props.saveLabelKey ? t(props.saveLabelKey) : t('request_detail.passenger_save'),
)
const saveBusyText = computed(() =>
  props.saveBusyLabelKey ? t(props.saveBusyLabelKey) : t('request_detail.passenger_save_busy'),
)

const inputId = computed(() => `extracurricular-sc-${props.req?.id}`)

const displayCount = computed(() => {
  const raw = props.req?.student_count_actual ?? props.req?.passenger_count
  return Math.max(1, Math.min(999, Math.round(Number(raw) || 1)))
})
</script>
