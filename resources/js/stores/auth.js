import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/index.js'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null)
    const token = ref(localStorage.getItem('pos_token'))
    const loading = ref(false)

    const isAuthenticated = computed(() => !!token.value && !!user.value)
    const restaurant = computed(() => user.value?.restaurant)
    const permissions = computed(() => user.value?.role?.permissions ?? [])

    function hasPermission(slug) {
        return permissions.value.includes(slug)
    }

    function hasAnyPermission(slugs) {
        return slugs.some(s => permissions.value.includes(s))
    }

    async function login(email, password) {
        loading.value = true
        try {
            const { data } = await api.post('/auth/login', { email, password })
            token.value = data.token
            user.value = data.user
            localStorage.setItem('pos_token', data.token)
            return { success: true }
        } catch (err) {
            return { success: false, message: err.response?.data?.message || 'Login failed' }
        } finally {
            loading.value = false
        }
    }

    async function pinLogin(restaurantId, pinCode) {
        loading.value = true
        try {
            const { data } = await api.post('/auth/pin-login', { restaurant_id: restaurantId, pin_code: pinCode })
            token.value = data.token
            user.value = data.user
            localStorage.setItem('pos_token', data.token)
            return { success: true }
        } catch (err) {
            return { success: false, message: err.response?.data?.message || 'Invalid PIN' }
        } finally {
            loading.value = false
        }
    }

    async function fetchUser() {
        if (!token.value) return false
        try {
            const { data } = await api.get('/auth/me')
            user.value = data.user
            return true
        } catch {
            logout()
            return false
        }
    }

    async function logout() {
        try {
            if (token.value) await api.post('/auth/logout')
        } finally {
            user.value = null
            token.value = null
            localStorage.removeItem('pos_token')
        }
    }

    return { user, token, loading, isAuthenticated, restaurant, permissions, hasPermission, hasAnyPermission, login, pinLogin, fetchUser, logout }
})
