import { onMounted, onUnmounted, ref } from 'vue'

/** Toolbar export dropdown: relative wrapper + DatagridToolbarActionButton (no details/summary). */
export function useExportDetailsMenu() {
  const exportMenuRef = ref(null)
  const showExportMenu = ref(false)

  function toggleExportMenu() {
    showExportMenu.value = !showExportMenu.value
  }

  function closeExportMenu() {
    showExportMenu.value = false
  }

  function onDocClick(event) {
    const el = exportMenuRef.value
    if (showExportMenu.value && el && !el.contains(event.target)) {
      showExportMenu.value = false
    }
  }

  onMounted(() => document.addEventListener('click', onDocClick, true))
  onUnmounted(() => document.removeEventListener('click', onDocClick, true))

  return { exportMenuRef, showExportMenu, toggleExportMenu, closeExportMenu }
}
