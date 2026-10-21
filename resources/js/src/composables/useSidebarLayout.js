import { computed } from 'vue'
import { useUiStore } from '../store/ui'

/**
 * Bố cục sidebar: ưu tiên từ store (auto / dọc / ngang), trục thực tế từ getter Pinia.
 */
export function useSidebarLayout() {
  const ui = useUiStore()

  // Phones use a header and drawer, preserving the desktop layout preference.
  const axis = computed(() => ui.viewportWidth < 768 ? 'horizontal' : ui.sidebarEffectiveAxis)
  const isVertical = computed(() => axis.value === 'vertical')
  const isHorizontal = computed(() => axis.value === 'horizontal')
  const preferenceLabelKey = computed(() => ui.sidebarAxisPreferenceLabelKey)

  return {
    axis,
    isVertical,
    isHorizontal,
    preferenceLabelKey,
    cyclePreference: () => ui.cycleSidebarAxisPreference(),
  }
}
