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
    permissionNames: () => [],
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
    hasPermission() {
      return !!this.user
    },
    hasAnyPermission() {
      return !!this.user
    },
  },
})
