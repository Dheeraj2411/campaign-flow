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

        <!-- Pending Activation Banner -->
        <div v-if="hasPendingActivation" class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
            <span class="material-symbols-outlined text-amber-500 text-[20px] mt-0.5">hourglass_top</span>
            <div>
                <p class="text-sm font-medium text-amber-800">Payment received — awaiting admin activation</p>
                <p class="text-xs text-amber-600 mt-0.5">Your plan upgrade will be activated by the administrator shortly.</p>
            </div>
        </div>

        <!-- Current Plan -->
        <div class="card mb-6 border-2 border-admin-primary/20 bg-gradient-to-br from-admin-primary/5 to-transparent">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-admin-primary/10 rounded-xl">
                        <span class="material-symbols-outlined text-admin-primary text-[24px]"
                              style="font-variation-settings: 'FILL' 1">workspace_premium</span>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Current Plan</p>
                        <h2 class="text-xl font-black text-slate-800 capitalize">{{ plan }}</h2>
                    </div>
                </div>
                <BaseBadge v-if="plan === 'free'" variant="neutral">Free Tier</BaseBadge>
                <BaseBadge v-else-if="plan === 'pro'" variant="primary">Pro</BaseBadge>
                <BaseBadge v-else variant="success">Enterprise</BaseBadge>
            </div>
        </div>

        <!-- Usage Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
            <StatsCard label="Contacts"      :value="usage.contacts"      icon="contacts"  color="primary" />
            <StatsCard label="Campaigns"      :value="usage.campaigns"     icon="campaign"  color="violet"  />
            <StatsCard label="Messages Sent"  :value="usage.messages"      icon="send"      color="success" />
            <StatsCard label="Conversations"  :value="usage.conversations" icon="forum"     color="warning" />
        </div>

        <!-- Pricing Tiers -->
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Available Plans</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

            <!-- Free -->
            <div class="card border-2 transition-all"
                 :class="plan === 'free' ? 'border-admin-primary/30 ring-2 ring-admin-primary/10' : 'border-transparent hover:border-slate-200'">
                <div class="text-center mb-6">
                    <div class="w-12 h-12 mx-auto bg-slate-100 rounded-xl flex items-center justify-center mb-3">
                        <span class="material-symbols-outlined text-slate-500 text-[24px]">rocket_launch</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Free</h3>
                    <p class="text-3xl font-black text-slate-800 mt-2">₹0<span class="text-sm font-medium text-slate-400">/mo</span></p>
                </div>
                <ul class="space-y-2.5 mb-6">
                    <li v-for="f in freePlanFeatures" :key="f" class="flex items-center gap-2 text-sm text-slate-600">
                        <span class="material-symbols-outlined text-emerald-500 text-[16px]">check_circle</span>
                        {{ f }}
                    </li>
                </ul>
                <BaseButton v-if="plan === 'free'" variant="ghost" class="w-full" disabled>Current Plan</BaseButton>
                <BaseButton v-else variant="outline" class="w-full" disabled>Downgrade</BaseButton>
            </div>

            <!-- Pro -->
            <div class="card border-2 transition-all relative"
                 :class="plan === 'pro' ? 'border-admin-primary/30 ring-2 ring-admin-primary/10' : 'border-admin-primary/20 hover:border-admin-primary/40 shadow-lg'">
                <div v-if="plan !== 'pro'" class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <span class="px-3 py-1 bg-admin-primary text-white text-xs font-bold rounded-full">POPULAR</span>
                </div>
                <div class="text-center mb-6">
                    <div class="w-12 h-12 mx-auto bg-admin-primary/10 rounded-xl flex items-center justify-center mb-3">
                        <span class="material-symbols-outlined text-admin-primary text-[24px]"
                              style="font-variation-settings: 'FILL' 1">diamond</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Pro</h3>
                    <p class="text-3xl font-black text-slate-800 mt-2">₹2,900<span class="text-sm font-medium text-slate-400">/mo</span></p>
                </div>
                <ul class="space-y-2.5 mb-6">
                    <li v-for="f in proPlanFeatures" :key="f" class="flex items-center gap-2 text-sm text-slate-600">
                        <span class="material-symbols-outlined text-admin-primary text-[16px]">check_circle</span>
                        {{ f }}
                    </li>
                </ul>
                <BaseButton v-if="plan === 'pro'" variant="ghost" class="w-full" disabled>Current Plan</BaseButton>
                <div v-else class="text-center p-2.5 rounded-lg bg-orange-50 border border-orange-200">
                    <p class="text-xs font-semibold text-orange-700">Contact admin to upgrade</p>
                </div>
            </div>

            <!-- Enterprise -->
            <div class="card border-2 transition-all"
                 :class="plan === 'enterprise' ? 'border-admin-primary/30 ring-2 ring-admin-primary/10' : 'border-transparent hover:border-slate-200'">
                <div class="text-center mb-6">
                    <div class="w-12 h-12 mx-auto bg-amber-100 rounded-xl flex items-center justify-center mb-3">
                        <span class="material-symbols-outlined text-amber-600 text-[24px]"
                              style="font-variation-settings: 'FILL' 1">shield</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Enterprise</h3>
                    <p class="text-3xl font-black text-slate-800 mt-2">₹9,900<span class="text-sm font-medium text-slate-400">/mo</span></p>
                </div>
                <ul class="space-y-2.5 mb-6">
                    <li v-for="f in enterprisePlanFeatures" :key="f" class="flex items-center gap-2 text-sm text-slate-600">
                        <span class="material-symbols-outlined text-amber-500 text-[16px]">check_circle</span>
                        {{ f }}
                    </li>
                </ul>
                <BaseButton v-if="plan === 'enterprise'" variant="ghost" class="w-full" disabled>Current Plan</BaseButton>
                <div v-else class="text-center p-2.5 rounded-lg bg-orange-50 border border-orange-200">
                    <p class="text-xs font-semibold text-orange-700">Contact admin to upgrade</p>
                </div>
            </div>
        </div>

        <!-- Transaction History -->
        <div v-if="transactions.length" class="card">
            <h2 class="text-sm font-semibold text-slate-800 mb-4">Payment History</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Date</th>
                            <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Plan</th>
                            <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Gateway</th>
                            <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Amount</th>
                            <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Payment Status</th>
                            <th class="pb-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Activation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="txn in transactions" :key="txn.id" class="border-b border-slate-50">
                            <td class="py-3 text-xs text-slate-500">{{ formatDate(txn.created_at) }}</td>
                            <td class="py-3 font-medium capitalize text-slate-700">{{ txn.plan }}</td>
                            <td class="py-3">
                                <BaseBadge :variant="txn.gateway === 'razorpay' ? 'info' : 'primary'" size="sm">{{ txn.gateway }}</BaseBadge>
                            </td>
                            <td class="py-3 text-slate-700">₹{{ (txn.amount / 100).toLocaleString() }}</td>
                            <td class="py-3">
                                <BaseBadge :variant="txn.status === 'completed' ? 'success' : txn.status === 'failed' ? 'danger' : 'warning'" :dot="true" size="sm">
                                    {{ txn.status }}
                                </BaseBadge>
                            </td>
                            <td class="py-3">
                                <BaseBadge v-if="txn.activated_at" variant="success" size="sm">Activated</BaseBadge>
                                <BaseBadge v-else-if="txn.status === 'completed'" variant="warning" size="sm">Pending</BaseBadge>
                                <span v-else class="text-xs text-slate-400">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed }      from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseButton        from '@/Components/BaseButton.vue'
import BaseBadge         from '@/Components/BaseBadge.vue'
import StatsCard         from '@/Components/StatsCard.vue'

const props = defineProps({
    plan:           { type: String,  default: 'free' },
    usage:          { type: Object,  default: () => ({}) },
    transactions:   { type: Array,   default: () => [] },
    gateways:       { type: Object,  default: () => ({}) },
    razorpayKeyId:  { type: String,  default: '' },
    flash:          { type: Object,  default: () => ({}) },
})

const hasPendingActivation = computed(() =>
    props.transactions.some(t => t.status === 'completed' && !t.activated_at)
)

const formatDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' })
}

const freePlanFeatures = [
    '500 contacts', '3 campaigns / month', '1,000 messages / month',
    'WhatsApp integration', 'Basic analytics',
]
const proPlanFeatures = [
    '10,000 contacts', 'Unlimited campaigns', '50,000 messages / month',
    'WhatsApp + Telegram', 'Advanced analytics', 'Priority support', 'CSV import',
]
const enterprisePlanFeatures = [
    'Unlimited contacts', 'Unlimited campaigns', 'Unlimited messages',
    'All integrations', 'Custom analytics', 'Dedicated support', 'API access', 'SSO / SAML',
]
</script>
