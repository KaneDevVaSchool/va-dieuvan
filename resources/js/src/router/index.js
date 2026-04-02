import { createRouter, createWebHistory } from 'vue-router'

import DashboardView from '../views/DashboardView.vue'
import AdminDashboard from '../views/admin/AdminDashboard.vue'
import DispatcherDashboard from '../views/dispatcher/DispatcherDashboard.vue'
import DriverDashboard from '../views/driver/DriverDashboard.vue'
import AccountantDashboard from '../views/accountant/AccountantDashboard.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'dashboard', component: DashboardView },
    { path: '/admin', name: 'admin', component: AdminDashboard },
    { path: '/dispatcher', name: 'dispatcher', component: DispatcherDashboard },
    { path: '/driver', name: 'driver', component: DriverDashboard },
    { path: '/accountant', name: 'accountant', component: AccountantDashboard },
  ],
})

export default router

