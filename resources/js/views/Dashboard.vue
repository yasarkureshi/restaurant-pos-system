<template>
    <div class="p-6 space-y-6">
        <!-- Stats grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <StatCard title="Today's Sales" :value="'₹' + fmt(stats.today_sales)" :sub="salesGrowthText" :icon="TrendingUp" color="orange" />
            <StatCard title="Today's Orders" :value="stats.today_orders" :sub="'Yesterday: ' + stats.yesterday_orders" :icon="ShoppingBag" color="blue" />
            <StatCard title="Active Orders" :value="stats.active_orders" sub="In progress" :icon="Clock" color="yellow" />
            <StatCard title="Tables" :value="stats.available_tables + '/' + stats.total_tables" sub="Available" :icon="LayoutGrid" color="green" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Hourly Sales Chart -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4 text-gray-800">Today's Sales by Hour</h2>
                <div class="flex items-end gap-1 h-40">
                    <template v-for="h in 24" :key="h">
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <div class="w-full bg-orange-100 rounded-t relative group" :style="{ height: hourHeight(h-1) + '%', minHeight: '4px' }">
                                <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 whitespace-nowrap z-10">
                                    {{ hourLabel(h-1) }}: ₹{{ fmt(getHourSales(h-1)) }}
                                </div>
                                <div class="h-full bg-orange-500 rounded-t"></div>
                            </div>
                            <span v-if="(h-1) % 4 === 0" class="text-xs text-gray-400">{{ hourLabel(h-1) }}</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Payment Breakdown -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4 text-gray-800">Payment Methods</h2>
                <div class="space-y-3">
                    <div v-for="p in paymentBreakdown" :key="p.payment_method" class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full" :class="paymentColor(p.payment_method)"></div>
                            <span class="text-sm capitalize text-gray-700">{{ p.payment_method }}</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">₹{{ fmt(p.total) }}</span>
                    </div>
                    <div v-if="!paymentBreakdown.length" class="text-center text-gray-400 text-sm py-4">No payments today</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Products -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4 text-gray-800">Top Selling Today</h2>
                <div class="space-y-3">
                    <div v-for="(product, i) in topProducts" :key="product.id" class="flex items-center gap-3">
                        <span class="w-6 text-center text-sm font-bold text-gray-400">{{ i + 1 }}</span>
                        <div class="w-10 h-10 bg-orange-100 rounded-lg overflow-hidden flex-shrink-0">
                            <img v-if="product.image" :src="product.image" class="w-full h-full object-cover" />
                            <UtensilsCrossed v-else class="w-5 h-5 text-orange-400 m-2.5" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ product.name }}</p>
                            <p class="text-xs text-gray-500">{{ product.total_qty }} sold</p>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">₹{{ fmt(product.total_revenue) }}</span>
                    </div>
                    <div v-if="!topProducts.length" class="text-center text-gray-400 text-sm py-4">No sales yet today</div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Recent Orders</h2>
                    <router-link to="/orders" class="text-sm text-orange-500 hover:underline">View all</router-link>
                </div>
                <div class="space-y-2">
                    <div v-for="order in recentOrders" :key="order.id"
                        class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ order.order_number }}</p>
                            <p class="text-xs text-gray-500">{{ order.table?.name ?? order.order_type }}</p>
                        </div>
                        <div class="text-right">
                            <StatusBadge :status="order.status" />
                            <p class="text-sm font-semibold mt-1">₹{{ fmt(order.grand_total) }}</p>
                        </div>
                    </div>
                    <div v-if="!recentOrders.length" class="text-center text-gray-400 text-sm py-4">No orders yet</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { TrendingUp, ShoppingBag, Clock, LayoutGrid, UtensilsCrossed } from 'lucide-vue-next'
import api from '@/api/index.js'
import StatCard from '@/components/StatCard.vue'
import StatusBadge from '@/components/StatusBadge.vue'

const stats = ref({})
const topProducts = ref([])
const recentOrders = ref([])
const hourlySales = ref([])
const paymentBreakdown = ref([])

const salesGrowthText = computed(() => {
    const g = stats.value.sales_growth
    if (g === undefined) return ''
    return g >= 0 ? `+${g}% from yesterday` : `${g}% from yesterday`
})

function fmt(v) {
    return Number(v || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 })
}

function getHourSales(hour) {
    return hourlySales.value.find(h => h.hour === hour)?.sales ?? 0
}

const maxSales = computed(() => Math.max(...hourlySales.value.map(h => h.sales), 1))

function hourHeight(hour) {
    const sales = getHourSales(hour)
    return Math.max((sales / maxSales.value) * 100, 2)
}

function hourLabel(hour) {
    return hour === 0 ? '12a' : hour < 12 ? `${hour}a` : hour === 12 ? '12p' : `${hour - 12}p`
}

const paymentColors = { cash: 'bg-green-500', card: 'bg-blue-500', upi: 'bg-purple-500', wallet: 'bg-yellow-500' }
function paymentColor(method) {
    return paymentColors[method] ?? 'bg-gray-400'
}

async function loadDashboard() {
    const { data } = await api.get('/dashboard')
    stats.value = data.stats
    topProducts.value = data.top_products
    recentOrders.value = data.recent_orders
    hourlySales.value = data.hourly_sales
    paymentBreakdown.value = data.payment_breakdown
}

onMounted(loadDashboard)
</script>
