<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Inventory</h1>
            <div class="flex gap-2">
                <button @click="showLowStock = !showLowStock" :class="['px-4 py-2 rounded-lg text-sm font-medium border transition-colors', showLowStock ? 'bg-red-100 border-red-300 text-red-700' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50']">
                    <AlertTriangle class="w-4 h-4 inline mr-1" />Low Stock
                </button>
                <button @click="showAddItem = true" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 flex items-center gap-2">
                    <Plus class="w-4 h-4" /> Add Item
                </button>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div v-if="loading" class="flex items-center justify-center h-40">
                <div class="animate-spin w-8 h-8 border-4 border-orange-500 border-t-transparent rounded-full"></div>
            </div>
            <table v-else class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Item</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Category</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Current Stock</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Min Stock</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Unit Cost</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in items" :key="item.id"
                        :class="['hover:bg-gray-50', item.current_stock <= item.reorder_point ? 'bg-red-50' : '']">
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ item.name }}</p>
                            <p class="text-xs text-gray-400">{{ item.sku }} · {{ item.unit_type }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ item.category ?? '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <span :class="['font-bold', item.current_stock <= item.minimum_stock ? 'text-red-600' : 'text-gray-900']">
                                {{ item.current_stock }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-500">{{ item.minimum_stock }}</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-700">₹{{ item.cost_per_unit }}</td>
                        <td class="px-6 py-4">
                            <span v-if="item.current_stock <= item.reorder_point"
                                class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-xs font-medium flex items-center gap-1 w-fit">
                                <AlertTriangle class="w-3 h-3" /> Low Stock
                            </span>
                            <span v-else class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs font-medium">OK</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button @click="openAdjust(item)" class="text-orange-500 hover:underline text-sm">Adjust</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="!loading && !items.length" class="text-center py-16 text-gray-400">
                <Package class="w-12 h-12 mx-auto mb-3 opacity-40" />
                <p>No inventory items found</p>
            </div>
        </div>
    </div>

    <!-- Adjust Modal -->
    <Teleport to="body">
        <div v-if="showAdjust" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl w-full max-w-sm p-6">
                <h3 class="font-bold text-lg mb-1">Adjust Stock</h3>
                <p class="text-gray-500 text-sm mb-4">{{ adjustItem?.name }}</p>
                <div class="space-y-3">
                    <div><label class="text-sm font-medium mb-1 block">Transaction Type</label>
                        <select v-model="adjustForm.transaction_type" class="w-full border border-gray-300 rounded-lg px-3 py-2.5">
                            <option value="purchase">Purchase (Add)</option>
                            <option value="wastage">Wastage (Remove)</option>
                            <option value="adjustment">Manual Adjustment</option>
                        </select></div>
                    <div><label class="text-sm font-medium mb-1 block">Quantity</label>
                        <input v-model="adjustForm.quantity" type="number" step="0.001"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" /></div>
                    <div><label class="text-sm font-medium mb-1 block">Notes</label>
                        <input v-model="adjustForm.notes" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" /></div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button @click="showAdjust = false" class="flex-1 py-3 border rounded-lg text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button @click="submitAdjust" class="flex-1 py-3 bg-orange-500 text-white rounded-lg font-semibold">Save</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import { Plus, AlertTriangle, Package } from 'lucide-vue-next'
import api from '@/api/index.js'

const items = ref([])
const loading = ref(false)
const showLowStock = ref(false)
const showAddItem = ref(false)
const showAdjust = ref(false)
const adjustItem = ref(null)
const adjustForm = reactive({ transaction_type: 'purchase', quantity: '', notes: '' })

async function loadItems() {
    loading.value = true
    try {
        const params = showLowStock.value ? { low_stock: 1 } : {}
        const { data } = await api.get('/inventory/items', { params })
        items.value = data.items.data
    } finally {
        loading.value = false
    }
}

function openAdjust(item) {
    adjustItem.value = item
    Object.assign(adjustForm, { transaction_type: 'purchase', quantity: '', notes: '' })
    showAdjust.value = true
}

async function submitAdjust() {
    let qty = parseFloat(adjustForm.quantity)
    if (adjustForm.transaction_type === 'wastage') qty = -Math.abs(qty)
    await api.post(`/inventory/items/${adjustItem.value.id}/adjust`, { ...adjustForm, quantity: qty })
    toast.success('Stock adjusted')
    showAdjust.value = false
    await loadItems()
}

watch(showLowStock, loadItems)
onMounted(loadItems)
</script>
