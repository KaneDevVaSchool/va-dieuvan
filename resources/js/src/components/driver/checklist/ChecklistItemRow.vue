<template>
  <li
    class="rounded-xl bg-driver-surface p-3 ring-1 transition"
    :class="[error ? 'ring-rose-400/60' : item.result === 'ng' ? 'ring-rose-400/30' : 'ring-white/[0.06]']"
    :data-testid="`checklist-item-${item.id}`"
  >
    <div class="flex items-start gap-2">
      <div class="min-w-0 flex-1">
        <p class="text-[15px] font-semibold leading-snug">
          {{ item.title }}
          <span
            v-if="item.severity === 'critical'"
            class="ml-1 inline-block rounded bg-rose-500/20 px-1.5 py-0.5 align-middle text-[10px] font-bold uppercase tracking-wide text-rose-200"
          >{{ t('checklist.critical') }}</span>
        </p>
        <p v-if="item.requirement" class="mt-0.5 text-xs leading-snug text-driver-muted">{{ item.requirement }}</p>
        <p v-if="hintText" class="mt-1 inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[11px] font-medium" :class="hintClass">
          <InformationCircleIcon class="h-3.5 w-3.5" aria-hidden="true" />{{ hintText }}
        </p>
      </div>
      <span v-if="checkedTime" class="shrink-0 text-[11px] tabular-nums text-driver-muted/80">{{ checkedTime }}</span>
    </div>

    <div class="mt-2 grid gap-1.5" :class="item.allow_na ? 'grid-cols-3' : 'grid-cols-2'" role="radiogroup" :aria-label="item.title">
      <button
        v-for="opt in choices"
        :key="opt.value"
        type="button"
        role="radio"
        :aria-checked="item.result === opt.value"
        :disabled="!editable"
        class="min-h-10 min-w-0 rounded-lg px-1.5 py-1 text-[13px] font-semibold leading-4 ring-1 transition active:scale-[0.98] disabled:active:scale-100"
        :class="item.result === opt.value ? opt.active : 'bg-driver-bg/60 text-driver-muted ring-white/10'"
        :data-testid="`checklist-item-${item.id}-${opt.value}`"
        @click="$emit('update:result', item.result === opt.value ? null : opt.value)"
      >
        {{ opt.label }}
      </button>
    </div>

    <div v-if="item.result === 'ng'" class="mt-3 space-y-2">
      <p v-if="item.ng_action" class="rounded-lg bg-rose-500/10 px-2.5 py-2 text-xs leading-snug text-rose-100 ring-1 ring-rose-400/20">
        <span class="font-bold">{{ t('checklist.ng_action') }}:</span> {{ item.ng_action }}
      </p>
      <label :for="`checklist-note-${item.id}`" class="block text-xs font-semibold text-driver-ink">{{ t('checklist.ng_note_label') }}</label>
      <textarea
        v-autosize-textarea
        :id="`checklist-note-${item.id}`"
        :value="item.note || ''"
        :readonly="!editable"
        rows="2"
        maxlength="1000"
        class="w-full rounded-xl border border-white/10 bg-driver-bg/60 px-3 py-2 text-sm text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
        :placeholder="t('checklist.ng_note_ph')"
        :aria-invalid="!String(item.note || '').trim()"
        aria-required="true"
        @input="$emit('update:note', $event.target.value)"
      />
      <div class="flex flex-wrap items-center gap-2">
        <AuthAttachmentThumb
          v-for="a in item.attachments"
          :key="a.id"
          :attachment="a"
          size="sm"
          dark
          :removable="editable"
          :remove-label="t('checklist.photo_remove')"
          @remove="$emit('remove-photo', a)"
        />
        <label
          v-if="editable"
          class="flex h-10 cursor-pointer items-center gap-1.5 rounded-lg bg-driver-bg/60 px-3 text-xs font-semibold text-driver-accent ring-1 ring-white/10"
          :class="uploading ? 'opacity-50' : ''"
        >
          <CameraIcon class="h-5 w-5" aria-hidden="true" />
          {{ uploading ? t('checklist.photo_uploading') : t('checklist.photo_add') }}
          <input type="file" accept="image/*" capture="environment" class="sr-only" :disabled="uploading" @change="onFile" />
        </label>
      </div>
    </div>
    <p v-else-if="item.note && !editable" class="mt-2 text-xs text-driver-muted">{{ item.note }}</p>

    <p v-if="error" class="mt-2 text-xs font-medium text-rose-300">{{ error }}</p>
  </li>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { CameraIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'
import AuthAttachmentThumb from '../../shared/AuthAttachmentThumb.vue'
import vAutosizeTextarea from '../../../directives/autosizeTextarea'

const props = defineProps({
  item: { type: Object, required: true },
  editable: { type: Boolean, default: true },
  error: { type: String, default: '' },
  uploading: { type: Boolean, default: false },
})
const emit = defineEmits(['update:result', 'update:note', 'upload-photo', 'remove-photo'])
const { t } = useI18n()

const choices = computed(() => {
  const list = [
    { value: 'ok', label: t('checklist.result_ok'), active: 'bg-emerald-500 text-driver-bg ring-emerald-400' },
    { value: 'ng', label: t('checklist.result_ng'), active: 'bg-rose-500 text-white ring-rose-400' },
  ]
  if (props.item.allow_na) list.push({ value: 'na', label: t('checklist.result_na'), active: 'bg-slate-400 text-driver-bg ring-slate-300' })
  return list
})

const checkedTime = computed(() => {
  if (!props.item.checked_at) return ''
  return new Date(props.item.checked_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
})

const hintText = computed(() => {
  const h = props.item.prefill_hint
  if (!h) return ''
  const until = h.expires_at ? new Date(h.expires_at).toLocaleDateString('vi-VN') : ''
  return {
    missing: t('checklist.hint_missing'),
    exp: t('checklist.hint_expired', { date: until }),
    soon: t('checklist.hint_soon', { date: until }),
    ok: until ? t('checklist.hint_ok', { date: until }) : t('checklist.hint_ok_nodate'),
    none: t('checklist.hint_ok_nodate'),
  }[h.state] || ''
})

const hintClass = computed(() => ({
  exp: 'bg-rose-500/15 text-rose-200',
  missing: 'bg-amber-500/15 text-amber-200',
  soon: 'bg-amber-500/15 text-amber-200',
}[props.item.prefill_hint?.state] || 'bg-emerald-500/10 text-emerald-200'))

function onFile(ev) {
  const file = ev.target?.files?.[0]
  if (file) emit('upload-photo', file)
  ev.target.value = ''
}
</script>
