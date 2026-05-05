<template>
  <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
    <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $t('trip_detail.timeline.title') }}</h2>
    <div class="mt-4 overflow-x-auto pb-2">
      <div class="flex min-w-[640px] items-start justify-between gap-2 px-1">
        <template v-for="(step, idx) in STEPS" :key="step.key">
          <div class="flex flex-1 flex-col items-center">
            <div class="relative flex w-full items-center">
              <div
                v-if="idx > 0"
                class="absolute right-1/2 top-[13px] h-0.5 w-1/2 -translate-y-1/2"
                :class="connectorLeftClass(idx)"
              />
              <div
                v-if="idx < STEPS.length - 1"
                class="absolute left-1/2 top-[13px] h-0.5 w-1/2 -translate-y-1/2"
                :class="connectorRightClass(idx)"
              />
              <div
                class="relative z-[1] mx-auto flex h-7 w-7 items-center justify-center rounded-full shadow-sm"
                :class="circleClass(idx)"
              >
                <PlusIcon v-if="step.icon === 'Plus'" class="h-3.5 w-3.5" />
                <CheckCircleIcon v-else-if="step.icon === 'CheckCircle'" class="h-3.5 w-3.5" />
                <TruckIcon v-else-if="step.icon === 'Car'" class="h-3.5 w-3.5" />
                <PlayCircleIcon v-else-if="step.icon === 'Navigation'" class="h-3.5 w-3.5" />
                <FlagIcon v-else class="h-3.5 w-3.5" />
              </div>
            </div>
            <div class="mt-2 text-center">
              <div class="text-[11px] font-medium leading-tight text-slate-800">{{ step.label }}</div>
              <div v-if="logFor(step.key)" class="mt-0.5 text-[10px] text-slate-500 tabular-nums">
                {{ fmtShort(logFor(step.key).created_at) }}
              </div>
              <div v-if="logFor(step.key)?.actor_name" class="text-[10px] italic text-slate-400">
                {{ logFor(step.key).actor_name }}
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  CheckCircleIcon,
  FlagIcon,
  PlayCircleIcon,
  PlusIcon,
  TruckIcon,
} from '@heroicons/vue/24/solid'

export type TimelineStep = {
  key: string
  label: string
  icon: 'Plus' | 'CheckCircle' | 'Car' | 'Navigation' | 'Flag'
}

export type TimelineLog = {
  status: string
  created_at: string
  actor_name: string
}

const STEPS: TimelineStep[] = [
  { key: 'created', label: 'Tạo yêu cầu', icon: 'Plus' },
  { key: 'approved', label: 'Phê duyệt', icon: 'CheckCircle' },
  { key: 'assigned', label: 'Gán xe & tài xế', icon: 'Car' },
  { key: 'running', label: 'Đang chạy', icon: 'Navigation' },
  { key: 'completed', label: 'Hoàn thành', icon: 'Flag' },
]

const props = defineProps<{
  currentStatus: string
  logs: TimelineLog[]
}>()

const { locale } = useI18n()

const currentStepIndex = computed(() => STEPS.findIndex((s) => s.key === props.currentStatus))

function logFor(key: string) {
  return props.logs.find((l) => l.status === key)
}

function fmtShort(iso: string) {
  const l = locale.value === 'en' ? 'en-US' : 'vi-VN'
  return iso ? new Date(iso).toLocaleString(l, { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) : ''
}

function circleClass(idx: number) {
  const cur = currentStepIndex.value
  if (idx < cur) return 'bg-green-500 text-white ring-2 ring-green-200'
  if (idx === cur) return 'trip-timeline-pulse bg-[#8B1A1A] text-white ring-2 ring-[#8B1A1A]/30'
  return 'border border-slate-300 bg-white text-slate-400 ring-2 ring-white'
}

function connectorLeftClass(idx: number) {
  const cur = currentStepIndex.value
  return idx <= cur ? 'bg-green-400' : 'border-t border-dashed border-slate-300 bg-transparent'
}

function connectorRightClass(idx: number) {
  const cur = currentStepIndex.value
  return idx < cur ? 'bg-green-400' : 'border-t border-dashed border-slate-300 bg-transparent'
}
</script>

<style scoped>
@keyframes pulse-ring {
  0% {
    box-shadow: 0 0 0 0 rgba(139, 26, 26, 0.35);
  }
  70% {
    box-shadow: 0 0 0 8px rgba(139, 26, 26, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(139, 26, 26, 0);
  }
}

.trip-timeline-pulse {
  animation: pulse-ring 2s ease-out infinite;
}
</style>
