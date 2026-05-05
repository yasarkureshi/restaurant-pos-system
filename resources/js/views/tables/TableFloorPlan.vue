<template>
    <div class="flex flex-col h-full overflow-hidden bg-gray-50">

        <!-- Top Bar -->
        <div class="bg-white border-b px-4 py-2.5 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2">
                <h1 class="text-sm font-bold text-gray-800">Table View</h1>
                <button @click="loadFloor" class="text-gray-400 hover:text-gray-700 p-1 rounded">
                    <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': floorLoading }" />
                </button>
            </div>
            <div class="flex items-center gap-1.5">
                <button @click="router.push({ name: 'new-order', query: { type: 'delivery' } })"
                    class="px-3 py-1.5 bg-red-600 text-white rounded text-xs font-semibold flex items-center gap-1.5 hover:bg-red-700">
                    <Truck class="w-3 h-3" /> Delivery
                </button>
                <button @click="router.push({ name: 'new-order', query: { type: 'takeaway' } })"
                    class="px-3 py-1.5 bg-red-600 text-white rounded text-xs font-semibold flex items-center gap-1.5 hover:bg-red-700">
                    <ShoppingBag class="w-3 h-3" /> Take Away
                </button>
            </div>
        </div>

        <!-- Sub Bar -->
        <div class="bg-white border-b px-4 py-2 flex items-center gap-2 shrink-0 flex-wrap">
            <button @click="showReservation = true"
                class="px-3 py-1.5 border border-red-500 text-red-600 rounded text-xs font-medium flex items-center gap-1 hover:bg-red-50">
                <CalendarDays class="w-3 h-3" /> Table Reservation
            </button>
            <button @click="showContactless = true"
                class="px-3 py-1.5 border border-red-500 text-red-600 rounded text-xs font-medium flex items-center gap-1 hover:bg-red-50">
                <QrCode class="w-3 h-3" /> Contactless
            </button>
            <div class="flex-1" />
            <div class="hidden sm:flex items-center gap-2.5 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="w-3.5 h-3.5 rounded border-2 border-dashed border-gray-400 bg-white inline-block"></span> Blank</span>
                <span class="flex items-center gap-1"><span class="w-3.5 h-3.5 rounded bg-blue-200 border border-blue-400 inline-block"></span> Running</span>
                <span class="flex items-center gap-1"><span class="w-3.5 h-3.5 rounded bg-yellow-200 border border-yellow-400 inline-block"></span> Cleaning</span>
                <span class="flex items-center gap-1"><span class="w-3.5 h-3.5 rounded bg-indigo-200 border border-indigo-400 inline-block"></span> Reserved</span>
            </div>
        </div>

        <!-- Section Tabs -->
        <div class="bg-white border-b px-4 flex items-center justify-between shrink-0">
            <div class="flex overflow-x-auto">
                <button v-for="sec in [{ id: null, name: 'All' }, ...sections]" :key="sec.id ?? 'all'"
                    @click="activeSection = sec.id"
                    :class="['px-4 py-2.5 text-xs font-medium border-b-2 whitespace-nowrap transition-colors',
                        activeSection === sec.id ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                    {{ sec.name }}
                </button>
            </div>
            <div class="hidden md:flex items-center gap-2 text-xs text-gray-500 shrink-0 pl-3">
                <span>Floor Plan</span>
                <select class="border border-gray-200 rounded px-2 py-1 text-xs text-gray-700 bg-white">
                    <option>Default Layout</option>
                </select>
            </div>
        </div>

        <!-- Tables Grid -->
        <div class="flex-1 overflow-y-auto p-3">
            <div v-if="floorLoading && !tables.length" class="flex items-center justify-center h-40">
                <div class="animate-spin w-7 h-7 border-4 border-red-500 border-t-transparent rounded-full"></div>
            </div>

            <template v-else v-for="section in visibleSections" :key="section.id">
                <div class="mb-4">
                    <p class="text-xs font-bold text-gray-700 mb-2 flex items-center gap-2">
                        {{ section.name }}
                        <span class="text-xs font-normal text-gray-400">({{ section.tables.length }} tables)</span>
                    </p>
                    <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 xl:grid-cols-12 gap-2">
                        <button v-for="table in section.tables" :key="table.id"
                            @click="goToTable(table)"
                            :class="['relative rounded-lg text-center border-2 transition-all focus:outline-none overflow-hidden',
                                table.status !== 'blocked' ? 'hover:shadow-md hover:scale-[1.03]' : '',
                                tableCardClass(table)]"
                            style="min-height: 68px; padding: 7px 5px 5px">

                            <p class="font-semibold text-xs leading-tight">{{ table.name }}</p>
                            <p class="text-gray-500 mt-0.5" style="font-size:10px">{{ table.capacity }} pax</p>

                            <template v-if="table.status === 'occupied'">
                                <p class="font-bold mt-1 text-blue-900" style="font-size:10px">
                                    ₹{{ table.current_order?.grand_total ?? '–' }}
                                </p>
                                <p class="opacity-60" style="font-size:9px">{{ elapsed(table.current_order?.created_at) }}</p>
                            </template>
                            <template v-else-if="table.status === 'reserved'">
                                <p class="opacity-70 mt-1" style="font-size:9px">Reserved</p>
                            </template>
                        </button>
                    </div>
                </div>
            </template>

            <div v-if="!floorLoading && !tables.length" class="text-center text-gray-400 py-16 text-xs">
                No tables found.
                <router-link to="/table-setup" class="text-red-600 hover:underline">Go to Table Setup</router-link>
                to add tables.
            </div>
        </div>

        <!-- Stats Footer -->
        <div class="bg-white border-t px-4 py-2 flex gap-4 text-xs text-gray-500 shrink-0 flex-wrap">
            <span>Total <b class="text-gray-700 ml-1">{{ tables.length }}</b></span>
            <span>Blank <b class="text-gray-700 ml-1">{{ stats.available }}</b></span>
            <span>Running <b class="text-blue-600 ml-1">{{ stats.occupied }}</b></span>
            <span>Reserved <b class="text-indigo-600 ml-1">{{ stats.reserved }}</b></span>
            <span>Cleaning <b class="text-yellow-600 ml-1">{{ stats.cleaning }}</b></span>
        </div>
    </div>

    <!-- Reservation Modal -->
    <Teleport to="body">
        <div v-if="showReservation" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl p-5 max-w-sm w-full shadow-2xl">
                <h3 class="font-bold text-sm text-gray-800 mb-4 flex items-center gap-2">
                    <CalendarDays class="w-4 h-4 text-red-500" /> New Reservation
                </h3>
                <div class="space-y-2.5">
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">Guest Name *</label>
                        <input v-model="reservationForm.customer_name"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400"
                            placeholder="Customer name" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">Phone</label>
                        <input v-model="reservationForm.customer_phone" type="tel"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400"
                            placeholder="Mobile number" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-medium text-gray-700 mb-1 block">Date *</label>
                            <input v-model="reservationForm.reservation_date" type="date"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-700 mb-1 block">Time *</label>
                            <input v-model="reservationForm.reservation_time" type="time"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-medium text-gray-700 mb-1 block">Guests</label>
                            <input v-model="reservationForm.number_of_guests" type="number" min="1"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-700 mb-1 block">Table</label>
                            <select v-model="reservationForm.table_id"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400">
                                <option :value="null">Auto Assign</option>
                                <option v-for="t in availableTables" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">Occasion</label>
                        <select v-model="reservationForm.occasion"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400">
                            <option value="">None</option>
                            <option value="birthday">Birthday</option>
                            <option value="anniversary">Anniversary</option>
                            <option value="business">Business Lunch</option>
                            <option value="date">Date Night</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">Special Requests</label>
                        <textarea v-model="reservationForm.special_requests" rows="2"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400"
                            placeholder="Any special requests..."></textarea>
                    </div>
                </div>
                <div class="flex gap-2 mt-4">
                    <button @click="showReservation = false"
                        class="flex-1 py-2 border rounded text-xs text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button @click="saveReservation" :disabled="savingReservation"
                        class="flex-1 py-2 bg-red-600 text-white rounded text-xs font-bold hover:bg-red-700 disabled:opacity-40">
                        {{ savingReservation ? 'Saving...' : 'Reserve' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Contactless QR Modal -->
    <Teleport to="body">
        <div v-if="showContactless" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl p-5 max-w-xs w-full shadow-2xl text-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <QrCode class="w-6 h-6 text-red-600" />
                </div>
                <h3 class="font-bold text-sm text-gray-800 mb-1">Contactless Ordering</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Select a table to get a link for contactless digital menu ordering.
                </p>
                <div class="mb-4 text-left">
                    <label class="text-xs font-medium text-gray-700 mb-1 block">Select Table</label>
                    <select v-model="contactlessTable"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option :value="null">Choose table...</option>
                        <option v-for="t in tables" :key="t.id" :value="t">{{ t.name }} ({{ t.capacity }} pax)</option>
                    </select>
                </div>
                <div v-if="contactlessTable" class="bg-gray-100 rounded-lg p-3 mb-4">
                    <p class="text-xs font-mono text-gray-600 break-all">{{ contactlessUrl }}</p>
                    <button @click="copyContactlessUrl"
                        class="mt-2 text-xs text-red-600 hover:text-red-700 font-medium flex items-center gap-1 mx-auto">
                        <Copy class="w-3 h-3" /> Copy Link
                    </button>
                </div>
                <button @click="showContactless = false"
                    class="w-full py-2 border rounded text-xs text-gray-600 hover:bg-gray-50">
                    Close
                </button>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { RefreshCw, CalendarDays, QrCode, Truck, ShoppingBag, Copy } from 'lucide-vue-next'
import { toast } from 'vue3-toastify'
import api from '@/api/index.js'

const router = useRouter()

// ── State ─────────────────────────────────────────────────────────
const tables        = ref([])
const sections      = ref([])
const floorLoading  = ref(false)
const activeSection = ref(null)

const showReservation   = ref(false)
const savingReservation = ref(false)
const reservationForm   = reactive({
    customer_name: '',
    customer_phone: '',
    reservation_date: new Date().toISOString().split('T')[0],
    reservation_time: '19:00',
    number_of_guests: 2,
    table_id: null,
    occasion: '',
    special_requests: '',
})

const showContactless  = ref(false)
const contactlessTable = ref(null)
const contactlessUrl   = computed(() =>
    contactlessTable.value
        ? `${window.location.origin}/menu?table=${contactlessTable.value.id}`
        : ''
)

// ── Computed ──────────────────────────────────────────────────────
const availableTables = computed(() => tables.value.filter(t => t.status === 'available'))

const stats = computed(() => ({
    available: tables.value.filter(t => t.status === 'available').length,
    occupied:  tables.value.filter(t => t.status === 'occupied').length,
    reserved:  tables.value.filter(t => t.status === 'reserved').length,
    cleaning:  tables.value.filter(t => t.status === 'cleaning').length,
}))

const visibleSections = computed(() =>
    sections.value.map(s => ({
        ...s,
        tables: tables.value.filter(t =>
            t.section_id === s.id &&
            (!activeSection.value || t.section_id === activeSection.value)
        ),
    })).filter(s => s.tables.length > 0)
)

// ── Load Floor ────────────────────────────────────────────────────
async function loadFloor() {
    if (floorLoading.value) return
    floorLoading.value = true
    try {
        const { data } = await api.get('/tables')
        tables.value   = data.tables ?? []
        sections.value = data.sections ?? []
    } catch {
        toast.error('Failed to load tables')
    } finally {
        floorLoading.value = false
    }
}

// ── Navigation ────────────────────────────────────────────────────
function goToTable(table) {
    if (table.status === 'blocked') return
    router.push({ name: 'table-order', params: { tableId: table.id } })
}

// ── Reservation ───────────────────────────────────────────────────
async function saveReservation() {
    if (!reservationForm.customer_name.trim()) return toast.warning('Guest name is required')
    if (!reservationForm.reservation_date)     return toast.warning('Date is required')
    if (!reservationForm.reservation_time)     return toast.warning('Time is required')

    savingReservation.value = true
    try {
        await api.post('/reservations', { ...reservationForm })
        toast.success('Reservation confirmed!')
        showReservation.value = false
        Object.assign(reservationForm, {
            customer_name: '', customer_phone: '',
            reservation_date: new Date().toISOString().split('T')[0],
            reservation_time: '19:00', number_of_guests: 2,
            table_id: null, occasion: '', special_requests: '',
        })
        await loadFloor()
    } catch (err) {
        toast.error(err?.response?.data?.message ?? 'Failed to save reservation')
    } finally {
        savingReservation.value = false
    }
}

// ── Contactless ───────────────────────────────────────────────────
function copyContactlessUrl() {
    navigator.clipboard.writeText(contactlessUrl.value)
        .then(() => toast.success('Link copied!'))
        .catch(() => toast.error('Could not copy link'))
}

// ── Helpers ───────────────────────────────────────────────────────
function tableCardClass(table) {
    if (table.status === 'occupied')  return 'bg-blue-100 border-blue-400 text-blue-900'
    if (table.status === 'reserved')  return 'bg-indigo-100 border-indigo-400 text-indigo-900'
    if (table.status === 'cleaning')  return 'bg-yellow-100 border-yellow-400 text-yellow-900'
    if (table.status === 'blocked')   return 'bg-gray-100 border-gray-300 text-gray-400 cursor-not-allowed'
    return 'bg-white border-dashed border-gray-400 text-gray-700 hover:border-gray-500'
}

function elapsed(dateStr) {
    if (!dateStr) return ''
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 60000)
    if (diff < 1)  return 'now'
    if (diff < 60) return `${diff}m`
    return `${Math.floor(diff / 60)}h ${diff % 60}m`
}

// ── Lifecycle ─────────────────────────────────────────────────────
let timer
onMounted(() => {
    loadFloor()
    timer = setInterval(loadFloor, 30000)
})
onUnmounted(() => clearInterval(timer))
</script>
