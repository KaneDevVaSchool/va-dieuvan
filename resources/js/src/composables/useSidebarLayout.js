import { computed } from 'vue'
import { useUiStore } from '../store/ui'

/**
 * Bố cục sidebar: ưu tiên từ store (auto / dọc / ngang), trục thực tế từ getter Pinia.
 */
export function useSidebarLayout() {
  const ui = useUiStore()

  const axis = computed(() => ui.sidebarEffectiveAxis)
  const isVertical = computed(() => ui.sidebarEffectiveAxis === 'vertical')
  const isHorizontal = computed(() => ui.sidebarEffectiveAxis === 'horizontal')
  const preferenceLabelKey = computed(() => ui.sidebarAxisPreferenceLabelKey)

  return {
    axis,
    isVertical,
    isHorizontal,
    preferenceLabelKey,
    cyclePreference: () => ui.cycleSidebarAxisPreference(),
  }
}
