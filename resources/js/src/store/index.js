import { defineStore } from 'pinia'
import { http, TOKEN_KEY } from '../api/http'
import * as authApi from '../api/auth'

const USER_HINT_KEY = 'vas_user_hint'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
    /** Đang khôi phục phiên (GET /user). */
    isRestoring: false,
  }),
  getters: {
    isLoggedIn: (s) => !!s.token || !!localStorage.getItem(TOKEN_KEY),
    /** Alias cho guard / onboarding — cùng điều kiện với isLoggedIn. */
    isAuthenticated() {
      return this.isLoggedIn
    },
    roleNames: (s) => (s.user?.roles ?? []).map((r) => r.name),
    permissionNames: (s) => s.user?.permissions ?? [],
    /**
     * @returns {(featureKey: string) => { is_enabled: boolean, maintenance_mode: boolean, upgrade_notice: boolean, name: string } | null}
     */
    featureToggleRuntimeState: (s) => (featureKey) => {
      if (!featureKey || !s.user) return null
      const st = s.user.feature_toggle_states
      if (!st || typeof st !== 'object') return null
      const row = st[featureKey]
      if (!row || typeof row !== 'object') return null
      return {
        is_enabled: !!row.is_enabled,
        maintenance_mode: !!row.maintenance_mode,
        upgrade_notice: !!row.upgrade_notice,
        name: typeof row.name === 'string' ? row.name : '',
      }
    },
  },
  actions: {
    /** Gọi trước khi mount app: cookie Bearer + user từ /api/user (Sanctum). */
    async restoreSession() {
      this.isRestoring = true
      try {
        const t = localStorage.getItem(TOKEN_KEY)
        if (!t) {
          this.user = null
          return
        }
        await this.fetchMe()
        try {
          if (this.user) {
            localStorage.setItem(
              USER_HINT_KEY,
              JSON.stringify({ id: this.user.id, name: this.user.name }),
            )
          }
        } catch {
          /* ignore */
        }
      } catch {
        this.user = null
        this.setToken(null)
        try {
          localStorage.removeItem(USER_HINT_KEY)
        } catch {
          /* ignore */
        }
      } finally {
        this.isRestoring = false
      }
    },
    initFromStorage() {
      const t = localStorage.getItem(TOKEN_KEY)
      if (t) {
        this.token = t
      }
    },
    setToken(token) {
      this.token = token
      if (token) {
        localStorage.setItem(TOKEN_KEY, token)
      } else {
        localStorage.removeItem(TOKEN_KEY)
      }
    },
    async login(email, password, deviceName = 'web') {
      const res = await authApi.login({ email, password, device_name: deviceName })
      this.setToken(res.token)
      this.user = res.user
      try {
        if (res.user) {
          localStorage.setItem(
            USER_HINT_KEY,
            JSON.stringify({ id: res.user.id, name: res.user.name }),
          )
        }
      } catch {
        /* ignore */
      }
      return res
    },
    async logout() {
      try {
        await authApi.logout()
      } catch {
        // ignore
      }
      this.user = null
      this.setToken(null)
      try {
        localStorage.removeItem(USER_HINT_KEY)
      } catch {
        /* ignore */
      }
    },
    async fetchMe() {
      const { data } = await http.get('/user')
      this.user = data
      return this.user
    },
    /** Khớp backend User::canAccessDispatchWebApp — superadmin / admin / dispatcher / department_head / internal_user. */
    canAccessDispatchWebApp() {
      const u = this.user
      if (!u) return false
      if (u.is_superadmin) return true
      const allow = new Set(['superadmin', 'admin', 'dispatcher', 'department_head', 'internal_user'])
      return (u.roles ?? []).some((r) => r && allow.has(r.name))
    },
    /** Khớp backend User::canAccessDriverWebApp — role driver. */
    canAccessDriverWebApp() {
      const u = this.user
      if (!u) return false
      return (u.roles ?? []).some((r) => r && r.name === 'driver')
    },
    async patchProfile(payload) {
      const data = await authApi.patchProfile(payload)
      this.user = data
      return this.user
    },
    hasPermission(name) {
      if (!this.user) return false
      if (this.user.is_superadmin) return true
      const p = this.user.permissions ?? []
      return p.includes(name)
    },
    hasAnyPermission(names) {
      if (!this.user) return false
      if (this.user.is_superadmin) return true
      const p = this.user.permissions ?? []
      return names.some((n) => p.includes(n))
    },
    /** User đăng nhập nhưng chưa có role staff/driver — chỉ dùng /portal. */
    isPortalUser() {
      const u = this.user
      if (!u) return false
      return !this.canAccessDispatchWebApp() && !this.canAccessDriverWebApp()
    },
    isFeatureEnabled(featureKey) {
      if (!featureKey) return true
      if (!this.user) return false
      if (this.user.is_superadmin) return true
      const ft = this.user.feature_toggles
      if (!ft || typeof ft !== 'object') return true
      if (!Object.prototype.hasOwnProperty.call(ft, featureKey)) return true
      return ft[featureKey] === true
    },
    /** Menu + route: hiện khi bật tính năng hoặc đang đánh dấu bảo trì (đang phát triển). */
    isNavFeatureVisible(featureKey) {
      if (!featureKey) return true
      if (!this.user) return false
      if (this.user.is_superadmin) return true
      const st = this.featureToggleRuntimeState(featureKey)
      if (st?.maintenance_mode) return true
      return this.isFeatureEnabled(featureKey)
    },
  },
})

export { useDriverDashboardStore } from './driverDashboard'
