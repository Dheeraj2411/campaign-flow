<template>
    <HeadTitle title="Analytics" subtitle="Campaign performance and messaging insights">
        <div v-if="loading" class="flex items-center justify-center h-64">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>
        <div v-else-if="error" class="text-center py-16">
            <div class="text-red-500 text-lg font-semibold">{{ error }}</div>
            <button @click="window.location.reload()" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-xl">
                Retry
            </button>
        </div>
        <div v-else>
            <!-- Filters Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div class="flex flex-wrap items-center gap-4 bg-white p-2 rounded-xl shadow-sm border border-gray-200">
                
                <!-- Date Range Picker -->
                <div class="flex items-center gap-2 border-r border-gray-200 pr-4">
                    <input 
                        type="date" 
                        v-model="customStartDate" 
                        class="text-xs rounded-lg border-gray-300 text-gray-600 focus:ring-indigo-500 focus:border-indigo-500 px-2 py-1.5"
                    />
                    <span class="text-xs text-gray-400 font-medium">to</span>
                    <input 
                        type="date" 
                        v-model="customEndDate" 
                        class="text-xs rounded-lg border-gray-300 text-gray-600 focus:ring-indigo-500 focus:border-indigo-500 px-2 py-1.5"
                    />
                    <button 
                        @click="applyCustomDateRange"
                        class="ml-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                    >
                        Apply
                    </button>
                </div>

                <!-- Quick Buttons -->
                <div class="flex items-center gap-1">
                    <button 
                        v-for="opt in [7, 30, 90]" 
                        :key="opt"
                        @click="setDays(opt)"
                        class="px-4 py-1.5 text-xs font-medium rounded-lg transition-all duration-200"
                        :class="filters.days == opt ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50'"
                    >
                        {{ opt }} Days
                    </button>
                </div>
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
             <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 flex items-center gap-4">
                 <div class="p-3 bg-red-50 rounded-xl">
                     <span class="material-symbols-outlined text-red-600">error</span>
                 </div>
                 <div>
                     <p class="text-sm text-gray-500">Failed Messages</p>
                     <p class="text-2xl font-bold text-gray-900">{{ kpis.totalFailed }}</p>
                 </div>
             </div>
             <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 flex items-center gap-4">
                 <div class="p-3 bg-emerald-50 rounded-xl">
                     <span class="material-symbols-outlined text-emerald-600">speed</span>
                 </div>
                 <div>
                     <p class="text-sm text-gray-500">Delivery Rate</p>
                     <p class="text-2xl font-bold text-gray-900">{{ kpis.deliveryRate }}%</p>
                 </div>
             </div>
             <!-- Progress to Goal -->
             <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 lg:col-span-2 flex items-center justify-between">
                 <div class="flex-1 mr-6">
                     <div class="flex justify-between text-xs font-medium mb-2">
                         <span class="text-gray-500">Messaging Efficiency</span>
                         <span class="text-emerald-600 font-semibold">{{ kpis.deliveryRate }}%</span>
                     </div>
                     <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                         <div class="bg-emerald-500 h-full rounded-full transition-all duration-1000" :style="{ width: kpis.deliveryRate + '%' }"></div>
                     </div>
                 </div>
                 <div class="text-xs text-gray-400 max-w-[120px]">
                     Goal: 95% delivery for Pro users.
                 </div>
             </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            <!-- Daily Trend Chart -->
            <div class="lg:col-span-2">
                <TrendChart :data="trendData" />
            </div>

            <!-- Platform Split Chart -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <div class="mb-4">
                    <h2 class="text-base font-semibold text-gray-900">Platform Split</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Overall distribution</p>
                </div>
                <div class="h-64 flex items-center justify-center relative">
                    <Doughnut v-if="doughnutChartData" :data="doughnutChartData" :options="doughnutChartOptions" />
                    <div v-else class="text-center text-gray-400">
                        <span class="material-symbols-outlined text-[48px] block mb-2 text-gray-300">donut_large</span>
                        <p class="text-sm">No distribution data</p>
                    </div>
                    <!-- Center Overlay -->
                    <div v-if="doughnutChartData" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-4">
                        <p class="text-xs text-gray-500 uppercase font-medium tracking-wider">Total</p>
                        <p class="text-2xl font-bold text-gray-900">{{ kpis.totalSent }}</p>
                    </div>
                </div>
                <!-- Legend -->
                <div v-if="hasPlatformData" class="space-y-2 mt-4">
                    <div v-if="platformSplit.whatsapp" class="flex items-center justify-between p-2.5 rounded-xl bg-emerald-50">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                            <span class="text-xs text-gray-700 font-medium">WhatsApp</span>
                        </div>
                        <span class="text-xs font-semibold text-emerald-700">{{ platformSplit.whatsapp }}</span>
                    </div>
                    <div v-if="platformSplit.telegram" class="flex items-center justify-between p-2.5 rounded-xl bg-blue-50">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                            <span class="text-xs text-gray-700 font-medium">Telegram</span>
                        </div>
                        <span class="text-xs font-semibold text-blue-700">{{ platformSplit.telegram }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Top Campaigns Table -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Top Performing Campaigns</h2>
                        <p class="text-sm text-gray-500 mt-0.5">Ranked by volume and success</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table v-if="topCampaigns.length" class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Campaign</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Platform</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Sent</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Failed</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Success %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="c in topCampaigns" :key="c.id"
                                class="hover:bg-gray-50 transition-colors duration-150 cursor-pointer"
                                @click="router.visit(route('campaigns.show', c.id))">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 text-sm">{{ c.name }}</div>
                                    <div class="text-xs text-gray-400">{{ new Date(c.created_at).toLocaleDateString() }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <BaseBadge :variant="c.platform === 'whatsapp' ? 'success' : 'info'">
                                        {{ c.platform }}
                                    </BaseBadge>
                                </td>
                                <td class="px-6 py-4 text-right font-medium tabular-nums text-sm">{{ c.total_sent }}</td>
                                <td class="px-6 py-4 text-right font-medium tabular-nums text-red-500 text-sm">{{ c.total_failed }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <span class="font-semibold tabular-nums text-sm">
                                            {{ c.total_sent > 0 ? Math.round((c.total_delivered / c.total_sent) * 100) : 0 }}%
                                        </span>
                                        <div class="w-10 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="bg-emerald-500 h-full rounded-full" :style="{ width: (c.total_sent > 0 ? (c.total_delivered / c.total_sent) * 100 : 0) + '%' }"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="py-16 text-center">
                        <span class="material-symbols-outlined text-[48px] block mb-3 text-gray-300">campaign</span>
                        <h3 class="text-lg font-semibold text-gray-900">No campaign data</h3>
                        <p class="text-sm text-gray-500 mt-1">No data available for this date range</p>
                    </div>
                </div>
            </div>

            <!-- Inbox Health -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <div class="mb-4">
                    <h2 class="text-base font-semibold text-gray-900">Inbox Health</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Real-time support metrics</p>
                </div>
                <div class="space-y-4">
                    <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-100 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 opacity-10 group-hover:scale-110 transition-transform">
                             <span class="material-symbols-outlined text-[80px]">chat</span>
                        </div>
                        <p class="text-xs font-medium text-emerald-600 uppercase tracking-wider mb-1">Open Conversations</p>
                        <p class="text-4xl font-bold text-emerald-700 tabular-nums">{{ conversations.open }}</p>
                        <button @click="router.visit(route('inbox'))" class="mt-3 text-xs font-medium text-emerald-700 bg-emerald-100 px-3 py-1.5 rounded-lg hover:bg-emerald-200 transition-colors">
                            Go to Inbox →
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                         <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                             <p class="text-xs text-gray-500 font-medium mb-1">Avg Response</p>
                             <p class="text-xl font-bold text-gray-900 tabular-nums">1.2m</p>
                         </div>
                         <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                             <p class="text-xs text-gray-500 font-medium mb-1">Unread Msgs</p>
                             <p class="text-xl font-bold text-gray-900 tabular-nums">{{ kpis.unreadMessages }}</p>
                         </div>
                    </div>

                    <div class="p-4 bg-indigo-600 rounded-xl text-white shadow-sm">
                         <p class="text-xs font-medium opacity-80 mb-2">Pro Tip</p>
                         <p class="text-sm font-medium leading-relaxed">
                             Campaigns with personalized templates have a <span class="font-bold underline">24% higher</span> engagement rate.
                         </p>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </HeadTitle>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import HeadTitle from '@/Components/HeadTitle.vue'
import StatsCard   from '@/Components/StatsCard.vue'
import BaseBadge   from '@/Components/BaseBadge.vue'
import TrendChart  from '@/Components/Analytics/TrendChart.vue'
import { Doughnut } from 'vue-chartjs'
import axios from 'axios'
import {
    Chart as ChartJS,
    ArcElement, Tooltip, Legend
} from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps({
    kpis:             { type: Object, default: () => ({ totalSent: 0, totalDelivered: 0, totalFailed: 0, deliveryRate: 0, activeContacts: 0, unreadMessages: 0 }) },
    filters:          { type: Object, default: () => ({ days: 30, start_date: '', end_date: '' }) },
    platformSplit:    { type: Object, default: () => ({}) },
    topCampaigns:     { type: Array, default: () => [] },
    conversations:    { type: Object, default: () => ({ open: 0 }) },
})

const customStartDate = ref(props.filters.start_date || '')
const customEndDate = ref(props.filters.end_date || '')
const trendData = ref([])

const error = ref(null)
const loading = ref(true)

const fetchTrendData = async () => {
    try {
        const params = {
            start_date: customStartDate.value,
            end_date: customEndDate.value
        }
        const response = await axios.get('/analytics/trend', { params })
        trendData.value = response.data
    } catch (e) {
        error.value = e.response?.data?.message || e.message || 'Error fetching trend data'
        console.error('Error fetching trend data', e)
    }
}

onMounted(async () => {
    try {
        await fetchTrendData()
    } catch (e) {
        error.value = e.message || 'Something went wrong during analytics initialization'
    } finally {
        loading.value = false
    }
})

const applyCustomDateRange = () => {
    router.get(route('analytics'), { start_date: customStartDate.value, end_date: customEndDate.value }, { 
        preserveState: true,
        preserveScroll: true,
        only: ['kpis', 'platformSplit', 'topCampaigns', 'conversations', 'filters']
    })
    fetchTrendData()
}

const setDays = (days) => {
    const end = new Date()
    const start = new Date()
    start.setDate(end.getDate() - days)
    
    customStartDate.value = start.toISOString().split('T')[0]
    customEndDate.value = end.toISOString().split('T')[0]

    router.get(route('analytics'), { days, start_date: customStartDate.value, end_date: customEndDate.value }, { 
        preserveState: true,
        preserveScroll: true,
        only: ['kpis', 'platformSplit', 'topCampaigns', 'conversations', 'filters']
    })
    fetchTrendData()
}

watch(() => props.filters, (newFilters) => {
    customStartDate.value = newFilters.start_date || customStartDate.value
    customEndDate.value = newFilters.end_date || customEndDate.value
})

// ── Doughnut Chart ────────────────────────────────────────
const hasPlatformData = computed(() => Object.keys(props.platformSplit).length > 0)

const doughnutChartData = computed(() => {
    if (!hasPlatformData.value) return null
    const labels = []
    const data = []
    const colors = []
    if (props.platformSplit.whatsapp) { labels.push('WhatsApp'); data.push(props.platformSplit.whatsapp); colors.push('#10b981') }
    if (props.platformSplit.telegram) { labels.push('Telegram'); data.push(props.platformSplit.telegram); colors.push('#6366f1') }
    return { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 4, borderColor: '#ffffff', hoverOffset: 10, borderRadius: 10 }] }
})

const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '80%',
    plugins: { legend: { display: false }, tooltip: { backgroundColor: '#111827', padding: 12, cornerRadius: 10 } },
}
</script>
