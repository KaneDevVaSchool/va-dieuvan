<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex justify-end"
      role="dialog"
      aria-modal="true"
      :aria-label="t('tp_attendance_page.notify_title')"
    >
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[1px]" @click="$emit('close')" />
      <div
        class="relative flex h-full w-full max-w-lg flex-col border-l border-slate-200 bg-white shadow-2xl"
      >
        <header class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
          <h2 class="text-base font-semibold text-slate-900">{{ t('tp_attendance_page.notify_title') }}</h2>
          <button type="button" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100" @click="$emit('close')">
            <XMarkIcon class="h-5 w-5" />
          </button>
        </header>

        <div class="flex-1 space-y-4 overflow-y-auto p-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
              {{ t('tp_attendance_page.notify_scope') }}
            </p>
            <div class="mt-2 space-y-2">
              <label
                v-for="opt in scopeOptions"
                :key="opt.value"
                class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50"
                :class="scope === opt.value ? 'border-teal-300 bg-teal-50/50' : ''"
              >
                <input
                  v-model="scope"
                  type="radio"
                  class="mt-0.5 text-teal-600"
                  :value="opt.value"
                  @change="$emit('scope-change', scope)"
                />
                <span>
                  <span class="font-medium text-slate-800">{{ opt.label }}</span>
                  <span v-if="opt.hint" class="mt-0.5 block text-xs text-slate-500">{{ opt.hint }}</span>
                </span>
              </label>
            </div>
          </div>

          <div v-if="previewLoading" class="text-sm text-slate-500">{{ t('tp_attendance_page.notify_preview_loading') }}</div>
          <template v-else-if="preview">
            <div class="grid grid-cols-3 gap-2 text-center text-xs">
              <div class="rounded-lg bg-slate-50 px-2 py-2">
                <div class="font-semibold text-slate-900">{{ preview.recipient_count }}</div>
                <div class="text-slate-500">{{ t('tp_attendance_page.notify_stat_students') }}</div>
              </div>
              <div class="rounded-lg bg-emerald-50 px-2 py-2">
                <div class="font-semibold text-emerald-800">{{ preview.with_phone }}</div>
                <div class="text-emerald-700">{{ t('tp_attendance_page.notify_stat_with_phone') }}</div>
              </div>
              <div class="rounded-lg bg-amber-50 px-2 py-2">
                <div class="font-semibold text-amber-900">{{ preview.without_phone }}</div>
                <div class="text-amber-800">{{ t('tp_attendance_page.notify_stat_no_phone') }}</div>
              </div>
            </div>

            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                {{ t('tp_attendance_page.notify_preview') }}
              </p>
              <pre class="mt-2 whitespace-pre-wrap rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">{{ preview.message }}</pre>
            </div>
          </template>

          <div>
            <div class="flex items-center justify-between gap-2">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                {{ t('tp_attendance_page.notify_log') }}
              </p>
              <button type="button" class="text-xs text-teal-600 hover:underline" @click="$emit('refresh-logs')">
                {{ t('tp_attendance_page.notify_refresh_log') }}
              </button>
            </div>
            <ul v-if="logs.length" class="mt-2 max-h-48 space-y-2 overflow-y-auto text-xs">
              <li
                v-for="log in logs"
                :key="log.id"
                class="rounded-lg border border-slate-100 bg-white px-3 py-2"
              >
                <div class="flex justify-between gap-2 text-slate-500">
                  <span>{{ log.actor_name || '—' }}</span>
                  <span>{{ formatLogTime(log.created_at) }}</span>
                </div>
                <div class="mt-1 text-slate-700">
                  {{ t('tp_attendance_page.notify_log_line', {
                    ok: log.metadata?.queued ?? 0,
                    skip: log.metadata?.skipped_no_phone ?? 0,
                    fail: log.metadata?.failed ?? 0,
                  }) }}
                </div>
                <button
                  v-if="(log.metadata?.failed ?? 0) > 0 && log.metadata?.batch_id"
                  type="button"
                  class="mt-1 text-teal-600 hover:underline"
                  :disabled="sending"
                  @click="$emit('retry', log.metadata.batch_id)"
                >
                  {{ t('tp_attendance_page.notify_retry') }}
                </button>
              </li>
            </ul>
            <p v-else class="mt-2 text-xs text-slate-400">{{ t('tp_attendance_page.notify_log_empty') }}</p>
          </div>
        </div>

        <footer class="flex gap-2 border-t border-slate-100 p-4">
          <Button variant="secondary" class="flex-1" @click="$emit('close')">{{ t('common.cancel') }}</Button>
          <Button
            class="flex-1"
            :loading="sending"
            :disabled="!preview || preview.with_phone === 0"
            @click="$emit('send', { scope, studentIds: resolvedStudentIds })"
          >
            {{ t('tp_attendance_page.notify_send') }}
          </Button>
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import Button from '../../../components/ui/Button.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  preview: { type: Object, default: null },
  previewLoading: { type: Boolean, default: false },
  logs: { type: Array, default: () => [] },
  sending: { type: Boolean, default: false },
  selectedIds: { type: Array, default: () => [] },
  filteredIds: { type: Array, default: () => [] },
  initialScope: { type: String, default: 'selected' },
})

const emit = defineEmits(['close', 'send', 'refresh-logs', 'retry', 'scope-change'])

const { t } = useI18n()
const scope = ref(props.initialScope)

watch(
  () => props.open,
  (v) => {
    if (v) {
      scope.value = props.selectedIds.length ? 'selected' : 'all_absent'
      emit('scope-change', scope.value)
    }
  },
)

const scopeOptions = computed(() => [
  {
    value: 'selected',
    label: t('tp_attendance_page.notify_scope_selected'),
    hint: props.selectedIds.length
      ? t('tp_attendance_page.notify_scope_selected_n', { n: props.selectedIds.length })
      : t('tp_attendance_page.notify_scope_selected_empty'),
  },
  {
    value: 'filtered',
    label: t('tp_attendance_page.notify_scope_filtered'),
    hint: t('tp_attendance_page.notify_scope_filtered_n', { n: props.filteredIds.length }),
  },
  { value: 'all_absent', label: t('tp_attendance_page.notify_scope_all_absent') },
  { value: 'not_marked', label: t('tp_attendance_page.notify_scope_not_marked') },
])

const resolvedStudentIds = computed(() => {
  if (scope.value === 'selected') return props.selectedIds
  if (scope.value === 'filtered') return props.filteredIds
  return []
})

function formatLogTime(iso) {
  if (!iso) return ''
  try {
    return new Date(iso).toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' })
  } catch {
    return ''
  }
}
</script>
