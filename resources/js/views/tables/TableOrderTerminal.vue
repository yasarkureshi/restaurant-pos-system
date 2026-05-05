<template>
    <div class="flex flex-col h-screen overflow-hidden bg-white">

        <!-- ═══ TOP BAR ═══ -->
        <div class="bg-gray-800 text-white px-3 py-2 flex items-center gap-2 shrink-0">
            <button @click="router.push({ name: isTableMode ? 'tables' : 'tables' })"
                class="flex items-center gap-1 text-xs text-gray-300 hover:text-white px-2 py-1.5 rounded hover:bg-gray-700 transition-colors shrink-0">
                <ArrowLeft class="w-3.5 h-3.5" /> Back
            </button>
            <div class="h-4 border-l border-gray-600 shrink-0"></div>
            <div class="flex-1 min-w-0">
                <span class="font-bold text-sm">
                    {{ isTableMode ? (table?.name ?? 'Loading...') : orderTypeLabel }}
                </span>
                <span v-if="isTableMode && table?.section" class="text-gray-400 text-xs ml-2">{{ table.section.name }}</span>
                <span v-if="currentOrder" class="ml-2 text-xs bg-blue-600 px-2 py-0.5 rounded-full">
                    {{ currentOrder.order_number }}
                </span>
            </div>
            <!-- Order type tabs -->
            <div class="flex border border-gray-600 rounded overflow-hidden text-xs font-bold shrink-0">
                <button v-for="t in orderTypes" :key="t.value"
                    @click="orderType = t.value"
                    :class="['px-3 py-1.5 transition-colors', orderType === t.value ? 'bg-red-600 text-white' : 'text-gray-300 hover:bg-gray-700']">
                    {{ t.label }}
                </button>
            </div>
        </div>

        <!-- Mobile tab bar (hidden on md+) -->
        <div class="flex border-b bg-white md:hidden shrink-0">
            <button @click="mobileTab = 'menu'"
                :class="['flex-1 py-2.5 text-xs font-bold border-b-2 transition-colors', mobileTab === 'menu' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500']">
                MENU
            </button>
            <button @click="mobileTab = 'cart'"
                :class="['flex-1 py-2.5 text-xs font-bold border-b-2 transition-colors relative', mobileTab === 'cart' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500']">
                CART
                <span v-if="localCartCount > 0"
                    class="absolute top-1.5 right-6 w-4 h-4 bg-red-600 text-white text-xs rounded-full flex items-center justify-center font-bold leading-none">
                    {{ localCartCount }}
                </span>
            </button>
            <button @click="mobileTab = 'pay'"
                :class="['flex-1 py-2.5 text-xs font-bold border-b-2 transition-colors', mobileTab === 'pay' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500']">
                PAY
            </button>
        </div>

        <!-- ═══ MAIN LAYOUT ═══ -->
        <div class="flex flex-1 overflow-hidden">

            <!-- LEFT: Category List -->
            <div :class="['w-32 flex-shrink-0 flex-col border-r bg-gray-50 overflow-hidden',
                mobileTab === 'menu' ? 'flex' : 'hidden md:flex']">
                <div class="flex-1 overflow-y-auto">
                    <button
                        @click="activeCategory = null; menuSearch = ''"
                        :class="['w-full px-2 py-2.5 text-left text-xs font-medium border-b border-gray-200 transition-colors leading-tight',
                            activeCategory === null
                                ? 'bg-green-600 text-white border-green-600'
                                : 'text-gray-700 hover:bg-white']">
                        All Items
                    </button>
                    <button v-for="cat in categories" :key="cat.id"
                        @click="activeCategory = cat.id; menuSearch = ''"
                        :class="['w-full px-2 py-2.5 text-left text-xs font-medium border-b border-gray-200 transition-colors leading-tight',
                            activeCategory === cat.id
                                ? 'bg-green-600 text-white border-green-600'
                                : 'text-gray-700 hover:bg-white']">
                        {{ cat.name }}
                    </button>
                </div>
            </div>

            <!-- MIDDLE: Search + Items Grid -->
            <div :class="['flex-1 flex-col overflow-hidden',
                mobileTab === 'menu' ? 'flex' : 'hidden md:flex']">
                <!-- Search bar -->
                <div class="px-2.5 py-2 border-b bg-white shrink-0">
                    <div class="flex items-center gap-2 bg-gray-100 rounded px-2.5 py-1.5">
                        <Search class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                        <input v-model="menuSearch" placeholder="Search item..."
                            class="bg-transparent text-xs outline-none flex-1 placeholder-gray-400" />
                        <button v-if="menuSearch" @click="menuSearch = ''" class="text-gray-400">
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Items grid -->
                <div class="flex-1 overflow-y-auto p-2 bg-gray-50">
                    <div v-if="loading" class="flex items-center justify-center h-32">
                        <div class="animate-spin w-6 h-6 border-4 border-red-500 border-t-transparent rounded-full"></div>
                    </div>
                    <div v-else-if="!filteredProducts.length" class="text-center text-gray-400 py-10 text-xs">
                        No items found
                    </div>
                    <div v-else class="grid gap-1.5" style="grid-template-columns: repeat(auto-fill, minmax(100px, 1fr))">
                        <button v-for="prod in filteredProducts" :key="prod.id"
                            @click="handleAddProduct(prod)"
                            :disabled="!prod.is_available"
                            :class="['bg-white rounded border text-left p-2 transition-all text-xs relative',
                                localCart[prod.id] ? 'border-red-400 bg-red-50' : 'border-gray-200 hover:border-gray-400 hover:shadow-sm',
                                !prod.is_available ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer']">

                            <!-- Top row: veg + qty badge -->
                            <div class="flex items-start justify-between mb-1">
                                <span :class="['w-3 h-3 rounded-sm border-2 flex items-center justify-center shrink-0',
                                    prod.is_veg ? 'border-green-500' : 'border-red-500']">
                                    <span :class="['w-1.5 h-1.5 rounded-full', prod.is_veg ? 'bg-green-500' : 'bg-red-500']"></span>
                                </span>
                                <span v-if="localCart[prod.id]"
                                    class="w-4 h-4 bg-red-600 text-white text-xs rounded-full flex items-center justify-center font-bold leading-none">
                                    {{ localCart[prod.id].qty }}
                                </span>
                            </div>

                            <p class="font-medium text-gray-800 leading-tight line-clamp-2 mb-1" style="font-size:11px">{{ prod.name }}</p>
                            <p class="font-bold text-red-600" style="font-size:11px">₹{{ prod.price }}</p>

                            <!-- +/- controls -->
                            <div v-if="localCart[prod.id]"
                                class="flex items-center justify-between mt-1 bg-white border border-red-200 rounded overflow-hidden">
                                <button @click.stop="removeFromLocalCart(prod)"
                                    class="w-6 h-5 text-red-600 font-bold flex items-center justify-center hover:bg-red-50 text-sm leading-none">−</button>
                                <span class="text-xs font-bold text-red-700">{{ localCart[prod.id].qty }}</span>
                                <button @click.stop="handleAddProduct(prod)"
                                    class="w-6 h-5 text-red-600 font-bold flex items-center justify-center hover:bg-red-50 text-sm leading-none">+</button>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Cart + Bill + Actions -->
            <div :class="['flex-shrink-0 flex-col border-l bg-white overflow-hidden',
                mobileTab === 'menu' ? 'hidden md:flex md:w-64' : 'flex w-full md:w-64',
                mobileTab === 'pay' ? 'w-full' : '']">

                <!-- Delivery/Takeaway customer info (non-table mode) -->
                <div v-if="!isTableMode" class="border-b px-3 py-2 bg-blue-50 shrink-0">
                    <p class="text-xs font-semibold text-blue-700 mb-2">
                        {{ orderType === 'delivery' ? 'Delivery Info' : 'Customer Info' }}
                    </p>
                    <div class="space-y-1.5">
                        <input v-model="customerName" placeholder="Customer name *"
                            class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs outline-none focus:border-red-400" />
                        <input v-model="customerPhone" placeholder="Phone number" type="tel"
                            class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs outline-none focus:border-red-400" />
                        <textarea v-if="orderType === 'delivery'" v-model="deliveryAddress"
                            placeholder="Delivery address"
                            rows="2"
                            class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs outline-none focus:border-red-400 resize-none"></textarea>
                    </div>
                </div>

                <!-- Cart header tabs -->
                <div class="flex border-b shrink-0">
                    <button @click="cartTab = 'items'"
                        :class="['flex-1 py-1.5 text-xs font-bold border-b-2 transition-colors',
                            cartTab === 'items' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                        ITEMS
                    </button>
                    <button @click="cartTab = 'check'"
                        :class="['flex-1 py-1.5 text-xs font-bold border-b-2 transition-colors',
                            cartTab === 'check' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                        CHECK
                    </button>
                    <div class="flex items-center px-2 gap-2 text-xs text-gray-400 shrink-0 border-l">
                        <span>QTY</span>
                        <span>AMT</span>
                    </div>
                </div>

                <!-- Cart items -->
                <div class="flex-1 overflow-y-auto">
                    <div v-if="orderLoading" class="flex items-center justify-center py-8">
                        <div class="animate-spin w-5 h-5 border-4 border-red-500 border-t-transparent rounded-full"></div>
                    </div>

                    <div v-else-if="!currentOrder?.items?.length && !localCartCount"
                        class="flex flex-col items-center justify-center py-10 text-gray-400 px-4 text-center">
                        <UtensilsCrossed class="w-8 h-8 mb-2 opacity-30" />
                        <p class="text-xs font-medium">No Item Selected</p>
                        <p class="text-xs opacity-60 mt-1">Select items from the menu</p>
                    </div>

                    <template v-else>
                        <!-- Ordered Items -->
                        <template v-if="currentOrder?.items?.length">
                            <div class="px-3 py-1 bg-blue-50 border-b">
                                <p class="text-xs font-semibold text-blue-600">Ordered Items</p>
                            </div>
                            <div v-for="item in currentOrder.items" :key="item.id"
                                class="flex items-center gap-2 px-3 py-1.5 border-b border-gray-50 hover:bg-gray-50">
                                <span :class="['w-3 h-3 rounded-sm border-2 shrink-0 flex items-center justify-center',
                                    item.product?.is_veg ? 'border-green-500' : 'border-red-500']">
                                    <span :class="['w-1.5 h-1.5 rounded-full', item.product?.is_veg ? 'bg-green-500' : 'bg-red-500']"></span>
                                </span>
                                <p class="flex-1 text-xs text-gray-800 font-medium leading-tight truncate">{{ item.product_name }}</p>
                                <span class="text-xs text-gray-500 w-5 text-center shrink-0">{{ item.quantity }}</span>
                                <span class="text-xs font-semibold text-gray-800 w-10 text-right shrink-0">₹{{ item.item_total }}</span>
                            </div>
                        </template>

                        <!-- Pending KOT -->
                        <template v-if="localCartCount > 0">
                            <div class="px-3 py-1 bg-orange-50 border-b border-t">
                                <p class="text-xs font-semibold text-orange-600">Pending KOT</p>
                            </div>
                            <div v-for="(cartItem, prodId) in localCart" :key="prodId"
                                class="flex items-center gap-2 px-3 py-1.5 border-b border-gray-50 hover:bg-orange-50 group">
                                <span :class="['w-3 h-3 rounded-sm border-2 shrink-0 flex items-center justify-center',
                                    cartItem.product.is_veg ? 'border-green-500' : 'border-red-500']">
                                    <span :class="['w-1.5 h-1.5 rounded-full', cartItem.product.is_veg ? 'bg-green-500' : 'bg-red-500']"></span>
                                </span>
                                <p class="flex-1 text-xs text-gray-800 font-medium leading-tight truncate">{{ cartItem.product.name }}</p>
                                <div class="flex items-center gap-0.5 shrink-0">
                                    <button @click="removeFromLocalCart(cartItem.product)"
                                        class="w-4 h-4 bg-red-100 text-red-600 rounded-full text-xs font-bold flex items-center justify-center hover:bg-red-200">−</button>
                                    <span class="w-4 text-center text-xs font-bold text-gray-700">{{ cartItem.qty }}</span>
                                    <button @click="addToLocalCartDirect(cartItem.product)"
                                        class="w-4 h-4 bg-red-100 text-red-600 rounded-full text-xs font-bold flex items-center justify-center hover:bg-red-200">+</button>
                                </div>
                                <span class="text-xs font-semibold text-orange-700 w-10 text-right shrink-0">
                                    ₹{{ (cartItem.product.price * cartItem.qty).toFixed(0) }}
                                </span>
                            </div>
                        </template>
                    </template>
                </div>

                <!-- Bill Summary -->
                <div class="border-t px-3 py-2 bg-gray-50 shrink-0 space-y-1 text-xs">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span><span>₹{{ billSubtotal }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Tax (GST)</span><span>₹{{ billTax }}</span>
                    </div>
                    <div v-if="currentOrder && parseFloat(currentOrder.discount_amount ?? 0) > 0"
                        class="flex justify-between text-green-600 font-medium">
                        <span>Discount</span><span>-₹{{ currentOrder.discount_amount }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-sm text-gray-900 pt-1 border-t border-gray-200">
                        <span>Total</span>
                        <span class="text-red-600">₹{{ billTotal }}</span>
                    </div>
                    <label class="flex items-center gap-2 text-gray-500 cursor-pointer pt-0.5">
                        <input type="checkbox" v-model="complimentary" class="rounded accent-red-600" />
                        <span>Complimentary</span>
                    </label>
                </div>

                <!-- Payment method -->
                <div class="border-t px-3 py-2 shrink-0">
                    <div class="flex gap-1 flex-wrap">
                        <button v-for="m in paymentMethods" :key="m.key"
                            @click="payMethod = m.key"
                            :class="['px-2 py-1 rounded text-xs font-semibold border transition-colors',
                                payMethod === m.key ? 'bg-red-600 text-white border-red-600' : 'border-gray-300 text-gray-600 hover:border-gray-400 hover:bg-gray-50']">
                            {{ m.label }}
                        </button>
                    </div>
                    <div v-if="payMethod === 'cash' && (currentOrder || localCartCount)" class="mt-1.5 flex items-center gap-2">
                        <span class="text-xs text-gray-500 shrink-0">Tendered ₹</span>
                        <input v-model="cashTendered" type="number" :placeholder="billTotal"
                            class="flex-1 border border-gray-300 rounded px-2 py-1 text-xs outline-none focus:border-red-400" />
                        <span v-if="cashTendered && parseFloat(cashTendered) >= parseFloat(billTotal)"
                            class="text-xs text-green-600 font-bold shrink-0">
                            ↩ ₹{{ (parseFloat(cashTendered) - parseFloat(billTotal)).toFixed(2) }}
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="border-t px-3 py-2 grid grid-cols-2 gap-1.5 shrink-0 bg-white">
                    <button @click="sendKOT"
                        :disabled="!localCartCount || placing"
                        class="py-2 bg-gray-700 text-white rounded text-xs font-bold hover:bg-gray-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        KOT
                    </button>
                    <button @click="sendKOT"
                        :disabled="!localCartCount || placing"
                        class="py-2 bg-gray-500 text-white rounded text-xs font-bold hover:bg-gray-600 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        KOT & PRINT
                    </button>
                    <button @click="sendKOT"
                        :disabled="!localCartCount || placing"
                        class="py-2 bg-blue-600 text-white rounded text-xs font-bold hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        {{ placing ? 'Saving...' : 'SAVE' }}
                    </button>
                    <button @click="saveAndPay"
                        :disabled="(!currentOrder && !localCartCount) || paying"
                        class="py-2 bg-red-600 text-white rounded text-xs font-bold hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        {{ paying ? 'Processing...' : 'SAVE & PAY' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Duplicate item confirmation -->
    <Teleport to="body">
        <div v-if="confirmItem" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl p-5 max-w-xs w-full shadow-2xl">
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-9 h-9 bg-orange-100 rounded-full flex items-center justify-center shrink-0">
                        <AlertCircle class="w-5 h-5 text-orange-500" />
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm">Item Already in Order</h3>
                        <p class="text-xs text-gray-600 mt-1">
                            <b>{{ confirmItem.name }}</b> is already in the running order. Add more quantity?
                        </p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="confirmItem = null"
                        class="flex-1 py-2 border border-gray-300 rounded text-gray-600 text-xs hover:bg-gray-50">
                        Cancel
                    </button>
                    <button @click="confirmAndAdd"
                        class="flex-1 py-2 bg-red-600 text-white rounded text-xs font-bold hover:bg-red-700">
                        Yes, Add More
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import { ArrowLeft, Search, X, UtensilsCrossed, AlertCircle } from 'lucide-vue-next'
import api from '@/api/index.js'

const route  = useRoute()
const router = useRouter()

// ── Mode detection ────────────────────────────────────────────────
const tableId    = route.params.tableId ? parseInt(route.params.tableId) : null
const isTableMode = !!tableId
const defaultType = route.query.type ?? 'dine_in'

// ── State ─────────────────────────────────────────────────────────
const table        = ref(null)
const categories   = ref([])
const products     = ref([])
const currentOrder = ref(null)
const loading      = ref(true)
const orderLoading = ref(false)
const placing      = ref(false)
const paying       = ref(false)

const activeCategory = ref(null)
const menuSearch     = ref('')
const orderType      = ref(isTableMode ? 'dine_in' : defaultType)
const cartTab        = ref('items')
const complimentary  = ref(false)
const confirmItem    = ref(null)
const mobileTab      = ref('menu')

// Delivery/takeaway customer fields
const customerName   = ref('')
const customerPhone  = ref('')
const deliveryAddress = ref('')

const localCart    = reactive({})
const payMethod    = ref('cash')
const cashTendered = ref('')

const orderTypes = [
    { value: 'dine_in',  label: 'DINE IN' },
    { value: 'delivery', label: 'DELIVERY' },
    { value: 'takeaway', label: 'PICK UP' },
]

const paymentMethods = [
    { key: 'cash',   label: 'Cash' },
    { key: 'card',   label: 'Card' },
    { key: 'upi',    label: 'UPI' },
    { key: 'credit', label: 'Due' },
]

const orderTypeLabel = computed(() => {
    const map = { delivery: 'Delivery Order', takeaway: 'Take Away', dine_in: 'New Order' }
    return map[orderType.value] ?? 'New Order'
})

// ── Computed ──────────────────────────────────────────────────────
const filteredProducts = computed(() => {
    let prods = products.value
    if (activeCategory.value) prods = prods.filter(p => p.category_id === activeCategory.value)
    if (menuSearch.value) {
        const q = menuSearch.value.toLowerCase()
        prods = prods.filter(p => p.name.toLowerCase().includes(q))
    }
    return prods
})

const localCartCount    = computed(() => Object.values(localCart).reduce((s, i) => s + i.qty, 0))
const localCartSubtotal = computed(() => Object.values(localCart).reduce((s, i) => s + i.product.price * i.qty, 0))

const billSubtotal = computed(() => {
    const orderSub = parseFloat(currentOrder.value?.sub_total ?? 0)
    return (orderSub + localCartSubtotal.value).toFixed(2)
})

const billTax = computed(() => parseFloat(currentOrder.value?.tax_amount ?? 0).toFixed(2))

const billTotal = computed(() => {
    if (currentOrder.value) {
        return (parseFloat(currentOrder.value.grand_total) + localCartSubtotal.value).toFixed(2)
    }
    return localCartSubtotal.value.toFixed(2)
})

// ── Data Loading ──────────────────────────────────────────────────
onMounted(async () => {
    loading.value = true
    try {
        const requests = [
            api.get('/categories'),
            api.get('/products?pos_only=1&per_page=200'),
        ]
        if (isTableMode) requests.unshift(api.get(`/tables/${tableId}`))

        const results = await Promise.all(requests)
        const offset = isTableMode ? 1 : 0

        if (isTableMode) {
            table.value = results[0].data.table
            if (table.value?.order_type) orderType.value = table.value.order_type
        }

        categories.value = results[offset].data.categories ?? []
        products.value   = results[offset + 1].data.products?.data ?? results[offset + 1].data.products ?? []

        if (isTableMode && table.value?.current_order_id) {
            orderLoading.value = true
            const { data } = await api.get(`/orders/${table.value.current_order_id}`)
            currentOrder.value = data.order
            orderLoading.value = false
        }
    } catch (err) {
        toast.error('Failed to load data')
        if (isTableMode) router.push({ name: 'tables' })
    } finally {
        loading.value = false
    }
})

// ── Cart Logic ────────────────────────────────────────────────────
function handleAddProduct(prod) {
    if (!prod.is_available) return
    const alreadyOrdered = currentOrder.value?.items?.some(i => i.product_id === prod.id)
    if (alreadyOrdered && !localCart[prod.id]) {
        confirmItem.value = prod
        return
    }
    addToLocalCartDirect(prod)
}

function addToLocalCartDirect(prod) {
    if (localCart[prod.id]) localCart[prod.id].qty++
    else localCart[prod.id] = { product: prod, qty: 1 }
}

function confirmAndAdd() {
    if (confirmItem.value) {
        addToLocalCartDirect(confirmItem.value)
        confirmItem.value = null
    }
}

function removeFromLocalCart(prod) {
    if (!localCart[prod.id]) return
    localCart[prod.id].qty--
    if (localCart[prod.id].qty <= 0) delete localCart[prod.id]
}

function clearLocalCart() {
    Object.keys(localCart).forEach(k => delete localCart[k])
}

// ── Order Actions ─────────────────────────────────────────────────
async function sendKOT() {
    if (!localCartCount.value || placing.value) return

    // Delivery/takeaway: customer name required
    if (!isTableMode && !customerName.value.trim()) {
        return toast.warning('Please enter customer name first')
    }

    placing.value = true
    try {
        const items = Object.values(localCart).map(i => ({
            product_id: i.product.id,
            quantity:   i.qty,
        }))

        if (currentOrder.value) {
            const { data } = await api.post(`/orders/${currentOrder.value.id}/items`, { items })
            currentOrder.value = data.order
        } else {
            const payload = {
                order_type: orderType.value,
                items,
            }
            if (isTableMode) {
                payload.table_id = tableId
            } else {
                payload.customer_name  = customerName.value
                payload.customer_phone = customerPhone.value
            }
            const { data } = await api.post('/orders', payload)
            currentOrder.value = data.order
        }

        clearLocalCart()
        toast.success('KOT sent to kitchen')
        mobileTab.value = 'cart'
    } catch (err) {
        toast.error(err?.response?.data?.message ?? 'Failed to send KOT')
    } finally {
        placing.value = false
    }
}

async function saveAndPay() {
    if (paying.value) return

    if (!isTableMode && !customerName.value.trim()) {
        return toast.warning('Please enter customer name first')
    }

    if (localCartCount.value) {
        await sendKOT()
        if (!currentOrder.value) return
    }

    if (!currentOrder.value) {
        return toast.warning('No order to pay. Add items first.')
    }

    paying.value = true
    try {
        await api.post(`/orders/${currentOrder.value.id}/payment`, {
            payments: [{
                method:          payMethod.value,
                amount:          parseFloat(currentOrder.value.grand_total),
                tendered_amount: payMethod.value === 'cash'
                    ? parseFloat(cashTendered.value || currentOrder.value.grand_total)
                    : undefined,
            }],
        })
        toast.success('Payment done!')
        router.push({ name: 'tables' })
    } catch (err) {
        toast.error(err?.response?.data?.message ?? 'Payment failed')
    } finally {
        paying.value = false
    }
}
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
