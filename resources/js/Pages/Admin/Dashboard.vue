<template>
    <AdminLayout title="Admin Overview" subtitle="High-level metrics for your CampaignFlow SaaS">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Users -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-admin-primary/5 rounded-full transition-transform group-hover:scale-150 duration-500 ease-out"></div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-admin-primary/10 text-admin-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">group</span>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-500">Total Users</h3>
                </div>
                <div class="mt-2">
                    <span class="text-3xl font-black tracking-tight text-slate-800">{{ stats.total_users }}</span>
                </div>
            </div>

            <!-- Total Workspaces -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-blue-500/5 rounded-full transition-transform group-hover:scale-150 duration-500 ease-out"></div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">corporate_fare</span>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-500">Workspaces</h3>
                </div>
                <div class="mt-2">
                    <span class="text-3xl font-black tracking-tight text-slate-800">{{ stats.total_workspaces }}</span>
                </div>
            </div>

            <!-- Active Campaigns -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-amber-500/5 rounded-full transition-transform group-hover:scale-150 duration-500 ease-out"></div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">campaign</span>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-500">Active Campaigns</h3>
                </div>
                <div class="mt-2">
                    <span class="text-3xl font-black tracking-tight text-slate-800">{{ stats.active_campaigns }}</span>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-500/5 rounded-full transition-transform group-hover:scale-150 duration-500 ease-out"></div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">payments</span>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-500">Total Revenue</h3>
                </div>
                <div class="mt-2">
                    <span class="text-3xl font-black tracking-tight text-slate-800">
                        {{ formatCurrency(stats.total_revenue) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Users -->
            <div class="bg-white border border-slate-100 rounded-2xl shadow-sm flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <span class="material-symbols-outlined text-admin-primary/70 text-[18px]">person_add</span>
                        Newest Users
                    </h2>
                    <Link :href="route('admin.users')" class="text-xs font-semibold text-admin-primary hover:text-admin-highlight transition-colors">
                        View all
                    </Link>
                </div>
                <div class="p-0 overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Plan</th>
                                <th class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="user in recentUsers" :key="user.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-admin-primary to-admin-highlight flex items-center justify-center text-white text-xs font-bold shrink-0">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">{{ user.name }}</p>
                                            <p class="text-xs text-slate-400">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium capitalize"
                                          :class="planVariant(user.active_workspace?.plan)">
                                        {{ user.active_workspace?.plan ?? 'free' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    {{ new Date(user.created_at).toLocaleDateString() }}
                                </td>
                            </tr>
                            <tr v-if="recentUsers.length === 0">
                                <td colspan="3" class="px-6 py-8 text-center text-slate-500 text-sm">
                                    No users found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="bg-white border border-slate-100 rounded-2xl shadow-sm p-6">
                <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-admin-primary/70 text-[18px]">bolt</span>
                    Quick Actions
                </h2>
                
                <div class="space-y-3">
                    <Link :href="route('admin.users')" class="flex items-center gap-4 p-4 rounded-xl border border-slate-100 hover:border-admin-primary/30 hover:shadow-sm transition-all group">
                        <div class="w-10 h-10 rounded-lg bg-slate-50 group-hover:bg-admin-primary/10 flex items-center justify-center text-slate-400 group-hover:text-admin-primary transition-colors">
                            <span class="material-symbols-outlined">manage_accounts</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-slate-800">Manage Users</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Approve plans, suspend accounts</p>
                        </div>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-admin-primary transition-colors">chevron_right</span>
                    </Link>

                    <Link :href="route('admin.transactions')" class="flex items-center gap-4 p-4 rounded-xl border border-slate-100 hover:border-emerald-500/30 hover:shadow-sm transition-all group">
                        <div class="w-10 h-10 rounded-lg bg-slate-50 group-hover:bg-emerald-500/10 flex items-center justify-center text-slate-400 group-hover:text-emerald-500 transition-colors">
                            <span class="material-symbols-outlined">receipt_long</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-slate-800">Review Transactions</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Activate pending manual payments</p>
                        </div>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-emerald-500 transition-colors">chevron_right</span>
                    </Link>
                    
                    <Link :href="route('dashboard')" class="flex items-center gap-4 p-4 rounded-xl border border-slate-100 hover:border-indigo-500/30 hover:shadow-sm transition-all group">
                        <div class="w-10 h-10 rounded-lg bg-slate-50 group-hover:bg-indigo-500/10 flex items-center justify-center text-slate-400 group-hover:text-indigo-500 transition-colors">
                            <span class="material-symbols-outlined">grid_view</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-slate-800">Switch Context</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Return to your personal User Dashboard</p>
                        </div>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-indigo-500 transition-colors">chevron_right</span>
                    </Link>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    stats:       Object,
    recentUsers: Array,
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0
    }).format(amount)
}

const planVariant = (plan) => {
    return {
        'enterprise': 'bg-admin-primary/10 text-admin-primary',
        'pro': 'bg-emerald-500/10 text-emerald-600',
        'free': 'bg-slate-100 text-slate-600'
    }[plan] || 'bg-slate-100 text-slate-600'
}
</script>
