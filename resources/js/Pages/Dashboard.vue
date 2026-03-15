<template>
    <AppLayout title="Dashboard" subtitle="Welcome back! Here's what's happening today.">

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <StatsCard label="Total Contacts"   :value="stats.contacts"   icon="contacts"         color="primary" :change="12" />
            <StatsCard label="Campaigns Sent"   :value="stats.campaigns"  icon="campaign"         color="violet"  :change="8"  />
            <StatsCard label="Messages Sent"    :value="stats.messages"   icon="send"             color="success" :change="23" />
            <StatsCard label="Active Inbox"     :value="stats.inbox"      icon="mark_unread_chat_alt" color="warning" :change="-3" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Recent Campaigns -->
            <div class="lg:col-span-2 card">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Recent Campaigns</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Last 5 campaigns across all channels</p>
                    </div>
                    <BaseButton variant="outline" size="sm" icon="add" :href="route('campaigns.create')">New Campaign</BaseButton>
                </div>

                <div class="space-y-2">
                    <div v-if="!recentCampaigns.length" class="py-10 text-center text-slate-400">
                        <span class="material-symbols-outlined text-[40px] block mb-2 text-slate-200">campaign</span>
                        <p class="text-sm">No campaigns yet. Create your first one!</p>
                    </div>
                    <div
                        v-for="c in recentCampaigns"
                        :key="c.id"
                        class="flex items-center gap-4 p-3 rounded-xl hover:bg-slate-50 transition-colors cursor-pointer"
                    >
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                             :class="platformBg(c.platform)">
                            <span class="material-symbols-outlined text-[18px]" :class="platformColor(c.platform)">
                                {{ platformIcon(c.platform) }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ c.name }}</p>
                            <p class="text-xs text-slate-400">{{ c.contacts_count }} contacts · {{ c.created_at }}</p>
                        </div>
                        <BaseBadge :variant="statusVariant(c.status)" :dot="true">{{ c.status }}</BaseBadge>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <h2 class="text-sm font-semibold text-slate-800 mb-4">Quick Actions</h2>
                <div class="space-y-2">
                    <Link :href="route('campaigns.create')"
                          class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:border-admin-primary/30 hover:bg-admin-primary/4 transition-all cursor-pointer">
                        <div class="p-2 bg-admin-primary/10 rounded-lg">
                            <span class="material-symbols-outlined text-admin-primary text-[18px]">campaign</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">New Campaign</p>
                            <p class="text-xs text-slate-400">Send to WhatsApp or Telegram</p>
                        </div>
                    </Link>
                    <Link :href="route('contacts.create')"
                          class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:border-emerald-300 hover:bg-emerald-50/50 transition-all cursor-pointer">
                        <div class="p-2 bg-emerald-100 rounded-lg">
                            <span class="material-symbols-outlined text-emerald-600 text-[18px]">person_add</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Add Contact</p>
                            <p class="text-xs text-slate-400">Import or add manually</p>
                        </div>
                    </Link>
                    <Link :href="route('templates.create')"
                          class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:border-violet-300 hover:bg-violet-50/50 transition-all cursor-pointer">
                        <div class="p-2 bg-admin-highlight/15 rounded-lg">
                            <span class="material-symbols-outlined text-admin-highlight text-[18px]">description</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Create Template</p>
                            <p class="text-xs text-slate-400">Reusable message templates</p>
                        </div>
                    </Link>
                    <Link :href="route('inbox')"
                          class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:border-amber-300 hover:bg-amber-50/50 transition-all cursor-pointer">
                        <div class="p-2 bg-amber-100 rounded-lg">
                            <span class="material-symbols-outlined text-amber-600 text-[18px]">forum</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Open Inbox</p>
                            <p class="text-xs text-slate-400">Reply to customer messages</p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseButton  from '@/Components/BaseButton.vue'
import BaseBadge   from '@/Components/BaseBadge.vue'
import StatsCard   from '@/Components/StatsCard.vue'
import { Link }    from '@inertiajs/vue3'

const props = defineProps({
    stats:           { type: Object, default: () => ({ contacts: 0, campaigns: 0, messages: 0, inbox: 0 }) },
    recentCampaigns: { type: Array,  default: () => [] },
})

const platformIcon  = (p) => ({ whatsapp: 'chat', telegram: 'send', both: 'forum' }[p] ?? 'chat')
const platformBg    = (p) => ({ whatsapp: 'bg-emerald-100', telegram: 'bg-blue-100', both: 'bg-violet-100' }[p] ?? 'bg-slate-100')
const platformColor = (p) => ({ whatsapp: 'text-emerald-600', telegram: 'text-blue-600', both: 'text-violet-600' }[p] ?? 'text-slate-500')
const statusVariant = (s) => ({ draft: 'neutral', scheduled: 'info', running: 'warning', completed: 'success', failed: 'danger' }[s] ?? 'neutral')
</script>
