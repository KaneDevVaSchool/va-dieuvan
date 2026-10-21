<template>
  <div class="space-y-3 sm:space-y-4">
    <RouterLink :to="{ name: 'checklists' }" class="inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:underline dark:text-teal-400">
      <ArrowLeftIcon class="h-4 w-4" /> {{ t('checklist.back_to_list') }}
    </RouterLink>

    <p v-if="loading" class="text-sm text-slate-500">{{ t('checklist.loading') }}</p>

    <section
      v-for="tpl in templates"
      :key="tpl.id"
      class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
          <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ tpl.name }}</h2>
          <p class="text-xs text-slate-500">{{ t(`checklist.freq_${tpl.frequency}`) }} · v{{ tpl.version }} · {{ tpl.items.length }} {{ t('checklist.items_count') }}</p>
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
          <input type="checkbox" class="h-4 w-4 accent-teal-600" :checked="tpl.is_active" :disabled="!canManage" @change="toggleTemplate(tpl, $event.target.checked)" />
          {{ t('checklist.active') }}
        </label>
      </div>

      <!-- Khung giờ nhắc -->
      <div class="mt-4 rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ t('checklist.slots_title') }}</h3>
        <p class="text-xs text-slate-500">{{ t('checklist.slots_hint') }}</p>
        <ul class="mt-2 space-y-2">
          <li v-for="slot in tpl.reminder_slots" :key="slot.id" class="flex flex-wrap items-center gap-2 rounded-md bg-white p-2 ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700">
            <input v-model="slot.remind_at" type="time" class="rounded border border-slate-200 px-2 py-1 text-sm dark:border-slate-600 dark:bg-slate-800" :disabled="!canManage" />
            <div class="flex flex-wrap gap-1">
              <button
                v-for="d in 7"
                :key="d"
                type="button"
                class="h-8 w-9 rounded text-xs font-semibold ring-1"
                :class="slot.weekdays.includes(d) ? 'bg-teal-600 text-white ring-teal-600' : 'bg-white text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-600'"
                :disabled="!canManage"
                @click="toggleDay(slot, d)"
              >
                {{ weekdayLabel(d) }}
              </button>
            </div>
            <label class="inline-flex items-center gap-1 text-xs text-slate-600 dark:text-slate-400">
              {{ t('checklist.escalate_after') }}
              <input v-model.number="slot.escalate_after_minutes" type="number" min="5" max="720" placeholder="—" class="w-20 rounded border border-slate-200 px-2 py-1 text-sm dark:border-slate-600 dark:bg-slate-800" :disabled="!canManage" />
              {{ t('checklist.minutes') }}
            </label>
            <label class="inline-flex items-center gap-1 text-xs"><input v-model="slot.is_active" type="checkbox" class="accent-teal-600" :disabled="!canManage" />{{ t('checklist.active') }}</label>
            <span class="flex-1" />
            <template v-if="canManage">
              <button type="button" class="rounded-md bg-teal-600 px-2.5 py-1 text-xs font-semibold text-white" @click="saveSlot(slot)">{{ t('checklist.save') }}</button>
              <button type="button" class="rounded-md px-2 py-1 text-xs text-rose-600" @click="removeSlot(tpl, slot)">{{ t('checklist.delete') }}</button>
            </template>
          </li>
        </ul>
        <button v-if="canManage" type="button" class="mt-2 text-sm font-medium text-teal-700 hover:underline dark:text-teal-400" @click="addSlot(tpl)">+ {{ t('checklist.add_slot') }}</button>
      </div>

      <!-- Mục kiểm tra -->
      <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[900px] border-separate border-spacing-0 text-left text-sm">
          <thead>
            <tr class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-400">
              <th class="w-16 border-b border-slate-200 px-2 py-2 dark:border-slate-700" />
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('checklist.col_group') }}</th>
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('checklist.col_item') }}</th>
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('checklist.col_severity') }}</th>
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('checklist.col_options') }}</th>
              <th class="w-32 border-b border-slate-200 px-2 py-2 dark:border-slate-700" />
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-for="(item, idx) in tpl.items" :key="item.id" :class="item.is_active ? '' : 'opacity-50'">
              <td class="px-2 py-2 align-top">
                <div v-if="canManage" class="flex flex-col">
                  <button type="button" class="text-slate-500 disabled:opacity-30" :disabled="idx === 0" :aria-label="t('checklist.move_up')" @click="move(tpl, idx, -1)">▲</button>
                  <button type="button" class="text-slate-500 disabled:opacity-30" :disabled="idx === tpl.items.length - 1" :aria-label="t('checklist.move_down')" @click="move(tpl, idx, 1)">▼</button>
                </div>
              </td>
              <td class="px-2 py-2 align-top"><input v-model="item.group_name" :disabled="!canManage" class="w-40 rounded border border-slate-200 px-2 py-1 text-xs dark:border-slate-600 dark:bg-slate-800" /></td>
              <td class="px-2 py-2 align-top">
                <input v-model="item.title" :disabled="!canManage" class="w-full rounded border border-slate-200 px-2 py-1 text-sm font-medium dark:border-slate-600 dark:bg-slate-800" />
                <textarea v-model="item.requirement" :disabled="!canManage" rows="1" :placeholder="t('checklist.requirement')" class="mt-1 w-full rounded border border-slate-200 px-2 py-1 text-xs dark:border-slate-600 dark:bg-slate-800" />
                <textarea v-model="item.ng_action" :disabled="!canManage" rows="1" :placeholder="t('checklist.ng_action')" class="mt-1 w-full rounded border border-slate-200 px-2 py-1 text-xs dark:border-slate-600 dark:bg-slate-800" />
              </td>
              <td class="px-2 py-2 align-top">
                <select v-model="item.severity" :disabled="!canManage" class="rounded border border-slate-200 py-1 pl-2 pr-7 text-xs dark:border-slate-600 dark:bg-slate-800">
                  <option value="critical">Critical</option>
                  <option value="non_critical">Non-critical</option>
                </select>
              </td>
              <td class="px-2 py-2 align-top text-xs">
                <label class="flex items-center gap-1"><input v-model="item.allow_na" type="checkbox" :disabled="!canManage" class="accent-teal-600" />{{ t('checklist.opt_allow_na') }}</label>
                <label class="flex items-center gap-1"><input v-model="item.is_active" type="checkbox" :disabled="!canManage" class="accent-teal-600" />{{ t('checklist.active') }}</label>
                <span v-if="item.auto_source" class="mt-1 inline-block rounded bg-sky-50 px-1.5 py-0.5 text-[10px] text-sky-700 dark:bg-sky-900/30 dark:text-sky-300">{{ item.auto_source }}</span>
              </td>
              <td class="px-2 py-2 align-top">
                <template v-if="canManage">
                  <button type="button" class="rounded-md bg-teal-600 px-2.5 py-1 text-xs font-semibold text-white" @click="saveItem(tpl, item)">{{ t('checklist.save') }}</button>
                  <button type="button" class="ml-1 rounded-md px-2 py-1 text-xs text-rose-600" @click="removeItem(tpl, item)">{{ t('checklist.delete') }}</button>
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <button v-if="canManage" type="button" class="mt-3 text-sm font-medium text-teal-700 hover:underline dark:text-teal-400" @click="addItem(tpl)">+ {{ t('checklist.add_item') }}</button>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'
import {
  createChecklistItem,
  createReminderSlot,
  deleteChecklistItem,
  deleteReminderSlot,
  getChecklistSettings,
  reorderChecklistItems,
  updateChecklistItem,
  updateChecklistTemplate,
  updateReminderSlot,
} from '../../api/checklists'
import { confirmAction } from '../../composables/useConfirm'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { useAuthStore } from '../../store'

