// src/stores/auth.ts
import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: '',
    user: null
  }),
  actions: {
    setAuth(token: string, user: any) {
      this.token = token
      this.user = user
      localStorage.setItem('strapi_jwt', token)
    }
  }
})
