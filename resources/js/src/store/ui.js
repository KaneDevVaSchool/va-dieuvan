import { defineStore } from 'pinia'

const THEME_KEY = 'va-theme'
const SIDEBAR_KEY = 'va-sidebar-rail'

export const useUiStore = defineStore('ui', {
  state: () => ({
    /** Số form đang dirty (tăng/giảm từ các màn hình) */
    formDirtyCount: 0,
    sidebarCollapsed: false,
    theme: 'light',
  }),
  getters: {
    isDark: (s) => s.theme === 'dark',
  },
  actions: {
    initFromStorage() {
      if (typeof localStorage === 'undefined') return
      this.sidebarCollapsed = localStorage.getItem(SIDEBAR_KEY) === '1'
      const t = localStorage.getItem(THEME_KEY)
      if (t === 'dark' || t === 'light') {
        this.theme = t
      }
      this.applyThemeClass()
    },
    applyThemeClass() {
      if (typeof document === 'undefined') return
      document.documentElement.classList.toggle('dark', this.theme === 'dark')
    },
    setSidebarCollapsed(v) {
      this.sidebarCollapsed = !!v
      if (typeof localStorage !== 'undefined') {
        localStorage.setItem(SIDEBAR_KEY, this.sidebarCollapsed ? '1' : '0')
      }
    },
    toggleSidebarCollapsed() {
      this.setSidebarCollapsed(!this.sidebarCollapsed)
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
    /** Gọi khi form bắt đầu / kết thúc chỉnh sửa chưa lưu */
    setFormDirty(delta) {
      this.formDirtyCount = Math.max(0, this.formDirtyCount + delta)
    },
    clearFormDirty() {
      this.formDirtyCount = 0
    },
  },
})
