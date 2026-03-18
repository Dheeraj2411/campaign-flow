<template>
    <AppLayout title="Dashboard" subtitle="Welcome back! Here's what's happening today.">

        <!-- Top Section: Stats & Plan -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- KPI Cards (Col 1-3) -->
            <div class="lg:col-span-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <StatsCard label="Total Contacts"   :value="stats.contacts"   icon="contacts"         color="primary" />
                    <StatsCard label="Campaigns"        :value="stats.campaigns"  icon="campaign"         color="violet"  />
                    <StatsCard label="Messages Sent"    :value="stats.messages"   icon="send"             color="success" />
                    <StatsCard label="Active Inbox"     :value="stats.inbox"      icon="mark_unread_chat_alt" color="warning" />
                </div>
            </div>

            <!-- Current Plan / Quick Upgrade (Col 4) -->
            <div class="card bg-gradient-to-br from-admin-primary to-indigo-700 text-white border-none relative overflow-hidden flex flex-col justify-between p-6">
                <div class="absolute -right-6 -top-6 opacity-20">
                     <span class="material-symbols-outlined text-[100px]">workspace_premium</span>
                </div>
                <div>
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80 mb-1">Your Plan</h3>
                    <h2 class="text-2xl font-black capitalize">{{ plan }}</h2>
                </div>
                <div class="mt-4">
                    <button @click="router.visit(route('billing'))" class="w-full py-2 bg-white/20 hover:bg-white/30 rounded-xl text-xs font-bold transition-colors">
                        Manage Plan →
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            
            <!-- Recent Campaigns (Col 1-2) -->
            <div class="lg:col-span-2 card">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Recent Activity</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Last 5 campaigns</p>
                    </div>
                    <BaseButton variant="outline" size="sm" icon="add" :href="route('campaigns.create')">New Campaign</BaseButton>
                </div>

                <div class="space-y-3">
                    <div v-if="!recentCampaigns.length" class="py-12 text-center text-slate-400">
                        <span class="material-symbols-outlined text-[40px] block mb-2 text-slate-200">campaign</span>
                        <p class="text-sm">No campaigns yet. Launch one today!</p>
                    </div>
                    <div
                        v-for="c in recentCampaigns"
                        :key="c.id"
                        class="flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-50 transition-all cursor-pointer group border border-transparent hover:border-slate-100"
                        @click="router.visit(route('campaigns.details', c.id))"
                    >
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-sm"
                             :class="platformBg(c.platform)">
                            <span class="material-symbols-outlined text-[20px]" :class="platformColor(c.platform)">
                                {{ platformIcon(c.platform) }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-slate-800 truncate group-hover:text-admin-primary transition-colors">{{ c.name }}</p>
                            <p class="text-[10px] text-slate-400 font-medium uppercase tracking-tight mt-0.5">
                                {{ c.platform }} · {{ new Date(c.created_at).toLocaleDateString() }}
                            </p>
                        </div>
                        <BaseBadge :variant="statusVariant(c.status)" :dot="true" class="font-bold uppercase tracking-tighter">{{ c.status }}</BaseBadge>
                    </div>
                </div>
            </div>

            <!-- Usage Progres (Col 3) -->
            <div class="card bg-slate-50/50">
                <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-6">Plan Usage</h2>
                <div class="space-y-8">
                    <!-- Contacts -->
                    <div v-if="usage && usage.contacts">
                        <div class="flex justify-between items-end mb-2.5">
                             <div>
                                 <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Contacts</p>
                                 <p class="text-xs font-bold text-slate-700">{{ usage.contacts.current }} / {{ usage.contacts.limit == -1 ? '∞' : usage.contacts.limit }}</p>
                             </div>
                             <span class="text-xs font-black text-slate-400">{{ usage.contacts.limit == -1 ? 0 : usage.contacts.percent }}%</span>
                        </div>
                        <div class="h-1.5 w-full bg-slate-200 rounded-full overflow-hidden">
                             <div 
                                class="h-full bg-admin-primary transition-all duration-1000"
                                :style="{ width: (usage.contacts.limit == -1 ? 0 : usage.contacts.percent) + '%' }"
                                :class="{ 'bg-red-500': usage.contacts.percent > 90 }"
                             ></div>
                        </div>
                    </div>
                    <!-- Messages -->
                    <div v-if="usage && usage.messages">
                        <div class="flex justify-between items-end mb-2.5">
                             <div>
                                 <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Messages</p>
                                 <p class="text-xs font-bold text-slate-700">{{ usage.messages.current }} / {{ usage.messages.limit == -1 ? '∞' : usage.messages.limit }}</p>
                             </div>
                             <span class="text-xs font-black text-slate-400">{{ usage.messages.limit == -1 ? 0 : usage.messages.percent }}%</span>
                        </div>
                        <div class="h-1.5 w-full bg-slate-200 rounded-full overflow-hidden">
                             <div 
                                class="h-full bg-emerald-500 transition-all duration-1000"
                                :style="{ width: (usage.messages.limit == -1 ? 0 : usage.messages.percent) + '%' }"
                                :class="{ 'bg-orange-500': usage.messages.percent > 90 }"
                             ></div>
                        </div>
                    </div>

                    <!-- Pro Promo -->
                    <div v-if="plan === 'free'" class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm mt-4">
                         <div class="flex items-center gap-2 mb-2">
                             <span class="material-symbols-outlined text-admin-primary text-[16px]">celebration</span>
                             <span class="text-[10px] font-bold text-slate-800 uppercase tracking-wider">Pro Benefit</span>
                         </div>
                         <p class="text-[11px] text-slate-500 font-medium leading-relaxed">
                             Unlock <span class="text-slate-800 font-bold">50,000</span> monthly messages and Telegram integration with Pro.
                         </p>
                         <button @click="router.visit(route('billing'))" class="mt-3 text-[10px] font-bold text-admin-primary">
                             Upgrade Now →
                         </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 3: Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <Link :href="route('campaigns.create')"
                  class="flex items-center gap-3 p-4 bg-white rounded-2xl border border-slate-100 shadow-sm hover:border-admin-primary/30 hover:shadow-md transition-all group">
                <div class="p-2.5 bg-admin-primary/10 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-admin-primary text-[20px]">send</span>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-700">New Campaign</p>
                    <p class="text-[10px] text-slate-400 font-medium">Blast messages instantly</p>
                </div>
            </Link>
            <Link :href="route('contacts.index')"
                  class="flex items-center gap-3 p-4 bg-white rounded-2xl border border-slate-100 shadow-sm hover:border-emerald-300 hover:shadow-md transition-all group">
                <div class="p-2.5 bg-emerald-100 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-emerald-600 text-[20px]">person_add</span>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-700">Add Contacts</p>
                    <p class="text-[10px] text-slate-400 font-medium">Build your audience</p>
                </div>
            </Link>
            <Link :href="route('inbox')"
                  class="flex items-center gap-3 p-4 bg-white rounded-2xl border border-slate-100 shadow-sm hover:border-amber-300 hover:shadow-md transition-all group">
                <div class="p-2.5 bg-amber-100 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-amber-600 text-[20px]">forum</span>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-700">Live Inbox</p>
                    <p class="text-[10px] text-slate-400 font-medium">Engage in real-time</p>
                </div>
            </Link>
            <Link :href="route('analytics')"
                  class="flex items-center gap-3 p-4 bg-white rounded-2xl border border-slate-100 shadow-sm hover:border-indigo-300 hover:shadow-md transition-all group">
                <div class="p-2.5 bg-indigo-100 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-indigo-600 text-[20px]">query_stats</span>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-700">Insights</p>
                    <p class="text-[10px] text-slate-400 font-medium">View detailed reports</p>
                </div>
            </Link>
        </div>
    </AppLayout>
</template>

<script setup>
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
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
const platformBg    = (p) => ({ whatsapp: 'bg-emerald-50', telegram: 'bg-blue-50', both: 'bg-indigo-50' }[p] ?? 'bg-slate-50')
const platformColor = (p) => ({ whatsapp: 'text-emerald-500', telegram: 'text-blue-500', both: 'text-indigo-500' }[p] ?? 'text-slate-500')
const statusVariant = (s) => ({ draft: 'neutral', scheduled: 'info', running: 'warning', completed: 'success', failed: 'danger' }[s] ?? 'neutral')
</script>
