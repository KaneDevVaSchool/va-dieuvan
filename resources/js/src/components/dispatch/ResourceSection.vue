<template>
  <div class="border-b border-slate-200 py-3 last:border-b-0 dark:border-slate-700">
    <div class="mb-2 flex items-center gap-2">
      <div
        v-if="icon"
        class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400"
      >
        <component :is="icon" class="h-3.5 w-3.5" aria-hidden="true" />
      </div>
      <div class="min-w-0 flex-1">
        <div class="text-[13px] font-medium text-slate-900 dark:text-slate-100">{{ title }}</div>
        <div class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ subtitle }}</div>
      </div>
      <span
        v-if="modelValue.length > 0"
        class="ml-auto shrink-0 rounded-full bg-[#8B1A1A] px-2 py-0.5 text-[11px] font-medium text-white"
      >
        {{ modelValue.length }}
      </span>
    </div>

    <div v-if="!isLoading" class="relative">
      <input
        v-model="searchQuery"
        type="text"
        class="w-full rounded-md border border-slate-200 bg-white py-1.5 pl-2.5 pr-2 text-[13px] text-slate-900 outline-none ring-2 ring-transparent placeholder:text-slate-400 focus:border-[#8B1A1A] focus:ring-[#8B1A1A]/15 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
        :placeholder="placeholder"
        @focus="isOpen = true"
        @blur="onBlur"
        @keydown.enter.prevent="onEnterKey"
      />

      <Transition
        enter-active-class="transition duration-100 ease-out"
        enter-from-class="-translate-y-1 opacity-0"
        leave-active-class="transition duration-100 ease-in"
        leave-to-class="-translate-y-1 opacity-0"
      >
        <div
          v-if="isOpen"
          class="absolute left-0 right-0 top-full z-50 mt-1 max-h-[200px] overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-600 dark:bg-slate-900"
        >
          <template v-if="filteredOptions.length > 0">
            <button
              v-for="opt in filteredOptions"
              :key="opt.id"
              type="button"
              class="flex w-full items-center gap-2 border-b border-slate-100 px-3 py-2 text-left text-slate-900 last:border-b-0 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-55 dark:border-slate-800 dark:text-slate-100 dark:hover:bg-slate-800/80"
              :class="isSelected(opt) ? 'cursor-default bg-rose-50/80 dark:bg-rose-950/30' : ''"
              :disabled="opt.available === false || isSelected(opt)"
              @mousedown.prevent="select(opt)"
            >
              <span class="flex-1 text-[13px] font-medium">{{ opt.label }}</span>
              <span v-if="opt.sublabel" class="text-[11px] text-slate-500 dark:text-slate-400">{{ opt.sublabel }}</span>
              <span
                v-if="opt.available === false"
                class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300"
              >
                {{ busyLabel }}
              </span>
              <span v-if="isSelected(opt)" class="text-[13px] font-semibold text-[#8B1A1A]">✓</span>
            </button>
          </template>
          <div v-else class="px-3 py-2.5 text-center text-[12px] text-slate-500 dark:text-slate-400">
            <template v-if="allowCustomEntry && searchQuery.trim()">
              {{ customAddHint }}
            </template>
            <template v-else>
              {{ emptySearchLabel }}
            </template>
          </div>
          <div
            v-if="allowCustomEntry && searchQuery.trim() && filteredOptions.length > 0"
            class="border-t border-slate-100 px-3 py-2 text-center text-[11px] text-slate-500 dark:border-slate-800 dark:text-slate-400"
          >
            {{ customAddHint }}
          </div>
        </div>
      </Transition>
      <p v-if="allowCustomEntry" class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
        {{ t('trip_detail.coordination.resource_custom_entry_hint') }}
      </p>
    </div>

    <div v-if="isLoading" class="mt-1.5 h-8 animate-pulse rounded-md bg-slate-100 dark:bg-slate-800" />

    <div v-if="modelValue.length > 0 && !isLoading" class="mt-2 flex flex-wrap gap-1.5">
      <div
        v-for="item in modelValue"
        :key="item.id"
        class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 text-[12px] text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
      >
        <span>{{ item.label }}</span>
        <button
          type="button"
          class="px-0.5 text-[14px] leading-none text-slate-500 hover:text-[#8B1A1A] dark:text-slate-400"
          aria-label="Remove"
          @click="remove(item)"
        >
          ×
        </button>
      </div>
    </div>

    <div v-else-if="!isLoading" class="mt-1 py-0.5 text-[12px] italic text-slate-500 dark:text-slate-400">
      {{ emptyStateLabel }}
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  modelValue: { type: Array, required: true },
  options: { type: Array, required: true },
  title: { type: String, required: true },
  subtitle: { type: String, required: true },
  placeholder: { type: String, required: true },
  emptyHint: { type: String, required: true },
  isLoading: { type: Boolean, default: false },
  icon: { type: [Object, Function], default: null },
  /** Khi false: chỉ một lựa chọn (thay thế danh sách hiện tại) */
  multiple: { type: Boolean, default: true },
  /** Enter / gợi ý: thêm mục nhập tay (id custom:...) */
  allowCustomEntry: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()

const searchQuery = ref('')
const isOpen = ref(false)

const busyLabel = computed(() => t('trip_detail.coordination.resource_busy_badge'))
const emptySearchLabel = computed(() => t('trip_detail.coordination.resource_search_empty'))
const emptyStateLabel = computed(() => t('trip_detail.coordination.resource_empty_line', { hint: props.emptyHint }))
const customAddHint = computed(() =>
  t('trip_detail.coordination.resource_press_enter_add', { name: searchQuery.value.trim() }),
)

const filteredOptions = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  const list = props.options || []
  if (!q) return list
  return list.filter((opt) => {
    const label = String(opt.label ?? '').toLowerCase()
    const sub = String(opt.sublabel ?? '').toLowerCase()
    return label.includes(q) || sub.includes(q)
  })
})

function isSelected(opt) {
  return props.modelValue.some((v) => String(v.id) === String(opt.id))
}

function select(opt) {
  if (opt.available === false || isSelected(opt)) return
  if (props.multiple) emit('update:modelValue', [...props.modelValue, opt])
  else emit('update:modelValue', [opt])
  searchQuery.value = ''
  isOpen.value = false
}

function remove(opt) {
  emit(
    'update:modelValue',
    props.modelValue.filter((v) => String(v.id) !== String(opt.id)),
  )
}

function onEnterKey() {
  if (!props.allowCustomEntry) return
  const q = searchQuery.value.trim()
  if (!q) return
  const qLower = q.toLowerCase()
  const exactLabel = filteredOptions.value.filter(
    (o) => o.available !== false && !isSelected(o) && String(o.label).toLowerCase() === qLower,
  )
  if (exactLabel.length === 1) {
    select(exactLabel[0])
    return
  }
  if (
    props.modelValue.some((v) => v.isCustom === true && String(v.label).toLowerCase() === qLower)
  ) {
    searchQuery.value = ''
    isOpen.value = false
    return
  }
  const id = `custom:${Date.now()}-${Math.random().toString(36).slice(2, 9)}`
  const item = { id, label: q, available: true, isCustom: true }
  if (props.multiple) emit('update:modelValue', [...props.modelValue, item])
  else emit('update:modelValue', [item])
  searchQuery.value = ''
  isOpen.value = false
}

function onBlur() {
  setTimeout(() => {
    isOpen.value = false
  }, 150)
}
</script>
