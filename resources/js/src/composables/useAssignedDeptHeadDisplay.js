import { computed, ref, toValue, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { getAvailableDeptHeads } from '../api/requests'

function formatDeptHeadChosenLabel(u) {
  const email = u?.email ? String(u.email).trim() : ''
  const name = u?.name ? String(u.name).trim() : ''
  return email !== '' ? `${name} — ${email}` : name
}

/**
 * Trưởng đơn vị gán trên phiếu (portal) — hiển thị read-only trên tab form.
 * @param {import('vue').MaybeRefOrGetter<object|null|undefined>} reqSource
 */
export function useAssignedDeptHeadDisplay(reqSource) {
  const { t } = useI18n()
  const deptHeadsLoadErr = ref('')
  const deptHeadLockedLabel = ref('')

  const presetDeptHeadId = computed(() => {
    const raw = toValue(reqSource)?.assigned_dept_head_id
    if (raw == null || raw === '') return null
    const n = Number(raw)
    return Number.isFinite(n) && n > 0 ? n : null
  })

  const showDeptHeadPanel = computed(() => toValue(reqSource)?.trip_type !== 'door_to_door')

  const hasAssignedDeptHead = computed(() => presetDeptHeadId.value != null)

  const showAssignedDeptHeadOnForm = computed(() => {
    if (!showDeptHeadPanel.value) return false
    if (presetDeptHeadId.value != null) return true
    const snapLabel = toValue(reqSource)?.wizard_snapshot?.form?.dept_head_label
    return Boolean(snapLabel && String(snapLabel).trim())
  })

  const deptHeadDisplayLine = computed(() => {
    if (deptHeadLockedLabel.value.trim()) return deptHeadLockedLabel.value.trim()
    const req = toValue(reqSource)
    const h = req?.assigned_dept_head
    if (h) return formatDeptHeadChosenLabel(h)
    const fromSnap = req?.wizard_snapshot?.form?.dept_head_label
      ? String(req.wizard_snapshot.form.dept_head_label).trim()
      : ''
    if (fromSnap) return fromSnap
    return presetDeptHeadId.value != null ? `#${presetDeptHeadId.value}` : '—'
  })

  watch(
    () => {
      const req = toValue(reqSource)
      return [req?.id, req?.assigned_dept_head_id, req?.assigned_dept_head]
    },
    async () => {
      deptHeadsLoadErr.value = ''
      deptHeadLockedLabel.value = ''
      const req = toValue(reqSource)
      const rid = req?.id
      const hid = req?.assigned_dept_head_id
      if (rid == null || hid == null || hid === '') return
      const h = req?.assigned_dept_head
      if (h && Number(h.id) === Number(hid)) {
        deptHeadLockedLabel.value = formatDeptHeadChosenLabel(h)
        return
      }
      try {
        const list = await getAvailableDeptHeads(rid, { pick: hid })
        const u = (list ?? []).find((row) => Number(row.id) === Number(hid))
        if (u) deptHeadLockedLabel.value = formatDeptHeadChosenLabel(u)
      } catch (e) {
        deptHeadsLoadErr.value =
          typeof e?.response?.data?.message === 'string'
            ? e.response.data.message
            : t('request_detail.assign_dept_head_load_err')
      }
    },
    { immediate: true },
  )

  const deptHeadPresetLocked = computed(() => presetDeptHeadId.value != null)

  return {
    showDeptHeadPanel,
    hasAssignedDeptHead,
    showAssignedDeptHeadOnForm,
    deptHeadDisplayLine,
    deptHeadsLoadErr,
    presetDeptHeadId,
    deptHeadPresetLocked,
  }
}
