import { defineStore } from 'pinia'
import { http, TOKEN_KEY } from '../api/http'
import * as authApi from '../api/auth'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
  }),
  getters: {
    isLoggedIn: (s) => !!s.token || !!localStorage.getItem(TOKEN_KEY),
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
    },
    async fetchMe() {
      const { data } = await http.get('/user')
      this.user = data
      return this.user
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
    isFeatureEnabled(featureKey) {
      if (!featureKey) return true
      if (!this.user) return false
      if (this.user.is_superadmin) return true
      const ft = this.user.feature_toggles
      if (!ft || typeof ft !== 'object') return true
      if (!Object.prototype.hasOwnProperty.call(ft, featureKey)) return true
      return ft[featureKey] === true
    },
  },
})
