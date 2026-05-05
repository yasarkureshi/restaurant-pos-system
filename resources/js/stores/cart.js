import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCartStore = defineStore('cart', () => {
    const items = ref([])
    const orderType = ref('dine_in')
    const selectedTable = ref(null)
    const selectedCustomer = ref(null)
    const discount = ref({ type: null, value: 0, reason: '' })
    const specialInstructions = ref('')
    const numberOfGuests = ref(1)

    const subtotal = computed(() =>
        items.value.reduce((sum, item) => sum + item.unit_price * item.quantity, 0)
    )

    const taxTotal = computed(() =>
        items.value.reduce((sum, item) => {
            const itemSubtotal = item.unit_price * item.quantity
            return sum + (itemSubtotal * (item.tax_percentage || 0)) / 100
        }, 0)
    )

    const discountAmount = computed(() => {
        if (!discount.value.type || !discount.value.value) return 0
        if (discount.value.type === 'percentage') {
            return (subtotal.value * discount.value.value) / 100
        }
        return discount.value.value
    })

    const grandTotal = computed(() =>
        Math.round(subtotal.value + taxTotal.value - discountAmount.value)
    )

    const itemCount = computed(() =>
        items.value.reduce((sum, item) => sum + item.quantity, 0)
    )

    function addItem(product, quantity = 1, variant = null, addons = []) {
        const existingIdx = items.value.findIndex(
            i => i.product_id === product.id &&
                 i.variant_id === (variant?.id ?? null) &&
                 JSON.stringify(i.addons) === JSON.stringify(addons)
        )

        const addonTotal = addons.reduce((s, a) => s + a.addon_price, 0)
        const unitPrice = product.current_price + (variant?.price_adjustment ?? 0) + addonTotal

        if (existingIdx >= 0) {
            items.value[existingIdx].quantity += quantity
        } else {
            items.value.push({
                id: Date.now(),
                product_id: product.id,
                product_name: product.name,
                product_price: product.price,
                image: product.image,
                unit_price: unitPrice,
                quantity,
                unit_type: product.unit_type,
                variant_id: variant?.id ?? null,
                variant_name: variant?.name ?? null,
                variant_price: variant?.price_adjustment ?? 0,
                addons,
                addons_total: addonTotal,
                tax_percentage: product.taxCategory?.tax_percentage ?? 0,
                special_instructions: '',
                is_veg: product.is_veg,
            })
        }
    }

    function removeItem(itemId) {
        items.value = items.value.filter(i => i.id !== itemId)
    }

    function updateQuantity(itemId, quantity) {
        const item = items.value.find(i => i.id === itemId)
        if (!item) return
        if (quantity <= 0) {
            removeItem(itemId)
        } else {
            item.quantity = quantity
        }
    }

    function updateItemNote(itemId, note) {
        const item = items.value.find(i => i.id === itemId)
        if (item) item.special_instructions = note
    }

    function setDiscount(type, value, reason = '') {
        discount.value = { type, value: parseFloat(value), reason }
    }

    function clearDiscount() {
        discount.value = { type: null, value: 0, reason: '' }
    }

    function clearCart() {
        items.value = []
        orderType.value = 'dine_in'
        selectedTable.value = null
        selectedCustomer.value = null
        discount.value = { type: null, value: 0, reason: '' }
        specialInstructions.value = ''
        numberOfGuests.value = 1
    }

    function toOrderPayload() {
        return {
            order_type: orderType.value,
            table_id: selectedTable.value?.id ?? null,
            customer_id: selectedCustomer.value?.id ?? null,
            customer_name: selectedCustomer.value?.name ?? null,
            customer_phone: selectedCustomer.value?.phone ?? null,
            number_of_guests: numberOfGuests.value,
            special_instructions: specialInstructions.value || null,
            discount_type: discount.value.type,
            discount_amount: discount.value.value,
            discount_reason: discount.value.reason,
            items: items.value.map(i => ({
                product_id: i.product_id,
                quantity: i.quantity,
                variant_id: i.variant_id,
                special_instructions: i.special_instructions || null,
                addons: i.addons,
            })),
        }
    }

    return {
        items, orderType, selectedTable, selectedCustomer, discount,
        specialInstructions, numberOfGuests,
        subtotal, taxTotal, discountAmount, grandTotal, itemCount,
        addItem, removeItem, updateQuantity, updateItemNote,
        setDiscount, clearDiscount, clearCart, toOrderPayload,
    }
})
