import { ref, computed } from 'vue'

const ONBOARD_KEY = 'vas_onboarded'

export function useOnboarding() {
  const done = ref(false)

  function readStorage() {
    try {
      done.value = localStorage.getItem(ONBOARD_KEY) === '1'
    } catch {
      done.value = false
    }
  }

  readStorage()

  const hasOnboarded = computed(() => done.value)

  function completeOnboarding() {
    try {
      localStorage.setItem(ONBOARD_KEY, '1')
    } catch {
      /* ignore */
    }
    done.value = true
  }

  return { hasOnboarded, completeOnboarding, refreshOnboarding: readStorage }
}
