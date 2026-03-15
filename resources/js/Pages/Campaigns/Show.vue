<template>
    <AppLayout :title="campaign.name" subtitle="Campaign performance details">

        <!-- Back Link -->
        <div class="mb-4">
            <Link :href="route('campaigns.index')" class="inline-flex items-center gap-1 text-sm text-admin-primary hover:text-admin-highlight transition-colors font-medium">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                Back to Campaigns
            </Link>
        </div>

        <!-- Campaign Header -->
        <div class="card mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl" :class="platformBg(campaign.platform)">
                        <span class="material-symbols-outlined text-[22px]" :class="platformColor(campaign.platform)"
                              style="font-variation-settings: 'FILL' 1">
                            {{ platformIcon(campaign.platform) }}
                        </span>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-slate-800">{{ campaign.name }}</h1>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Created {{ formatDate(campaign.created_at) }} ·
                            {{ campaign.platform.charAt(0).toUpperCase() + campaign.platform.slice(1) }}
                        </p>
                    </div>
                </div>
                <BaseBadge :variant="statusVariant(campaign.status)" :dot="true" size="md">
                    {{ campaign.status }}
                </BaseBadge>
            </div>
        </div>

        <!-- Delivery Stats KPIs -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <StatsCard label="Total Recipients" :value="campaign.total_sent"      icon="group"    color="primary" />
            <StatsCard label="Delivered"         :value="campaign.total_delivered" icon="done_all" color="success" />
            <StatsCard label="Failed"            :value="campaign.total_failed"   icon="error"    color="danger"  />
            <StatsCard label="Delivery Rate"     :value="deliveryRate"            icon="speed"    color="info" suffix="%" />
        </div>

        <!-- Delivery Progress Bar -->
        <div class="card mb-6">
            <h2 class="text-sm font-semibold text-slate-800 mb-3">Delivery Progress</h2>
            <div class="w-full h-4 bg-slate-100 rounded-full overflow-hidden flex">
                <div v-if="campaign.total_delivered > 0"
                     class="h-full bg-emerald-500 transition-all duration-500"
                     :style="{ width: deliveredPct + '%' }"
                     :title="`Delivered: ${campaign.total_delivered}`">
                </div>
                <div v-if="campaign.total_failed > 0"
                     class="h-full bg-red-400 transition-all duration-500"
                     :style="{ width: failedPct + '%' }"
                     :title="`Failed: ${campaign.total_failed}`">
                </div>
                <div v-if="pendingCount > 0"
                     class="h-full bg-amber-300 transition-all duration-500"
                     :style="{ width: pendingPct + '%' }"
                     :title="`Pending: ${pendingCount}`">
                </div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-slate-500">
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span> Delivered ({{ deliveredPct }}%)</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-400 inline-block"></span> Failed ({{ failedPct }}%)</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-300 inline-block"></span> Pending ({{ pendingPct }}%)</span>
                </div>
                <span class="font-medium text-slate-700">{{ campaign.total_sent }} total</span>
            </div>
        </div>

        <!-- Message Logs Table -->
        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Message Log</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Per-contact delivery status</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table v-if="logs.data && logs.data.length" class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Contact</th>
                            <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Phone / Username</th>
                            <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Platform</th>
                            <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Status</th>
                            <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Error</th>
                            <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in logs.data" :key="log.id"
                            class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                            <td class="py-3 font-medium text-slate-800">{{ log.contact?.name ?? '—' }}</td>
                            <td class="py-3 text-slate-500">{{ log.contact?.phone || log.contact?.telegram_username || '—' }}</td>
                            <td class="py-3">
                                <BaseBadge :variant="log.platform === 'whatsapp' ? 'success' : 'info'" size="sm">
                                    {{ log.platform }}
                                </BaseBadge>
                            </td>
                            <td class="py-3">
                                <BaseBadge :variant="logStatusVariant(log.status)" :dot="true" size="sm">
                                    {{ log.status }}
                                </BaseBadge>
                            </td>
                            <td class="py-3 text-xs text-red-500 max-w-[200px] truncate">{{ log.error_message || '—' }}</td>
                            <td class="py-3 text-xs text-slate-400">{{ formatDate(log.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div v-else class="py-10 text-center text-slate-400">
                    <span class="material-symbols-outlined text-[40px] block mb-2 text-slate-200">list_alt</span>
                    <p class="text-sm">No message logs recorded for this campaign.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="logs.last_page > 1" class="flex justify-center gap-1 mt-6">
                <Link v-for="link in logs.links" :key="link.label"
                      :href="link.url || '#'"
                      :class="[
                          'px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                          link.active ? 'bg-admin-primary text-white' : 'text-slate-500 hover:bg-slate-100',
                          !link.url ? 'opacity-30 pointer-events-none' : ''
                      ]"
                      v-html="link.label" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link }     from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseBadge    from '@/Components/BaseBadge.vue'
import StatsCard    from '@/Components/StatsCard.vue'

const props = defineProps({
    campaign: { type: Object, required: true },
    logs:     { type: Object, default: () => ({ data: [], links: [], last_page: 1 }) },
})

const deliveryRate = computed(() => {
    return props.campaign.total_sent > 0
        ? Math.round((props.campaign.total_delivered / props.campaign.total_sent) * 100)
        : 0
})

const pendingCount = computed(() => {
    return Math.max(0, props.campaign.total_sent - props.campaign.total_delivered - props.campaign.total_failed)
})

const deliveredPct = computed(() => props.campaign.total_sent > 0 ? Math.round((props.campaign.total_delivered / props.campaign.total_sent) * 100) : 0)
const failedPct    = computed(() => props.campaign.total_sent > 0 ? Math.round((props.campaign.total_failed / props.campaign.total_sent) * 100) : 0)
const pendingPct   = computed(() => Math.max(0, 100 - deliveredPct.value - failedPct.value))

const platformIcon  = (p) => ({ whatsapp: 'chat', telegram: 'send', both: 'forum' }[p] ?? 'chat')
const platformBg    = (p) => ({ whatsapp: 'bg-emerald-100', telegram: 'bg-blue-100', both: 'bg-violet-100' }[p] ?? 'bg-slate-100')
const platformColor = (p) => ({ whatsapp: 'text-emerald-600', telegram: 'text-blue-600', both: 'text-violet-600' }[p] ?? 'text-slate-500')
const statusVariant = (s) => ({ draft: 'neutral', scheduled: 'info', running: 'warning', completed: 'success', failed: 'danger' }[s] ?? 'neutral')
const logStatusVariant = (s) => ({ sent: 'info', delivered: 'success', failed: 'danger', pending: 'warning' }[s] ?? 'neutral')

const formatDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
