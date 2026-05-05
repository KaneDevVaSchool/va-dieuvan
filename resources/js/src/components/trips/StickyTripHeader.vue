<template>
  <header
    class="sticky top-0 z-50 flex h-14 items-center gap-3 border-b border-slate-200 bg-white px-4 print:hidden dark:border-slate-700 dark:bg-slate-950"
  >
    <!-- Left zone: back + identity -->
    <div class="flex min-w-0 flex-1 items-center gap-2.5">
      <button
        type="button"
        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800"
        :aria-label="$t('trip_detail.header.back_trips')"
        @click="$emit('back')"
      >
        <ArrowLeftIcon class="h-4 w-4" />
      </button>
      <div class="min-w-0 truncate text-sm font-medium text-slate-900 dark:text-slate-100">
        <span class="tabular-nums text-slate-500 dark:text-slate-400">{{ reqCode }}</span>
        <span class="mx-2 text-slate-300 dark:text-slate-600">·</span>
        <span class="tabular-nums font-semibold text-slate-800 dark:text-slate-100">{{ tripCodeDisp }}</span>
        <span
          class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium align-middle"
          :class="badgeClass"
        >
          {{ labelTripStatus(trip.status) }}
        </span>
      </div>
      <span
        v-if="countdown"
        class="hidden shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 sm:inline-block dark:bg-slate-800 dark:text-slate-300"
      >
        {{ countdown }}
      </span>
    </div>

    <!-- Right zone: utility + actions -->
    <div class="flex shrink-0 items-center gap-1.5">
      <button
        type="button"
        class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800"
        :disabled="refreshing"
        :aria-label="$t('trip_detail.actions.refresh')"
        @click="$emit('refresh')"
      >
        <ArrowPathIcon class="h-4 w-4" :class="refreshing ? 'animate-spin' : ''" />
      </button>

      <div v-if="canReject || canApprove" class="ml-1 flex items-center gap-1.5">
        <button
          v-if="canReject"
          type="button"
          class="rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-800/60 dark:bg-transparent dark:text-rose-400 dark:hover:bg-rose-950/30"
          @click="$emit('reject')"
        >
          {{ $t('trip_detail.coordination.reject') }}
        </button>
        <button
          v-if="canApprove"
          type="button"
          class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="assignDisabled"
          :aria-label="$t('trip_detail.coordination.approve_transfer')"
          @click="$emit('approve')"
        >
          {{ $t('trip_detail.coordination.approve_transfer') }}
        </button>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { ArrowLeftIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'
import { labelTripStatus } from '../../util/labels'

type TripLike = {
  id?: number
  status?: string
  depart_at?: string | null
  created_at?: string | null
  dispatch_request?: { id?: number; created_at?: string | null } | null
}

const props = defineProps<{
  trip: TripLike
  canApprove: boolean
  canReject: boolean
  refreshing?: boolean
  assignDisabled?: boolean
}>()

defineEmits<{
  approve: []
  reject: []
  back: []
  refresh: []
}>()

const tripCodeDisp = computed(() => {
  const id = props.trip?.id
  if (!id) return 'TRP-—'
  return `TRP-${String(id).padStart(4, '0')}`
})

const reqCode = computed(() => {
  const r = props.trip?.dispatch_request
  if (!r?.id) return '—'
  const d = r.created_at ? new Date(r.created_at) : new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  return `${y}${m}-${String(r.id).padStart(3, '0')}`
})

const badgeClass = computed(() => {
  const s = String(props.trip?.status ?? '')
  const map: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-800',
    approved: 'bg-green-100 text-green-800',
    assigned: 'bg-blue-100 text-blue-800',
    driver_confirmed: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-gray-100 text-gray-700',
    cancelled: 'bg-red-100 text-red-800',
    incident: 'bg-red-100 text-red-800',
  }
  return map[s] ?? 'bg-slate-100 text-slate-700'
})

const tick = ref(0)
let timer: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  timer = setInterval(() => {
    tick.value += 1
  }, 60000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

const countdown = computed(() => {
  void tick.value
  const iso = props.trip?.depart_at
  if (!iso) return null
  const diff = Math.floor((new Date(iso).getTime() - Date.now()) / 60000)
  if (!Number.isFinite(diff) || diff <= 0) return null
  const h = Math.floor(diff / 60)
  const m = diff % 60
  return h > 0 ? `${h}h ${m}p` : `${m} phút`
})
</script>
