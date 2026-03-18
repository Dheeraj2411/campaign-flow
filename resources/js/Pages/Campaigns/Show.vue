<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
// Refreshed: 2026-03-17 11:45
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    campaign: Object,
    logs: Object,
});

const getStatusColor = (status) => {
    switch (status) {
        case 'delivered': return 'text-green-500 bg-green-50';
        case 'sent': return 'text-blue-500 bg-blue-50';
        case 'failed': return 'text-red-500 bg-red-50';
        default: return 'text-gray-500 bg-gray-50';
    }
};
</script>

<template>
    <Head :title="`Campaign: ${campaign.name}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Campaign Details: {{ campaign.name }}
                </h2>
                <Link :href="route('campaigns.index')" class="text-sm text-indigo-600 hover:text-indigo-900">
                    &larr; Back to Campaigns
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Recipients</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ campaign.total_sent }}</div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="text-sm font-medium text-green-600 uppercase tracking-wider">Delivered / Sent</div>
                        <div class="mt-2 text-3xl font-bold text-green-600">{{ campaign.total_delivered }}</div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="text-sm font-medium text-red-600 uppercase tracking-wider">Failed</div>
                        <div class="mt-2 text-3xl font-bold text-red-600">{{ campaign.total_failed }}</div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="text-sm font-medium text-indigo-600 uppercase tracking-wider">Platform</div>
                        <div class="mt-2 text-xl font-bold text-indigo-600 capitalize">{{ campaign.platform }}</div>
                    </div>
                </div>

                <!-- Template Preview -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <span class="material-symbols-outlined mr-2 text-indigo-500">chat</span>
                        Message Template
                    </h3>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 whitespace-pre-wrap text-gray-700">
                        {{ campaign.template?.body }}
                    </div>
                    <div v-if="campaign.template?.header_text" class="mt-2 text-sm text-gray-500">
                        <strong>Header:</strong> {{ campaign.template.header_text }}
                    </div>
                    <div v-if="campaign.template?.footer_text" class="mt-1 text-sm text-gray-500">
                        <strong>Footer:</strong> {{ campaign.template.footer_text }}
                    </div>
                </div>

                <!-- Detailed Logs -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Delivery Logs</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Identifier</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent At</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Error Details</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="log in logs.data" :key="log.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ log.contact?.name || 'Unknown' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ campaign.platform === 'whatsapp' ? log.contact?.phone : log.contact?.telegram_username }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="['px-2 py-1 rounded-full text-xs font-medium capitalize', getStatusColor(log.status)]">
                                            {{ log.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 italic">
                                        {{ new Date(log.created_at).toLocaleString() }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div v-if="log.error_message" class="text-red-500 flex items-start max-w-xs break-words">
                                            <span class="material-symbols-outlined text-[18px] mr-1 mt-0.5 shrink-0">report_problem</span>
                                            {{ log.error_message }}
                                        </div>
                                        <span v-else class="text-gray-400">---</span>
                                    </td>
                                </tr>
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                        No delivery logs found for this campaign.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Placeholder -->
                    <div class="p-4 border-t border-gray-100 flex justify-end">
                        <Link v-if="logs.prev_page_url" :href="logs.prev_page_url" class="px-3 py-1 text-sm text-indigo-600">&larr; Previous</Link>
                        <Link v-if="logs.next_page_url" :href="logs.next_page_url" class="px-3 py-1 text-sm text-indigo-600 ml-2">Next &rarr;</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
