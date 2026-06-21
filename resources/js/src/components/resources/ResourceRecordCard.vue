<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import EmptyValue from '../ui/EmptyValue.vue'
import { isEmptyDisplay } from '../../util/displayValue'

const props = defineProps({
  /** Heroicon component for the leading icon box (ignored when avatar shown). */
  icon: { type: [Object, Function], default: null },
  /** Avatar image URL (drivers); takes precedence over icon when present. */
  avatarUrl: { type: String, default: '' },
  /** Initials fallback when no avatar image (drivers). */
  initials: { type: String, default: '' },
  /** Colour tone for the icon box: teal | indigo | slate | rose. */
  tone: { type: String, default: 'teal' },
  /** Main heading (license plate / name). */
  title: { type: String, required: true },
  /** Optional router-link target for the title. */
  to: { type: [String, Object], default: null },
  /** Sub line under the title. */
  subtitle: { type: String, default: '' },
  /** Status badge text + class. */
  statusLabel: { type: String, default: '' },
  statusClass: { type: String, default: '' },
  /** Small pills shown next to the status badge: [{ label, class }]. */
  pills: { type: Array, default: () => [] },
  /** Detail fields: [{ label, value, mono?, emptyKey? }]. */
  fields: { type: Array, default: () => [] },
  /** Show a selection checkbox in the header. */
  selectable: { type: Boolean, default: false },
  selected: { type: Boolean, default: false },
  /** Whole-card click emits `select`. */
  clickable: { type: Boolean, default: false },
})

defineEmits(['select', 'toggle-select'])

const TONE_MAP = {
  teal: 'bg-teal-100 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400',
  indigo: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-300',
  slate: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
  rose: 'bg-rose-100 text-rose-600 dark:bg-rose-950/40 dark:text-rose-300',
}
const iconWrapClass = computed(() => TONE_MAP[props.tone] || TONE_MAP.teal)

const showSubtitle = computed(() => !isEmptyDisplay(props.subtitle))
</script>

<template>
  <article
    class="flex flex-col overflow-hidden rounded-2xl border bg-white shadow-sm transition dark:bg-slate-900/50"
    :class="[
      selected
        ? 'border-teal-300 ring-2 ring-teal-500/40 dark:border-teal-700'
        : 'border-slate-200/90 dark:border-slate-700',
      clickable ? 'cursor-pointer hover:border-slate-300 hover:shadow-md dark:hover:border-slate-600' : '',
    ]"
    @click="clickable && $emit('select')"
  >
    <div class="border-b border-slate-100 px-3 py-4 sm:px-5 dark:border-slate-800">
      <div class="flex min-w-0 gap-3 sm:gap-4">
        <input
          v-if="selectable"
          type="checkbox"
          class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
          :checked="selected"
          @click.stop
          @change="$emit('toggle-select')"
        />

        <img
          v-if="avatarUrl"
          :src="avatarUrl"
          :alt="title"
          class="h-11 w-11 shrink-0 rounded-xl object-cover ring-1 ring-slate-200 dark:ring-slate-600 sm:h-12 sm:w-12"
        />
        <div
          v-else-if="initials"
          class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-sm font-bold sm:h-12 sm:w-12"
          :class="iconWrapClass"
          aria-hidden="true"
        >
          {{ initials }}
        </div>
        <div
          v-else-if="icon"
          class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl sm:h-12 sm:w-12"
          :class="iconWrapClass"
        >
          <component :is="icon" class="h-6 w-6" aria-hidden="true" />
        </div>

        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-2">
            <RouterLink
              v-if="to"
              :to="to"
              class="truncate font-mono text-base font-bold tracking-tight text-slate-900 underline decoration-slate-300 underline-offset-2 hover:text-teal-700 hover:decoration-teal-400 dark:text-slate-100"
              @click.stop
            >
              {{ title }}
            </RouterLink>
            <span v-else class="truncate font-mono text-base font-bold tracking-tight text-slate-900 dark:text-slate-100">
              {{ title }}
            </span>
            <span v-if="statusLabel" :class="statusClass">{{ statusLabel }}</span>
            <span v-for="(p, i) in pills" :key="i" :class="p.class">{{ p.label }}</span>
          </div>

          <p v-if="showSubtitle" class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
            {{ subtitle }}
          </p>
        </div>
      </div>

      <dl
        v-if="fields.length"
        class="mt-3 rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-1 dark:border-slate-800 dark:bg-slate-800/40"
      >
        <div
          v-for="(f, i) in fields"
          :key="i"
          class="grid grid-cols-[minmax(0,11rem)_minmax(0,1fr)] items-baseline gap-x-3 gap-y-0 border-b border-slate-100 py-2 text-sm last:border-b-0 dark:border-slate-700/80"
        >
          <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ f.label }}
          </dt>
          <dd
            class="min-w-0 text-right font-medium text-slate-800 dark:text-slate-200 sm:text-left"
            :class="f.mono ? 'font-mono tabular-nums' : ''"
          >
            <EmptyValue
              :value="f.value"
              :empty-key="f.emptyKey || 'resources.empty_not_available'"
            />
          </dd>
        </div>
      </dl>
    </div>

    <div
      v-if="$slots.actions"
      class="mt-auto flex flex-wrap items-center justify-end gap-2 px-3 py-3 sm:px-5"
      @click.stop
    >
      <slot name="actions" />
    </div>
  </article>
</template>
