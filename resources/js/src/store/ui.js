import { defineStore } from 'pinia'

const THEME_KEY = 'va-theme'
const SIDEBAR_RAIL_KEY = 'va-sidebar-rail'
const SIDEBAR_AXIS_KEY = 'va-sidebar-axis'

/** Breakpoint lg (Tailwind): < này + auto → thanh ngang */
const SIDEBAR_VERTICAL_MIN_PX = 1024

let viewportListenerBound = false

export const useUiStore = defineStore('ui', {
  state: () => ({
    formDirtyCount: 0,
    sidebarCollapsed: false,
    /** Chỉ khi sidebar dọc: thu gọn rail icon */
    theme: 'light',
    /** `auto` | `vertical` | `horizontal` — tùy biến bố cục */
    sidebarAxisPreference: 'auto',
    viewportWidth: typeof window !== 'undefined' ? window.innerWidth : SIDEBAR_VERTICAL_MIN_PX,
  }),
  getters: {
    isDark: (s) => s.theme === 'dark',
    /** Trục hiển thị thực tế */
    sidebarEffectiveAxis: (s) => {
      if (s.sidebarAxisPreference === 'vertical') return 'vertical'
      if (s.sidebarAxisPreference === 'horizontal') return 'horizontal'
      return s.viewportWidth >= SIDEBAR_VERTICAL_MIN_PX ? 'vertical' : 'horizontal'
    },
    /** Khóa i18n mô tả chế độ đang chọn */
    sidebarAxisPreferenceLabelKey: (s) => {
      if (s.sidebarAxisPreference === 'auto') return 'app.sidebar_pref_auto'
      if (s.sidebarAxisPreference === 'vertical') return 'app.sidebar_pref_vertical'
      return 'app.sidebar_pref_horizontal'
    },
  },
  actions: {
    initFromStorage() {
      if (typeof localStorage === 'undefined') return
      this.sidebarCollapsed = localStorage.getItem(SIDEBAR_RAIL_KEY) === '1'
      const ax = localStorage.getItem(SIDEBAR_AXIS_KEY)
      if (ax === 'auto' || ax === 'vertical' || ax === 'horizontal') {
        this.sidebarAxisPreference = ax
      }
      const t = localStorage.getItem(THEME_KEY)
      if (t === 'dark' || t === 'light') {
        this.theme = t
      }
      this.applyThemeClass()
      if (typeof window !== 'undefined') {
        this.viewportWidth = window.innerWidth
      }
    },
    initViewportListener() {
      if (typeof window === 'undefined' || viewportListenerBound) return
      viewportListenerBound = true
      const update = () => {
        this.viewportWidth = window.innerWidth
      }
      update()
      window.addEventListener('resize', update, { passive: true })
    },
    applyThemeClass() {
      if (typeof document === 'undefined') return
      document.documentElement.classList.toggle('dark', this.theme === 'dark')
    },
    setSidebarCollapsed(v) {
      this.sidebarCollapsed = !!v
      if (typeof localStorage !== 'undefined') {
        localStorage.setItem(SIDEBAR_RAIL_KEY, this.sidebarCollapsed ? '1' : '0')
      }
    },
    toggleSidebarCollapsed() {
      this.setSidebarCollapsed(!this.sidebarCollapsed)
    },
    setSidebarAxisPreference(v) {
      if (v !== 'auto' && v !== 'vertical' && v !== 'horizontal') return
      this.sidebarAxisPreference = v
      if (typeof localStorage !== 'undefined') {
        localStorage.setItem(SIDEBAR_AXIS_KEY, v)
      }
    },
    cycleSidebarAxisPreference() {
      const order = ['auto', 'vertical', 'horizontal']
      const i = order.indexOf(this.sidebarAxisPreference)
      this.setSidebarAxisPreference(order[(i + 1) % order.length])
    },
    setTheme(mode) {
      this.theme = mode === 'dark' ? 'dark' : 'light'
      if (typeof localStorage !== 'undefined') {
        localStorage.setItem(THEME_KEY, this.theme)
      }
      this.applyThemeClass()
    },
    toggleTheme() {
      this.setTheme(this.theme === 'dark' ? 'light' : 'dark')
    },
    setFormDirty(delta) {
      this.formDirtyCount = Math.max(0, this.formDirtyCount + delta)
    },
    clearFormDirty() {
      this.formDirtyCount = 0
    },
  },
})
