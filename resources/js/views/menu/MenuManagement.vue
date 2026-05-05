<template>
    <div class="p-6">
        <!-- Tabs -->
        <div class="flex gap-4 mb-6">
            <button @click="tab = 'categories'" :class="['px-5 py-2.5 rounded-lg font-semibold text-sm transition-colors', tab === 'categories' ? 'bg-orange-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-100']">
                Categories ({{ categories.length }})
            </button>
            <button @click="tab = 'products'" :class="['px-5 py-2.5 rounded-lg font-semibold text-sm transition-colors', tab === 'products' ? 'bg-orange-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-100']">
                Products ({{ products.total ?? 0 }})
            </button>
        </div>

        <!-- Categories Tab -->
        <div v-if="tab === 'categories'">
            <div class="flex justify-end mb-4">
                <button @click="openCategoryForm()" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 flex items-center gap-2">
                    <Plus class="w-4 h-4" /> Add Category
                </button>
            </div>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Name</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Products</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Display</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="cat in categories" :key="cat.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                        <span class="text-orange-500 text-sm">{{ cat.name.charAt(0) }}</span>
                                    </div>
                                    <span class="font-medium text-gray-900">{{ cat.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ cat.products_count }}</td>
                            <td class="px-6 py-4">
                                <div class="flex gap-1">
                                    <span v-if="cat.display_in_pos" class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">POS</span>
                                    <span v-if="cat.display_in_kds" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded">KDS</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="cat.is_active ? 'text-green-600' : 'text-red-500'" class="text-sm font-medium">
                                    {{ cat.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button @click="openCategoryForm(cat)" class="text-orange-500 hover:underline text-sm mr-3">Edit</button>
                                <button @click="deleteCategory(cat)" class="text-red-500 hover:underline text-sm">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Products Tab -->
        <div v-else>
            <div class="flex items-center gap-3 mb-4">
                <select v-model="productFilters.category_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All Categories</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <input v-model="productFilters.search" placeholder="Search products..." class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1" />
                <button @click="openProductForm()" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 flex items-center gap-2 flex-shrink-0">
                    <Plus class="w-4 h-4" /> Add Product
                </button>
            </div>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Product</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Category</th>
                            <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Price</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Availability</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="product in productList" :key="product.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                        <img v-if="product.image" :src="product.image" class="w-full h-full object-cover" />
                                        <UtensilsCrossed v-else class="w-5 h-5 text-gray-400 m-2.5" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ product.name }}</p>
                                        <p class="text-xs text-gray-400">{{ product.sku }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ product.category?.name }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">₹{{ product.price }}</td>
                            <td class="px-6 py-4">
                                <div :class="['w-4 h-4 rounded-full border-2', product.is_veg ? 'border-green-500 bg-green-100' : 'border-red-500 bg-red-100']"></div>
                            </td>
                            <td class="px-6 py-4">
                                <button @click="toggleAvailability(product)"
                                    :class="['px-3 py-1 rounded-full text-xs font-medium transition-colors', product.is_available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                                    {{ product.is_available ? 'Available' : 'Unavailable' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button @click="openProductForm(product)" class="text-orange-500 hover:underline text-sm mr-3">Edit</button>
                                <button @click="deleteProduct(product)" class="text-red-500 hover:underline text-sm">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Category Form Modal -->
    <Teleport to="body">
        <div v-if="showCategoryModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl w-full max-w-md p-6">
                <h3 class="font-bold text-lg mb-4">{{ editingCategory?.id ? 'Edit' : 'Add' }} Category</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Name *</label>
                        <input v-model="categoryForm.name" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-orange-500 outline-none" />
                    </div>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="categoryForm.is_active" type="checkbox" class="w-4 h-4 accent-orange-500" />
                            Active
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="categoryForm.display_in_pos" type="checkbox" class="w-4 h-4 accent-orange-500" />
                            Show in POS
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="categoryForm.display_in_kds" type="checkbox" class="w-4 h-4 accent-orange-500" />
                            Show in KDS
                        </label>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button @click="showCategoryModal = false" class="flex-1 py-3 border rounded-lg text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button @click="saveCategory" class="flex-1 py-3 bg-orange-500 text-white rounded-lg font-semibold hover:bg-orange-600">Save</button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Product Form Modal -->
    <Teleport to="body">
        <div v-if="showProductModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto">
            <div class="bg-white rounded-2xl w-full max-w-lg p-6 my-4">
                <h3 class="font-bold text-lg mb-4">{{ editingProduct?.id ? 'Edit' : 'Add' }} Product</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Name *</label>
                            <input v-model="productForm.name" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Category *</label>
                            <select v-model="productForm.category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5">
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Price *</label>
                            <input v-model="productForm.price" type="number" step="0.01" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Cost Price</label>
                            <input v-model="productForm.cost_price" type="number" step="0.01" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">SKU</label>
                            <input v-model="productForm.sku" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Prep Time (min)</label>
                            <input v-model="productForm.preparation_time_minutes" type="number" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" />
                        </div>
                    </div>
                    <div class="flex gap-4 flex-wrap">
                        <label class="flex items-center gap-2 text-sm"><input v-model="productForm.is_veg" type="checkbox" class="w-4 h-4 accent-green-500" />Veg</label>
                        <label class="flex items-center gap-2 text-sm"><input v-model="productForm.is_available" type="checkbox" class="w-4 h-4 accent-orange-500" />Available</label>
                        <label class="flex items-center gap-2 text-sm"><input v-model="productForm.is_featured" type="checkbox" class="w-4 h-4 accent-orange-500" />Featured</label>
                        <label class="flex items-center gap-2 text-sm"><input v-model="productForm.is_active" type="checkbox" class="w-4 h-4 accent-orange-500" />Active</label>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Description</label>
                        <textarea v-model="productForm.short_description" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none"></textarea>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button @click="showProductModal = false" class="flex-1 py-3 border rounded-lg text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button @click="saveProduct" class="flex-1 py-3 bg-orange-500 text-white rounded-lg font-semibold hover:bg-orange-600">Save</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue'
