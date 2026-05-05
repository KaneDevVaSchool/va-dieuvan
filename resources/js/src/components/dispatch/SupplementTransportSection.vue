<template>
  <div class="border-b border-[0.5px] border-slate-200/80 pb-3 last:border-b-0 dark:border-slate-700/70">
    <div class="mb-2 flex items-start gap-2">
      <div
        v-if="icon"
        class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-[10px] border-[0.5px] border-slate-200/80 bg-slate-100/80 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"
      >
        <component :is="icon" class="h-3.5 w-3.5" aria-hidden="true" />
      </div>
      <div class="min-w-0 flex-1">
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <div class="text-[13px] font-medium text-slate-900 dark:text-slate-100">{{ title }}</div>
            <div class="mt-0.5 text-[11px] font-normal text-slate-500 dark:text-slate-400">{{ subtitle }}</div>
          </div>
          <div class="flex shrink-0 items-center gap-2">
            <template v-if="kind === 'vendor' && canQuickCreate">
              <button
                type="button"
                class="whitespace-nowrap text-[12px] font-normal text-[#8B1A1A] underline-offset-2 hover:underline dark:text-[#e57373]"
                @click="$emit('create-vendor')"
              >
                {{ t('trip_detail.coordination.provider_quick_add_inline') }}
              </button>
            </template>
            <span
              v-if="modelValue.length > 0"
              class="rounded-full border-[0.5px] border-slate-300/80 bg-slate-100/90 px-2 py-0.5 text-[11px] font-medium tabular-nums text-slate-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300"
            >
              {{ modelValue.length }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex flex-wrap items-end gap-2" :class="indentBody ? 'ml-[38px]' : ''">
      <div class="min-w-0 flex-1">
        <label class="mb-1 block text-[11px] font-medium text-slate-600 dark:text-slate-400">{{ nameFieldLabel }}</label>
        <input
          v-model="nameDraft"
          type="text"
          class="w-full rounded-[12px] border-[0.5px] border-slate-200/90 bg-white px-2.5 py-1.5 text-[13px] font-normal text-slate-900 outline-none ring-0 placeholder:text-slate-400 focus:border-[#8B1A1A]/45 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
          :placeholder="namePlaceholder"
          :disabled="disabled"
          :aria-busy="disabled"
          @keydown.enter.prevent="onAdd"
        />
      </div>
      <div class="w-[72px] shrink-0">
        <label class="mb-1 block text-[11px] font-medium text-slate-600 dark:text-slate-400">{{
          t('trip_detail.coordination.supplement_seats_field_label')
        }}</label>
        <input
          v-model="seatDraft"
          type="number"
          min="1"
          step="1"
          inputmode="numeric"
          class="w-full rounded-[12px] border-[0.5px] border-slate-200/90 bg-white px-2 py-1.5 text-center text-[13px] font-normal tabular-nums text-slate-900 outline-none ring-0 focus:border-[#8B1A1A]/45 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
          :disabled="disabled"
          :aria-label="t('trip_detail.coordination.supplement_seats_aria')"
        />
      </div>
      <button
        type="button"
        class="shrink-0 rounded-[12px] border-[0.5px] border-transparent bg-[#8B1A1A] px-3 py-2 text-[12px] font-normal text-white hover:bg-[#7a1717] disabled:cursor-not-allowed disabled:opacity-45 dark:hover:bg-[#9a2323]"
        :disabled="disabled"
        @click="onAdd"
      >
        {{ t('trip_detail.coordination.supplement_add_btn') }}
      </button>
    </div>

    <div v-if="isLoading" class="mt-2 h-8 animate-pulse rounded-[12px] bg-slate-100 dark:bg-slate-800" />

    <div v-if="!isLoading && modelValue.length > 0" class="mt-3 space-y-2">
      <div v-for="(item, idx) in modelValue" :key="String(item.id) + '-' + idx" class="space-y-1.5">
        <div
          class="flex min-h-[40px] items-center gap-2 rounded-[12px] border-[0.5px] border-slate-200/85 bg-white/80 px-2 py-1.5 dark:border-slate-700 dark:bg-slate-900/50"
        >
          <span class="min-w-0 flex-1 truncate text-[13px] font-normal text-slate-900 dark:text-slate-100">{{
            item.label
          }}</span>
          <span
            v-if="seatBadgeText(item)"
            class="shrink-0 rounded-[100px] border-[0.5px] border-[#1D9E75]/35 bg-[#E1F5EE] px-2 py-0.5 text-[12px] font-normal tabular-nums text-[#0F6E56] dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-200"
          >
            {{ seatBadgeText(item) }}
          </span>
          <button
            type="button"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] border-[0.5px] border-slate-200/80 text-[16px] font-normal leading-none text-slate-500 hover:border-rose-200/80 hover:text-rose-700 dark:border-slate-600 dark:text-slate-400 dark:hover:text-rose-400"
            :aria-label="t('trip_detail.coordination.supplement_remove_aria')"
            @click="removeAt(idx)"
          >
            ×
          </button>
        </div>
        <div
          v-if="kind === 'vendor'"
          class="ml-0 rounded-[12px] border-[0.5px] border-slate-200/60 bg-slate-50/50 dark:border-slate-700/60 dark:bg-slate-900/30"
        >
          <button
            type="button"
            class="flex w-full items-center justify-between gap-2 px-2.5 py-1.5 text-left text-[11px] font-normal text-slate-600 dark:text-slate-400"
            :aria-expanded="refsOpen[itemId(item)]"
            @click="toggleRefs(itemId(item))"
          >
            <span>{{ t('trip_detail.coordination.supplement_ncc_refs_toggle') }}</span>
            <span class="tabular-nums text-slate-400" aria-hidden="true">{{
              refsOpen[itemId(item)] ? '▾' : '▸'
            }}</span>
          </button>
          <div
            v-show="refsOpen[itemId(item)]"
            class="space-y-2 border-t border-[0.5px] border-slate-200/70 px-2.5 pb-2.5 pt-2 dark:border-slate-700/50"
          >
            <div>
              <label class="mb-1 block text-[11px] font-medium text-slate-600 dark:text-slate-400">{{
                t('trip_detail.coordination.external_vehicle')
              }}</label>
              <input
                :value="item.externalVehicleRef ?? ''"
                type="text"
                class="w-full rounded-[12px] border-[0.5px] border-slate-200/90 bg-white px-2.5 py-1.5 text-[13px] font-normal text-slate-900 outline-none ring-0 focus:border-[#8B1A1A]/40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                :placeholder="t('trip_detail.coordination.external_vehicle_ph')"
                @input="onExternalVehicleInput(idx, $event)"
              />
            </div>
            <div>
              <label class="mb-1 block text-[11px] font-medium text-slate-600 dark:text-slate-400">{{
                t('trip_detail.coordination.external_driver')
              }}</label>
              <input
                :value="item.externalDriverRef ?? ''"
                type="text"
                class="w-full rounded-[12px] border-[0.5px] border-slate-200/90 bg-white px-2.5 py-1.5 text-[13px] font-normal text-slate-900 outline-none ring-0 focus:border-[#8B1A1A]/40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                :placeholder="t('trip_detail.coordination.external_driver_ph')"
                @input="onExternalDriverInput(idx, $event)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type { ResourceItem } from '../../types/dispatch'

