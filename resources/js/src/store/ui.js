import { defineStore } from 'pinia'

const THEME_KEY = 'va-theme'
const SIDEBAR_RAIL_KEY = 'va-sidebar-rail'
const SIDEBAR_AXIS_KEY = 'va-sidebar-axis'

/** Breakpoint lg (Tailwind): < này + auto → thanh ngang */
const SIDEBAR_VERTICAL_MIN_PX = 1024

let viewportListenerBound = false
let systemThemeMediaQuery = null
let systemThemeListener = null

function getSystemPrefersDark() {
  if (typeof window === 'undefined') return false
  return window.matchMedia('(prefers-color-scheme: dark)').matches
}

export const useUiStore = defineStore('ui', {
  state: () => ({
    formDirtyCount: 0,
    sidebarCollapsed: false,
    /** `auto` | `light` | `dark` — chế độ giao diện */
    theme: 'light',
    /** `auto` | `vertical` | `horizontal` — tùy biến bố cục */
    sidebarAxisPreference: 'auto',
    viewportWidth: typeof window !== 'undefined' ? window.innerWidth : SIDEBAR_VERTICAL_MIN_PX,
  }),
  getters: {
    isDark: (s) => {
      if (s.theme === 'dark') return true
      if (s.theme === 'auto') return getSystemPrefersDark()
      return false
    },
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
      if (t === 'dark' || t === 'light' || t === 'auto') {
        this.theme = t
      }
      this.applyThemeClass()
      if (this.theme === 'auto') {
        this._attachSystemThemeListener()
      }
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
      const dark = this.theme === 'dark' || (this.theme === 'auto' && getSystemPrefersDark())
      document.documentElement.classList.toggle('dark', dark)
    },
    _attachSystemThemeListener() {
      if (typeof window === 'undefined') return
      this._detachSystemThemeListener()
      systemThemeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
      systemThemeListener = () => this.applyThemeClass()
      systemThemeMediaQuery.addEventListener('change', systemThemeListener)
    },
    _detachSystemThemeListener() {
      if (systemThemeMediaQuery && systemThemeListener) {
        systemThemeMediaQuery.removeEventListener('change', systemThemeListener)
        systemThemeMediaQuery = null
        systemThemeListener = null
      }
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
    peekNextSidebarAxisPreference() {
      const order = ['auto', 'vertical', 'horizontal']
      const i = order.indexOf(this.sidebarAxisPreference)
      return order[(i + 1) % order.length]
    },
    cycleSidebarAxisPreference() {
      const next = this.peekNextSidebarAxisPreference()
      this.setSidebarAxisPreference(next)
    },
    setTheme(mode) {
      if (mode !== 'auto' && mode !== 'light' && mode !== 'dark') return
      this.theme = mode
      if (typeof localStorage !== 'undefined') {
        localStorage.setItem(THEME_KEY, this.theme)
      }
      if (mode === 'auto') {
        this._attachSystemThemeListener()
      } else {
        this._detachSystemThemeListener()
      }
      this.applyThemeClass()
    },
    toggleTheme() {
      const next = this.theme === 'dark' ? 'light' : 'dark'
      this.setTheme(next)
    },
    setFormDirty(delta) {
      this.formDirtyCount = Math.max(0, this.formDirtyCount + delta)
    },
    clearFormDirty() {
      this.formDirtyCount = 0
    },
  },
})
