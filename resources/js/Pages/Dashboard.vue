<template>
    <HeadTitle title="Dashboard" subtitle="Welcome back! Here's what's happening today.">

        <!-- Top Section: Stats & Plan -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- KPI Cards (Col 1-3) -->
            <div class="lg:col-span-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <StatsCard label="Total Contacts"   :value="stats.contacts"   icon="contacts"         color="primary" />
                    <StatsCard label="Campaigns"        :value="stats.campaigns"  icon="campaign"         color="violet"  />
                    <StatsCard label="Messages Sent"    :value="stats.messages"   icon="send"             color="success" />
                    <StatsCard label="Active Inbox"     :value="stats.inbox"      icon="mark_unread_chat_alt" color="warning" />
                </div>
            </div>

            <!-- Current Plan / Quick Upgrade (Col 4) -->
            <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 text-white rounded-2xl border border-indigo-500/20 shadow-sm relative overflow-hidden flex flex-col justify-between p-6">
                <div class="absolute -right-6 -top-6 opacity-10">
                     <span class="material-symbols-outlined text-[100px]">workspace_premium</span>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-widest opacity-70 mb-1">Your Plan</p>
                    <h2 class="text-2xl font-bold capitalize">{{ plan }}</h2>
                </div>
                <div class="mt-4">
                    <button @click="router.visit(route('billing'))" class="w-full py-2.5 bg-white/20 hover:bg-white/30 rounded-xl text-sm font-medium transition-all duration-200">
                        Manage Plan →
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            
            <!-- Recent Campaigns (Col 1-2) -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Recent Activity</h2>
                        <p class="text-sm text-gray-500 mt-0.5">Last 5 campaigns</p>
                    </div>
                    <BaseButton variant="secondary" size="sm" icon="add" :href="route('campaigns.create')">New Campaign</BaseButton>
                </div>

                <div class="space-y-2">
                    <div v-if="!recentCampaigns.length" class="text-center py-16">
                        <span class="material-symbols-outlined text-[48px] block mb-3 text-gray-300">campaign</span>
                        <h3 class="text-lg font-semibold text-gray-900">No campaigns yet</h3>
                        <p class="text-sm text-gray-500 mt-1">Launch one today to get started</p>
                        <BaseButton variant="primary" size="sm" class="mt-4" :href="route('campaigns.create')">Create Now</BaseButton>
                    </div>
                    <div
                        v-for="c in recentCampaigns"
                        :key="c.id"
                        class="flex items-center gap-4 p-4 rounded-xl hover:bg-gray-50 transition-all duration-150 cursor-pointer group border border-transparent hover:border-gray-200"
                        @click="router.visit(route('campaigns.details', c.id))"
                    >
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                             :class="platformBg(c.platform)">
                            <span class="material-symbols-outlined text-[20px]" :class="platformColor(c.platform)">
                                {{ platformIcon(c.platform) }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate group-hover:text-indigo-600 transition-colors">{{ c.name }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ c.platform }} · {{ new Date(c.created_at).toLocaleDateString() }}
                            </p>
                        </div>
                        <BaseBadge :variant="statusVariant(c.status)" :dot="true">{{ c.status }}</BaseBadge>
                    </div>
                </div>
            </div>

            <!-- Usage Progress (Col 3) -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-6">Plan Usage</h2>
                <div class="space-y-8">
                    <!-- Contacts -->
                    <div v-if="usage && usage.contacts">
                        <div class="flex justify-between items-end mb-2.5">
                             <div>
                                 <p class="text-sm text-gray-500">Contacts</p>
                                 <p class="text-sm font-semibold text-gray-900">{{ usage.contacts.current }} / {{ usage.contacts.limit == -1 ? '∞' : usage.contacts.limit }}</p>
                             </div>
                             <span class="text-xs font-medium text-gray-400">{{ usage.contacts.limit == -1 ? 0 : usage.contacts.percent }}%</span>
                        </div>
                        <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                             <div 
                                class="h-full rounded-full bg-indigo-600 transition-all duration-1000"
                                :style="{ width: (usage.contacts.limit == -1 ? 0 : usage.contacts.percent) + '%' }"
                                :class="{ 'bg-red-500': usage.contacts.percent > 90 }"
                             ></div>
                        </div>
                    </div>
                    <!-- Messages -->
                    <div v-if="usage && usage.messages">
                        <div class="flex justify-between items-end mb-2.5">
                             <div>
                                 <p class="text-sm text-gray-500">Messages</p>
                                 <p class="text-sm font-semibold text-gray-900">{{ usage.messages.current }} / {{ usage.messages.limit == -1 ? '∞' : usage.messages.limit }}</p>
                             </div>
                             <span class="text-xs font-medium text-gray-400">{{ usage.messages.limit == -1 ? 0 : usage.messages.percent }}%</span>
                        </div>
                        <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                             <div 
                                class="h-full rounded-full bg-emerald-500 transition-all duration-1000"
                                :style="{ width: (usage.messages.limit == -1 ? 0 : usage.messages.percent) + '%' }"
                                :class="{ 'bg-red-500': usage.messages.percent > 90 }"
                             ></div>
                        </div>
                    </div>

                    <!-- Pro Promo -->
                    <div v-if="plan === 'free'" class="p-4 bg-gray-50 rounded-xl border border-gray-200 mt-4">
                         <div class="flex items-center gap-2 mb-2">
                             <span class="material-symbols-outlined text-indigo-600 text-[16px]">celebration</span>
                             <span class="text-xs font-semibold text-gray-900 uppercase tracking-wider">Pro Benefit</span>
                         </div>
                         <p class="text-sm text-gray-500 leading-relaxed">
                             Unlock <span class="text-gray-900 font-semibold">50,000</span> monthly messages and Telegram integration with Pro.
                         </p>
                         <button @click="router.visit(route('billing'))" class="mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-700 transition-colors">
                             Upgrade Now →
                         </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 3: Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <Link :href="route('campaigns.create')"
                  class="flex items-center gap-3 p-4 bg-white rounded-2xl border border-gray-200 shadow-sm hover:border-indigo-300 hover:shadow-md transition-all duration-200 group">
                <div class="p-3 bg-indigo-50 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-indigo-600 text-[20px]">send</span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">New Campaign</p>
                    <p class="text-xs text-gray-500">Blast messages instantly</p>
                </div>
            </Link>
            <Link :href="route('contacts.index')"
                  class="flex items-center gap-3 p-4 bg-white rounded-2xl border border-gray-200 shadow-sm hover:border-emerald-300 hover:shadow-md transition-all duration-200 group">
                <div class="p-3 bg-emerald-50 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-emerald-600 text-[20px]">person_add</span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Add Contacts</p>
                    <p class="text-xs text-gray-500">Build your audience</p>
                </div>
            </Link>
            <Link :href="route('inbox')"
                  class="flex items-center gap-3 p-4 bg-white rounded-2xl border border-gray-200 shadow-sm hover:border-amber-300 hover:shadow-md transition-all duration-200 group">
                <div class="p-3 bg-amber-50 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-amber-600 text-[20px]">forum</span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Live Inbox</p>
                    <p class="text-xs text-gray-500">Engage in real-time</p>
                </div>
            </Link>
            <Link :href="route('analytics')"
                  class="flex items-center gap-3 p-4 bg-white rounded-2xl border border-gray-200 shadow-sm hover:border-indigo-300 hover:shadow-md transition-all duration-200 group">
                <div class="p-3 bg-indigo-50 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-indigo-600 text-[20px]">query_stats</span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Insights</p>
                    <p class="text-xs text-gray-500">View detailed reports</p>
                </div>
            </Link>
        </div>
    </HeadTitle>
</template>

<script setup>
import { router, Link } from '@inertiajs/vue3'
import HeadTitle from '@/Components/HeadTitle.vue'
import BaseButton  from '@/Components/BaseButton.vue'
import BaseBadge   from '@/Components/BaseBadge.vue'
import StatsCard   from '@/Components/StatsCard.vue'

const props = defineProps({
    stats:           { type: Object, default: () => ({ contacts: 0, campaigns: 0, messages: 0, inbox: 0 }) },
    recentCampaigns: { type: Array,  default: () => [] },
    usage:           { type: Object, default: () => ({}) },
    plan:            { type: String, default: 'free' },
})

const platformIcon  = (p) => ({ whatsapp: 'chat', telegram: 'send', both: 'forum' }[p] ?? 'chat')
const platformBg    = (p) => ({ whatsapp: 'bg-emerald-50', telegram: 'bg-blue-50', both: 'bg-indigo-50' }[p] ?? 'bg-gray-50')
const platformColor = (p) => ({ whatsapp: 'text-emerald-600', telegram: 'text-blue-600', both: 'text-indigo-600' }[p] ?? 'text-gray-500')
const statusVariant = (s) => ({ draft: 'neutral', scheduled: 'info', running: 'warning', completed: 'success', failed: 'danger' }[s] ?? 'neutral')
</script>