const { t } = useI18n()
const auth = useAuthStore()
const canManage = computed(() => auth.hasPermission('checklist.manage'))

const templates = ref([])
const loading = ref(false)

function weekdayLabel(d) {
  return d === 7 ? 'CN' : `T${d + 1}`
}

async function load() {
  loading.value = true
  try {
    const res = await getChecklistSettings()
    templates.value = (res.templates ?? []).map((tpl) => ({
      ...tpl,
      reminder_slots: tpl.reminder_slots.map((s) => ({ ...s, weekdays: [...(s.weekdays ?? [])] })),
    }))
  } catch (err) {
    showAppErrorFromApi(err, t('checklist.load_error'))
  } finally {
    loading.value = false
  }
}

async function run(fn, successMsg) {
  try {
    await fn()
    if (successMsg) showAppSuccess(successMsg)
  } catch (err) {
    showAppErrorFromApi(err, t('checklist.save_error'))
  }
}

function toggleTemplate(tpl, active) {
  void run(async () => {
    await updateChecklistTemplate(tpl.id, { is_active: active })
    tpl.is_active = active
  }, t('checklist.saved'))
}

function toggleDay(slot, d) {
  slot.weekdays = slot.weekdays.includes(d) ? slot.weekdays.filter((x) => x !== d) : [...slot.weekdays, d].sort()
}