import { toast } from 'vue3-toastify'
import { Plus, UtensilsCrossed } from 'lucide-vue-next'
import api from '@/api/index.js'

const tab = ref('categories')
const categories = ref([])
const products = ref({})
const productFilters = reactive({ category_id: '', search: '' })

const productList = computed(() => products.value.data ?? [])

const showCategoryModal = ref(false)
const showProductModal = ref(false)
const editingCategory = ref(null)
const editingProduct = ref(null)

const categoryForm = reactive({ name: '', is_active: true, display_in_pos: true, display_in_kds: true })
const productForm = reactive({
    name: '', category_id: '', price: '', cost_price: '', sku: '',
    is_veg: true, is_available: true, is_featured: false, is_active: true,
    short_description: '', preparation_time_minutes: 15,
})

async function loadCategories() {
    const { data } = await api.get('/categories')
    categories.value = data.categories
}

async function loadProducts() {
    const params = {}
    if (productFilters.category_id) params.category_id = productFilters.category_id
    if (productFilters.search) params.search = productFilters.search
    const { data } = await api.get('/products', { params })
    products.value = data.products
}

function openCategoryForm(cat = null) {
    editingCategory.value = cat
    if (cat) Object.assign(categoryForm, { name: cat.name, is_active: cat.is_active, display_in_pos: cat.display_in_pos, display_in_kds: cat.display_in_kds })
    else Object.assign(categoryForm, { name: '', is_active: true, display_in_pos: true, display_in_kds: true })
    showCategoryModal.value = true
}

async function saveCategory() {
    const payload = { ...categoryForm }
    if (editingCategory.value?.id) {
        await api.put(`/categories/${editingCategory.value.id}`, payload)
        toast.success('Category updated')
    } else {
        await api.post('/categories', payload)
        toast.success('Category created')
    }
    showCategoryModal.value = false
    await loadCategories()
}

async function deleteCategory(cat) {
    if (!confirm(`Delete category "${cat.name}"?`)) return
    await api.delete(`/categories/${cat.id}`)
    toast.success('Category deleted')
    await loadCategories()
}

function openProductForm(prod = null) {
    editingProduct.value = prod
    if (prod) {
        Object.assign(productForm, {
            name: prod.name, category_id: prod.category_id, price: prod.price,
            cost_price: prod.cost_price, sku: prod.sku, is_veg: prod.is_veg,
            is_available: prod.is_available, is_featured: prod.is_featured,
            is_active: prod.is_active, short_description: prod.short_description,
            preparation_time_minutes: prod.preparation_time_minutes,
        })
    } else {
        Object.assign(productForm, {
            name: '', category_id: '', price: '', cost_price: '', sku: '',
            is_veg: true, is_available: true, is_featured: false, is_active: true,
            short_description: '', preparation_time_minutes: 15,
        })
    }
    showProductModal.value = true
}

async function saveProduct() {
    if (editingProduct.value?.id) {
        await api.put(`/products/${editingProduct.value.id}`, productForm)
        toast.success('Product updated')
    } else {
        await api.post('/products', productForm)
        toast.success('Product created')
    }
    showProductModal.value = false
    await loadProducts()
}

async function deleteProduct(product) {
    if (!confirm(`Delete product "${product.name}"?`)) return
    await api.delete(`/products/${product.id}`)
    toast.success('Product deleted')
    await loadProducts()
}

async function toggleAvailability(product) {
    await api.patch(`/products/${product.id}/toggle`)
    product.is_available = !product.is_available
}

let searchTimer = null
watch(productFilters, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(loadProducts, 400)
}, { deep: true })

onMounted(() => {
    loadCategories()
    loadProducts()
})
</script>
