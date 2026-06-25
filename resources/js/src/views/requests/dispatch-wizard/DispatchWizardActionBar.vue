<template>
  <div
    class="dw-wizard-action-bar"
    role="toolbar"
    :aria-label="t('dispatch_wizard.create.action_bar_aria')"
    data-testid="dispatch-wizard-action-bar"
  >
    <div class="dw-wizard-action-bar__inner">
      <button
        type="button"
        class="dw-wizard-action-bar__back"
        :disabled="backDisabled"
        data-testid="dispatch-wizard-back"
        @click="$emit('back')"
      >
        <ArrowLeftIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
        {{ t('dispatch_wizard.create.back') }}
      </button>
      <div class="dw-wizard-action-bar__right">
        <button
          v-if="showSaveDraft"
          type="button"
          class="dw-wizard-action-bar__secondary"
          data-testid="dispatch-wizard-save-draft"
          @click="$emit('save-draft')"
        >
          {{ t('dispatch_wizard.create.save_draft') }}
        </button>
        <button
          v-if="showNext"
          type="button"
          class="dw-wizard-action-bar__primary"
          :disabled="!canGoNext"
          data-testid="dispatch-wizard-next"
          @click="$emit('next')"
        >
          {{ nextLabel }}
        </button>
        <button
          v-else-if="showSubmit"
          type="button"
          class="dw-wizard-action-bar__primary"
          :disabled="submitDisabled"
          data-testid="dispatch-wizard-submit"
          @click="$emit('submit')"
        >
          <span
            v-if="loading"
            class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"
            aria-hidden="true"
          />
          {{ submitLabel }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

defineProps({
  backDisabled: { type: Boolean, default: false },
  canGoNext: { type: Boolean, default: false },
  showNext: { type: Boolean, default: true },
  showSaveDraft: { type: Boolean, default: true },
  showSubmit: { type: Boolean, default: false },
  submitDisabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  nextLabel: { type: String, default: '' },
  submitLabel: { type: String, default: '' },
})

defineEmits(['back', 'next', 'save-draft', 'submit'])

const { t } = useI18n()
</script>
