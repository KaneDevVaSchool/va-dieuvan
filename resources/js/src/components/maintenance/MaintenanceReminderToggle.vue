<template>
  <div class="space-y-3 rounded-2xl bg-[#111d16] px-4 py-4 ring-1 ring-white/8">
    <div class="flex items-center justify-between gap-3">
      <span class="text-base font-semibold text-white">{{ t('driver_maintenance.reminder_toggle_label') }}</span>
      <!-- Toggle switch -->
      <button
        type="button"
        role="switch"
        :aria-checked="enabled"
        :class="[
          'relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none',
          enabled ? 'bg-[#1dbc5e]' : 'bg-white/20',
        ]"
        @click="enabled = !enabled"
      >
        <span
          :class="[
            'pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow transition duration-200',
            enabled ? 'translate-x-5' : 'translate-x-0',
          ]"
        />
      </button>
    </div>

    <Transition name="fade">
      <div v-if="enabled" class="flex items-center gap-2">
        <span class="shrink-0 text-xs text-white/50">{{ t('driver_maintenance.reminder_days_label') }}</span>
        <div class="flex flex-1 gap-1.5">
          <button
            v-for="d in [7, 14, 30, 60]"
            :key="d"
            type="button"
            :class="[
              'flex-1 rounded-full py-1 text-xs font-semibold transition',
              selectedDays === d
                ? 'bg-[#7fdcc8] text-[#0a0f0d]'
                : 'bg-white/10 text-white/60 hover:bg-white/20',
            ]"
            @click="selectedDays = d"
          >
            {{ t(`driver_maintenance.reminder_days_${d}`) }}
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  modelEnabled: { type: Boolean, default: false },
  modelDays: { type: Number, default: 14 },
})

const emit = defineEmits(['update:modelEnabled', 'update:modelDays'])

const enabled = ref(props.modelEnabled)
const selectedDays = ref(props.modelDays ?? 14)

watch(enabled, (v) => emit('update:modelEnabled', v))
watch(selectedDays, (v) => emit('update:modelDays', v))
watch(() => props.modelEnabled, (v) => { enabled.value = v })
watch(() => props.modelDays, (v) => { if (v) selectedDays.value = v })
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s, transform 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
