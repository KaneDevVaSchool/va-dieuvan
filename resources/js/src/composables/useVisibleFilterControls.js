import { computed, reactive, ref, watch } from 'vue'

/**
 * Datagrid filter row visibility (persisted). See .cursor/skills/datagrid-toolbar
 * @param {Array<{ key: string, label: string, default?: boolean }>} controls
 * @param {string} storageKey
 */
export function useVisibleFilterControls(controls, storageKey) {
  const defaults = Object.fromEntries(
    controls.map((c) => [c.key, c.default !== undefined ? c.default : false]),
  )

  function load() {
    try {
      const raw = localStorage.getItem(storageKey)
      if (!raw) return { ...defaults }
      const parsed = JSON.parse(raw)
      return { ...defaults, ...parsed }
    } catch {
      return { ...defaults }
    }
  }

  const visibleFilters = reactive(load())

  function persistVisibleFilters() {
    try {
      localStorage.setItem(storageKey, JSON.stringify({ ...visibleFilters }))
    } catch {
      /* ignore */
    }
  }

  watch(visibleFilters, persistVisibleFilters, { deep: true })

  const hasFilterRow = computed(() => controls.some((c) => visibleFilters[c.key] === true))

  const showFilterPanelDd = ref(false)

  function openFilterPanel(closeOthers) {
    closeOthers?.()
    showFilterPanelDd.value = !showFilterPanelDd.value
  }

  function closeFilterPanel() {
    showFilterPanelDd.value = false
  }

  return {
    visibleFilters,
    hasFilterRow,
    persistVisibleFilters,
    showFilterPanelDd,
    openFilterPanel,
    closeFilterPanel,
    filterControlDefs: controls,
  }
}
