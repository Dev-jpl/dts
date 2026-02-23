import axios from 'axios'

const STRAPI_BASE_URL = 'http://localhost:1337' // You can move this to an env variable

interface AuthCredentials {
    identifier: string
    password: string
}

interface AuthResponse {
    jwt: string
    user: {
        id: number
        username: string
        email: string
        // Add any other user fields you're using
    }
}

export class StrapiAuthService {
    static async login({ identifier, password }: AuthCredentials): Promise<AuthResponse> {
        const response = await axios.post(`${STRAPI_BASE_URL}/api/auth/local`, {
            identifier,
            password
        })

        return response.data
    }

    static async logout(): Promise<void> {
        // This method can be expanded to clear token from store/localStorage
    }

    static async getProfile(token: string): Promise<any> {
        const response = await axios.get(`${STRAPI_BASE_URL}/api/users/me`, {
            headers: {
                Authorization: `Bearer ${token}`
            }
        })

        return response.data
    }
}
