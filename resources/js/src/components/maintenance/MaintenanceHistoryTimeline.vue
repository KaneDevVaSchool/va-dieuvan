<template>
  <div class="space-y-0">
    <!-- Collapsible header -->
    <button
      type="button"
      class="flex w-full items-center justify-between gap-2 py-3"
      @click="open = !open"
    >
      <span class="text-base font-semibold text-white">{{ t('driver_maintenance.history_title') }}</span>
      <svg
        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
        :class="['h-5 w-5 text-white/40 transition-transform duration-200', open ? 'rotate-180' : '']"
        aria-hidden="true"
      >
        <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
      </svg>
    </button>

    <Transition name="slide">
      <div v-if="open" class="pb-2">
        <p v-if="!history.length" class="py-4 text-center text-sm text-white/40">
          {{ t('driver_maintenance.history_empty') }}
        </p>

        <ol v-else class="relative border-l border-[#7fdcc8]/20 ml-2 space-y-0">
          <li
            v-for="(row, idx) in history"
            :key="row.id ?? idx"
            class="pb-5 pl-5"
          >
            <!-- Dot -->
            <span class="absolute -left-1.5 mt-1.5 flex h-3 w-3 items-center justify-center rounded-full bg-[#0a0f0d] ring-2 ring-[#7fdcc8]/50">
              <span class="h-1.5 w-1.5 rounded-full bg-[#7fdcc8]" />
            </span>

            <div class="rounded-xl bg-[#111d16] px-3 py-2.5 ring-1 ring-white/8">
              <div class="flex items-start justify-between gap-2">
                <span class="text-sm font-semibold text-white">{{ actionLabel(row.action) }}</span>
                <span class="shrink-0 text-xs text-white/40">{{ row.created_at }}</span>
              </div>
              <p v-if="row.amount_paid" class="mt-0.5 text-sm text-[#7fdcc8]">
                {{ t('driver_maintenance.history_amount') }}: {{ formatMoney(row.amount_paid) }}
              </p>
              <p v-if="row.notes" class="mt-0.5 text-sm text-white/60">{{ row.notes }}</p>
              <p v-if="row.updated_by" class="mt-1 text-xs text-white/35">
                {{ t('driver_maintenance.history_by') }}: {{ row.updated_by }}
              </p>
            </div>
          </li>
        </ol>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  history: { type: Array, default: () => [] },
})

const open = ref(false)

function actionLabel(action) {
  const map = {
    renewed: t('driver_maintenance.history_action_renewed'),
    updated: t('driver_maintenance.history_action_updated'),
    noted: t('driver_maintenance.history_action_noted'),
  }
  return map[action] ?? action
}

function formatMoney(amount) {
  if (amount == null) return '—'
  return Number(amount).toLocaleString('vi-VN') + ' ₫'
}
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
  transition: opacity 0.2s, max-height 0.3s;
  overflow: hidden;
  max-height: 1000px;
}
.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  max-height: 0;
}
</style>
