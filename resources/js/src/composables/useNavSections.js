import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '../store'
import { BOTTOM_NAV, NAV_SECTIONS } from '../config/nav'
import { fetchNavBadges } from '../api/navBadges'

/**
 * Section điều hướng + badge từ API (khi đăng nhập).
 */
export function useNavSections() {
  const auth = useAuthStore()
  const badges = ref({})

  function itemVisible(item) {
    if (!auth.isNavFeatureVisible(item.featureKey)) return false
    if (item.permissionKey && !auth.hasPermission(item.permissionKey)) return false
    return true
  }

  function filterItems(items) {
    if (!items?.length) return []
    return items
      .filter((i) => itemVisible(i))
      .map((i) => {
        if (i.children?.length) {
          const children = filterItems(i.children)
          if (children.length === 0) return null
          return { ...i, children }
        }
        return i
      })
      .filter(Boolean)
  }

  const sections = computed(() => {
    if (auth.canAccessDriverWebApp() && !auth.canAccessDispatchWebApp()) {
      return [
        {
          headingKey: 'nav.section_driver',
          items: [
            {
              to: '/driver',
              labelKey: 'nav.driver_home',
              icon: 'home',
            },
            {
              to: '/driver/schedule',
              labelKey: 'nav.bottom_driver_schedule',
              icon: 'calendar',
            },
            {
              to: '/driver/costs',
              labelKey: 'nav.bottom_driver_costs',
              icon: 'costs',
            },
            {
              to: '/driver/account',
              labelKey: 'nav.bottom_driver_account',
              icon: 'user',
            },
          ],
        },
      ]
    }
    return NAV_SECTIONS.map((sec) => ({
      headingKey: sec.headingKey,
      items: filterItems(sec.items),
    })).filter((sec) => sec.items.length > 0)
  })

  /** Thanh điều hướng dưới (mobile, layout ngang) */
  const bottomNavItems = computed(() => {
    if (auth.canAccessDriverWebApp() && !auth.canAccessDispatchWebApp()) {
      return [
        { to: '/driver', labelKey: 'nav.bottom_driver_home', icon: 'home' },
        { to: '/driver/schedule', labelKey: 'nav.bottom_driver_schedule', icon: 'calendar' },
        { to: '/driver/costs', labelKey: 'nav.bottom_driver_costs', icon: 'costs' },
        { to: '/driver/account', labelKey: 'nav.bottom_driver_account', icon: 'user' },
      ]
    }
    return BOTTOM_NAV
  })

  onMounted(async () => {
    if (!auth.isLoggedIn) return
    try {
      const b = await fetchNavBadges()
      badges.value = b && typeof b === 'object' ? b : {}
    } catch {
      badges.value = {}
    }
  })

  function badgeCount(item) {
    const key = item.badgeKey
    if (!key) return 0
    const v = badges.value[key]
    if (v == null || v === '') return 0
    const n = Number(v)
    return Number.isFinite(n) ? n : 0
  }

  return { sections, badgeCount, bottomNavItems }
}
