<template>
    <AppLayout title="Analytics" subtitle="Campaign performance and messaging insights">
        
        <!-- Filters Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <h1 class="text-xl font-bold text-slate-800">Workspace Analytics</h1>
            <div class="flex items-center gap-2 bg-white p-1 rounded-xl shadow-sm border border-slate-200">
                <button 
                    v-for="opt in [7, 30, 90]" 
                    :key="opt"
                    @click="setDays(opt)"
                    class="px-4 py-1.5 text-xs font-semibold rounded-lg transition-all"
                    :class="filters.days == opt ? 'bg-admin-primary text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'"
                >
                    {{ opt }} Days
                </button>
            </div>
        </div>

        <!-- KPI Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <StatsCard label="Messages Sent"   :value="kpis.totalSent"      icon="send"         color="primary" />
            <StatsCard label="Delivered"        :value="kpis.totalDelivered" icon="done_all"     color="success" />
            <StatsCard label="Active Contacts"  :value="kpis.activeContacts" icon="person"       color="info"    />
            <StatsCard label="Unread Chats"     :value="kpis.unreadMessages" icon="chat_bubble"   color="warning" />
        </div>

        <!-- Delivery Performance Metrics -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
             <div class="card flex items-center gap-4">
                 <div class="p-3 bg-red-100 rounded-xl">
                     <span class="material-symbols-outlined text-red-600">error</span>
                 </div>
                 <div>
                     <p class="text-xs text-slate-400 font-medium">Failed Messages</p>
                     <p class="text-xl font-bold text-slate-800">{{ kpis.totalFailed }}</p>
                 </div>
             </div>
             <div class="card flex items-center gap-4 lg:col-span-1">
                 <div class="p-3 bg-emerald-100 rounded-xl">
                     <span class="material-symbols-outlined text-emerald-600">speed</span>
                 </div>
                 <div>
                     <p class="text-xs text-slate-400 font-medium">Delivery Rate</p>
                     <p class="text-xl font-bold text-slate-800">{{ kpis.deliveryRate }}%</p>
                 </div>
             </div>
             <!-- Progress to Goal (Simulated) -->
             <div class="card lg:col-span-2 flex items-center justify-between px-6">
                 <div class="flex-1 mr-6">
                     <div class="flex justify-between text-xs font-semibold mb-1.5">
                         <span class="text-slate-500">Messaging Efficiency</span>
                         <span class="text-emerald-600">{{ kpis.deliveryRate }}%</span>
                     </div>
                     <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                         <div class="bg-emerald-500 h-full transition-all duration-1000" :style="{ width: kpis.deliveryRate + '%' }"></div>
                     </div>
                 </div>
                 <div class="text-xs text-slate-400 max-w-[120px]">
                     Goal: 95% delivery for Pro users.
                 </div>
             </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            <!-- Messages Over Time Chart (Multi-Platform) -->
            <div class="lg:col-span-2 card">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Volume by Channel</h2>
                        <p class="text-xs text-slate-400 mt-0.5">{{ filters.days }} day trend</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase">WhatsApp</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Telegram</span>
                        </div>
                    </div>
                </div>
                <div class="h-72">
                    <Line v-if="lineChartData" :data="lineChartData" :options="lineChartOptions" />
                    <div v-else class="flex items-center justify-center h-full text-slate-400">
                        <div class="text-center">
                            <span class="material-symbols-outlined text-4xl block mb-2 opacity-30">show_chart</span>
                            <p class="text-sm">No volume data for this period</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Platform Split Chart -->
            <div class="card">
                <div class="mb-4">
                    <h2 class="text-sm font-semibold text-slate-800">Platform Split</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Overall distribution</p>
                </div>
                <div class="h-64 flex items-center justify-center relative">
                    <Doughnut v-if="doughnutChartData" :data="doughnutChartData" :options="doughnutChartOptions" />
                    <div v-else class="text-center text-slate-400">
                        <span class="material-symbols-outlined text-4xl block mb-2 opacity-30">donut_large</span>
                        <p class="text-sm">No distribution data</p>
                    </div>
                    <!-- Center Overlay -->
                    <div v-if="doughnutChartData" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-4">
                        <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total</p>
                        <p class="text-2xl font-black text-slate-800">{{ kpis.totalSent }}</p>
                    </div>
                </div>
                <!-- Legend -->
                <div v-if="hasPlatformData" class="space-y-2 mt-4">
                    <div v-if="platformSplit.whatsapp" class="flex items-center justify-between p-2 rounded-lg bg-emerald-50/50">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                            <span class="text-xs text-slate-700 font-medium">WhatsApp</span>
                        </div>
                        <span class="text-xs font-bold text-emerald-700">{{ platformSplit.whatsapp }}</span>
                    </div>
                    <div v-if="platformSplit.telegram" class="flex items-center justify-between p-2 rounded-lg bg-blue-50/50">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                            <span class="text-xs text-slate-700 font-medium">Telegram</span>
                        </div>
                        <span class="text-xs font-bold text-blue-700">{{ platformSplit.telegram }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Top Campaigns Table -->
            <div class="lg:col-span-2 card">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Top Performing Campaigns</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Ranked by volume and success</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table v-if="topCampaigns.length" class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Campaign</th>
                                <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Platform</th>
                                <th class="pb-3 text-right font-semibold text-slate-600 text-xs uppercase tracking-wider">Sent</th>
                                <th class="pb-3 text-right font-semibold text-slate-600 text-xs uppercase tracking-wider">Failed</th>
                                <th class="pb-3 text-right font-semibold text-slate-600 text-xs uppercase tracking-wider">Success %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in topCampaigns" :key="c.id"
                                class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors cursor-pointer"
                                @click="router.visit(route('campaigns.show', c.id))">
                                <td class="py-3">
                                    <div class="font-medium text-slate-800">{{ c.name }}</div>
                                    <div class="text-[10px] text-slate-400">{{ new Date(c.created_at).toLocaleDateString() }}</div>
                                </td>
                                <td class="py-3">
                                    <BaseBadge :variant="c.platform === 'whatsapp' ? 'success' : 'info'" size="sm">
                                        {{ c.platform }}
                                    </BaseBadge>
                                </td>
                                <td class="py-3 text-right font-medium tabular-nums">{{ c.total_sent }}</td>
                                <td class="py-3 text-right font-medium tabular-nums text-red-500">{{ c.total_failed }}</td>
                                <td class="py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <span class="font-bold tabular-nums">
                                            {{ c.total_sent > 0 ? Math.round((c.total_delivered / c.total_sent) * 100) : 0 }}%
                                        </span>
                                        <div class="w-8 h-1 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="bg-emerald-500 h-full" :style="{ width: (c.total_sent > 0 ? (c.total_delivered / c.total_sent) * 100 : 0) + '%' }"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="py-10 text-center text-slate-400">
                        <p class="text-sm font-medium">No campaign data available for this range.</p>
                    </div>
                </div>
            </div>

            <!-- Conversion Stats -->
            <div class="card">
                <div class="mb-4">
                    <h2 class="text-sm font-semibold text-slate-800">Inbox Health</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Real-time support metrics</p>
                </div>
                <div class="space-y-4">
                    <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-100 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 opacity-10 group-hover:scale-110 transition-transform">
                             <span class="material-symbols-outlined text-[80px]">chat</span>
                        </div>
                        <p class="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-1">Open Conversations</p>
                        <p class="text-4xl font-black text-emerald-700 tabular-nums">{{ conversations.open }}</p>
                        <button @click="router.visit(route('inbox'))" class="mt-3 text-[10px] font-bold text-emerald-700 bg-emerald-100 px-3 py-1 rounded-full hover:bg-emerald-200 transition-colors">
                            Go to Inbox →
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                         <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                             <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Average Response</p>
                             <p class="text-xl font-bold text-slate-700 tabular-nums">1.2m</p>
                         </div>
                         <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                             <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Unread Msg</p>
                             <p class="text-xl font-bold text-slate-700 tabular-nums">{{ kpis.unreadMessages }}</p>
                         </div>
                    </div>

                    <div class="p-4 bg-admin-primary blur-0 rounded-xl text-white shadow-lg shadow-admin-primary/20">
                         <p class="text-xs font-semibold opacity-80 mb-2">Pro Tip</p>
                         <p class="text-sm font-medium leading-relaxed">
                             Campaigns with personalized templates have a <span class="font-bold underline">24% higher</span> engagement rate.
                         </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatsCard   from '@/Components/StatsCard.vue'
import BaseBadge   from '@/Components/BaseBadge.vue'
import { Line, Doughnut } from 'vue-chartjs'
import {
    Chart as ChartJS,
    CategoryScale, LinearScale, PointElement, LineElement,
    ArcElement, Tooltip, Legend, Filler
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Tooltip, Legend, Filler)

const props = defineProps({
    kpis:             { type: Object, default: () => ({ totalSent: 0, totalDelivered: 0, totalFailed: 0, deliveryRate: 0, activeContacts: 0, unreadMessages: 0 }) },
    filters:          { type: Object, default: () => ({ days: 30 }) },
    platformSplit:    { type: Object, default: () => ({}) },
    messagesOverTime: { type: Array, default: () => [] },
    topCampaigns:     { type: Array, default: () => [] },
    conversations:    { type: Object, default: () => ({ open: 0 }) },
})

const setDays = (days) => {
    router.get(route('analytics'), { days }, { 
        preserveState: true,
        preserveScroll: true,
        only: ['kpis', 'platformSplit', 'messagesOverTime', 'topCampaigns', 'conversations', 'filters']
    })
}

// ── Line Chart ────────────────────────────────────────────
const lineChartData = computed(() => {
    if (!props.messagesOverTime.length) return null
    return {
        labels: props.messagesOverTime.map(r => {
            const d = new Date(r.date)
            return d.toLocaleDateString([], { month: 'short', day: 'numeric' })
        }),
        datasets: [
            {
                label: 'WhatsApp',
                data: props.messagesOverTime.map(r => r.whatsapp),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.05)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 0,
                pointHoverRadius: 6,
            },
            {
                label: 'Telegram',
                data: props.messagesOverTime.map(r => r.telegram),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.05)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 0,
                pointHoverRadius: 6,
            }
        ]
    }
})

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: { 
        legend: { display: false }, 
        tooltip: { 
            backgroundColor: '#1e293b', 
            titleFont: { size: 12, weight: 'bold' }, 
            bodyFont: { size: 12 }, 
            padding: 12, 
            cornerRadius: 10,
            displayColors: true,
        } 
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10, weight: '600' }, color: '#94a3b8', maxRotation: 0 } },
        y: { grid: { color: '#f1f5f9', drawBorder: false }, ticks: { font: { size: 10, weight: '600' }, color: '#94a3b8', stepSize: 1 }, beginAtZero: true },
    },
}

// ── Doughnut Chart ────────────────────────────────────────
const hasPlatformData = computed(() => Object.keys(props.platformSplit).length > 0)

const doughnutChartData = computed(() => {
    if (!hasPlatformData.value) return null
    const labels = []
    const data = []
    const colors = []
    if (props.platformSplit.whatsapp) { labels.push('WhatsApp'); data.push(props.platformSplit.whatsapp); colors.push('#10b981') }
    if (props.platformSplit.telegram) { labels.push('Telegram'); data.push(props.platformSplit.telegram); colors.push('#3b82f6') }
    return { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 4, borderColor: '#ffffff', hoverOffset: 10, borderRadius: 10 }] }
})

const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '80%',
    plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 10 } },
}
</script>
