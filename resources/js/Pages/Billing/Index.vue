<template>
    <AppLayout title="Billing" subtitle="Manage your subscription and view usage">

        <!-- Flash Messages -->
        <div v-if="flash.success" class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-2 text-sm text-emerald-700">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            {{ flash.success }}
        </div>
        <div v-if="flash.error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl flex items-center gap-2 text-sm text-red-700">
            <span class="material-symbols-outlined text-[18px]">error</span>
            {{ flash.error }}
        </div>

        <!-- Pending (Success) Notification -->
        <div v-if="flash.success && plan !== 'free'" class="mb-6 p-5 bg-gradient-to-r from-emerald-600 to-teal-500 rounded-2xl text-white shadow-lg shadow-emerald-200 flex items-center justify-between">
             <div class="flex items-center gap-4">
                 <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center animate-bounce">
                     <span class="material-symbols-outlined">rocket_launch</span>
                 </div>
                 <div>
                     <p class="text-lg font-bold">Plan Activated!</p>
                     <p class="text-sm opacity-90 font-medium">Your account limits have been updated instantly.</p>
                 </div>
             </div>
        </div>

        <!-- Current Plan & Usage Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-1 card bg-admin-primary border-none text-white relative overflow-hidden flex flex-col justify-between">
                <div class="absolute -right-10 -top-10 opacity-20 rotate-12">
                     <span class="material-symbols-outlined text-[180px]">workspace_premium</span>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.2em] opacity-70 mb-1">Active Plan</h3>
                    <h2 class="text-3xl font-black capitalize">{{ plan }}</h2>
                </div>
                <div class="mt-8 space-y-3">
                    <div class="flex items-center gap-2 text-sm font-medium">
                        <span class="material-symbols-outlined text-[16px]">verified</span>
                        <span>Multi-platform priority</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm font-medium">
                        <span class="material-symbols-outlined text-[16px]">verified</span>
                        <span>24/7 dedicated support</span>
                    </div>
                </div>
            </div>

            <!-- Detailed Usage Progress -->
            <div class="lg:col-span-2 card">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Usage & Quotas</h3>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Resets monthly</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    <!-- Contacts Usage -->
                    <div v-if="usage && usage.contacts">
                        <div class="flex justify-between items-end mb-2">
                             <span class="text-xs font-bold text-slate-600">Contacts</span>
                             <span class="text-[10px] font-black text-slate-400">
                                {{ usage.contacts.current }} / {{ usage.contacts.limit == -1 ? '∞' : usage.contacts.limit }}
                             </span>
                        </div>
                        <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                             <div 
                                class="h-full bg-admin-primary transition-all duration-1000"
                                :style="{ width: (usage.contacts.limit == -1 ? 0 : usage.contacts.percent) + '%' }"
                                :class="{ 'bg-red-500': usage.contacts.percent > 90 }"
                             ></div>
                        </div>
                    </div>
                    <!-- Messages Usage -->
                    <div v-if="usage && usage.messages">
                        <div class="flex justify-between items-end mb-2">
                             <span class="text-xs font-bold text-slate-600">Monthly Messages</span>
                             <span class="text-[10px] font-black text-slate-400">
                                {{ usage.messages.current }} / {{ usage.messages.limit == -1 ? '∞' : usage.messages.limit }}
                             </span>
                        </div>
                        <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                             <div 
                                class="h-full bg-emerald-500 transition-all duration-1000"
                                :style="{ width: (usage.messages.limit == -1 ? 0 : usage.messages.percent) + '%' }"
                                :class="{ 'bg-orange-500': usage.messages.percent > 85 }"
                             ></div>
                        </div>
                    </div>
                    <!-- Campaigns Usage -->
                    <div v-if="usage && usage.campaigns">
                        <div class="flex justify-between items-end mb-2">
                             <span class="text-xs font-bold text-slate-600">Campaigns Created</span>
                             <span class="text-[10px] font-black text-slate-400">
                                {{ usage.campaigns.current }} / {{ usage.campaigns.limit == -1 ? '∞' : usage.campaigns.limit }}
                             </span>
                        </div>
                        <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                             <div 
                                class="h-full bg-violet-500 transition-all duration-1000"
                                :style="{ width: (usage.campaigns.limit == -1 ? 0 : usage.campaigns.percent) + '%' }"
                             ></div>
                        </div>
                    </div>
                    <!-- Support Status -->
                    <div>
                         <div class="flex items-center gap-2 mb-2">
                              <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                              <span class="text-xs font-bold text-slate-600">API Health</span>
                         </div>
                         <p class="text-[10px] text-slate-400 font-medium leading-relaxed">
                            Webhooks and message relays operating at optimal performance (99.9% uptime).
                         </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing Tiers -->
        <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6">Upgrade Your Potential</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">

            <!-- Free -->
            <div class="card border-2 transition-all p-8"
                 :class="plan === 'free' ? 'border-admin-primary/40 ring-4 ring-admin-primary/5' : 'border-transparent opacity-60'">
                <div class="text-center mb-10">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter">{{ planMap.free?.name ?? 'Starter' }}</h3>
                    <p class="text-5xl font-black text-slate-800 mt-4 mb-2">Free</p>
                    <p class="text-xs font-bold text-slate-400 tracking-widest">Everything to get started</p>
                </div>
                <ul class="space-y-4 mb-10">
                    <li v-for="f in (planMap.free?.features ?? freeFeaturesFallback)" :key="f" class="flex items-center gap-3 text-sm font-semibold text-slate-600">
                        <span class="material-symbols-outlined text-emerald-500 font-black">done</span>
                        {{ f }}
                    </li>
                </ul>
                <BaseButton variant="ghost" class="w-full h-12" disabled>
                    {{ plan === 'free' ? 'Current Plan' : 'Downgrade' }}
                </BaseButton>
            </div>

            <!-- Pro -->
            <div class="card border-2 transition-all p-8 relative ring-offset-4 ring-offset-slate-50"
                 :class="plan === 'pro' ? 'border-admin-primary/40 border-2' : 'border-admin-primary/20 hover:border-admin-primary shadow-2xl md:scale-105 z-10 bg-white'">
                <div v-if="plan !== 'pro'" class="absolute -top-4 left-1/2 -translate-x-1/2 whitespace-nowrap">
                    <span class="px-6 py-2 bg-admin-primary text-white text-[10px] font-black rounded-full tracking-[0.2em]">MOST RECOMMENDED</span>
                </div>
                <div class="text-center mb-10">
                    <h3 class="text-lg font-black text-admin-primary uppercase tracking-tighter">{{ planMap.pro?.name ?? 'Professional' }}</h3>
                    <p class="text-5xl font-black text-slate-800 mt-4 mb-2">₹{{ formatPrice(planMap.pro?.price) }}</p>
                    <p class="text-xs font-bold text-slate-400 tracking-widest">PER WORKSPACE / MONTH</p>
                </div>
                <ul class="space-y-4 mb-10">
                    <li v-for="f in (planMap.pro?.features ?? proFeaturesFallback)" :key="f" class="flex items-center gap-3 text-sm font-semibold text-slate-600">
                        <span class="material-symbols-outlined text-admin-primary font-black">done</span>
                        {{ f }}
                    </li>
                </ul>
                <BaseButton 
                    v-if="plan !== 'pro'"
                    @click="handleUpgrade('pro')" 
                    class="w-full h-12 bg-admin-primary text-white font-bold hover:scale-[1.02] transition-transform shadow-lg shadow-admin-primary/20"
                >
                    Upgrade Now
                </BaseButton>
                <BaseButton v-else variant="ghost" class="w-full h-12" disabled>Current Active Plan</BaseButton>
            </div>

            <!-- Enterprise -->
            <div class="card border-2 transition-all p-8"
                 :class="plan === 'enterprise' ? 'border-amber-400/40 ring-4 ring-amber-400/5' : 'border-transparent'">
                <div class="text-center mb-10">
                    <h3 class="text-lg font-black text-amber-600 uppercase tracking-tighter">{{ planMap.enterprise?.name ?? 'Enterprise' }}</h3>
                    <p class="text-5xl font-black text-slate-800 mt-4 mb-2">₹{{ formatPrice(planMap.enterprise?.price) }}</p>
                    <p class="text-xs font-bold text-slate-400 tracking-widest">UNLIMITED SCALE</p>
                </div>
                <ul class="space-y-4 mb-10">
                    <li v-for="f in (planMap.enterprise?.features ?? entFeaturesFallback)" :key="f" class="flex items-center gap-3 text-sm font-semibold text-slate-600">
                        <span class="material-symbols-outlined text-amber-500 font-black">done</span>
                        {{ f }}
                    </li>
                </ul>
                <BaseButton 
                    v-if="plan !== 'enterprise'"
                    @click="handleUpgrade('enterprise')" 
                    variant="outline"
                    class="w-full h-12 border-2 border-slate-200 text-slate-800 font-bold hover:bg-slate-50"
                >
                    Expand Scale
                </BaseButton>
                <BaseButton v-else variant="ghost" class="w-full h-12" disabled>Current Active Plan</BaseButton>
            </div>
        </div>

        <!-- Transaction History -->
        <div v-if="transactions.length" class="card">
            <div class="flex items-center justify-between mb-6">
                 <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Billing History</h2>
                 <span class="text-[10px] font-bold text-slate-400">Download invoices in Dashboard</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="pb-3 text-left font-bold text-slate-400 text-xs uppercase tracking-widest">Date</th>
                            <th class="pb-3 text-left font-bold text-slate-400 text-xs uppercase tracking-widest">Plan</th>
                            <th class="pb-3 text-left font-bold text-slate-400 text-xs uppercase tracking-widest">Method</th>
                            <th class="pb-3 text-left font-bold text-slate-400 text-xs uppercase tracking-widest">Amount</th>
                            <th class="pb-3 text-left font-bold text-slate-400 text-xs uppercase tracking-widest">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="txn in transactions" :key="txn.id" class="border-b border-slate-50 last:border-none">
                            <td class="py-4 text-xs font-bold text-slate-500">{{ formatDate(txn.created_at) }}</td>
                            <td class="py-4 font-black capitalize text-slate-700">{{ txn.plan }}</td>
                            <td class="py-4">
                                <span class="capitalize text-xs font-bold text-slate-600 px-2 py-0.5 bg-slate-100 rounded-md">{{ txn.gateway }}</span>
                            </td>
                            <td class="py-4 text-slate-800 font-black">₹{{ (txn.amount / 100).toLocaleString() }}</td>
                            <td class="py-4">
                                <BaseBadge 
                                    :variant="txn.status === 'completed' ? 'success' : txn.status === 'failed' ? 'danger' : 'warning'" 
                                    :dot="true" 
                                    size="sm"
                                    class="font-bold uppercase tracking-tighter"
                                >
                                    {{ txn.status }}
                                </BaseBadge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>

    <!-- Razorpay Checkout logic (simplified) -->
