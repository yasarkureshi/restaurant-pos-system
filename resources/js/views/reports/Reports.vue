<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Sales Reports</h1>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6 flex items-center gap-4 flex-wrap">
            <div>
                <label class="text-xs text-gray-500 block mb-1">From</label>
                <input v-model="filters.from" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">To</label>
                <input v-model="filters.to" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Group By</label>
                <select v-model="filters.group_by" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="day">Daily</option>
                    <option value="week">Weekly</option>
                    <option value="month">Monthly</option>
                </select>
            </div>
            <button @click="loadReport" class="mt-4 bg-orange-500 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-orange-600">
                Generate Report
            </button>
        </div>

        <div v-if="loading" class="flex items-center justify-center h-40">
            <div class="animate-spin w-10 h-10 border-4 border-orange-500 border-t-transparent rounded-full"></div>
        </div>

        <template v-else-if="report">
            <!-- Summary cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <StatCard title="Total Sales" :value="'₹' + fmt(report.summary.total_sales)" :icon="TrendingUp" color="orange" />
                <StatCard title="Total Orders" :value="report.summary.total_orders" :icon="ShoppingBag" color="blue" />
                <StatCard title="Avg Order Value" :value="'₹' + fmt(report.summary.avg_order_value)" :icon="Target" color="purple" />
                <StatCard title="Total Tax" :value="'₹' + fmt(report.summary.total_tax)" :icon="Receipt" color="green" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Sales table -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="p-4 border-b">
                        <h2 class="font-bold text-gray-800">Sales Breakdown</h2>
                    </div>
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Period</th>
                                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Orders</th>
                                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Sales</th>
                                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Discount</th>
                                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Tax</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="row in report.sales" :key="row.period" class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ row.period }}</td>
                                <td class="px-6 py-3 text-right text-sm text-gray-600">{{ row.total_orders }}</td>
                                <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">₹{{ fmt(row.total_sales) }}</td>
                                <td class="px-6 py-3 text-right text-sm text-red-500">₹{{ fmt(row.total_discounts) }}</td>
                                <td class="px-6 py-3 text-right text-sm text-blue-600">₹{{ fmt(row.total_tax) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Right side panels -->
                <div class="space-y-6">
                    <!-- Payment Breakdown -->
                    <div class="bg-white rounded-xl shadow-sm p-5">
                        <h2 class="font-bold text-gray-800 mb-4">Payment Methods</h2>
                        <div class="space-y-3">
                            <div v-for="p in report.payment_breakdown" :key="p.payment_method"
                                class="flex items-center justify-between">
                                <span class="text-sm capitalize text-gray-700">{{ p.payment_method.replace('_', ' ') }}</span>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900">₹{{ fmt(p.total) }}</p>
                                    <p class="text-xs text-gray-400">{{ p.count }} txns</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="bg-white rounded-xl shadow-sm p-5">
                        <h2 class="font-bold text-gray-800 mb-4">Top Products</h2>
                        <div class="space-y-3">
                            <div v-for="(p, i) in report.top_products" :key="p.id" class="flex items-center gap-3">
                                <span class="w-6 text-center text-xs font-bold text-gray-400">{{ i + 1 }}</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ p.name }}</p>
                                    <p class="text-xs text-gray-500">{{ p.total_qty }} sold</p>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">₹{{ fmt(p.total_revenue) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { TrendingUp, ShoppingBag, Target, Receipt } from 'lucide-vue-next'
import api from '@/api/index.js'
import StatCard from '@/components/StatCard.vue'

const report = ref(null)
const loading = ref(false)

const today = new Date().toISOString().split('T')[0]
const monthStart = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0]

const filters = reactive({ from: monthStart, to: today, group_by: 'day' })

function fmt(v) {
    return Number(v || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 })
}

async function loadReport() {
    loading.value = true
    try {
        const { data } = await api.get('/reports/sales', { params: filters })
        report.value = data
    } finally {
        loading.value = false
    }
}

loadReport()
</script>
