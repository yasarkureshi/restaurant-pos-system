<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Customers</h1>
            <button @click="showForm = true; editingCustomer = null" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 flex items-center gap-2">
                <Plus class="w-4 h-4" /> Add Customer
            </button>
        </div>

        <!-- Search & Filters -->
        <div class="flex gap-3 mb-5">
            <input v-model="search" placeholder="Search by name, phone, email..." class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm flex-1 focus:ring-2 focus:ring-orange-500 outline-none" />
            <select v-model="typeFilter" class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                <option value="">All Types</option>
                <option value="new">New</option>
                <option value="regular">Regular</option>
                <option value="vip">VIP</option>
            </select>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Name</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Phone</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Visits</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Total Spent</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Last Visit</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="c in customers" :key="c.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center text-sm font-bold text-orange-600">
                                    {{ c.name.charAt(0) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ c.name }}</p>
                                    <p class="text-xs text-gray-400">{{ c.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ c.phone }}</td>
                        <td class="px-6 py-4">
                            <span :class="typeClass(c.customer_type)" class="px-2.5 py-1 rounded-full text-xs font-medium capitalize">
                                {{ c.customer_type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-600">{{ c.total_visits }}</td>
                        <td class="px-6 py-4 text-right font-semibold text-gray-900">₹{{ c.total_spent }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ c.last_visit_at ? formatDate(c.last_visit_at) : 'Never' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button @click="openEdit(c)" class="text-orange-500 hover:underline text-sm">Edit</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="!customers.length" class="text-center py-16 text-gray-400">
                <Users class="w-12 h-12 mx-auto mb-3 opacity-40" />
                <p>No customers found</p>
            </div>
        </div>
    </div>

    <!-- Customer Form Modal -->
    <Teleport to="body">
        <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl w-full max-w-md p-6">
                <h3 class="font-bold text-lg mb-4">{{ editingCustomer ? 'Edit' : 'Add' }} Customer</h3>
                <div class="space-y-3">
                    <div><label class="text-sm font-medium mb-1 block">Name *</label>
                        <input v-model="form.name" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" /></div>
                    <div><label class="text-sm font-medium mb-1 block">Phone *</label>
                        <input v-model="form.phone" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" :disabled="!!editingCustomer" /></div>
                    <div><label class="text-sm font-medium mb-1 block">Email</label>
                        <input v-model="form.email" type="email" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" /></div>
                    <div><label class="text-sm font-medium mb-1 block">Date of Birth</label>
                        <input v-model="form.date_of_birth" type="date" class="w-full border border-gray-300 rounded-lg px-4 py-2.5" /></div>
                    <div><label class="text-sm font-medium mb-1 block">Notes</label>
                        <textarea v-model="form.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none"></textarea></div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button @click="showForm = false" class="flex-1 py-3 border rounded-lg text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button @click="saveCustomer" class="flex-1 py-3 bg-orange-500 text-white rounded-lg font-semibold">Save</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import { Plus, Users } from 'lucide-vue-next'
import api from '@/api/index.js'

const customers = ref([])
const search = ref('')
const typeFilter = ref('')
const showForm = ref(false)
const editingCustomer = ref(null)
const form = reactive({ name: '', phone: '', email: '', date_of_birth: '', notes: '' })

function typeClass(type) {
    const map = { new: 'bg-gray-100 text-gray-700', regular: 'bg-blue-100 text-blue-700', vip: 'bg-purple-100 text-purple-700', blacklisted: 'bg-red-100 text-red-700' }
    return map[type] ?? 'bg-gray-100 text-gray-600'
}

function formatDate(dt) {
    return new Date(dt).toLocaleDateString('en-IN')
}

async function loadCustomers() {
    const params = {}
    if (search.value) params.search = search.value
    if (typeFilter.value) params.type = typeFilter.value
    const { data } = await api.get('/customers', { params })
    customers.value = data.customers.data
}

function openEdit(c) {
    editingCustomer.value = c
    Object.assign(form, { name: c.name, phone: c.phone, email: c.email ?? '', date_of_birth: c.date_of_birth ?? '', notes: c.notes ?? '' })
    showForm.value = true
}

async function saveCustomer() {
    if (editingCustomer.value) {
        await api.put(`/customers/${editingCustomer.value.id}`, form)
        toast.success('Customer updated')
    } else {
        await api.post('/customers', form)
        toast.success('Customer created')
    }
    showForm.value = false
    await loadCustomers()
}

let timer = null
watch([search, typeFilter], () => { clearTimeout(timer); timer = setTimeout(loadCustomers, 400) })
onMounted(loadCustomers)
</script>
