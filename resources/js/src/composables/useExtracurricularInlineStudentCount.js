import { reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { patchPassengerCount, patchPortalRecurringInstance } from '../api/requests'
import { formatApiError } from '../api/http'
import { showAppSuccess } from './appMessage'

/**
 * Inline draft + save for extracurricular student count (list views).
 * @param {import('vue').Ref|import('vue').ComputedRef} requestsRef
 * @param {ReturnType<import('./useExtracurricularRequestRow').useExtracurricularRequestRow>} row
 * @param {{ variant: import('vue').Ref<string>|string, onSaved?: () => void, i18nPrefix: import('vue').Ref<string>|string }} options
 */
export function useExtracurricularInlineStudentCount(requestsRef, row, options) {
  const { t } = useI18n()
  const variant = () => (typeof options.variant === 'string' ? options.variant : options.variant.value)
  const prefix = () => (typeof options.i18nPrefix === 'string' ? options.i18nPrefix : options.i18nPrefix.value)
  const drafts = reactive({})
  const errors = reactive({})
  const savingId = ref(null)

  watch(
    requestsRef,
    (list) => {
      for (const req of list || []) {
        if (req?.id != null) drafts[req.id] = row.actualStudentCount(req)
      }
    },
    { immediate: true, deep: true },
  )

  function draftFor(id) {
    return drafts[id] ?? 1
  }

  function setDraft(id, val) {
    drafts[id] = val
    delete errors[id]
  }

  async function saveCount(req) {
    if (!row.canEditStudentCount(req) || savingId.value) return false
    const id = req.id
    const n = Math.round(Number(draftFor(id)))
    const p = prefix()
    if (!Number.isFinite(n) || n < 1 || n > 999) {
      errors[id] = t(`${p}.invalid_count`)
      return false
    }
    savingId.value = id
    delete errors[id]
    try {
      if (variant() === 'portal') {
        await patchPortalRecurringInstance(id, { student_count_actual: n })
      } else {
        await patchPassengerCount(id, n)
      }
      if (req.student_count_actual !== undefined) {
        req.student_count_actual = n
      }
      showAppSuccess(
        t(`${p}.save_success`, { count: n, id }),
        t(`${p}.save_success_title`),
      )
      options.onSaved?.()
      return true
    } catch (e) {
      errors[id] = formatApiError(e, t(`${p}.save_fail`))
      return false
    } finally {
      savingId.value = null
    }
  }

  return {
    drafts,
    errors,
    savingId,
    draftFor,
    setDraft,
    saveCount,
  }
}
