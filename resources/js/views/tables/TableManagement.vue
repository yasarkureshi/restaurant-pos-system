<template>
    <div class="p-4">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-sm font-bold text-gray-800">Table Management</h1>
            <div class="flex gap-1.5">
                <button @click="openSectionForm()" class="bg-white border border-gray-300 text-gray-700 px-3 py-1.5 rounded text-xs font-medium hover:bg-gray-50 flex items-center gap-1.5">
                    <Plus class="w-3.5 h-3.5" /> Add Section
                </button>
                <button @click="openTableForm()" class="bg-red-600 text-white px-3 py-1.5 rounded text-xs font-medium hover:bg-red-700 flex items-center gap-1.5">
                    <Plus class="w-3.5 h-3.5" /> Add Table
                </button>
            </div>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-4 gap-2 mb-4">
            <div v-for="s in tableSummary" :key="s.status" class="bg-white rounded-lg p-3 shadow-sm text-center">
                <p class="text-xl font-bold" :class="s.color">{{ s.count }}</p>
                <p class="text-xs text-gray-500 capitalize">{{ s.status }}</p>
            </div>
        </div>

        <!-- Sections & Tables -->
        <div v-for="section in sectionsWithTables" :key="section.id" class="mb-4">
            <h2 class="text-xs font-bold text-gray-700 mb-2 flex items-center gap-2">
                <Layers class="w-3.5 h-3.5 text-red-500" />
                {{ section.name }}
                <span class="text-xs font-normal text-gray-400">({{ section.tables.length }} tables)</span>
            </h2>
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2">
                <div v-for="table in section.tables" :key="table.id"
                    :class="['bg-white rounded-lg p-3 border-2 cursor-pointer transition-all hover:shadow-md relative', statusBorder(table.status)]"
                    @click="openTableActions(table)">
                    <div class="text-center">
                        <p class="font-bold text-sm text-gray-900">{{ table.name }}</p>
                        <p class="text-xs text-gray-500">{{ table.capacity }} seats</p>
                        <StatusBadge :status="table.status" class="mt-1.5" />
                    </div>
                    <button @click.stop="openTableForm(table)"
                        class="absolute top-1 right-1 w-5 h-5 rounded-full bg-gray-100 hover:bg-red-100 flex items-center justify-center">
                        <Pencil class="w-2.5 h-2.5 text-gray-500" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Uncategorized tables -->
        <div v-if="uncategorizedTables.length" class="mb-4">
            <h2 class="text-xs font-bold text-gray-700 mb-2 flex items-center gap-2">
                <Layers class="w-3.5 h-3.5 text-gray-400" /> General Area
            </h2>
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2">
                <div v-for="table in uncategorizedTables" :key="table.id"
                    :class="['bg-white rounded-lg p-3 border-2 cursor-pointer transition-all hover:shadow-md relative', statusBorder(table.status)]"
                    @click="openTableActions(table)">
                    <div class="text-center">
                        <p class="font-bold text-sm text-gray-900">{{ table.name }}</p>
                        <p class="text-xs text-gray-500">{{ table.capacity }} seats</p>
                        <StatusBadge :status="table.status" class="mt-1.5" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Form Modal -->
    <Teleport to="body">
        <div v-if="showTableModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl w-full max-w-sm p-5">
                <h3 class="font-bold text-sm mb-3">{{ editingTable?.id ? 'Edit Table' : 'Add Table' }}</h3>
                <div class="space-y-2.5">
                    <div><label class="text-xs font-medium mb-1 block">Table Name *</label>
                        <input v-model="tableForm.name" class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400" /></div>
                    <div><label class="text-xs font-medium mb-1 block">Table Number</label>
                        <input v-model="tableForm.table_number" class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400" /></div>
                    <div><label class="text-xs font-medium mb-1 block">Section</label>
                        <select v-model="tableForm.section_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                            <option :value="null">No Section</option>
                            <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select></div>
                    <div><label class="text-xs font-medium mb-1 block">Capacity *</label>
                        <input v-model="tableForm.capacity" type="number" min="1" class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400" /></div>
                    <div><label class="text-xs font-medium mb-1 block">Type</label>
                        <select v-model="tableForm.table_type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                            <option v-for="t in tableTypes" :key="t" :value="t" class="capitalize">{{ t }}</option>
                        </select></div>
                </div>
                <div class="flex gap-2 mt-4">
                    <button @click="showTableModal = false" class="flex-1 py-2 border rounded text-xs text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button @click="saveTable" class="flex-1 py-2 bg-red-600 text-white rounded text-xs font-semibold">Save</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import { Plus, Layers, Pencil } from 'lucide-vue-next'
