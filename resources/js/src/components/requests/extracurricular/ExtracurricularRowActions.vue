<template>
  <AppRowActionsMenu
    align="end"
    :aria-label="t('portal.extracurricular_list.action_menu_label')"
    :trigger-sr-only="t('portal.extracurricular_list.action_menu_label')"
    root-class="text-right"
  >
    <button
      v-if="showCompleteBm03"
      type="button"
      role="menuitem"
      class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm font-semibold text-violet-900 transition hover:bg-violet-50 disabled:cursor-not-allowed disabled:opacity-50"
      :disabled="!canCompleteBm03"
      :title="!canCompleteBm03 ? completeDisabledHint : undefined"
      @click="goCompleteBm03"
    >
      <DocumentTextIcon class="h-4 w-4 shrink-0 text-violet-600" aria-hidden="true" />
      <span class="min-w-0">
        <span class="block">{{ t('portal.extracurricular_list.action_complete_bm03') }}</span>
        <span v-if="variant === 'portal'" class="block text-[11px] font-normal text-violet-700/90">
          {{ t('portal.extracurricular_list.action_complete_bm03_hint') }}
        </span>
      </span>
    </button>
    <button
      v-if="variant === 'portal'"
      type="button"
      role="menuitem"
      class="flex w-full items-center gap-2 px-3 py-2 text-left text-slate-700 transition hover:bg-slate-50"
      @click="$emit('open-detail')"
    >
      <EyeIcon class="h-4 w-4 shrink-0 text-slate-500" aria-hidden="true" />
      {{ variant === 'portal' ? t('portal.extracurricular_table.open_detail') : t('requests_page.cta_detail') }}
    </button>
    <button
      v-if="showClone"
      type="button"
      role="menuitem"
      class="flex w-full items-center gap-2 border-t border-slate-100 px-3 py-2 text-left text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
      :disabled="!canClone || cloneBusy"
      :title="!canClone ? cloneDisabledHint : undefined"
      @click="canClone && $emit('clone')"
    >
      <DocumentDuplicateIcon
        v-if="!cloneBusy"
        class="h-4 w-4 shrink-0 text-slate-500"
        aria-hidden="true"
      />
      <span
        v-else
        class="h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-slate-300 border-t-slate-600"
        aria-hidden="true"
      />
      {{ cloneBusy ? cloneBusyLabel : cloneLabel }}
    </button>
  </AppRowActionsMenu>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import {
  DocumentDuplicateIcon,
  DocumentTextIcon,
  EyeIcon,
} from '@heroicons/vue/24/outline'
import AppRowActionsMenu from '../../ui/AppRowActionsMenu.vue'

const props = defineProps({
  req: { type: Object, required: true },
  variant: { type: String, default: 'portal' },
  detailRouteName: { type: String, default: '' },
  showCompleteBm03: { type: Boolean, default: false },
  canCompleteBm03: { type: Boolean, default: true },
  completeDisabledHint: { type: String, default: '' },
  showClone: { type: Boolean, default: true },
  canClone: { type: Boolean, default: false },
  cloneBusy: { type: Boolean, default: false },
  cloneLabel: { type: String, default: '' },
  cloneBusyLabel: { type: String, default: '' },
  cloneDisabledHint: { type: String, default: '' },
})

defineEmits(['clone', 'open-detail'])

const { t } = useI18n()
const router = useRouter()

function goCompleteBm03() {
  if (!props.canCompleteBm03) return
  if (props.variant === 'portal' && props.detailRouteName) {
    router.push({
      name: props.detailRouteName,
      params: { id: String(props.req.id) },
      query: { operate: '1' },
    })
  }
}
</script>
