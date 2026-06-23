import { ref } from 'vue'

/** Toolbar export dropdown: details + DatagridToolbarActionButton (manual toggle; button inside summary does not open natively). */
export function useExportDetailsMenu() {
  const exportMenuRef = ref(null)

  function toggleExportMenu(event) {
    event?.preventDefault?.()
    event?.stopPropagation?.()
    const el = exportMenuRef.value
    if (el) el.open = !el.open
  }

  function closeExportMenu() {
    exportMenuRef.value?.removeAttribute?.('open')
  }

  return { exportMenuRef, toggleExportMenu, closeExportMenu }
}
