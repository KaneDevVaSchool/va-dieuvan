import { computed, reactive, ref, watch } from 'vue'

/**
 * Datagrid filter row visibility (optional localStorage). See .cursor/skills/datagrid-toolbar
 * @param {Array<{ key: string, label: string, default?: boolean }>} controls
 * @param {string} storageKey
 * @param {{ persist?: boolean }} [options]
 */
export function useVisibleFilterControls(controls, storageKey, options = {}) {
  const persist = options.persist !== false
  const defaults = Object.fromEntries(
    controls.map((c) => [c.key, c.default !== undefined ? c.default : false]),
  )

  function load() {
    if (!persist) return { ...defaults }
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
    if (!persist) return
    try {
      localStorage.setItem(storageKey, JSON.stringify({ ...visibleFilters }))
    } catch {
      /* ignore */
    }
  }

  if (persist) {
    watch(visibleFilters, persistVisibleFilters, { deep: true })
  }

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
