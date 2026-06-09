import { computed, reactive } from 'vue'

/**
 * Toolbar filter chip visibility (separate from applied query/API filter state).
 * Resets to defaults on each page entry — do not persist to localStorage.
 */
export function useFilterBarVisibility(ids, defaults) {
  const visible = reactive({ ...defaults })

  function resetVisibility() {
    for (const id of ids) {
      visible[id] = defaults[id]
    }
  }

  const hasVisibleOnBar = computed(() => ids.some((id) => visible[id] === true))

  return { visible, resetVisibility, hasVisibleOnBar }
}
