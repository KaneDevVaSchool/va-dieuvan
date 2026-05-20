<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  cards: { type: Array, default: () => [] },
  scheduleLegs: { type: Array, default: () => [] },
  selectedKey: { type: String, default: '' },
})

const emit = defineEmits(['update:selectedKey'])

const { t } = useI18n()

const openKey = ref('')

watch(
  () => props.cards,
  (list) => {
    if (!list?.length) {
      openKey.value = ''
      return
    }
    if (!openKey.value || !list.some((c) => c.key === openKey.value)) {
      openKey.value = list[0].key
      emit('update:selectedKey', list[0].key)
    }
  },
  { immediate: true },
)

function assignmentStatus(key) {
  const leg = props.scheduleLegs.find((l) => l.key === key)
  if (leg?.assigned) return 'assigned'
  if (leg?.assignment) return 'partial'
  return 'pending'
}

function statusChipClass(status) {
  if (status === 'assigned') {
    return 'bg-emerald-50 text-emerald-800 ring-emerald-100'
  }
  if (status === 'partial') {
    return 'bg-amber-50 text-amber-900 ring-amber-100'
  }
  return 'bg-slate-100 text-slate-600 ring-slate-200'
}

function statusLabel(status) {
  if (status === 'assigned') return t('trip_detail.schedules.status_assigned')
  if (status === 'partial') return t('trip_detail.schedules.status_partial')
  return t('trip_detail.schedules.status_pending')
}

function toggle(key) {
  openKey.value = openKey.value === key ? '' : key
  emit('update:selectedKey', key)
}

const showPanel = computed(() => (props.cards?.length ?? 0) > 0)
</script>

<template>
  <section
    v-if="showPanel"
    class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm"
  >
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">
        {{ t('trip_detail.schedules.title') }}
      </h2>
      <span
        v-if="cards.length > 1"
        class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700"
      >
        {{ t('trip_detail.schedules.count', { n: cards.length }) }}
      </span>
    </div>

    <div class="mt-3 flex flex-col gap-2">
      <div
        v-for="card in cards"
        :key="card.key"
        class="overflow-hidden rounded-xl border border-slate-100 bg-slate-50/60"
      >
        <button
          type="button"
          class="flex w-full items-start gap-2 px-3 py-2.5 text-left transition hover:bg-white/80"
          :aria-expanded="openKey === card.key"
          @click="toggle(card.key)"
        >
          <ChevronDownIcon
            class="mt-0.5 size-4 shrink-0 text-slate-400 transition"
            :class="openKey === card.key ? 'rotate-180' : ''"
            aria-hidden="true"
          />
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-sm font-semibold text-slate-900">{{ card.heading }}</span>
              <span
                class="rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1"
                :class="statusChipClass(assignmentStatus(card.key))"
              >
                {{ statusLabel(assignmentStatus(card.key)) }}
              </span>
            </div>
            <p class="mt-0.5 truncate text-xs text-slate-600">
              {{ card.pickup || '—' }}
              <span class="text-slate-400">→</span>
              {{ card.dropoff || '—' }}
            </p>
          </div>
        </button>
        <div
          v-show="openKey === card.key"
          class="border-t border-slate-100 bg-white px-3 py-2.5"
        >
          <dl class="space-y-2">
            <div
              v-for="(ln, i) in card.lines"
              :key="i"
              class="grid grid-cols-1 gap-0.5 sm:grid-cols-3"
            >
              <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                {{ ln.label }}
              </dt>
              <dd class="sm:col-span-2 text-sm text-slate-800">{{ ln.value }}</dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  </section>
</template>
