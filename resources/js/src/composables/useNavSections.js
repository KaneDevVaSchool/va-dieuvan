import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '../store'
import { NAV_SECTIONS } from '../config/nav'
import { fetchNavBadges } from '../api/navBadges'

/**
 * Section điều hướng đã lọc theo quyền + badge từ API (khi đăng nhập).
 */
export function useNavSections() {
  const auth = useAuthStore()
  const badges = ref({})

  function filterItems(items) {
    if (!items?.length) return []
    return items
      .map((i) => {
        if (i.children?.length) {
          const ch = filterItems(i.children)
          if (!ch.length) return null
          if (i.perms?.length && !auth.hasAnyPermission(i.perms)) return null
          return { ...i, children: ch }
        }
        if (!auth.hasAnyPermission(i.perms)) return null
        return i
      })
      .filter(Boolean)
  }

  const sections = computed(() =>
    NAV_SECTIONS.map((sec) => ({
      headingKey: sec.headingKey,
      items: filterItems(sec.items),
    })).filter((sec) => sec.items.length > 0),
  )

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

  return { sections, badgeCount }
}