</template>

<script setup>
import { computed }      from 'vue'
import { router }        from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseButton        from '@/Components/BaseButton.vue'
import BaseBadge         from '@/Components/BaseBadge.vue'
import StatsCard         from '@/Components/StatsCard.vue'

const props = defineProps({
    plan:           { type: String,  default: 'free' },
    plans:          { type: Array,   default: () => [] },
    usage:          { type: Object,  default: () => ({}) },
    transactions:   { type: Array,   default: () => [] },
    gateways:       { type: Object,  default: () => ({}) },
    razorpayKeyId:  { type: String,  default: '' },
    flash:          { type: Object,  default: () => ({}) },
})

const formatDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatPrice = (pricePaise) => {
    if (!pricePaise) return '0'
    return (pricePaise / 100).toLocaleString('en-IN')
}

// Build a slug -> plan lookup from the backend plans
const planMap = computed(() => {
    const map = {}
    props.plans.forEach(p => { map[p.slug] = p })
    return map
})

const handleUpgrade = (selectedPlan) => {
    // Default to Razorpay for now, as it's the primary listed gateway
    router.post(route('billing.checkout'), {
        gateway: 'razorpay',
        plan: selectedPlan
    })
}

// Fallback values used only if plans table is empty
const freeFeaturesFallback = ['100 Contacts', '5 Campaigns', '500 Messages/mo', 'Basic Analytics']
const proFeaturesFallback  = ['5,000 Contacts', '50 Campaigns', '50,000 Messages/mo', 'Collaborative Inbox', 'Canned Responses']
const entFeaturesFallback  = ['Unlimited Contacts', 'Unlimited Messages', 'Direct API Access', 'Custom White-label']
</script>
