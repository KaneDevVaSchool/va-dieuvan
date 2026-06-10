<template>
  <Modal
    :open="open"
    extra-wide
    :title="t('request_detail.pricing_modal_title')"
    :description="t('request_detail.pricing_modal_lead')"
    @close="$emit('close')"
  >
    <ReferencePricingReadOnlyBody
      :loading="loading"
      :error="error"
      :passenger-fares="passengerFares"
      :cargo-fares="cargoFares"
      :notes="notes"
    />
    <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4">
      <RouterLink
        :to="{ name: 'pricing' }"
        class="inline-flex items-center gap-1.5 text-sm font-semibold text-teal-700 underline decoration-teal-500/30 underline-offset-2 hover:text-teal-900"
        data-testid="reference-pricing-modal-open-page"
        @click="$emit('close')"
      >
        {{ t('request_detail.pricing_modal_open_app') }}
        <ArrowTopRightOnSquareIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
      </RouterLink>
      <RouterLink
        v-if="canManage"
        :to="{ name: 'pricing' }"
        class="text-sm font-medium text-slate-600 underline decoration-slate-300 underline-offset-2 hover:text-slate-900"
        data-testid="reference-pricing-modal-manage"
        @click="$emit('close')"
      >
        {{ t('request_detail.reference_pricing_manage_link') }}
      </RouterLink>
    </div>
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline'
import Modal from '../ui/Modal.vue'
import ReferencePricingReadOnlyBody from './ReferencePricingReadOnlyBody.vue'
import { getReferencePricing } from '../../api/pricing'
import { useAuthStore } from '../../store'

const props = defineProps({
  open: { type: Boolean, default: false },
})

defineEmits(['close'])

const { t } = useI18n()
const auth = useAuthStore()
const canManage = computed(() => auth.hasPermission('reference_pricing.manage'))

const loading = ref(false)
const error = ref('')
const passengerFares = ref([])
const cargoFares = ref([])
const notes = ref([])

let loaded = false

async function loadPricing() {
  loading.value = true
  error.value = ''
  try {
    const data = await getReferencePricing()
    passengerFares.value = data?.passenger_fares ?? []
    cargoFares.value = data?.cargo_fares ?? []
    notes.value = data?.notes ?? []
    loaded = true
  } catch (e) {
    error.value = e?.response?.data?.message ?? t('request_detail.reference_pricing_load_error')
  } finally {
    loading.value = false
  }
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen && !loaded) loadPricing()
  },
)
</script>
