<template>
    <div class="min-h-screen bg-gray-900 text-white p-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-orange-400">Kitchen Display System</h1>
                <p class="text-gray-400 text-sm">{{ new Date().toLocaleString('en-IN') }}</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 text-sm">
                    <div class="w-3 h-3 rounded-full bg-green-400 animate-pulse"></div>
                    <span class="text-gray-400">Live</span>
                </div>
                <router-link to="/" class="text-gray-400 hover:text-white text-sm">← Back</router-link>
            </div>
        </div>

        <div v-if="!orders.length" class="flex items-center justify-center h-64">
            <div class="text-center text-gray-500">
                <ChefHat class="w-16 h-16 mx-auto mb-4 opacity-30" />
                <p class="text-xl">No orders in queue</p>
                <p class="text-sm mt-2">New orders will appear here automatically</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div v-for="order in orders" :key="order.id"
                :class="['bg-gray-800 rounded-xl overflow-hidden border-t-4', orderBorderColor(order)]">
                <!-- Order header -->
                <div class="p-4 bg-gray-750">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="font-bold text-lg text-white">{{ order.order_number }}</p>
                            <p class="text-gray-400 text-sm">{{ order.order_type.replace('_', ' ') }}
                                <span v-if="order.table">- {{ order.table.name }}</span>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-orange-400 font-bold text-lg">{{ orderTime(order.created_at) }}</p>
                            <p class="text-gray-500 text-xs">minutes ago</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button v-if="order.status === 'placed'" @click="markPreparing(order)"
                            class="flex-1 py-1.5 bg-yellow-500 text-black rounded-lg text-xs font-bold hover:bg-yellow-400">
                            START PREPARING
                        </button>
                        <button v-if="order.status === 'preparing'" @click="markReady(order)"
                            class="flex-1 py-1.5 bg-green-500 text-white rounded-lg text-xs font-bold hover:bg-green-400">
                            MARK READY
                        </button>
                    </div>
                </div>

                <!-- Items -->
                <div class="divide-y divide-gray-700">
                    <div v-for="item in order.items" :key="item.id"
                        :class="['p-3 flex items-start gap-3', item.item_status === 'ready' ? 'opacity-50' : '']">
                        <div :class="['w-3 h-3 rounded-full mt-1 flex-shrink-0', item.product?.is_veg ? 'bg-green-400' : 'bg-red-400']"></div>
                        <div class="flex-1">
                            <p class="text-white font-medium">{{ item.product_name }}</p>
                            <p v-if="item.variant_name" class="text-gray-400 text-xs">{{ item.variant_name }}</p>
                            <p v-if="item.special_instructions" class="text-yellow-400 text-xs italic">
                                "{{ item.special_instructions }}"
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="bg-gray-700 text-white px-2 py-0.5 rounded text-sm font-bold">×{{ item.quantity }}</span>
                            <button @click="markItemReady(item)"
                                v-if="item.item_status !== 'ready'"
                                class="w-6 h-6 bg-green-600 rounded flex items-center justify-center hover:bg-green-500">
                                <Check class="w-4 h-4" />
                            </button>
                            <Check v-else class="w-5 h-5 text-green-400" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { ChefHat, Check } from 'lucide-vue-next'
import api from '@/api/index.js'

const orders = ref([])
let refreshTimer = null

function orderTime(dt) {
    return Math.floor((Date.now() - new Date(dt)) / 60000)
}

function orderBorderColor(order) {
    const age = orderTime(order.created_at)
    if (age >= 20) return 'border-red-500'
    if (age >= 10) return 'border-yellow-500'
    if (order.status === 'preparing') return 'border-orange-500'
    return 'border-blue-500'
}

async function loadOrders() {
    const { data } = await api.get('/kds')
    orders.value = data.orders
}

async function markPreparing(order) {
    await api.patch(`/kds/orders/${order.id}/status`, { status: 'preparing' })
    await loadOrders()
}

async function markReady(order) {
    await api.patch(`/kds/orders/${order.id}/status`, { status: 'ready' })
    await loadOrders()
}

async function markItemReady(item) {
    await api.patch(`/kds/items/${item.id}/status`, { status: 'ready' })
    await loadOrders()
}

onMounted(() => {
    loadOrders()
    refreshTimer = setInterval(loadOrders, 15000)
})

onUnmounted(() => clearInterval(refreshTimer))
</script>
