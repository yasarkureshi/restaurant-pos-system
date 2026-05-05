<template>
    <div class="overflow-y-auto h-full">
        <div class="p-4 max-w-3xl mx-auto">
            <h1 class="text-sm font-bold text-gray-800 mb-4">Settings</h1>

            <!-- Tabs -->
            <div class="flex gap-1.5 mb-4 flex-wrap">
                <button v-for="t in tabs" :key="t.key" @click="tab = t.key"
                    :class="['px-3 py-1.5 rounded text-xs font-medium transition-colors',
                        tab === t.key ? 'bg-red-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200']">
                    {{ t.label }}
                </button>
            </div>

            <!-- Restaurant Settings -->
            <div v-if="tab === 'restaurant'" class="bg-white rounded-lg shadow-sm p-4">
                <h2 class="font-bold text-sm text-gray-800 mb-3">Restaurant Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">Restaurant Name</label>
                        <input v-model="restaurantForm.name" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-1 focus:ring-red-500 outline-none" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">Email</label>
                        <input v-model="restaurantForm.email" type="email" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-1 focus:ring-red-500 outline-none" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">Phone</label>
                        <input v-model="restaurantForm.phone" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-1 focus:ring-red-500 outline-none" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">GST Number</label>
                        <input v-model="restaurantForm.gst_number" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-1 focus:ring-red-500 outline-none" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">FSSAI License</label>
                        <input v-model="restaurantForm.fssai_license" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-1 focus:ring-red-500 outline-none" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">Currency Symbol</label>
                        <input v-model="restaurantForm.currency_symbol" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-1 focus:ring-red-500 outline-none" placeholder="₹" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">Timezone</label>
                        <select v-model="restaurantForm.timezone" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                            <option value="Asia/Kolkata">Asia/Kolkata (IST)</option>
                            <option value="UTC">UTC</option>
                            <option value="Asia/Dubai">Asia/Dubai</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">Open Time</label>
                        <input v-model="restaurantForm.operation_start_time" type="time" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">Close Time</label>
                        <input v-model="restaurantForm.operation_end_time" type="time" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-xs font-medium text-gray-700 mb-1 block">Address</label>
                        <textarea v-model="restaurantForm.address" rows="2" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-1 focus:ring-red-500 outline-none resize-none"></textarea>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="restaurantForm.is_24x7" type="checkbox" id="is24x7" class="w-3.5 h-3.5 accent-red-600" />
                        <label for="is24x7" class="text-xs font-medium text-gray-700">Open 24/7</label>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button @click="saveRestaurant" class="bg-red-600 text-white px-4 py-2 rounded text-xs font-semibold hover:bg-red-700">
                        Save Changes
                    </button>
                </div>
            </div>

            <!-- Branding -->
            <div v-if="tab === 'branding'" class="bg-white rounded-lg shadow-sm p-4">
                <h2 class="font-bold text-sm text-gray-800 mb-3">Branding Settings</h2>

                <!-- Logo -->
                <div class="mb-4">
                    <label class="text-xs font-medium text-gray-700 mb-2 block">Restaurant Logo</label>
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-lg border-2 flex items-center justify-center overflow-hidden shrink-0"
                            :style="brandingForm.logo_url ? '' : { borderColor: brandingForm.primary_color, background: brandingForm.primary_color }">
                            <img v-if="brandingForm.logo_url" :src="brandingForm.logo_url" class="w-full h-full object-cover" alt="logo" />
                            <span v-else class="font-bold text-white text-lg">{{ (restaurantForm.name || 'P').charAt(0) }}</span>
                        </div>
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <input type="file" id="logoUpload" accept="image/*" @change="handleLogoUpload" class="hidden" />
                                <label for="logoUpload" class="cursor-pointer bg-gray-100 text-gray-700 px-3 py-1.5 rounded text-xs hover:bg-gray-200 border border-gray-300">
                                    Upload Image
                                </label>
                                <button v-if="brandingForm.logo_url" @click="brandingForm.logo_url = ''"
                                    class="text-xs text-red-500 hover:text-red-700">Remove</button>
                            </div>
                            <p class="text-xs text-gray-400">PNG, JPG up to 2MB. Will be shown in sidebar.</p>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-600">Or enter URL:</span>
                                <input v-model="brandingForm.logo_url" placeholder="https://..."
                                    class="flex-1 border border-gray-300 rounded px-2 py-1 text-xs outline-none focus:border-red-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Primary Color -->
                <div class="mb-4">
                    <label class="text-xs font-medium text-gray-700 mb-2 block">Primary Color</label>
                    <div class="flex items-center gap-3 flex-wrap">
                        <input type="color" v-model="brandingForm.primary_color"
                            class="w-10 h-10 rounded cursor-pointer border border-gray-300 p-0.5" />
                        <input v-model="brandingForm.primary_color"
                            class="border border-gray-300 rounded px-3 py-2 text-sm font-mono w-28 outline-none focus:border-red-400"
                            placeholder="#ef4444" />
                        <div class="flex gap-1.5">
                            <button v-for="color in presetColors" :key="color"
                                @click="brandingForm.primary_color = color"
                                :style="{ background: color }"
                                :class="['w-6 h-6 rounded-full border-2 cursor-pointer hover:scale-110 transition-transform',
                                    brandingForm.primary_color === color ? 'border-gray-600 ring-2 ring-offset-1 ring-gray-400' : 'border-white shadow-sm']" />
                        </div>
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <div class="w-8 h-8 rounded" :style="{ background: brandingForm.primary_color }"></div>
                        <span class="text-xs text-gray-500">Preview</span>
                        <button class="px-3 py-1 rounded text-white text-xs font-bold" :style="{ background: brandingForm.primary_color }">
                            Button Preview
                        </button>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button @click="saveBranding" class="bg-red-600 text-white px-4 py-2 rounded text-xs font-semibold hover:bg-red-700">
                        Save Branding
                    </button>
                </div>
            </div>

            <!-- Tax Categories -->
            <div v-if="tab === 'taxes'" class="bg-white rounded-lg shadow-sm p-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-bold text-sm text-gray-800">Tax Categories</h2>
                    <button @click="showTaxForm = true"
                        class="bg-red-600 text-white px-3 py-1.5 rounded text-xs font-medium hover:bg-red-700 flex items-center gap-1.5">
                        <Plus class="w-3 h-3" /> Add Tax
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="border-b">
                            <tr>
                                <th class="text-left py-2 font-semibold text-gray-500">Name</th>
                                <th class="text-right py-2 font-semibold text-gray-500">Total %</th>
                                <th class="text-right py-2 font-semibold text-gray-500">CGST %</th>
                                <th class="text-right py-2 font-semibold text-gray-500">SGST %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="tax in taxCategories" :key="tax.id" class="hover:bg-gray-50">
                                <td class="py-2 font-medium text-gray-900">{{ tax.name }}</td>
                                <td class="py-2 text-right text-gray-700">{{ tax.tax_percentage }}%</td>
                                <td class="py-2 text-right text-gray-500">{{ tax.cgst_percentage ?? '-' }}%</td>
                                <td class="py-2 text-right text-gray-500">{{ tax.sgst_percentage ?? '-' }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tax Form Modal -->
    <Teleport to="body">
        <div v-if="showTaxForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl w-full max-w-sm p-5">
                <h3 class="font-bold text-sm mb-3">Add Tax Category</h3>
                <div class="space-y-2.5">
                    <div>
                        <label class="text-xs font-medium mb-1 block">Name *</label>
                        <input v-model="taxForm.name" placeholder="e.g. GST 5%"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-1 focus:ring-red-500 outline-none" />
                    </div>
                    <div>
                        <label class="text-xs font-medium mb-1 block">Total Tax % *</label>
                        <input v-model="taxForm.tax_percentage" type="number" step="0.01"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-1 focus:ring-red-500 outline-none" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-medium mb-1 block">CGST %</label>
                            <input v-model="taxForm.cgst_percentage" type="number" step="0.01"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400" />
                        </div>
                        <div>
                            <label class="text-xs font-medium mb-1 block">SGST %</label>
                            <input v-model="taxForm.sgst_percentage" type="number" step="0.01"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400" />
                        </div>
                    </div>
                </div>
                <div class="flex gap-2 mt-4">
                    <button @click="showTaxForm = false" class="flex-1 py-2 border rounded text-xs text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button @click="saveTax" class="flex-1 py-2 bg-red-600 text-white rounded text-xs font-semibold">Save</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import { Plus } from 'lucide-vue-next'
import api from '@/api/index.js'

const tab = ref('restaurant')
const tabs = [
    { key: 'restaurant', label: 'Restaurant' },
    { key: 'branding',   label: 'Branding' },
    { key: 'taxes',      label: 'Tax Categories' },
]

const restaurantForm = reactive({
    name: '', email: '', phone: '', gst_number: '', fssai_license: '',
    currency_symbol: '₹', timezone: 'Asia/Kolkata',
    operation_start_time: '09:00', operation_end_time: '23:00',
    address: '', is_24x7: false,
})

const brandingForm = reactive({
    primary_color: '#ef4444',
    logo_url: '',
})

const presetColors = [
    '#ef4444', // red
    '#f97316', // orange
    '#eab308', // yellow
    '#22c55e', // green
    '#3b82f6', // blue
    '#8b5cf6', // violet
    '#ec4899', // pink
    '#14b8a6', // teal
    '#1f2937', // dark
]

const taxCategories = ref([])
const showTaxForm   = ref(false)
const taxForm       = reactive({ name: '', tax_percentage: '', cgst_percentage: '', sgst_percentage: '' })

async function loadSettings() {
    try {
        const [restRes, taxRes] = await Promise.all([
            api.get('/settings/restaurant'),
            api.get('/settings/tax-categories'),
        ])
        const restaurant = restRes.data.restaurant ?? {}
        Object.assign(restaurantForm, {
            name: restaurant.name ?? '',
            email: restaurant.email ?? '',
            phone: restaurant.phone ?? '',
            gst_number: restaurant.gst_number ?? '',
            fssai_license: restaurant.fssai_license ?? '',
            currency_symbol: restaurant.currency_symbol ?? '₹',
            timezone: restaurant.timezone ?? 'Asia/Kolkata',
            operation_start_time: restaurant.operation_start_time ?? '09:00',
            operation_end_time: restaurant.operation_end_time ?? '23:00',
            address: restaurant.address ?? '',
            is_24x7: restaurant.is_24x7 ?? false,
        })
        // Load branding from settings JSON
        const settings = restaurant.settings ?? {}
        brandingForm.primary_color = settings.primary_color ?? '#ef4444'
        brandingForm.logo_url      = settings.logo_url ?? ''

        taxCategories.value = taxRes.data.tax_categories
    } catch (err) {
        toast.error('Failed to load settings')
    }
}

async function saveRestaurant() {
    try {
        await api.put('/settings/restaurant', {
            ...restaurantForm,
        })
        toast.success('Restaurant settings saved')
    } catch {
        toast.error('Failed to save settings')
    }
}

async function saveBranding() {
    try {
        // Store branding in the restaurant settings JSON field
        const { data } = await api.get('/settings/restaurant')
        const existingSettings = data.restaurant?.settings ?? {}
        await api.put('/settings/restaurant', {
            settings: {
                ...existingSettings,
                primary_color: brandingForm.primary_color,
                logo_url: brandingForm.logo_url,
            },
        })
        toast.success('Branding saved! Refresh to see changes in sidebar.')
    } catch {
        toast.error('Failed to save branding')
    }
}

function handleLogoUpload(event) {
    const file = event.target.files?.[0]
    if (!file) return
    if (file.size > 2 * 1024 * 1024) {
        toast.warning('Image must be under 2MB')
        return
    }
    const reader = new FileReader()
    reader.onload = (e) => {
        brandingForm.logo_url = e.target.result
    }
    reader.readAsDataURL(file)
}

async function saveTax() {
    try {
        await api.post('/settings/tax-categories', taxForm)
        toast.success('Tax category created')
        showTaxForm.value = false
        Object.assign(taxForm, { name: '', tax_percentage: '', cgst_percentage: '', sgst_percentage: '' })
        const { data } = await api.get('/settings/tax-categories')
        taxCategories.value = data.tax_categories
    } catch {
        toast.error('Failed to save tax category')
    }
}

onMounted(loadSettings)
</script>