function slotPayload(slot) {
  return {
    template_id: slot.template_id,
    remind_at: String(slot.remind_at).slice(0, 5),
    weekdays: slot.weekdays,
    escalate_after_minutes: slot.escalate_after_minutes || null,
    is_active: !!slot.is_active,
  }
}

function saveSlot(slot) {
  void run(async () => {
    const saved = slot.id ? await updateReminderSlot(slot.id, slotPayload(slot)) : await createReminderSlot(slotPayload(slot))
    Object.assign(slot, saved)
  }, t('checklist.saved'))
}

function addSlot(tpl) {
  tpl.reminder_slots.push({ id: null, template_id: tpl.id, remind_at: '06:00', weekdays: [1, 2, 3, 4, 5], escalate_after_minutes: null, is_active: true })
}

async function removeSlot(tpl, slot) {
  if (slot.id) {
    const ok = await confirmAction({ message: t('checklist.delete_slot_confirm'), danger: true })
    if (!ok) return
    await run(() => deleteReminderSlot(slot.id))
  }
  tpl.reminder_slots = tpl.reminder_slots.filter((s) => s !== slot)
}

function itemPayload(item) {
  return {
    group_name: item.group_name,
    title: item.title,
    requirement: item.requirement || null,
    severity: item.severity,
    ng_action: item.ng_action || null,
    allow_na: !!item.allow_na,
    is_active: !!item.is_active,
  }
}

function saveItem(tpl, item) {
  void run(async () => {
    const saved = item.id ? await updateChecklistItem(tpl.id, item.id, itemPayload(item)) : await createChecklistItem(tpl.id, itemPayload(item))
    Object.assign(item, saved)
    tpl.version += 1
  }, t('checklist.saved'))
}

function addItem(tpl) {
  const last = tpl.items[tpl.items.length - 1]
  tpl.items.push({ id: null, group_name: last?.group_name || '', title: '', requirement: '', severity: 'critical', ng_action: '', allow_na: false, is_active: true, auto_source: null })
}

async function removeItem(tpl, item) {
  if (item.id) {
    const ok = await confirmAction({ message: t('checklist.delete_item_confirm'), danger: true })
    if (!ok) return
    await run(() => deleteChecklistItem(tpl.id, item.id))
  }
  tpl.items = tpl.items.filter((i) => i !== item)
}

function move(tpl, idx, dir) {
  const target = idx + dir
  const list = [...tpl.items]
  ;[list[idx], list[target]] = [list[target], list[idx]]
  tpl.items = list
  const ids = list.map((i) => i.id).filter(Boolean)
  void run(() => reorderChecklistItems(tpl.id, ids))
}

onMounted(load)
</script>
