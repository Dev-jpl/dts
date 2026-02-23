// stores/useAuthStore.ts
import { defineStore } from 'pinia'
import axios from 'axios'

const API = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
})

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as any,
    token: localStorage.getItem('token') || null,
  }),

  actions: {
    async login(email: string, password: string) {
      try {
        const { data } = await API.post('/login', { email, password })

        console.log(data);
        
        this.token = data.access_token
        this.user = data.user

        // persist token
        localStorage.setItem('token', this.token)

        // attach token to axios
        API.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
      } catch (error) {
        throw error
      }
    },

    async fetchUser() {
      if (!this.token) return null

      try {
        const { data } = await API.get('/me', {
          headers: { Authorization: `Bearer ${this.token}` },
        })
        this.user = data
        return data
      } catch (error) {
        this.logout()
        throw error
      }
    },

    logout() {
      this.user = null
      this.token = null
      localStorage.removeItem('token')
      delete API.defaults.headers.common['Authorization']
    },
  },
})
