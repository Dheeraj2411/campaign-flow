<script setup>
import HeadTitle from '@/Components/HeadTitle.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const workspace = computed(() => page.props.workspace)
const usagePercent = computed(() => {
    const sent = workspace.value?.messages_sent_this_month ?? 0
    const limit = workspace.value?.monthly_message_limit ?? 1000
    return limit > 0 ? Math.min(Math.round((sent / limit) * 100), 100) : 0
})
</script>

<template>
    <HeadTitle title="Billing & Plans" subtitle="Manage your workspace subscription and limits">
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">
                            {{ workspace?.subscription_status === 'active' ? 'Active Plan' : 'Free Trial' }}
                        </h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 mt-1">
                            {{ workspace?.subscription_status ?? 'trial' }}
                        </span>
                    </div>
                    <button class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold text-sm transition-all">
                        ⚡ Upgrade Plan
                    </button>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-semibold text-gray-700">Monthly Message Limit</p>
                        <p class="text-xl font-bold text-gray-900">
                            {{ (workspace?.messages_sent_this_month ?? 0).toLocaleString() }} / {{ (workspace?.monthly_message_limit ?? 1000).toLocaleString() }}
                        </p>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full bg-indigo-600 transition-all" :style="{ width: usagePercent + '%' }"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ usagePercent }}% Used</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Invoice History</h3>
                <div class="text-center py-12">
                    <p class="text-gray-400 text-sm">No invoices found for this workspace yet.</p>
                </div>
            </div>
        </div>
    </HeadTitle>
</template>
