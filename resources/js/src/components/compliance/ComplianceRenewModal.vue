<template>
  <Teleport to="body">
    <div
      v-if="open && doc"
      class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-4 sm:items-center"
      role="dialog"
      aria-modal="true"
      @click.self="$emit('close')"
    >
      <div
        class="flex max-h-[min(90dvh,calc(100dvh-2rem))] w-full max-w-lg flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-600 dark:bg-slate-900"
        @click.stop
      >
        <div class="shrink-0 border-b border-slate-200 px-4 py-3 dark:border-slate-700">
          <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('compliance.renew_title') }}</h2>
          <p class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">
            {{ docTypeLabel }}<span v-if="doc.title"> · {{ doc.title }}</span>
          </p>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('compliance.renew_hint') }}</p>
          <p v-if="doc.expires_at" class="mt-1 text-xs font-medium text-amber-700 dark:text-amber-400">
            {{ t('compliance.current_expires', { date: formatIsoDate(doc.expires_at, locale) }) }}
          </p>
        </div>

        <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submit">
          <div class="min-h-0 flex-1 space-y-3 overflow-y-auto overscroll-y-contain p-4">
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('compliance.file_required') }} *
              <input
                ref="fileInput"
                type="file"
                accept=".pdf,.jpg,.jpeg,.png,.webp,.heic,.heif,.doc,.docx,.xls,.xlsx"
                class="mt-1 w-full text-sm file:mr-3 file:rounded file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-teal-800 dark:file:bg-teal-950 dark:file:text-teal-300"
                data-testid="compliance-renew-file"
                @change="file = $event.target.files?.[0] || null"
              />
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('driver_detail.issued_at') }}
                <input
                  v-model="form.issued_at"
                  type="date"
                  class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                />
              </label>
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('driver_detail.expires_at') }} *
                <input
                  v-model="form.expires_at"
                  type="date"
                  required
                  :min="minExpires"
                  class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                  data-testid="compliance-renew-expires"
                />
              </label>
            </div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('compliance.document_no') }}
              <input
                v-model="form.document_no"
                type="text"
                maxlength="100"
                :placeholder="t('compliance.ph_document_no')"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              />
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.col_title') }}
              <input
                v-model="form.title"
                type="text"
                maxlength="255"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              />
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.notes') }}
              <textarea
                v-model="form.notes"
                rows="2"
                maxlength="2000"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              />
            </label>
            <p v-if="error" class="text-xs text-rose-600" role="alert">{{ error }}</p>
          </div>
          <div class="flex shrink-0 gap-2 border-t border-slate-200 p-4 dark:border-slate-700">
            <button
              type="button"
              class="flex-1 rounded-lg border border-slate-200 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
              @click="$emit('close')"
            >
              {{ t('app.cancel') }}
            </button>
            <button
              type="submit"
              class="flex-1 rounded-lg bg-teal-600 py-2 text-sm font-medium text-white hover:bg-teal-500 disabled:opacity-50"
              :disabled="saving"
              data-testid="compliance-renew-submit"
            >
              {{ saving ? t('resources.loading') : t('compliance.submit_renew') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatIsoDate } from '../../util/datetime'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

/**
 * Gia hạn chứng từ: parent truyền `submit(formData)` gọi API renew tương ứng (xe / tài xế).
 */
const props = defineProps({
  open: { type: Boolean, default: false },
  /** Chứng từ đang hiệu lực cần gia hạn. */
  doc: { type: Object, default: null },
  docTypeLabel: { type: String, default: '' },
  /** @type {(fd: FormData) => Promise<unknown>} */
  submit: { type: Function, required: true },
})

const emit = defineEmits(['close', 'renewed'])

const { t, locale } = useI18n()

const fileInput = ref(null)
const file = ref(null)
const saving = ref(false)
const error = ref('')
const form = ref(emptyForm())

function emptyForm() {
  return { issued_at: '', expires_at: '', document_no: '', title: '', notes: '' }
}

/** Hạn mới phải sau hạn hiện tại. */
const minExpires = computed(() => {
  const cur = props.doc?.expires_at
  if (!cur) return undefined
  const d = new Date(`${cur}T12:00:00`)
  d.setDate(d.getDate() + 1)
  return d.toISOString().slice(0, 10)
})

watch(
  () => [props.open, props.doc?.id],
  ([open]) => {
    if (!open) return
    form.value = { ...emptyForm(), title: props.doc?.title || '' }
    file.value = null
    error.value = ''
    if (fileInput.value) fileInput.value.value = ''
  },
)

async function submit() {
  error.value = ''
  if (!file.value) {
    error.value = t('compliance.file_missing')
    return
  }
  if (!form.value.expires_at) {
    error.value = t('compliance.expires_required')
    return
  }
  const fd = new FormData()
  fd.append('file', file.value)
  fd.append('expires_at', form.value.expires_at)
  for (const k of ['issued_at', 'document_no', 'title', 'notes']) {
    const v = String(form.value[k] || '').trim()
    if (v) fd.append(k, v)
  }

  saving.value = true
  try {
    const renewed = await props.submit(fd)
    showAppSuccess(t('compliance.renewed_toast'))
    emit('renewed', renewed)
  } catch (e) {
    if (e?.response?.status === 422) {
      const msg = e.response.data?.message
      const errs = e.response.data?.errors
      error.value =
        (errs && typeof errs === 'object' ? Object.values(errs).flat().join(' ') : '') ||
        (typeof msg === 'string' && msg) ||
        t('resources.load_error')
    } else {
      showAppErrorFromApi(e, t('resources.load_error'))
    }
  } finally {
    saving.value = false
  }
}
</script>
