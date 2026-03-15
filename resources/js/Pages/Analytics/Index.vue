<template>
    <AppLayout title="Analytics" subtitle="Campaign performance and messaging insights">

        <!-- KPI Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <StatsCard label="Messages Sent"   :value="kpis.totalSent"      icon="send"         color="primary" />
            <StatsCard label="Delivered"        :value="kpis.totalDelivered" icon="done_all"     color="success" />
            <StatsCard label="Failed"           :value="kpis.totalFailed"    icon="error"        color="danger"  />
            <StatsCard label="Delivery Rate"    :value="kpis.deliveryRate"   icon="speed"        color="info"    suffix="%" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            <!-- Messages Over Time Chart -->
            <div class="lg:col-span-2 card">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Messages Over Time</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Last 30 days</p>
                    </div>
                </div>
                <div class="h-64">
                    <Line v-if="lineChartData" :data="lineChartData" :options="lineChartOptions" />
                    <div v-else class="flex items-center justify-center h-full text-slate-400">
                        <div class="text-center">
                            <span class="material-symbols-outlined text-4xl block mb-2 opacity-30">show_chart</span>
                            <p class="text-sm">No message data yet</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Platform Split Chart -->
            <div class="card">
                <div class="mb-4">
                    <h2 class="text-sm font-semibold text-slate-800">Platform Split</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Messages by channel</p>
                </div>
                <div class="h-56 flex items-center justify-center">
                    <Doughnut v-if="doughnutChartData" :data="doughnutChartData" :options="doughnutChartOptions" />
                    <div v-else class="text-center text-slate-400">
                        <span class="material-symbols-outlined text-4xl block mb-2 opacity-30">donut_large</span>
                        <p class="text-sm">No platform data yet</p>
                    </div>
                </div>
                <!-- Legend -->
                <div v-if="hasPlatformData" class="flex justify-center gap-6 mt-4">
                    <div v-if="platformSplit.whatsapp" class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <span class="text-xs text-slate-600 font-medium">WhatsApp ({{ platformSplit.whatsapp }})</span>
                    </div>
                    <div v-if="platformSplit.telegram" class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        <span class="text-xs text-slate-600 font-medium">Telegram ({{ platformSplit.telegram }})</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Top Campaigns Table -->
            <div class="lg:col-span-2 card">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Top Campaigns</h2>
                        <p class="text-xs text-slate-400 mt-0.5">By total messages sent</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table v-if="topCampaigns.length" class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Campaign</th>
                                <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Platform</th>
                                <th class="pb-3 text-right font-semibold text-slate-600 text-xs uppercase tracking-wider">Sent</th>
                                <th class="pb-3 text-right font-semibold text-slate-600 text-xs uppercase tracking-wider">Delivered</th>
                                <th class="pb-3 text-right font-semibold text-slate-600 text-xs uppercase tracking-wider">Failed</th>
                                <th class="pb-3 text-right font-semibold text-slate-600 text-xs uppercase tracking-wider">Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in topCampaigns" :key="c.id"
                                class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors cursor-pointer"
                                @click="$inertia.visit(route('campaigns.show', c.id))">
                                <td class="py-3 font-medium text-slate-800">{{ c.name }}</td>
                                <td class="py-3">
                                    <BaseBadge :variant="c.platform === 'whatsapp' ? 'success' : 'info'" size="sm">
                                        {{ c.platform }}
                                    </BaseBadge>
                                </td>
                                <td class="py-3 text-right font-medium tabular-nums">{{ c.total_sent }}</td>
                                <td class="py-3 text-right font-medium tabular-nums text-emerald-600">{{ c.total_delivered }}</td>
                                <td class="py-3 text-right font-medium tabular-nums text-red-500">{{ c.total_failed }}</td>
                                <td class="py-3 text-right font-medium tabular-nums">
                                    {{ c.total_sent > 0 ? Math.round((c.total_delivered / c.total_sent) * 100) : 0 }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="py-10 text-center text-slate-400">
                        <span class="material-symbols-outlined text-[40px] block mb-2 text-slate-200">campaign</span>
                        <p class="text-sm">No campaigns with messages yet.</p>
                    </div>
                </div>
            </div>

            <!-- Conversation Stats -->
            <div class="card">
                <div class="mb-4">
                    <h2 class="text-sm font-semibold text-slate-800">Conversations</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Inbox overview</p>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-emerald-50 rounded-xl border border-emerald-100">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-emerald-100 rounded-lg">
                                <span class="material-symbols-outlined text-emerald-600 text-[18px]">mark_unread_chat_alt</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-700">Open</p>
                                <p class="text-xs text-slate-400">Active conversations</p>
                            </div>
                        </div>
                        <span class="text-2xl font-black text-emerald-600 tabular-nums">{{ conversations.open }}</span>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-slate-200 rounded-lg">
                                <span class="material-symbols-outlined text-slate-500 text-[18px]">check_circle</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-700">Closed</p>
                                <p class="text-xs text-slate-400">Resolved conversations</p>
                            </div>
                        </div>
                        <span class="text-2xl font-black text-slate-600 tabular-nums">{{ conversations.closed }}</span>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-admin-primary/5 rounded-xl border border-admin-primary/10">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-admin-primary/10 rounded-lg">
                                <span class="material-symbols-outlined text-admin-primary text-[18px]">forum</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-700">Total</p>
                                <p class="text-xs text-slate-400">All conversations</p>
                            </div>
                        </div>
                        <span class="text-2xl font-black text-admin-primary tabular-nums">{{ conversations.open + conversations.closed }}</span>
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
    kpis:             { type: Object, default: () => ({ totalSent: 0, totalDelivered: 0, totalFailed: 0, deliveryRate: 0 }) },
    platformSplit:    { type: Object, default: () => ({}) },
    messagesOverTime: { type: Array, default: () => [] },
    topCampaigns:     { type: Array, default: () => [] },
    conversations:    { type: Object, default: () => ({ open: 0, closed: 0 }) },
})

// ── Line Chart ────────────────────────────────────────────
const lineChartData = computed(() => {
    if (!props.messagesOverTime.length) return null
    return {
        labels: props.messagesOverTime.map(r => {
            const d = new Date(r.date)
            return d.toLocaleDateString([], { month: 'short', day: 'numeric' })
        }),
        datasets: [{
            label: 'Messages',
            data: props.messagesOverTime.map(r => r.total),
            borderColor: '#4020C9',
            backgroundColor: 'rgba(64, 32, 201, 0.08)',
            borderWidth: 2.5,
            tension: 0.4,
            fill: true,
            pointRadius: 3,
            pointBackgroundColor: '#4020C9',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
        }]
    }
})

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e293b', titleFont: { size: 12 }, bodyFont: { size: 12 }, padding: 10, cornerRadius: 8 } },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#94a3b8' } },
        y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 11 }, color: '#94a3b8', stepSize: 1 }, beginAtZero: true },
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
    return { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 0, hoverOffset: 6 }] }
})

const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e293b', padding: 10, cornerRadius: 8 } },
}
</script>
