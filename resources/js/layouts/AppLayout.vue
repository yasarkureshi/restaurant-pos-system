<template>
    <div class="flex h-screen bg-gray-100 overflow-hidden">
        <!-- Sidebar -->
        <aside :class="['flex flex-col bg-gray-900 text-white transition-all duration-300 z-30 shrink-0', sidebarOpen ? 'w-52' : 'w-12']">
            <!-- Logo -->
            <div class="flex items-center gap-2 p-2.5 border-b border-gray-700 min-h-12">
                <div class="w-6 h-6 rounded flex items-center justify-center flex-shrink-0 overflow-hidden"
                    :style="{ background: primaryColor }">
                    <span v-if="!restaurantLogo" class="font-bold text-xs text-white">PP</span>
                    <img v-else :src="restaurantLogo" class="w-full h-full object-cover" alt="logo" />
                </div>
                <span v-if="sidebarOpen" class="font-bold text-xs truncate">{{ auth.restaurant?.name ?? 'PetPooja POS' }}</span>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto py-1">
                <template v-for="item in navItems" :key="item.name">
                    <router-link
                        :to="item.path"
                        v-if="!item.permission || auth.hasPermission(item.permission)"
                        class="flex items-center gap-2.5 px-2.5 py-2 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors"
                        active-class="bg-red-600 text-white"
                        :title="!sidebarOpen ? item.label : undefined"
                    >
                        <component :is="item.icon" class="w-4 h-4 flex-shrink-0" />
                        <span v-if="sidebarOpen" class="text-xs font-medium truncate">{{ item.label }}</span>
                    </router-link>
                </template>
            </nav>

            <!-- User info -->
            <div class="border-t border-gray-700 p-2.5">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold"
                        :style="{ background: primaryColor }">
                        {{ userInitial }}
                    </div>
                    <div v-if="sidebarOpen" class="overflow-hidden flex-1">
                        <p class="text-xs font-medium truncate">{{ auth.user?.name }}</p>
                        <p class="text-gray-400 truncate" style="font-size:10px">{{ auth.user?.role?.name }}</p>
                    </div>
                </div>
                <button v-if="sidebarOpen" @click="handleLogout"
                    class="mt-1.5 w-full text-left text-xs text-gray-400 hover:text-white flex items-center gap-1.5">
                    <LogOut class="w-3 h-3" /> Logout
                </button>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col overflow-hidden min-w-0">
            <!-- Top bar -->
            <header class="bg-white shadow-sm h-11 flex items-center px-3 gap-2.5 z-20 shrink-0">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700 p-1 shrink-0">
                    <Menu class="w-4 h-4" />
                </button>
                <div class="flex-1 min-w-0">
                    <h1 class="text-sm font-semibold text-gray-800 truncate">{{ pageTitle }}</h1>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs text-gray-500 hidden sm:block truncate max-w-32">{{ auth.restaurant?.name }}</span>
                    <div class="w-6 h-6 rounded-full flex items-center justify-center"
                        :style="{ background: primaryColor }">
                        <span class="text-white font-bold text-xs">{{ userInitial }}</span>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto">
                <router-view class="h-full" />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import {
    LayoutDashboard, ClipboardList, UtensilsCrossed,
    Table2, LayoutGrid, Users, Package, BarChart3, Settings, ChefHat, Menu, LogOut
} from 'lucide-vue-next'
import api from '@/api/index.js'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const sidebarOpen = ref(true)

const restaurantSettings = ref({})
const primaryColor    = computed(() => restaurantSettings.value?.primary_color ?? '#ef4444')
const restaurantLogo  = computed(() => restaurantSettings.value?.logo_url ?? null)

const userInitial = computed(() => auth.user?.name?.charAt(0)?.toUpperCase() ?? 'U')

const navItems = [
    { name: 'dashboard',    label: 'Dashboard',    path: '/',            icon: LayoutDashboard },
    { name: 'tables',       label: 'Tables / POS',  path: '/tables',      icon: Table2,          permission: 'tables.view' },
    { name: 'table-setup',  label: 'Table Setup',   path: '/table-setup', icon: LayoutGrid,      permission: 'tables.manage' },
    { name: 'orders',       label: 'Orders',         path: '/orders',      icon: ClipboardList,   permission: 'orders.view' },
    { name: 'menu',         label: 'Menu',           path: '/menu',        icon: UtensilsCrossed, permission: 'menu.view' },
    { name: 'customers',    label: 'Customers',      path: '/customers',   icon: Users,           permission: 'customers.view' },
    { name: 'inventory',    label: 'Inventory',      path: '/inventory',   icon: Package,         permission: 'inventory.view' },
    { name: 'kds',          label: 'Kitchen',        path: '/kds',         icon: ChefHat,         permission: 'kitchen.view' },
    { name: 'reports',      label: 'Reports',        path: '/reports',     icon: BarChart3,       permission: 'reports.view' },
    { name: 'settings',     label: 'Settings',       path: '/settings',    icon: Settings,        permission: 'settings.view' },
]

const pageTitles = {
    dashboard:    'Dashboard',
    orders:       'Orders',
    'order-detail': 'Order Detail',
    menu:         'Menu Management',
    tables:       'Table View',
    'table-setup': 'Table Management',
    customers:    'Customers',
    inventory:    'Inventory',
    kds:          'Kitchen Display',
    reports:      'Reports',
    settings:     'Settings',
}

const pageTitle = computed(() => pageTitles[route.name] ?? 'PetPooja POS')

async function handleLogout() {
    await auth.logout()
    router.push('/login')
}

onMounted(async () => {
    try {
        const { data } = await api.get('/settings/restaurant')
        restaurantSettings.value = data.restaurant?.settings ?? {}
    } catch {
        // non-critical — use default color
    }
})
</script>
