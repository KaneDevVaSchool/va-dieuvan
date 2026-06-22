<template>
  <template v-if="paxKind === 'student'">
    <div v-if="paxList.length" class="overflow-hidden rounded-2xl bg-driver-card ring-1 ring-white/[0.06]">
      <div class="px-4 py-3">
        <h2 class="flex min-w-0 items-center gap-2 text-base font-semibold text-driver-ink sm:text-lg">
          <UserGroupIcon class="h-5 w-5 shrink-0 text-driver-muted/80" aria-hidden="true" />
          {{ t('driver_trip_detail.students_title', { n: paxSectionTotal }) }}
        </h2>
      </div>

      <ul class="max-h-[min(52vh,28rem)] divide-y divide-white/10 overflow-y-auto overscroll-y-contain">
        <li
          v-for="p in displayedPaxList"
          :key="p._origIndex"
          class="transition"
          :class="isPaused ? 'opacity-50' : ''"
        >
          <div
            role="button"
            tabindex="0"
            class="flex cursor-pointer items-start gap-3 px-4 py-3 outline-none focus-visible:ring-2 focus-visible:ring-[#7fdcc8]/50 focus-visible:ring-offset-2 focus-visible:ring-offset-driver-card"
            :aria-expanded="expandedStudentIdx === p._origIndex ? 'true' : 'false'"
            :aria-label="studentRowLabel(p)"
            @click="onToggle(p._origIndex)"
            @keydown.enter.prevent="onToggle(p._origIndex)"
            @keydown.space.prevent="onToggle(p._origIndex)"
          >
            <div class="relative shrink-0">
              <div class="flex h-12 w-12 items-center justify-center rounded-full bg-driver-elevated text-base font-bold text-driver-muted">
                {{ initials(p.name) }}
              </div>
              <div
                class="absolute -left-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full border-2 border-white text-xs font-bold"
                :class="stateClass(rowState(p._origIndex))"
              >
                {{ p._origIndex + 1 }}
              </div>
              <div
                v-if="rowState(p._origIndex) === 'picked_up'"
                class="driver-pop absolute -bottom-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-white ring-2 ring-white"
              >
                <CheckIcon class="h-3.5 w-3.5" aria-hidden="true" />
              </div>
            </div>

            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-base font-semibold leading-snug text-driver-ink sm:text-lg">{{ p.name }}</span>
                <span
                  v-if="isNextIndex(p._origIndex) && canMarkPickup"
                  class="rounded-md bg-amber-100 px-1.5 py-0.5 text-xs font-bold uppercase text-amber-900 sm:text-sm"
                >
                  {{ t('driver_trip_detail.tag_next') }}
                </span>
              </div>
              <p class="mt-0.5 text-sm leading-snug text-driver-muted sm:text-base">{{ p.subtitle }}</p>
              <p v-if="p.address" class="mt-1 flex items-start gap-1 text-sm leading-snug text-driver-muted/85 sm:text-base">
                <MapPinIcon class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                {{ p.address }}
              </p>
            </div>

            <div class="flex shrink-0 flex-col items-end gap-1">
              <span class="rounded-full px-2 py-0.5 text-xs font-semibold sm:text-sm" :class="pillClass(rowState(p._origIndex))">
                {{ pillText(rowState(p._origIndex)) }}
              </span>
              <span v-if="p.time" class="text-xs font-semibold tabular-nums text-driver-muted sm:text-sm">{{ p.time }}</span>
              <ChevronDownIcon
                class="h-5 w-5 text-driver-muted/60 transition-transform sm:h-4 sm:w-4"
                :class="expandedStudentIdx === p._origIndex ? 'rotate-180' : ''"
                aria-hidden="true"
              />
            </div>
          </div>

          <Transition :css="false" @enter="onRowEnter" @leave="onRowLeave">
            <div v-if="expandedStudentIdx === p._origIndex" class="flex flex-wrap gap-2 border-t border-white/[0.06] bg-driver-surface/50 px-4 py-3">
              <a
                v-if="p.phone"
                :href="`tel:${p.phone}`"
                class="flex min-h-[48px] min-w-[44px] flex-1 basis-[calc(50%-0.25rem)] items-center justify-center gap-2 rounded-xl bg-emerald-500 px-3 py-3 text-base font-bold text-white transition active:scale-[0.97] active:opacity-90 sm:flex-none sm:basis-auto"
                @click="haptics.tap()"
              >
                <PhoneIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
                {{ t('driver_trip_detail.call') }}
              </a>
              <button
                v-if="canMarkPickup && rowState(p._origIndex) !== 'picked_up'"
                type="button"
                :disabled="eventPosting"
                class="flex min-h-[48px] min-w-[44px] flex-1 basis-[calc(50%-0.25rem)] items-center justify-center gap-2 rounded-xl bg-[#7fdcc8] px-3 py-3 text-base font-bold text-driver-bg transition active:scale-[0.97] disabled:opacity-50 active:opacity-90 sm:flex-none sm:basis-auto"
                @click.stop="onSetState(p._origIndex, 'picked_up')"
              >
                <CheckIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
                {{ t('driver_trip_detail.btn_picked') }}
              </button>
              <button
                v-if="canMarkPickup && rowState(p._origIndex) !== 'absent'"
                type="button"
                :disabled="eventPosting"
                class="flex min-h-[48px] min-w-[44px] flex-1 basis-full items-center justify-center gap-2 rounded-xl bg-driver-surface px-3 py-3 text-base font-semibold text-driver-muted transition active:scale-[0.98] disabled:opacity-50 active:opacity-90 sm:basis-auto"
                @click.stop="onSetState(p._origIndex, 'absent')"
              >
                {{ t('driver_trip_detail.btn_absent') }}
              </button>
            </div>
          </Transition>
        </li>
      </ul>
    </div>

    <p
      v-else
      class="rounded-2xl border border-dashed border-white/10 bg-driver-surface/70 px-4 py-5 text-center text-base text-driver-muted sm:text-lg"
    >
      {{ t('driver_trip_detail.no_pax') }}
    </p>
  </template>

  <div v-else-if="paxList.length" class="overflow-hidden rounded-2xl bg-driver-card ring-1 ring-white/[0.06]">
    <div class="px-4 pb-2 pt-3.5">
      <h2 class="text-base font-semibold text-driver-ink sm:text-lg">
        {{
          paxKind === 'cargo'
            ? t('driver_trip_detail.cargo_list_title', { n: paxList.length })
            : t('driver_trip_detail.students_title', { n: paxSectionTotal })
        }}
      </h2>
    </div>
    <ul class="divide-y divide-white/10 px-4 pb-3">
      <li v-for="(p, i) in paxList" :key="i" class="flex items-start gap-3 py-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-driver-elevated text-sm font-bold text-driver-muted">
          {{ initials(p.name) }}
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-base font-semibold leading-snug text-driver-ink sm:text-lg">{{ p.name }}</p>
          <p class="mt-0.5 text-sm leading-snug text-driver-muted sm:text-base">{{ p.subtitle }}</p>
          <p
            v-if="paxKind === 'cargo' && p.deliverySubtitle"
            class="mt-0.5 text-sm leading-snug text-driver-muted/90 sm:text-base"
          >
            {{ p.deliverySubtitle }}
          </p>
          <div
            v-if="paxKind === 'cargo' && (p.senderLine || p.receiverLine)"
            class="mt-2.5 grid grid-cols-1 gap-2 sm:grid-cols-2"
          >
            <div v-if="p.senderLine" class="rounded-xl bg-driver-surface/60 px-3 py-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-driver-muted/70">
                {{ t('driver_trip_detail.cargo_sender') }}
              </p>
              <p class="mt-0.5 break-words text-sm font-medium leading-snug text-driver-ink sm:text-base">
                {{ p.senderLine }}
              </p>
              <a
                v-if="p.senderPhone"
                :href="`tel:${p.senderPhone}`"
                class="mt-1.5 inline-flex min-h-[40px] items-center gap-1.5 rounded-lg bg-emerald-500/15 px-2.5 py-1.5 text-sm font-semibold text-emerald-200"
                :data-testid="`driver-cargo-sender-call-${i}`"
                :aria-label="t('driver_trip_detail.call')"
                @click="haptics.tap()"
              >
                <PhoneIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                {{ t('driver_trip_detail.call') }}
              </a>
            </div>
            <div v-if="p.receiverLine" class="rounded-xl bg-driver-surface/60 px-3 py-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-driver-muted/70">
                {{ t('driver_trip_detail.cargo_receiver') }}
              </p>
              <p class="mt-0.5 break-words text-sm font-medium leading-snug text-driver-ink sm:text-base">
                {{ p.receiverLine }}
              </p>
              <a
                v-if="p.receiverPhone"
                :href="`tel:${p.receiverPhone}`"
                class="mt-1.5 inline-flex min-h-[40px] items-center gap-1.5 rounded-lg bg-emerald-500/15 px-2.5 py-1.5 text-sm font-semibold text-emerald-200"
                :data-testid="`driver-cargo-receiver-call-${i}`"
                :aria-label="t('driver_trip_detail.call')"
                @click="haptics.tap()"
              >
                <PhoneIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                {{ t('driver_trip_detail.call') }}
              </a>
            </div>
          </div>
        </div>
        <a
          v-if="paxKind !== 'cargo' && p.phone"
          :href="`tel:${p.phone}`"
          class="flex min-h-[44px] min-w-[44px] shrink-0 items-center justify-center rounded-full border border-white/10 bg-driver-surface text-driver-muted active:bg-driver-elevated"
          :aria-label="t('driver_trip_detail.call')"
        >
          <PhoneIcon class="h-5 w-5" aria-hidden="true" />
        </a>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import {
  CheckIcon,
  ChevronDownIcon,
  MapPinIcon,
  PhoneIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline'
import { useHaptics } from '../../../composables/useHaptics'

defineProps({
  paxKind: { type: String, required: true },
  paxList: { type: Array, default: () => [] },
  displayedPaxList: { type: Array, default: () => [] },
  paxSectionTotal: { type: Number, default: 0 },
  expandedStudentIdx: {
    default: null,
    validator: (v) => v === null || typeof v === 'number',
  },
  isPaused: { type: Boolean, default: false },
  canMarkPickup: { type: Boolean, default: false },
  eventPosting: { type: Boolean, default: false },
  rowState: { type: Function, required: true },
  isNextIndex: { type: Function, required: true },
})

const emit = defineEmits(['toggle-student', 'set-row-state'])

const { t } = useI18n()
const haptics = useHaptics()

function onToggle(idx) {
  haptics.tap()
  emit('toggle-student', idx)
}

function onSetState(idx, st) {
  if (st === 'picked_up') haptics.success()
  else haptics.select()
  emit('set-row-state', idx, st)
}

// ── Mở/đóng hàng học sinh: animate chiều cao + mờ (JS hooks) ───────────
const ROW_EASE = 'cubic-bezier(0.22, 1, 0.36, 1)'

function reduceMotion() {
  return (
    typeof window !== 'undefined' &&
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches
  )
}

function animateRow(el, fromH, toH, fromO, toO, dur, done) {
  let finished = false
  const finish = () => {
    if (finished) return
    finished = true
    el.removeEventListener('transitionend', onEnd)
    el.style.height = ''
    el.style.opacity = ''
    el.style.overflow = ''
    el.style.transition = ''
    done()
  }
  const onEnd = (e) => {
    if (e.target === el && e.propertyName === 'height') finish()
  }
  el.style.overflow = 'hidden'
  el.style.height = fromH
  el.style.opacity = fromO
  void el.offsetHeight // force reflow
  el.style.transition = `height ${dur}s ${ROW_EASE}, opacity ${dur}s ${ROW_EASE}`
  el.addEventListener('transitionend', onEnd)
  setTimeout(finish, dur * 1000 + 80) // fallback nếu transitionend không bắn
  el.style.height = toH
  el.style.opacity = toO
}

function onRowEnter(el, done) {
  if (reduceMotion()) {
    done()
    return
  }
  animateRow(el, '0px', `${el.scrollHeight}px`, '0', '1', 0.24, done)
}

function onRowLeave(el, done) {
  if (reduceMotion()) {
    done()
    return
  }
  animateRow(el, `${el.scrollHeight}px`, '0px', '1', '0', 0.2, done)
}

function initials(name) {
  const n = (name || '').trim() || '?'
  const p = n.split(/\s+/)
  if (p.length >= 2) return (p[0][0] + p[p.length - 1][0]).toUpperCase()
  return n.slice(0, 2).toUpperCase()
}

function studentRowLabel(p) {
  return t('driver_trip_detail.a11y_student_row', { name: p.name })
}

function stateClass(st) {
  if (st === 'picked_up') return 'bg-emerald-500 text-white'
  if (st === 'absent') return 'bg-driver-muted/80 text-driver-ink'
  return 'bg-driver-accent/20 text-driver-ink'
}

function pillClass(st) {
  if (st === 'picked_up') return 'bg-emerald-500/20 text-emerald-300'
  if (st === 'absent') return 'bg-driver-elevated text-driver-muted'
  return 'bg-amber-500/25 text-amber-100'
}

function pillText(st) {
  if (st === 'picked_up') return t('driver_trip_detail.state_picked')
  if (st === 'absent') return t('driver_trip_detail.btn_absent')
  return t('driver_trip_detail.status_waiting')
}
</script>
