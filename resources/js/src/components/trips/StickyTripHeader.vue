<template>
  <header
    class="sticky top-0 z-50 flex h-14 items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 shadow-sm"
  >
    <div class="flex min-w-0 flex-1 items-center gap-3">
      <button
        type="button"
        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50"
        :aria-label="$t('trip_detail.header.back_trips')"
        @click="$emit('back')"
      >
        <ArrowLeftIcon class="h-5 w-5" />
      </button>
      <div class="min-w-0 truncate text-sm font-semibold text-slate-900 sm:text-base">
        <span class="tabular-nums text-slate-600">REQ {{ reqCode }}</span>
        <span class="mx-2 text-slate-300">·</span>
        <span class="tabular-nums text-slate-800">{{ tripCodeDisp }}</span>
        <span
          class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium align-middle"
          :class="badgeClass"
        >
          {{ labelTripStatus(trip.status) }}
        </span>
      </div>
    </div>
    <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
      <span
        v-if="countdown"
        class="hidden rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-700 sm:inline-block"
      >
        Còn {{ countdown }}
      </span>
      <button
        type="button"
        class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-50"
        :disabled="refreshing"
        :aria-label="$t('trip_detail.actions.refresh')"
        @click="$emit('refresh')"
      >
        <ArrowPathIcon class="h-5 w-5" :class="refreshing ? 'animate-spin' : ''" />
      </button>
      <RouterLink
        v-if="auth.canAccessDispatchWebApp()"
        to="/notifications"
        class="hidden h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50 sm:flex"
        :aria-label="$t('trip_detail.header.notifications')"
      >
        <BellIcon class="h-5 w-5" />
      </RouterLink>
      <button
        v-if="canReject"
        type="button"
        class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50"
        @click="$emit('reject')"
      >
        {{ $t('trip_detail.coordination.reject') }}
      </button>
      <button
        v-if="canApprove"
        type="button"
        class="rounded-lg bg-sky-600 px-3 py-2 text-xs font-semibold text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="assignDisabled"
        :aria-label="$t('trip_detail.coordination.approve_transfer')"
        @click="$emit('approve')"
      >
        {{ $t('trip_detail.coordination.approve_transfer') }}
      </button>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowLeftIcon, ArrowPathIcon, BellIcon } from '@heroicons/vue/24/outline'
import { labelTripStatus } from '../../util/labels'
import { useAuthStore } from '../../store'

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

const auth = useAuthStore()

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
