import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/auth/Login.vue'),
        meta: { requiresGuest: true },
    },
    {
        path: '/',
        component: () => import('@/layouts/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '', name: 'dashboard', component: () => import('@/views/Dashboard.vue') },
            { path: 'orders', name: 'orders', component: () => import('@/views/orders/OrderList.vue') },
            { path: 'orders/:id', name: 'order-detail', component: () => import('@/views/orders/OrderDetail.vue') },
            { path: 'menu', name: 'menu', component: () => import('@/views/menu/MenuManagement.vue') },
            { path: 'tables', name: 'tables', component: () => import('@/views/tables/TableFloorPlan.vue') },
            { path: 'table-setup', name: 'table-setup', component: () => import('@/views/tables/TableManagement.vue') },
            { path: 'customers', name: 'customers', component: () => import('@/views/customers/CustomerList.vue') },
            { path: 'inventory', name: 'inventory', component: () => import('@/views/inventory/Inventory.vue') },
            { path: 'reports', name: 'reports', component: () => import('@/views/reports/Reports.vue') },
            { path: 'settings', name: 'settings', component: () => import('@/views/settings/Settings.vue') },
        ],
    },
    {
        // Table order terminal — fullscreen, no sidebar
        path: '/tables/:tableId/order',
        name: 'table-order',
        component: () => import('@/views/tables/TableOrderTerminal.vue'),
        meta: { requiresAuth: true, fullscreen: true },
    },
    {
        // Delivery / Takeaway standalone terminal — fullscreen
        path: '/order/new',
        name: 'new-order',
        component: () => import('@/views/tables/TableOrderTerminal.vue'),
        meta: { requiresAuth: true, fullscreen: true },
    },
    {
        path: '/kds',
        name: 'kds',
        component: () => import('@/views/kds/KitchenDisplay.vue'),
        meta: { requiresAuth: true },
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach(async (to) => {
    const auth = useAuthStore()

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        if (auth.token) {
            const ok = await auth.fetchUser()
            if (!ok) return { name: 'login' }
        } else {
            return { name: 'login' }
        }
    }

    if (to.meta.requiresGuest && auth.isAuthenticated) {
        return { name: 'dashboard' }
    }
})

export default router
