<template>
    <div class="flex h-full bg-gray-100">
        <!-- Left: Menu Panel -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Order type + table selector -->
            <div class="bg-white border-b px-4 py-3 flex items-center gap-3 flex-wrap">
                <div class="flex gap-2">
                    <button v-for="type in orderTypes" :key="type.value"
                        @click="cart.orderType = type.value"
                        :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', cart.orderType === type.value ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                        {{ type.label }}
                    </button>
                </div>
                <div v-if="cart.orderType === 'dine_in'" class="flex items-center gap-2">
                    <select v-model="cart.selectedTable" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 bg-white"
                        :value="cart.selectedTable">
                        <option :value="null">Select Table</option>
                        <option v-for="t in availableTables" :key="t.id" :value="t">
                            {{ t.name }} ({{ t.capacity }} seats)
                        </option>
                    </select>
                </div>
                <div class="flex items-center gap-2 ml-auto">
                    <Search class="w-4 h-4 text-gray-400" />
                    <input v-model="searchQuery" placeholder="Search items..."
                        class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 w-48 focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none" />
                </div>
            </div>

            <!-- Category tabs -->
            <div class="bg-white border-b">
                <div class="flex gap-1 px-4 py-2 overflow-x-auto">
                    <button @click="selectedCategoryId = null"
                        :class="['px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors', !selectedCategoryId ? 'bg-orange-500 text-white' : 'text-gray-600 hover:bg-gray-100']">
                        All
                    </button>
                    <button v-for="cat in categories" :key="cat.id"
                        @click="selectedCategoryId = cat.id"
                        :class="['px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors', selectedCategoryId === cat.id ? 'bg-orange-500 text-white' : 'text-gray-600 hover:bg-gray-100']">
                        {{ cat.name }}
                    </button>
                </div>
            </div>

            <!-- Products grid -->
            <div class="flex-1 overflow-y-auto p-4">
                <div v-if="loading" class="flex items-center justify-center h-40">
                    <div class="animate-spin w-8 h-8 border-4 border-orange-500 border-t-transparent rounded-full"></div>
                </div>
                <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                    <div v-for="product in filteredProducts" :key="product.id"
                        @click="addToCart(product)"
                        class="bg-white rounded-xl p-3 cursor-pointer hover:shadow-md hover:scale-105 transition-all border border-gray-100 select-none">
                        <div class="aspect-square bg-gray-100 rounded-lg mb-2 overflow-hidden relative">
                            <img v-if="product.image" :src="product.image" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <UtensilsCrossed class="w-8 h-8 text-gray-300" />
                            </div>
                            <div class="absolute top-1.5 left-1.5">
                                <div :class="['w-3 h-3 rounded-full border border-white', product.is_veg ? 'bg-green-500' : 'bg-red-500']"></div>
                            </div>
                            <div v-if="!product.is_available" class="absolute inset-0 bg-black/50 flex items-center justify-center rounded-lg">
                                <span class="text-white text-xs font-bold">UNAVAILABLE</span>
                            </div>
                        </div>
                        <h3 class="text-xs font-semibold text-gray-800 leading-tight truncate">{{ product.name }}</h3>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-sm font-bold text-orange-600">₹{{ product.current_price }}</span>
                            <span class="text-xs text-gray-400">{{ product.unit_type }}</span>
                        </div>
                    </div>
                </div>
                <div v-if="!loading && !filteredProducts.length" class="text-center py-20 text-gray-400">
                    <UtensilsCrossed class="w-12 h-12 mx-auto mb-3 opacity-50" />
                    <p>No items found</p>
                </div>
            </div>
        </div>

        <!-- Right: Cart Panel -->
        <div class="w-96 bg-white border-l flex flex-col shadow-lg">
            <!-- Cart header -->
            <div class="px-4 py-3 border-b bg-gray-50">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-gray-800">Order</h2>
                    <button @click="cart.clearCart()" class="text-red-500 text-sm hover:underline flex items-center gap-1">
                        <Trash2 class="w-4 h-4" /> Clear
                    </button>
                </div>
                <!-- Customer search -->
                <div class="mt-2 relative">
                    <input v-model="customerSearch" @input="debouncedCustomerSearch" placeholder="Search customer by phone..."
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 outline-none" />
                    <div v-if="customerResults.length" class="absolute top-full left-0 right-0 bg-white border rounded-lg shadow-lg z-20 mt-1">
                        <div v-for="c in customerResults" :key="c.id"
                            @click="selectCustomer(c)"
                            class="px-3 py-2 hover:bg-orange-50 cursor-pointer text-sm">
                            <p class="font-medium">{{ c.name }}</p>
                            <p class="text-gray-500">{{ c.phone }}</p>
                        </div>
                    </div>
                </div>
                <div v-if="cart.selectedCustomer" class="mt-2 flex items-center justify-between bg-orange-50 rounded-lg px-3 py-2">
                    <div>
                        <p class="text-sm font-medium text-orange-800">{{ cart.selectedCustomer.name }}</p>
                        <p class="text-xs text-orange-600">{{ cart.selectedCustomer.phone }}</p>
                    </div>
                    <button @click="cart.selectedCustomer = null; customerSearch = ''" class="text-gray-400 hover:text-red-500">
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Cart items -->
            <div class="flex-1 overflow-y-auto">
                <div v-if="!cart.items.length" class="flex flex-col items-center justify-center h-48 text-gray-400">
                    <ShoppingCart class="w-12 h-12 mb-3 opacity-40" />
                    <p class="text-sm">Cart is empty</p>
                    <p class="text-xs mt-1">Click items to add to order</p>
                </div>
                <div v-else class="divide-y">
                    <div v-for="item in cart.items" :key="item.id" class="px-4 py-3">
                        <div class="flex items-start gap-2">
                            <div :class="['w-3 h-3 rounded-full mt-1 flex-shrink-0', item.is_veg ? 'bg-green-500' : 'bg-red-500']"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ item.product_name }}</p>
                                <p v-if="item.variant_name" class="text-xs text-gray-500">{{ item.variant_name }}</p>
                                <div v-if="item.addons?.length" class="text-xs text-gray-500">
                                    + {{ item.addons.map(a => a.addon_name).join(', ') }}
                                </div>
                                <div class="flex items-center gap-2 mt-1.5">
                                    <button @click="cart.updateQuantity(item.id, item.quantity - 1)"
                                        class="w-7 h-7 flex items-center justify-center bg-gray-100 hover:bg-red-100 hover:text-red-600 rounded-full text-gray-600">
                                        <Minus class="w-3.5 h-3.5" />
                                    </button>
                                    <span class="w-6 text-center text-sm font-bold">{{ item.quantity }}</span>
                                    <button @click="cart.updateQuantity(item.id, item.quantity + 1)"
                                        class="w-7 h-7 flex items-center justify-center bg-gray-100 hover:bg-green-100 hover:text-green-600 rounded-full text-gray-600">
                                        <Plus class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-gray-900">₹{{ (item.unit_price * item.quantity).toFixed(0) }}</p>
                                <p class="text-xs text-gray-400">₹{{ item.unit_price }}/{{ item.unit_type }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Totals -->
            <div class="border-t p-4 space-y-2 bg-gray-50">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Subtotal</span><span>₹{{ cart.subtotal.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Tax</span><span>₹{{ cart.taxTotal.toFixed(2) }}</span>
                </div>
                <div v-if="cart.discountAmount > 0" class="flex justify-between text-sm text-green-600">
                    <span>Discount</span><span>-₹{{ cart.discountAmount.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-lg border-t pt-2">
                    <span>Total</span><span class="text-orange-600">₹{{ cart.grandTotal }}</span>
                </div>

                <!-- Action buttons -->
                <div class="flex gap-2 pt-1">
                    <button @click="showDiscountModal = true"
                        class="flex-1 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100">
                        Discount
                    </button>
                    <button @click="placeOrder"
                        :disabled="!cart.items.length || placingOrder"
                        class="flex-1 py-2 text-sm bg-orange-500 text-white rounded-lg font-semibold hover:bg-orange-600 disabled:opacity-50 transition-colors">
                        {{ placingOrder ? 'Placing...' : 'Place Order' }}
                    </button>
                </div>
                <button @click="showPaymentModal = true"
                    v-if="currentOrder"
                    class="w-full py-3 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 transition-colors">
                    <CreditCard class="w-5 h-5 inline mr-2" />Pay ₹{{ currentOrder.grand_total }}
                </button>
            </div>
        </div>
    </div>

    <!-- Discount Modal -->
    <Teleport to="body">
        <div v-if="showDiscountModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl w-full max-w-sm p-6">
                <h3 class="font-bold text-lg mb-4">Apply Discount</h3>
                <div class="space-y-4">
                    <div class="flex gap-2">
                        <button @click="discountForm.type = 'percentage'"
                            :class="['flex-1 py-2 rounded-lg text-sm font-medium', discountForm.type === 'percentage' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-600']">
                            Percentage %
                        </button>
                        <button @click="discountForm.type = 'fixed'"
                            :class="['flex-1 py-2 rounded-lg text-sm font-medium', discountForm.type === 'fixed' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-600']">
                            Fixed ₹
                        </button>
                    </div>
                    <input v-model="discountForm.value" type="number" min="0" placeholder="Enter amount"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-orange-500 outline-none" />
                    <input v-model="discountForm.reason" placeholder="Reason (optional)"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-orange-500 outline-none" />
                </div>
                <div class="flex gap-3 mt-6">
                    <button @click="showDiscountModal = false; cart.clearDiscount()" class="flex-1 py-3 border rounded-lg text-gray-600 hover:bg-gray-50">Remove</button>
                    <button @click="applyDiscount" class="flex-1 py-3 bg-orange-500 text-white rounded-lg font-semibold">Apply</button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Payment Modal -->
    <Teleport to="body">
        <div v-if="showPaymentModal && currentOrder" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-bold text-xl">Payment</h3>
                    <button @click="showPaymentModal = false"><X class="w-6 h-6 text-gray-400" /></button>
                </div>
                <div class="text-center mb-6">
                    <p class="text-sm text-gray-500">Amount to Pay</p>
                    <p class="text-4xl font-bold text-orange-600">₹{{ currentOrder.grand_total }}</p>
                </div>
                <div class="grid grid-cols-3 gap-2 mb-4">
                    <button v-for="method in paymentMethods" :key="method.value"
                        @click="selectedPaymentMethod = method.value"
                        :class="['py-3 rounded-xl text-sm font-medium border-2 transition-colors', selectedPaymentMethod === method.value ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50']">
                        {{ method.label }}
                    </button>
                </div>
                <div v-if="selectedPaymentMethod === 'cash'" class="mb-4">
                    <label class="text-sm text-gray-600 mb-1 block">Cash Tendered</label>
                    <input v-model="cashTendered" type="number" :placeholder="currentOrder.grand_total"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 text-lg font-bold focus:ring-2 focus:ring-orange-500 outline-none" />
                    <div v-if="cashTendered >= currentOrder.grand_total" class="mt-2 text-green-600 font-bold">
                        Change: ₹{{ (cashTendered - currentOrder.grand_total).toFixed(0) }}
                    </div>
                </div>
                <div v-else-if="selectedPaymentMethod === 'upi'" class="mb-4">
                    <input v-model="upiTransactionId" placeholder="UPI Transaction ID"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-orange-500 outline-none" />
                </div>
                <button @click="processPayment" :disabled="processingPayment"
                    class="w-full py-4 bg-green-600 text-white rounded-xl font-bold text-lg hover:bg-green-700 disabled:opacity-60 transition-colors">
                    {{ processingPayment ? 'Processing...' : `Collect ₹${currentOrder.grand_total}` }}
                </button>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import { useCartStore } from '@/stores/cart.js'
import api from '@/api/index.js'
import {
    ShoppingCart, UtensilsCrossed, Search, Trash2, Plus, Minus,
    X, CreditCard
} from 'lucide-vue-next'

const cart = useCartStore()
const categories = ref([])
const products = ref([])
const availableTables = ref([])
const selectedCategoryId = ref(null)
const searchQuery = ref('')
const loading = ref(false)
const customerSearch = ref('')
const customerResults = ref([])
const currentOrder = ref(null)
const placingOrder = ref(false)
const showDiscountModal = ref(false)
const showPaymentModal = ref(false)
const processingPayment = ref(false)
const selectedPaymentMethod = ref('cash')
const cashTendered = ref(null)
const upiTransactionId = ref('')

const orderTypes = [
    { value: 'dine_in', label: 'Dine In' },
    { value: 'takeaway', label: 'Takeaway' },
    { value: 'delivery', label: 'Delivery' },
]

const paymentMethods = [
    { value: 'cash', label: 'Cash' },
    { value: 'card', label: 'Card' },
    { value: 'upi', label: 'UPI' },
    { value: 'wallet', label: 'Wallet' },
    { value: 'credit', label: 'Credit' },
    { value: 'food_coupon', label: 'Coupon' },
]

const discountForm = ref({ type: 'percentage', value: '', reason: '' })

const filteredProducts = computed(() => {
    let list = products.value.filter(p => p.is_active)
    if (selectedCategoryId.value) list = list.filter(p => p.category_id === selectedCategoryId.value)
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase()
        list = list.filter(p => p.name.toLowerCase().includes(q))
    }
    return list
})

async function loadData() {
    loading.value = true
    try {
        const [catRes, prodRes, tableRes] = await Promise.all([
            api.get('/categories?pos_only=1'),
            api.get('/products?pos_only=1&per_page=200'),
            api.get('/tables'),
        ])
        categories.value = catRes.data.categories
        products.value = prodRes.data.products.data
        availableTables.value = tableRes.data.tables.filter(t => t.status === 'available')
    } finally {
        loading.value = false
    }
}

function addToCart(product) {
    if (!product.is_available) return
    cart.addItem(product)
    toast.success(`${product.name} added`, { autoClose: 1000 })
}

let customerTimer = null
function debouncedCustomerSearch() {
    clearTimeout(customerTimer)
    customerTimer = setTimeout(searchCustomer, 400)
}

async function searchCustomer() {
    if (!customerSearch.value || customerSearch.value.length < 3) { customerResults.value = []; return }
    const { data } = await api.get('/customers/find-by-phone?phone=' + customerSearch.value)
    customerResults.value = data.customer ? [data.customer] : []
}

function selectCustomer(c) {
    cart.selectedCustomer = c
    customerSearch.value = c.name
    customerResults.value = []
}

function applyDiscount() {
    cart.setDiscount(discountForm.value.type, discountForm.value.value, discountForm.value.reason)
    showDiscountModal.value = false
}

async function placeOrder() {
    if (!cart.items.length) return
    placingOrder.value = true
    try {
        const { data } = await api.post('/orders', cart.toOrderPayload())
        currentOrder.value = data.order
        toast.success(`Order ${data.order.order_number} placed!`)
    } catch (err) {
        toast.error(err.response?.data?.message || 'Failed to place order')
    } finally {
        placingOrder.value = false
    }
}

async function processPayment() {
    if (!currentOrder.value) return
    processingPayment.value = true
    try {
        const paymentData = {
            payments: [{
                method: selectedPaymentMethod.value,
                amount: currentOrder.value.grand_total,
                cash_tendered: selectedPaymentMethod.value === 'cash' ? (cashTendered.value || currentOrder.value.grand_total) : undefined,
                upi_transaction_id: selectedPaymentMethod.value === 'upi' ? upiTransactionId.value : undefined,
            }],
        }
        const { data } = await api.post(`/orders/${currentOrder.value.id}/payment`, paymentData)
        toast.success('Payment successful!')
        showPaymentModal.value = false
        currentOrder.value = null
        cart.clearCart()
        await loadData()
    } catch (err) {
        toast.error(err.response?.data?.message || 'Payment failed')
    } finally {
        processingPayment.value = false
    }
}

onMounted(loadData)
</script>
