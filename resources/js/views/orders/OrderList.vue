<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Orders</h1>
            <div class="flex gap-3">
                <input v-model="filters.date" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                <select v-model="filters.status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All Status</option>
                    <option v-for="s in statuses" :key="s" :value="s" class="capitalize">{{ s }}</option>
                </select>
                <select v-model="filters.order_type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All Types</option>
                    <option value="dine_in">Dine In</option>
                    <option value="takeaway">Takeaway</option>
                    <option value="delivery">Delivery</option>
                </select>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div v-if="loading" class="flex items-center justify-center h-40">
                <div class="animate-spin w-8 h-8 border-4 border-orange-500 border-t-transparent rounded-full"></div>
            </div>
            <table v-else class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Order #</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Type/Table</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Customer</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Payment</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Time</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm font-bold text-gray-800">{{ order.order_number }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600 capitalize">{{ order.order_type.replace('_', ' ') }}</span>
                            <span v-if="order.table" class="text-xs text-gray-400 block">{{ order.table.name }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ order.customer?.name ?? order.customer_name ?? 'Walk-in' }}
                        </td>
                        <td class="px-6 py-4"><StatusBadge :status="order.status" /></td>
                        <td class="px-6 py-4"><StatusBadge :status="order.payment_status" /></td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900">₹{{ order.grand_total }}</td>
                        <td class="px-6 py-4 text-right text-xs text-gray-400">
                            {{ formatTime(order.created_at) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <router-link :to="'/orders/' + order.id"
                                class="text-orange-500 hover:underline text-sm font-medium">
                                View
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="!loading && !orders.length" class="text-center py-16 text-gray-400">
                <ClipboardList class="w-12 h-12 mx-auto mb-3 opacity-50" />
                <p>No orders found</p>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t">
                <span class="text-sm text-gray-500">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
                <div class="flex gap-2">
                    <button @click="loadOrders(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                        class="px-3 py-1.5 text-sm border rounded-lg disabled:opacity-40 hover:bg-gray-50">Prev</button>
                    <button @click="loadOrders(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                        class="px-3 py-1.5 text-sm border rounded-lg disabled:opacity-40 hover:bg-gray-50">Next</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { ClipboardList } from 'lucide-vue-next'
import api from '@/api/index.js'
import StatusBadge from '@/components/StatusBadge.vue'

const orders = ref([])
const loading = ref(false)
const filters = ref({ status: '', order_type: '', date: new Date().toISOString().split('T')[0] })
const pagination = ref({ current_page: 1, last_page: 1 })

const statuses = ['placed', 'confirmed', 'preparing', 'ready', 'served', 'completed', 'cancelled']

function formatTime(dt) {
    return new Date(dt).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' })
}

async function loadOrders(page = 1) {
    loading.value = true
    try {
        const params = { page, per_page: 25, ...filters.value }
        Object.keys(params).forEach(k => !params[k] && delete params[k])
        const { data } = await api.get('/orders', { params })
        orders.value = data.orders.data
        pagination.value = data.orders
    } finally {
        loading.value = false
    }
}

watch(filters, () => loadOrders(1), { deep: true })
onMounted(() => loadOrders())
</script>
