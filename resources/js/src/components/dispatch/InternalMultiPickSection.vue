<template>
  <div
    class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900/50"
    :data-testid="dataTestid"
  >
    <div class="border-b border-slate-200/70 bg-slate-50 px-3 py-2.5 dark:border-slate-800 dark:bg-slate-900/40">
      <div class="flex items-center gap-2">
        <div
          v-if="icon"
          class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-800/90"
        >
          <component :is="icon" class="size-3.5" aria-hidden="true" />
        </div>
        <div class="min-w-0 flex-1">
          <div class="text-[12px] font-semibold text-slate-800 dark:text-slate-100">{{ title }}</div>
          <p v-if="subtitle" class="mt-0.5 text-[11px] leading-snug text-slate-500 dark:text-slate-400">
            {{ subtitle }}
          </p>
        </div>
        <span
          v-if="modelValue.length"
          class="shrink-0 rounded-full bg-slate-200/90 px-2 py-0.5 text-[10px] font-bold tabular-nums text-slate-700 dark:bg-slate-700 dark:text-slate-200"
        >
          {{ modelValue.length }}
        </span>
      </div>
      <div v-if="$slots.toolbar" class="mt-2">
        <slot name="toolbar" />
      </div>
      <button
        v-if="!isLoading"
        type="button"
        class="mt-2 w-full rounded-xl border border-dashed border-slate-300/90 bg-white px-3 py-2.5 text-left text-[12px] font-semibold text-[#8B1A1A] shadow-sm transition hover:border-[#8B1A1A]/35 hover:bg-rose-50/40 disabled:cursor-not-allowed disabled:opacity-45 dark:border-slate-600 dark:bg-slate-900 dark:text-[#e57373] dark:hover:bg-rose-950/20"
        :disabled="disabled"
        :data-testid="`${dataTestid}-open`"
        @click="openModal"
      >
        {{ pickButtonLabel }}
      </button>
    </div>

    <div v-if="isLoading" class="px-3 py-4">
      <div class="h-10 animate-pulse rounded-lg bg-slate-100 dark:bg-slate-800/60" />
    </div>

    <ul
      v-else-if="modelValue.length"
      class="divide-y divide-slate-100 dark:divide-slate-800"
      role="list"
    >
      <li
        v-for="(item, idx) in modelValue"
        :key="item.id"
        class="flex items-center gap-2 px-3 py-2"
      >
        <div class="min-w-0 flex-1">
          <div class="truncate text-[13px] font-medium text-slate-900 dark:text-slate-100">
            {{ item.label }}
          </div>
          <div v-if="item.sublabel" class="truncate text-[11px] text-slate-500 dark:text-slate-400">
            {{ item.sublabel }}
          </div>
        </div>
        <span
          v-if="idx === 0"
          class="shrink-0 rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400"
        >
          {{ t('trip_detail.coordination.assignment_list_primary') }}
        </span>
        <button
          type="button"
          class="shrink-0 rounded-lg px-2 py-1 text-[11px] font-medium text-slate-500 hover:bg-rose-50 hover:text-rose-700 disabled:opacity-45 dark:hover:bg-rose-950/40"
          :disabled="disabled"
          :aria-label="t('trip_detail.coordination.supplement_remove_aria')"
          @click="remove(item)"
        >
          ×
        </button>
      </li>
    </ul>

    <p
      v-else-if="!isLoading"
      class="px-3 py-3 text-center text-[11px] text-slate-500 dark:text-slate-400"
    >
      {{ emptyHint }}
    </p>

    <Teleport to="body">
      <div
        v-if="modalOpen"
        class="fixed inset-0 z-[320] flex items-end justify-center bg-black/45 p-0 sm:items-center sm:p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
        @click.self="closeModal"
      >
        <div
          class="flex max-h-[min(92dvh,640px)] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl dark:bg-slate-900"
          @click.stop
        >
          <div class="flex shrink-0 items-center justify-between gap-2 border-b border-slate-200/80 px-4 py-3 dark:border-slate-700">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ title }}</h2>
            <button
              type="button"
              class="rounded-lg bg-slate-100 px-3 py-1.5 text-[12px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200"
              data-testid="internal-multi-pick-done"
              @click="closeModal"
            >
              {{ t('trip_detail.coordination.multi_pick_done') }}
            </button>
          </div>
          <div class="shrink-0 px-4 pt-3">
            <input
              v-model="searchQuery"
              type="search"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-3 pr-2 text-[13px] text-slate-900 outline-none focus:border-[#8B1A1A]/40 focus:ring-2 focus:ring-[#8B1A1A]/15 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              :placeholder="searchPlaceholder"
              data-testid="internal-multi-pick-search"
            />
          </div>
          <ul
            class="min-h-0 flex-1 space-y-0.5 overflow-y-auto overscroll-y-contain px-2 py-2"
            role="listbox"
            :aria-multiselectable="true"
          >
            <li v-if="!filteredOptions.length" class="px-3 py-6 text-center text-[12px] text-slate-500">
              {{ t('trip_detail.coordination.resource_search_empty') }}
            </li>
            <li v-for="opt in filteredOptions" :key="opt.id" role="presentation">
              <button
                type="button"
                role="option"
                class="flex w-full items-start gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-slate-50 disabled:opacity-45 dark:hover:bg-slate-800/80"
                :class="isSelected(opt) ? 'bg-rose-50/80 ring-1 ring-[#8B1A1A]/20 dark:bg-rose-950/25' : ''"
                :disabled="disabled || opt.available === false"
                :aria-selected="isSelected(opt)"
                @click="toggle(opt)"
              >
                <span
                  class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-md border text-[11px] font-bold"
                  :class="
                    isSelected(opt)
                      ? 'border-[#8B1A1A] bg-[#8B1A1A] text-white'
                      : 'border-slate-300 bg-white text-transparent dark:border-slate-600 dark:bg-slate-900'
                  "
                  aria-hidden="true"
                >
                  ✓
                </span>
                <div class="min-w-0 flex-1">
                  <div
                    class="text-[13px] font-medium text-slate-900 dark:text-slate-100"
                    :class="optionLabelClass(opt)"
                  >
                    {{ opt.label }}
                  </div>
                  <div v-if="opt.sublabel" class="text-[11px] text-slate-500 dark:text-slate-400">
                    {{ opt.sublabel }}
                  </div>
                  <slot name="option-extra" :option="opt" />
                </div>
                <span
                  v-if="opt.available === false"
                  class="shrink-0 rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-medium text-rose-700 dark:bg-rose-900/40"
                >
                  {{ t('trip_detail.coordination.resource_busy_badge') }}
                </span>
              </button>
            </li>
          </ul>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  modelValue: { type: Array, required: true },
  options: { type: Array, required: true },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  pickButtonLabel: { type: String, required: true },
  searchPlaceholder: { type: String, required: true },
  emptyHint: { type: String, default: '' },
  isLoading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  icon: { type: [Object, Function], default: null },
  optionLabelClassFn: { type: Function, default: null },
  dataTestid: { type: String, default: 'internal-multi-pick' },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()
const modalOpen = ref(false)
const searchQuery = ref('')

watch(
  () => props.disabled,
  (d) => {
    if (d) modalOpen.value = false
  },
)

function normalizeSearchText(s) {
  return String(s ?? '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[\s·.,\-_/|()]/g, '')
}

const filteredOptions = computed(() => {
  const qRaw = searchQuery.value.trim()
  const list = props.options || []
  if (!qRaw) return list
  const q = normalizeSearchText(qRaw)
  if (!q) return list
  return list.filter((opt) => {
    const label = normalizeSearchText(opt.label)
    const sub = normalizeSearchText(opt.sublabel)
    return label.includes(q) || sub.includes(q)
  })
})

function optionLabelClass(opt) {
  const fn = props.optionLabelClassFn
  if (typeof fn !== 'function') return undefined
  return fn(opt) || undefined
}

function isSelected(opt) {
  return props.modelValue.some((v) => String(v.id) === String(opt.id))
}

function openModal() {
  if (props.disabled) return
  searchQuery.value = ''
  modalOpen.value = true
}

function closeModal() {
  modalOpen.value = false
}

function toggle(opt) {
  if (props.disabled || opt.available === false) return
  if (isSelected(opt)) {
    emit(
      'update:modelValue',
      props.modelValue.filter((v) => String(v.id) !== String(opt.id)),
    )
    return
  }
  emit('update:modelValue', [...props.modelValue, { ...opt }])
}

function remove(item) {
  if (props.disabled) return
  emit(
    'update:modelValue',
    props.modelValue.filter((v) => String(v.id) !== String(item.id)),
  )
}
</script>