import api from '@/api/index.js'
import StatusBadge from '@/components/StatusBadge.vue'

const tables = ref([])
const sections = ref([])
const showTableModal = ref(false)
const editingTable = ref(null)

const tableTypes = ['regular', 'couple', 'family', 'party', 'private', 'bar', 'outdoor', 'rooftop']
const tableForm = reactive({ name: '', table_number: '', section_id: null, capacity: 4, table_type: 'regular' })

const sectionsWithTables = computed(() =>
    sections.value.map(s => ({ ...s, tables: tables.value.filter(t => t.section_id === s.id) }))
        .filter(s => s.tables.length > 0)
)

const uncategorizedTables = computed(() => tables.value.filter(t => !t.section_id))

const statusCounts = computed(() => {
    const counts = {}
    tables.value.forEach(t => { counts[t.status] = (counts[t.status] || 0) + 1 })
    return counts
})

const tableSummary = computed(() => [
    { status: 'available', count: statusCounts.value.available ?? 0, color: 'text-green-600' },
    { status: 'occupied', count: statusCounts.value.occupied ?? 0, color: 'text-red-600' },
    { status: 'reserved', count: statusCounts.value.reserved ?? 0, color: 'text-blue-600' },
    { status: 'cleaning', count: statusCounts.value.cleaning ?? 0, color: 'text-yellow-600' },
])

function statusBorder(status) {
    const map = { available: 'border-green-300', occupied: 'border-red-400', reserved: 'border-blue-400', cleaning: 'border-yellow-400', blocked: 'border-gray-400' }
    return map[status] ?? 'border-gray-200'
}

async function loadData() {
    const { data } = await api.get('/tables')
    tables.value = data.tables
    sections.value = data.sections
}

function openTableForm(table = null) {
    editingTable.value = table
    if (table) Object.assign(tableForm, { name: table.name, table_number: table.table_number, section_id: table.section_id, capacity: table.capacity, table_type: table.table_type })
    else Object.assign(tableForm, { name: '', table_number: '', section_id: null, capacity: 4, table_type: 'regular' })
    showTableModal.value = true
}

async function saveTable() {
    if (editingTable.value?.id) {
        await api.put(`/tables/${editingTable.value.id}`, tableForm)
        toast.success('Table updated')
    } else {
        await api.post('/tables', { ...tableForm, table_number: tableForm.table_number || tableForm.name })
        toast.success('Table created')
    }
    showTableModal.value = false
    await loadData()
}

function openSectionForm() {
    const name = prompt('Section name:')
    if (!name) return
    api.post('/table-sections', { name }).then(() => { toast.success('Section created'); loadData() })
}

function openTableActions(table) {
    if (table.status === 'available') {
        const action = confirm(`Mark "${table.name}" as occupied?`)
        if (action) api.patch(`/tables/${table.id}/status`, { status: 'occupied' }).then(() => loadData())
    } else if (table.status === 'occupied') {
        const action = confirm(`Mark "${table.name}" as available?`)
        if (action) api.patch(`/tables/${table.id}/status`, { status: 'available' }).then(() => loadData())
    }
}

onMounted(loadData)
</script>
