import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { searchUsersForPortalForm } from '../api/requests'
import { formatApiError } from '../api/http'

/** Chỉ giữ chữ số, tối đa 11 ký tự; hỗ trợ +84 → 0… */
export function sanitizeVnPhoneDigits(raw) {
  if (raw == null) return ''
  let d = String(raw).replace(/\D/g, '')
  if (d.startsWith('84') && d.length >= 10) d = `0${d.slice(2)}`
  if (d.length > 11) d = d.slice(0, 11)
  return d
}

/**
 * Tìm nhân sự cho trưởng đoàn / phụ trách (BM.03 d.2) trên portal.
 * @param {{ coordinatorName: string, coordinatorEmail: string, coordinatorPhone: string }} draft
 */
export function usePortalCoordinatorUserSearch(draft) {
  const { t } = useI18n()

  const coordinatorSearchQ = ref('')
  const coordinatorSearchResults = ref([])
  const coordinatorSearchLoading = ref(false)
  const coordinatorDropdownOpen = ref(false)
  const coordinatorSearchError = ref('')

  let coordinatorSearchTimer = null
  let coordinatorBlurTimer = null

  function scheduleCoordinatorSearch() {
    coordinatorSearchError.value = ''
    clearTimeout(coordinatorSearchTimer)
    coordinatorSearchTimer = setTimeout(runCoordinatorSearch, 350)
  }

  async function runCoordinatorSearch() {
    const q = coordinatorSearchQ.value.trim()
    if (q.length < 2) {
      coordinatorSearchResults.value = []
      coordinatorSearchError.value = ''
      coordinatorDropdownOpen.value = false
      return
    }
    coordinatorSearchLoading.value = true
    coordinatorDropdownOpen.value = true
    try {
      const rows = await searchUsersForPortalForm(q)
      coordinatorSearchResults.value = Array.isArray(rows) ? rows : []
      coordinatorSearchError.value = ''
    } catch (e) {
      coordinatorSearchResults.value = []
      coordinatorSearchError.value = formatApiError(e, t('dispatch_wizard.search_staff_fail'))
    } finally {
      coordinatorSearchLoading.value = false
    }
  }

  function onCoordinatorSearchFocus() {
    clearTimeout(coordinatorBlurTimer)
    if (coordinatorSearchQ.value.trim().length >= 2) coordinatorDropdownOpen.value = true
  }

  function onCoordinatorSearchBlur() {
    coordinatorBlurTimer = setTimeout(() => {
      coordinatorDropdownOpen.value = false
    }, 200)
  }

  function pickCoordinator(u) {
    coordinatorSearchError.value = ''
    draft.coordinatorName = u?.name || ''
    draft.coordinatorEmail = u?.email || ''
    draft.coordinatorPhone = sanitizeVnPhoneDigits(u?.phone || '')
    coordinatorSearchQ.value = u?.name || ''
    coordinatorSearchResults.value = []
    coordinatorDropdownOpen.value = false
  }

  function syncCoordinatorSearchFromDraft() {
    coordinatorSearchQ.value = String(draft.coordinatorName || '').trim()
  }

  function onCoordinatorPhoneInput(e) {
    draft.coordinatorPhone = sanitizeVnPhoneDigits(e?.target?.value)
  }

  return {
    coordinatorSearchQ,
    coordinatorSearchResults,
    coordinatorSearchLoading,
    coordinatorDropdownOpen,
    coordinatorSearchError,
    scheduleCoordinatorSearch,
    onCoordinatorSearchFocus,
    onCoordinatorSearchBlur,
    pickCoordinator,
    syncCoordinatorSearchFromDraft,
    onCoordinatorPhoneInput,
  }
}
