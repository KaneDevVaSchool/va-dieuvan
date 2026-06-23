import { ref, unref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { searchPortalDeptHeads } from '../api/requests'
import { formatApiError } from '../api/http'

function chosenLabel(u) {
  const name = String(u?.name ?? '').trim()
  const email = String(u?.email ?? '').trim()
  return email ? `${name} — ${email}` : name
}

/**
 * Gán trưởng đơn vị khi tạo phiếu (portal hoặc app điều vận).
 * @param {{ value: object }} form — reactive form (`dept_head_user_id`, `dept_head_label`)
 * @param {import('vue').MaybeRefOrGetter<boolean>} [needsDeptHead] — bắt buộc chọn trưởng ĐV (portal, không D2D)
 * @param {{ searchDeptHeads?: (opts: { q?: string }) => Promise<unknown[]> }} [options]
 */
export function usePortalDeptHeadSearch(form, needsDeptHead, options = {}) {
  const searchDeptHeads = options.searchDeptHeads ?? searchPortalDeptHeads
  const { t } = useI18n()

  const deptHeadQ = ref('')
  const deptHeadResults = ref([])
  const deptHeadLoading = ref(false)
  const deptHeadDropdownOpen = ref(false)
  const deptHeadSearchError = ref('')
  const deptHeadClientError = ref('')
  const lockedLabel = ref('')

  let searchTimer = null
  let blurTimer = null

  function syncFromForm() {
    const label = String(form.value.dept_head_label ?? '').trim()
    const id = form.value.dept_head_user_id
    if (id != null && id !== '' && label) {
      lockedLabel.value = label
      deptHeadQ.value = label
    }
  }

  watch(
    () => [form.value.dept_head_user_id, form.value.dept_head_label],
    () => syncFromForm(),
    { immediate: true },
  )

  watch(deptHeadQ, () => {
    if (lockedLabel.value && deptHeadQ.value.trim() !== lockedLabel.value.trim()) {
      form.value.dept_head_user_id = ''
      form.value.dept_head_label = ''
    }
  })

  function scheduleDeptHeadSearch() {
    deptHeadSearchError.value = ''
    deptHeadClientError.value = ''
    clearTimeout(searchTimer)
    searchTimer = setTimeout(runDeptHeadSearch, 350)
  }

  async function runDeptHeadSearch() {
    const q = deptHeadQ.value.trim()
    if (q.length < 2) {
      deptHeadResults.value = []
      deptHeadDropdownOpen.value = false
      return
    }
    deptHeadLoading.value = true
    deptHeadDropdownOpen.value = true
    try {
      const rows = await searchDeptHeads({ q })
      deptHeadResults.value = Array.isArray(rows) ? rows : []
      deptHeadSearchError.value = ''
    } catch (e) {
      deptHeadResults.value = []
      deptHeadSearchError.value = formatApiError(e, t('request_detail.assign_dept_head_load_err'))
    } finally {
      deptHeadLoading.value = false
    }
  }

  function onDeptHeadSearchFocus() {
    clearTimeout(blurTimer)
    if (deptHeadQ.value.trim().length >= 2) {
      deptHeadDropdownOpen.value = true
      runDeptHeadSearch()
    }
  }

  function onDeptHeadSearchBlur() {
    blurTimer = setTimeout(() => {
      deptHeadDropdownOpen.value = false
      if (unref(needsDeptHead) && !String(form.value.dept_head_user_id ?? '').trim()) {
        validateDeptHeadSelected()
      }
    }, 200)
  }

  function pickDeptHead(u) {
    deptHeadClientError.value = ''
    form.value.dept_head_user_id = u?.id != null ? String(u.id) : ''
    lockedLabel.value = chosenLabel(u)
    form.value.dept_head_label = lockedLabel.value
    deptHeadQ.value = lockedLabel.value
    deptHeadResults.value = []
    deptHeadDropdownOpen.value = false
  }

  function clearDeptHeadSearch() {
    deptHeadQ.value = ''
    lockedLabel.value = ''
    form.value.dept_head_user_id = ''
    form.value.dept_head_label = ''
    deptHeadClientError.value = ''
    deptHeadSearchError.value = ''
  }

  function validateDeptHeadSelected() {
    deptHeadClientError.value = ''
    const raw = form.value.dept_head_user_id
    if (raw === '' || raw == null) {
      deptHeadClientError.value = t('request_detail.assign_dept_head_required')
      return false
    }
    return true
  }

  return {
    deptHeadQ,
    deptHeadResults,
    deptHeadLoading,
    deptHeadDropdownOpen,
    deptHeadSearchError,
    deptHeadClientError,
    scheduleDeptHeadSearch,
    onDeptHeadSearchFocus,
    onDeptHeadSearchBlur,
    pickDeptHead,
    clearDeptHeadSearch,
    validateDeptHeadSelected,
    syncDeptHeadFromForm: syncFromForm,
  }
}
