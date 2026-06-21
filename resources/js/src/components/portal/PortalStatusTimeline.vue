<template>
  <section
    class="overflow-hidden"
    :class="
      embedded
        ? 'p-0'
        : [
            'rounded-2xl border p-4 sm:p-5',
            variant === 'staff'
              ? 'border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900'
              : 'border-slate-200 bg-white',
          ]
    "
    :data-testid="embedded ? 'portal-status-timeline-embedded' : 'portal-status-timeline'"
  >
    <div
      v-if="title"
      class="flex items-center gap-2 border-slate-100 dark:border-slate-800"
      :class="embedded ? 'border-b pb-2' : 'gap-2.5 border-b pb-3'"
    >
      <span
        v-if="variant === 'staff' && !embedded"
        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-va-50 text-va-700 dark:bg-va-950/50 dark:text-va-300"
      >
        <QueueListIcon class="h-4 w-4" aria-hidden="true" />
      </span>
      <QueueListIcon
        v-else-if="variant === 'staff' && embedded"
        class="h-3.5 w-3.5 shrink-0 text-va-600 dark:text-va-400"
        aria-hidden="true"
      />
      <h2
        class="uppercase"
        :class="
          embedded
            ? 'text-[10px] font-semibold tracking-wider text-slate-500 dark:text-slate-400'
            : variant === 'staff'
              ? 'text-sm font-bold tracking-wide text-slate-600 dark:text-slate-300'
              : 'text-sm font-semibold tracking-wide text-slate-900'
        "
      >
        {{ title }}
      </h2>
    </div>

    <!-- Mobile: vertical -->
    <ol :class="embedded ? 'mt-2.5 md:hidden' : 'mt-4 md:hidden'">
      <li v-for="(step, idx) in steps" :key="`${step.key}-m`" class="flex" :class="embedded ? 'gap-2' : 'gap-3'">
        <div class="flex shrink-0 flex-col items-center" :class="embedded ? 'w-7' : 'w-9'">
          <div
            class="flex shrink-0 items-center justify-center rounded-full font-semibold"
            :class="[
              embedded ? 'h-7 w-7 text-[10px]' : 'h-9 w-9 text-xs',
              circleShellClass(step.state),
            ]"
            :aria-current="step.state === 'current' ? 'step' : undefined"
            :aria-label="stepCircleAria(step, idx)"
          >
            <CheckIcon v-if="step.state === 'done'" :class="embedded ? 'h-3 w-3' : 'h-4 w-4'" />
            <span v-else-if="step.state === 'rejected'" class="text-[10px] font-bold">!</span>
            <span v-else class="tabular-nums">{{ idx + 1 }}</span>
          </div>
          <div
            v-if="idx < steps.length - 1"
            class="mt-0.5 w-px flex-1 bg-slate-200 dark:bg-slate-700"
            :class="[
              embedded ? 'min-h-[1.25rem]' : 'mt-1 min-h-[1.75rem]',
              step.state === 'done' ? '!bg-va-600 dark:!bg-va-500' : '',
            ]"
            aria-hidden="true"
          />
        </div>
        <div class="min-w-0 flex-1 pt-px" :class="embedded ? 'pb-3' : 'pb-5 pt-0.5'">
          <p
            class="font-semibold leading-snug"
            :class="[embedded ? 'text-xs' : 'text-sm', labelClass(step.state)]"
          >
            {{ step.label }}
          </p>
          <template v-if="embedded">
            <p
              v-if="step.state === 'upcoming'"
              class="mt-0.5 text-[10px] italic text-slate-400 dark:text-slate-500"
            >
              {{ pendingLabel }}
            </p>
            <p
              v-else-if="step.actor || (step.sub && step.sub !== '—')"
              class="mt-0.5 text-[10px] leading-snug text-slate-500 dark:text-slate-400"
            >
              <span v-if="step.actor" class="font-medium text-slate-600 dark:text-slate-300">{{ step.actor }}</span>
              <span v-if="step.actor && step.sub && step.sub !== '—'" aria-hidden="true"> · </span>
              <span v-if="step.sub && step.sub !== '—'" class="tabular-nums">{{ step.sub }}</span>
            </p>
          </template>
          <template v-else>
            <p v-if="step.state === 'upcoming'" class="mt-1.5 text-xs italic text-slate-400 dark:text-slate-500">
              {{ pendingLabel }}
            </p>
            <p v-else-if="step.actor" class="mt-1.5 text-xs text-slate-600 dark:text-slate-400">
              <span class="font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ actorLabel }}</span>
              <span class="mt-0.5 block font-medium text-slate-700 dark:text-slate-300">{{ step.actor }}</span>
            </p>
            <p v-if="step.sub && step.sub !== '—'" class="mt-1.5 text-xs text-slate-500 dark:text-slate-500">
              <span class="font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ timeLabel }}</span>
              <span class="mt-0.5 block tabular-nums font-medium text-slate-600 dark:text-slate-400">{{ step.sub }}</span>
            </p>
          </template>
        </div>
      </li>
    </ol>

    <!-- Desktop: horizontal -->
    <div :class="embedded ? 'mt-2 hidden md:block' : 'mt-4 hidden md:block'">
      <div class="flex w-full items-start">
        <template v-for="(step, idx) in steps" :key="`${step.key}-d`">
          <div
            class="flex min-w-0 flex-1 flex-col items-center text-center"
            :class="[
              embedded ? 'px-0' : 'px-0.5 sm:px-1',
              step.state === 'current'
                ? embedded
                  ? 'rounded-md bg-va-50/80 py-1 ring-1 ring-va-200/60 dark:bg-va-950/25 dark:ring-va-800/40'
                  : 'rounded-lg bg-va-50/90 py-2 ring-1 ring-va-200/70 dark:bg-va-950/30 dark:ring-va-800/50'
                : '',
            ]"
          >
            <div
              class="flex shrink-0 items-center justify-center rounded-full font-semibold"
              :class="[embedded ? 'h-7 w-7 text-[10px]' : 'h-9 w-9 text-xs sm:text-sm', circleShellClass(step.state)]"
              :aria-current="step.state === 'current' ? 'step' : undefined"
              :aria-label="stepCircleAria(step, idx)"
            >
              <CheckIcon v-if="step.state === 'done'" :class="embedded ? 'h-3 w-3' : 'h-4 w-4 sm:h-5 sm:w-5'" />
              <span v-else-if="step.state === 'rejected'" :class="embedded ? 'text-xs font-bold' : 'text-sm font-bold'">!</span>
              <span v-else class="tabular-nums">{{ idx + 1 }}</span>
            </div>
            <p
              class="line-clamp-2 font-semibold leading-tight"
              :class="[
                embedded ? 'mt-1 text-[11px]' : 'mt-2 text-xs sm:text-sm',
                labelClass(step.state),
              ]"
            >
              {{ step.label }}
            </p>
            <template v-if="embedded">
              <p
                v-if="step.state === 'upcoming'"
                class="mt-0.5 text-[10px] italic text-slate-400 dark:text-slate-500"
              >
                {{ pendingLabel }}
              </p>
              <p
                v-else-if="step.actor || (step.sub && step.sub !== '—')"
                class="mt-0.5 line-clamp-2 text-[10px] leading-snug text-slate-500 dark:text-slate-400"
              >
                <span v-if="step.actor" class="font-medium text-slate-600 dark:text-slate-300">{{ step.actor }}</span>
                <span v-if="step.actor && step.sub && step.sub !== '—'" aria-hidden="true"> · </span>
                <span v-if="step.sub && step.sub !== '—'" class="tabular-nums">{{ step.sub }}</span>
              </p>
            </template>
            <template v-else>
              <p v-if="step.state === 'upcoming'" class="mt-1 text-[11px] italic text-slate-400 dark:text-slate-500">
                {{ pendingLabel }}
              </p>
              <p v-else-if="step.actor" class="mt-1 line-clamp-1 text-[11px] text-slate-600 dark:text-slate-400">
                <span class="block font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ actorLabel }}</span>
                <span class="mt-0.5 block truncate font-medium text-slate-700 dark:text-slate-300">{{ step.actor }}</span>
              </p>
              <p
                v-if="step.sub && step.sub !== '—'"
                class="mt-1 text-[11px] tabular-nums text-slate-500 dark:text-slate-500"
              >
                <span class="block font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ timeLabel }}</span>
                <span class="mt-0.5 block font-medium text-slate-600 dark:text-slate-400">{{ step.sub }}</span>
              </p>
            </template>
          </div>
          <div
            v-if="idx < steps.length - 1"
            class="mx-0.5 h-0.5 min-w-[0.25rem] flex-1 shrink self-start"
            :class="[
              embedded ? 'mt-[0.85rem]' : 'mt-[1.15rem] sm:mt-5',
              connectorClass(step, steps[idx + 1]),
            ]"
            aria-hidden="true"
          />
        </template>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { CheckIcon, QueueListIcon } from '@heroicons/vue/24/outline'