const props = defineProps<{
  modelValue: ResourceItem[]
  options: ResourceItem[]
  title: string
  subtitle: string
  namePlaceholder: string
  nameFieldLabel: string
  isLoading: boolean
  icon?: object | null
  kind: 'taxi' | 'vendor'
  defaultSeat: number
  indentBody?: boolean
  canQuickCreate?: boolean
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: ResourceItem[]]
  'create-vendor': []
}>()

const { t } = useI18n()

const nameDraft = ref('')
const seatDraft = ref(String(props.defaultSeat))
const refsOpen = ref<Record<string, boolean>>({})

function itemId(item: ResourceItem) {
  return String(item.id)
}

watch(
  () => props.defaultSeat,
  (n) => {
    const s = String(n)
    if (!nameDraft.value.trim()) seatDraft.value = s
  },
)

watch(
  () => props.modelValue,
  (list) => {
    const next = { ...refsOpen.value }
    for (const it of list) {
      const id = itemId(it)
      const has =
        String(it.externalVehicleRef ?? '').trim() !== '' ||
        String(it.externalDriverRef ?? '').trim() !== ''
      if (props.kind === 'vendor' && has && next[id] === undefined) next[id] = true
    }
    refsOpen.value = next
  },
  { deep: true, immediate: true },
)

