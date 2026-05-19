<template>
  <div class="min-w-0">
    <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-600">
      {{ label }}
      <span v-if="hint" class="ml-1 font-normal normal-case text-slate-500">{{ hint }}</span>
    </label>
    <textarea
      v-if="multiline"
      readonly
      tabindex="-1"
      rows="2"
      class="mt-1 w-full resize-none rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm leading-snug text-slate-900"
      :value="display"
    />
    <input
      v-else
      type="text"
      readonly
      tabindex="-1"
      class="mt-1 w-full rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-900"
      :class="compact ? 'py-1.5 text-xs' : ''"
      :value="display"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  label: { type: String, required: true },
  hint: { type: String, default: '' },
  /** Field value — empty shows em dash */
  modelValue: { type: String, default: '' },
  multiline: { type: Boolean, default: false },
  compact: { type: Boolean, default: false },
})

const display = computed(() => {
  const s = props.modelValue == null ? '' : String(props.modelValue).trim()
  return s !== '' ? s : '—'
})
</script>