defineProps({
  title: { type: String, default: '' },
  steps: { type: Array, required: true },
  /** `staff` — dark shell on /mng request detail */
  variant: { type: String, default: 'portal' },
  /** Flat layout inside tab panels (no outer card chrome) */
  embedded: { type: Boolean, default: false },
})

const { t } = useI18n()
const actorLabel = computed(() => t('portal.timeline_lbl_actor'))
const timeLabel = computed(() => t('portal.timeline_lbl_time'))
const pendingLabel = computed(() => t('portal.timeline_step_pending'))

function circleShellClass(state) {
  if (state === 'done') {
    return 'border-2 border-va-600 bg-va-600 text-white dark:border-va-500 dark:bg-va-500'
  }
  if (state === 'current') {
    return 'border-2 border-va-600 bg-va-50 text-va-800 ring-2 ring-va-200/80 dark:border-va-400 dark:bg-va-950/40 dark:text-va-300 dark:ring-va-800/50'
  }
  if (state === 'rejected') {
    return 'border-2 border-rose-400 bg-white text-rose-600 dark:bg-slate-900'
  }
  return 'border-2 border-dashed border-slate-300 bg-slate-50 text-slate-500 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-400'
}

function stepCircleAria(step, idx) {
  return `${idx + 1}. ${step.label}`
}

function labelClass(state) {
  if (state === 'current') return 'text-va-800 dark:text-va-300'
  if (state === 'upcoming') return 'text-slate-400 dark:text-slate-500'
  if (state === 'rejected') return 'text-rose-700 dark:text-rose-400'
  return 'text-slate-800 dark:text-slate-200'
}

function connectorClass(step, nextStep) {
  if (step.state === 'done' || nextStep?.state === 'done') {
    return 'bg-va-500/70 dark:bg-va-600/60'
  }
  return 'bg-slate-200 dark:bg-slate-700'
}
</script>
