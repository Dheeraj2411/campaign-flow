<script setup>
import { ref, onMounted } from 'vue';
import HeadTitle from '@/Components/HeadTitle.vue';
import FunnelChart from '@/Components/Analytics/FunnelChart.vue';
import BaseButton from '@/Components/BaseButton.vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    campaign: Object,
    logs: Object,
});

const activeTab = ref('overview');
const funnelData = ref({ sent: 0, delivered: 0, read: 0, failed: 0 });

const error = ref(null);
const loading = ref(true);

onMounted(async () => {
    try {
        const res = await axios.get(`/campaigns/${props.campaign.id}/funnel`);
        funnelData.value = res.data;
    } catch (e) {
        error.value = e.response?.data?.message || e.message || 'Failed to fetch funnel data';
        console.error('Failed to fetch funnel data', e);
    } finally {
        loading.value = false;
    }
});

const getStatusBadge = (status) => {
    switch (status) {
        case 'delivered': return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700';
        case 'sent':      return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700';
        case 'read':      return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700';
        case 'failed':    return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700';
        default:          return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700';
    }
};
</script>

<template>
    <Head :title="`Campaign: ${campaign.name}`" />

    <HeadTitle :title="campaign.name" subtitle="Campaign details and delivery analytics">
        <template #actions>
            <BaseButton variant="secondary" size="sm" :href="route('campaigns.index')">
                <span class="material-symbols-outlined text-[16px] mr-1">arrow_back</span>
                Back to Campaigns
            </BaseButton>
        </template>

        <div v-if="loading" class="flex items-center justify-center h-64">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>
        <div v-else-if="error" class="text-center py-16">
            <div class="text-red-500 text-lg font-semibold">{{ error }}</div>
            <button @click="window.location.reload()" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-xl">
                Retry
            </button>
        </div>
        <div v-else class="space-y-6">
            <!-- Tabs Row -->
            <div class="border-b border-gray-200 flex space-x-8">
                <button 
                    @click="activeTab = 'overview'"
                    :class="[activeTab === 'overview' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm transition-colors']"
                >
                    Overview
                </button>
                <button 
                    @click="activeTab = 'funnel'"
                    :class="[activeTab === 'funnel' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm transition-colors']"
                >
                    Funnel
                </button>
            </div>

            <!-- Overview Tab Content -->
            <div v-show="activeTab === 'overview'" class="space-y-6">
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-3 bg-indigo-50 rounded-xl">
                                <span class="material-symbols-outlined text-indigo-600 text-[20px]">group</span>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-gray-900">{{ campaign.total_sent }}</p>
                        <p class="text-sm text-gray-500 mt-1">Total Recipients</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-3 bg-emerald-50 rounded-xl">
                                <span class="material-symbols-outlined text-emerald-600 text-[20px]">check_circle</span>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-gray-900">{{ campaign.total_delivered }}</p>
                        <p class="text-sm text-gray-500 mt-1">Delivered</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-3 bg-red-50 rounded-xl">
                                <span class="material-symbols-outlined text-red-600 text-[20px]">error</span>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-gray-900">{{ campaign.total_failed }}</p>
                        <p class="text-sm text-gray-500 mt-1">Failed</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-3 bg-indigo-50 rounded-xl">
                                <span class="material-symbols-outlined text-indigo-600 text-[20px]">devices</span>
                            </div>
                        </div>
                        <p class="text-xl font-bold text-gray-900 capitalize">{{ campaign.platform }}</p>
                        <p class="text-sm text-gray-500 mt-1">Platform</p>
                    </div>
                </div>

                <!-- Template Preview -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-600 text-[20px]">chat</span>
                        Message Template
                    </h3>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 whitespace-pre-wrap text-sm text-gray-700">
                        {{ campaign.template?.body }}
                    </div>
                    <div v-if="campaign.template?.header_text" class="mt-3 text-sm text-gray-500">
                        <strong class="text-gray-700">Header:</strong> {{ campaign.template.header_text }}
                    </div>
                    <div v-if="campaign.template?.footer_text" class="mt-1 text-sm text-gray-500">
                        <strong class="text-gray-700">Footer:</strong> {{ campaign.template.footer_text }}
                    </div>
                </div>

                <!-- Delivery Logs -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Delivery Logs</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Identifier</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sent At</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Error Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ log.contact?.name || 'Unknown' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ campaign.platform === 'whatsapp' ? log.contact?.phone : log.contact?.telegram_username }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusBadge(log.status)" class="capitalize">{{ log.status }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ new Date(log.created_at).toLocaleString() }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div v-if="log.error_message" class="text-red-600 flex items-start max-w-xs">
                                            <span class="material-symbols-outlined text-[16px] mr-1 mt-0.5 shrink-0">report_problem</span>
                                            <span class="break-words">{{ log.error_message }}</span>
                                        </div>
                                        <span v-else class="text-gray-400">—</span>
                                    </td>
                                </tr>
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="5" class="px-6 py-16 text-center">
                                        <span class="material-symbols-outlined text-[48px] block mb-3 text-gray-300">inbox</span>
                                        <h3 class="text-lg font-semibold text-gray-900">No delivery logs</h3>
                                        <p class="text-sm text-gray-500 mt-1">Logs will appear here once the campaign is sent</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-3 border-t border-gray-100 flex justify-end gap-2">
                        <Link v-if="logs.prev_page_url" :href="logs.prev_page_url" class="px-4 py-2 text-sm font-medium text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors">&larr; Previous</Link>
                        <Link v-if="logs.next_page_url" :href="logs.next_page_url" class="px-4 py-2 text-sm font-medium text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors">Next &rarr;</Link>
                    </div>
                </div>
            </div>

            <!-- Funnel Tab Content -->
            <div v-show="activeTab === 'funnel'" class="space-y-6">
                <FunnelChart :data="funnelData" />
                
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900">Message Logs</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Failure Reason</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="log in logs.data" :key="'f-'+log.id" class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ log.contact?.name || 'Unknown' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusBadge(log.status)" class="capitalize">{{ log.status }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-red-600">
                                        {{ log.status === 'failed' ? (log.failure_reason || log.error_message || 'Unknown Error') : '—' }}
                                    </td>
                                </tr>
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="3" class="px-6 py-16 text-center">
                                        <span class="material-symbols-outlined text-[48px] block mb-3 text-gray-300">inbox</span>
                                        <h3 class="text-lg font-semibold text-gray-900">No delivery logs</h3>
                                        <p class="text-sm text-gray-500 mt-1">Logs will appear once messages are sent</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </HeadTitle>
</template>
