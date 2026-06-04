<template>
  <Modal :open="open" :title="t('p2p_policy_page.generate_title')" :description="t('p2p_policy_page.generate_hint')" @close="emit('close')">
    <div class="space-y-4">
      <Input
        v-model="date"
        type="date"
        :label="t('p2p_policy_page.filter_date')"
        required
      />

      <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs leading-relaxed text-slate-600">
        {{ t('p2p_policy_page.generate_note') }}
      </p>

      <div
        v-if="result"
        class="rounded-lg border px-3 py-2 text-sm"
        :class="result.ok ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800'"
      >
        {{ result.message }}
      </div>

      <div class="flex justify-end gap-2 pt-1">
        <Button variant="secondary" :disabled="loading" @click="emit('close')">{{ t('common.cancel') }}</Button>
        <Button :loading="loading" :disabled="!date" @click="submit">{{ t('p2p_policy_page.generate_action') }}</Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Modal from '../ui/Modal.vue'
import Button from '../ui/Button.vue'
import Input from '../ui/Input.vue'
import { generatePolicyTrips } from '../../api/p2p'
import { formatApiError } from '../../api/http'

const props = defineProps({
  open: { type: Boolean, default: false },
  defaultDate: { type: String, default: '' },
})
const emit = defineEmits(['close', 'generated'])
const { t } = useI18n()

const date = ref(props.defaultDate)
const loading = ref(false)
const result = ref(null)

watch(
  () => props.open,
  (v) => {
    if (v) {
      date.value = props.defaultDate || new Date().toISOString().slice(0, 10)
      result.value = null
    }
  },
)

async function submit() {
  loading.value = true
  result.value = null
  try {
    const res = await generatePolicyTrips(date.value)
    result.value = {
      ok: true,
      message: t('p2p_policy_page.generate_result', { created: res.created, skipped: res.skipped }),
    }
    emit('generated', { date: date.value, ...res })
  } catch (err) {
    result.value = { ok: false, message: formatApiError(err) }
  } finally {
    loading.value = false
  }
}
</script>
