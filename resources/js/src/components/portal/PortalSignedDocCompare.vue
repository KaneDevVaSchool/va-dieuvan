<template>
  <section v-if="pdfBlobUrl || signedBlobUrl" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-2.5 sm:px-5">
      <h3 class="text-sm font-bold text-slate-900">{{ t('portal.signed_compare_title') }}</h3>
      <div class="mt-2 flex gap-2 sm:hidden">
        <button
          type="button"
          class="flex-1 rounded-lg px-2 py-1.5 text-xs font-semibold"
          :class="mobileTab === 'original' ? 'bg-va-800 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200'"
          @click="mobileTab = 'original'"
        >
          {{ t('portal.signed_compare_tab_original') }}
        </button>
        <button
          type="button"
          class="flex-1 rounded-lg px-2 py-1.5 text-xs font-semibold"
          :class="mobileTab === 'signed' ? 'bg-va-800 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200'"
          :disabled="!signedBlobUrl"
          @click="mobileTab = 'signed'"
        >
          {{ t('portal.signed_compare_tab_signed') }}
        </button>
      </div>
    </div>
    <div class="grid gap-0 sm:grid-cols-2">
      <div class="border-b border-slate-100 p-2 sm:border-b-0 sm:border-r" :class="mobileHidden('original')">
        <p class="mb-2 hidden px-1 text-[11px] font-semibold uppercase text-slate-500 sm:block">{{ t('portal.signed_compare_tab_original') }}</p>
        <iframe v-if="pdfBlobUrl" :src="pdfIframeSrc" class="h-[min(50vh,480px)] w-full rounded-lg border border-slate-200 bg-white" :title="t('portal.pdf_preview_iframe_title')" />
      </div>
      <div class="p-2" :class="mobileHidden('signed')">
        <p class="mb-2 hidden px-1 text-[11px] font-semibold uppercase text-slate-500 sm:block">{{ t('portal.signed_compare_tab_signed') }}</p>
        <iframe
          v-if="signedBlobUrl && signedIsPdf"
          :src="signedIframeSrc"
          class="h-[min(50vh,480px)] w-full rounded-lg border border-slate-200 bg-white"
          :title="t('portal.signed_compare_tab_signed')"
        />
        <img
          v-else-if="signedBlobUrl"
          :src="signedBlobUrl"
          class="mx-auto max-h-[min(50vh,480px)] w-full rounded-lg border border-slate-200 object-contain"
          alt=""
        />
        <p v-else class="py-8 text-center text-xs text-slate-500">{{ t('portal.signed_compare_empty_signed') }}</p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  pdfBlobUrl: { type: String, default: '' },
  signedBlobUrl: { type: String, default: '' },
  signedMime: { type: String, default: '' },
})

const { t } = useI18n()
const mobileTab = ref('original')

const pdfIframeSrc = computed(() => (props.pdfBlobUrl ? `${props.pdfBlobUrl}#view=FitH` : ''))
const signedIsPdf = computed(() => String(props.signedMime || '').includes('pdf'))
const signedIframeSrc = computed(() => (props.signedBlobUrl && signedIsPdf.value ? `${props.signedBlobUrl}#view=FitH` : ''))

function mobileHidden(tab) {
  if (typeof window !== 'undefined' && window.matchMedia('(min-width: 640px)').matches) {
    return ''
  }
  return mobileTab.value === tab ? '' : 'hidden sm:block'
}
</script>
