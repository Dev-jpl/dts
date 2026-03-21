// api.ts
import axios from 'axios'

export const baseURL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'

const API = axios.create({
    baseURL: `${baseURL}/api`,
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
})

// Attach token automatically if stored
API.interceptors.request.use(config => {
    const token = localStorage.getItem('token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

export default API
