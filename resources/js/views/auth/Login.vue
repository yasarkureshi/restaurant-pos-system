<template>
    <div class="min-h-screen bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-orange-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <UtensilsCrossed class="w-8 h-8 text-white" />
                </div>
                <h1 class="text-2xl font-bold text-gray-900">PetPooja POS</h1>
                <p class="text-gray-500 text-sm mt-1">Restaurant Point of Sale System</p>
            </div>

            <!-- Tabs -->
            <div class="flex bg-gray-100 rounded-lg p-1 mb-6">
                <button
                    @click="loginMode = 'email'"
                    :class="['flex-1 py-2 rounded-md text-sm font-medium transition-colors', loginMode === 'email' ? 'bg-white shadow text-orange-600' : 'text-gray-500']"
                >Email Login</button>
                <button
                    @click="loginMode = 'pin'"
                    :class="['flex-1 py-2 rounded-md text-sm font-medium transition-colors', loginMode === 'pin' ? 'bg-white shadow text-orange-600' : 'text-gray-500']"
                >PIN Login</button>
            </div>

            <!-- Email Login -->
            <form v-if="loginMode === 'email'" @submit.prevent="handleEmailLogin" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input v-model="emailForm.email" type="email" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none"
                        placeholder="staff@restaurant.com" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input v-model="emailForm.password" type="password" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none"
                        placeholder="••••••••" />
                </div>
                <div v-if="error" class="bg-red-50 text-red-600 text-sm px-4 py-3 rounded-lg">{{ error }}</div>
                <button type="submit" :disabled="auth.loading"
                    class="w-full bg-orange-500 text-white py-3 rounded-lg font-semibold hover:bg-orange-600 transition-colors disabled:opacity-60">
                    {{ auth.loading ? 'Signing in...' : 'Sign In' }}
                </button>
            </form>

            <!-- PIN Login -->
            <form v-else @submit.prevent="handlePinLogin" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Restaurant ID</label>
                    <input v-model="pinForm.restaurantId" type="number" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none"
                        placeholder="1" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">PIN</label>
                    <div class="flex gap-2 justify-center">
                        <input v-for="i in 4" :key="i" v-model="pinDigits[i-1]"
                            type="text" maxlength="1"
                            class="w-12 h-12 text-center text-xl font-bold border-2 border-gray-300 rounded-lg focus:border-orange-500 outline-none"
                            @input="onPinInput(i-1, $event)"
                            @keydown.backspace="onPinBackspace(i-1)"
                            :ref="el => pinRefs[i-1] = el" />
                    </div>
                </div>
                <div v-if="error" class="bg-red-50 text-red-600 text-sm px-4 py-3 rounded-lg">{{ error }}</div>
                <button type="submit" :disabled="auth.loading"
                    class="w-full bg-orange-500 text-white py-3 rounded-lg font-semibold hover:bg-orange-600 transition-colors disabled:opacity-60">
                    {{ auth.loading ? 'Verifying...' : 'Sign In with PIN' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { UtensilsCrossed } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()
const loginMode = ref('email')
const error = ref('')

const emailForm = reactive({ email: '', password: '' })
const pinForm = reactive({ restaurantId: '' })
const pinDigits = ref(['', '', '', ''])
const pinRefs = ref([])

async function handleEmailLogin() {
    error.value = ''
    const result = await auth.login(emailForm.email, emailForm.password)
    if (result.success) {
        router.push('/')
    } else {
        error.value = result.message
    }
}

async function handlePinLogin() {
    error.value = ''
    const pin = pinDigits.value.join('')
    if (pin.length < 4) { error.value = 'Please enter full PIN'; return }
    const result = await auth.pinLogin(pinForm.restaurantId, pin)
    if (result.success) {
        router.push('/')
    } else {
        error.value = result.message
    }
}

function onPinInput(index, event) {
    const val = event.target.value.replace(/[^0-9]/g, '')
    pinDigits.value[index] = val
    if (val && index < 3) pinRefs.value[index + 1]?.focus()
}

function onPinBackspace(index) {
    if (!pinDigits.value[index] && index > 0) {
        pinDigits.value[index - 1] = ''
        pinRefs.value[index - 1]?.focus()
    }
}
</script>