function toggleRefs(id: string) {
  refsOpen.value = { ...refsOpen.value, [id]: !refsOpen.value[id] }
}

function onExternalVehicleInput(idx: number, e: Event) {
  const el = e.target as HTMLInputElement
  patchItem(idx, 'externalVehicleRef', el.value)
}

function onExternalDriverInput(idx: number, e: Event) {
  const el = e.target as HTMLInputElement
  patchItem(idx, 'externalDriverRef', el.value)
}

function patchItem(idx: number, key: 'externalVehicleRef' | 'externalDriverRef', value: string) {
  const next = props.modelValue.map((it, i) =>
    i === idx ? { ...it, [key]: value } : it,
  )
  emit('update:modelValue', next)
}

function parseSeats(): number | null {
  const n = Number(String(seatDraft.value).trim())
  if (!Number.isFinite(n) || n < 1) return null
  return Math.floor(n)
}

function tryResolveOption(name: string): ResourceItem | null {
  const q = name.trim().toLowerCase()
  if (!q) return null
  const list = props.options ?? []
  const exact = list.find(
    (o) => o.available !== false && String(o.label).toLowerCase() === q,
  )
  return exact ?? null
}

function seatBadgeText(item: ResourceItem) {
  const n = Number(item.supplementSeats)
  if (!Number.isFinite(n) || n < 1) return ''
  return t('trip_detail.coordination.seats_n', { n })
}

function isCustomDuplicate(name: string) {
  const q = name.toLowerCase()
  return props.modelValue.some((v) => {
    const isC = v.isCustom === true || String(v.id).startsWith('custom:')
    return isC && String(v.label).toLowerCase() === q
  })
}

function onAdd() {
  const name = nameDraft.value.trim()
  if (!name) return
  const seats = parseSeats()
  if (seats == null) return

  if (isCustomDuplicate(name)) {
    nameDraft.value = ''
    return
  }

  const resolved = tryResolveOption(name)
  if (resolved) {
    if (props.modelValue.some((v) => String(v.id) === String(resolved.id))) {
      nameDraft.value = ''
      return
    }
    const enriched: ResourceItem = {
      ...resolved,
      supplementSeats: seats,
    }
    emit('update:modelValue', [...props.modelValue, enriched])
    nameDraft.value = ''
    seatDraft.value = String(props.defaultSeat)
    return
  }

  const id = `custom:${Date.now()}-${Math.random().toString(36).slice(2, 9)}`
  const item: ResourceItem = {
    id,
    label: name,
    available: true,
    isCustom: true,
    supplementSeats: seats,
  }
  emit('update:modelValue', [...props.modelValue, item])
  nameDraft.value = ''
  seatDraft.value = String(props.defaultSeat)
}

function removeAt(index: number) {
  const removed = props.modelValue[index]
  const next = props.modelValue.filter((_, i) => i !== index)
  if (removed) {
    const id = itemId(removed)
    const ro = { ...refsOpen.value }
    delete ro[id]
    refsOpen.value = ro
  }
  emit('update:modelValue', next)
}
</script>
