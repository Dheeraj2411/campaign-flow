<template>
    <AdminLayout title="Plan Management" subtitle="Manage plan pricing and usage limits">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div v-for="plan in plans" :key="plan.id" 
                 class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col transition-all hover:shadow-md">
                <div class="p-6 border-b border-slate-50 bg-slate-50/50">
                    <div class="flex justify-between items-start mb-4">
                        <BaseBadge :variant="planVariant(plan.slug)" size="lg" class="capitalize">
                            {{ plan.name }}
                        </BaseBadge>
                        <span v-if="plan.is_active" class="flex items-center gap-1 text-[10px] font-bold text-emerald-500 uppercase tracking-widest">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Active
                        </span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-bold text-slate-900">₹{{ plan.price / 100 }}</span>
                        <span class="text-slate-400 text-sm">/month</span>
                    </div>
                </div>

                <div class="p-6 space-y-4 flex-grow">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-3 bg-slate-50 rounded-xl">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Contacts</p>
                            <p class="text-lg font-bold text-slate-700">{{ plan.max_contacts === -1 ? '∞' : plan.max_contacts }}</p>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Campaigns</p>
                            <p class="text-lg font-bold text-slate-700">{{ plan.max_campaigns === -1 ? '∞' : plan.max_campaigns }}</p>
                        </div>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Messages / Mo</p>
                        <p class="text-lg font-bold text-slate-700">{{ plan.max_messages_per_month === -1 ? '∞' : plan.max_messages_per_month }}</p>
                    </div>

                    <div class="pt-4 border-t border-slate-50">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Core Features</p>
                        <ul class="space-y-2">
                            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2 text-sm text-slate-600">
                                <span class="material-symbols-outlined text-emerald-500 text-[18px]">check_circle</span>
                                {{ feature }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="p-6 pt-0 mt-auto">
                    <BaseButton variant="admin" class="w-full" @click="editPlan(plan)">
                        <span class="material-symbols-outlined text-[18px] mr-2">edit</span>
                        Edit Details
                    </BaseButton>
                </div>
            </div>
        </div>

        <!-- Edit Plan Modal -->
        <BaseModal v-model="showEditModal" :title="'Edit ' + (selectedPlan?.name || 'Plan')" max-width="md">
            <form @submit.prevent="savePlan" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Price (INR)</label>
                        <input type="number" v-model="form.price_in_inr" step="0.01" required
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-admin-primary/20 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                        <select v-model="form.is_active" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-admin-primary/20 outline-none transition-all">
                            <option :value="true">Active</option>
                            <option :value="false">Disabled</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Max Contacts</label>
                        <input type="number" v-model="form.max_contacts" required
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-admin-primary/20 outline-none transition-all">
                        <p class="text-[10px] text-slate-400 mt-1">-1 for Unlimited</p>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Max Campaigns</label>
                        <input type="number" v-model="form.max_campaigns" required
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-admin-primary/20 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Max Messages</label>
                        <input type="number" v-model="form.max_messages_per_month" required
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-admin-primary/20 outline-none transition-all">
                    </div>
                </div>

                <div v-if="form.processing" class="pt-2">
                    <p class="text-xs text-admin-primary animate-pulse">Saving changes...</p>
                </div>
            </form>
            <template #footer>
                <BaseButton variant="ghost" @click="showEditModal = false">Cancel</BaseButton>
                <BaseButton variant="admin" :loading="form.processing" @click="savePlan">Save Changes</BaseButton>
            </template>
        </BaseModal>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import BaseBadge from '@/Components/BaseBadge.vue'
import BaseButton from '@/Components/BaseButton.vue'
import BaseModal from '@/Components/BaseModal.vue'

const props = defineProps({
    plans: Array
})

const showEditModal = ref(false)
const selectedPlan = ref(null)

const form = useForm({
    price_in_inr: 0,
    price: 0,
    max_contacts: 0,
    max_campaigns: 0,
    max_messages_per_month: 0,
    is_active: true,
    features: []
})

const planVariant = (slug) => {
    const variants = { free: 'neutral', pro: 'primary', enterprise: 'success' }
    return variants[slug] || 'neutral'
}

const editPlan = (plan) => {
    selectedPlan.value = plan
    form.price_in_inr = plan.price / 100
    form.max_contacts = plan.max_contacts
    form.max_campaigns = plan.max_campaigns
    form.max_messages_per_month = plan.max_messages_per_month
    form.is_active = plan.is_active
    form.features = plan.features
    showEditModal.value = true
}

const savePlan = () => {
    form.price = Math.round(form.price_in_inr * 100)
    form.put(route('admin.plans.update', selectedPlan.value.id), {
        onSuccess: () => {
            showEditModal.value = false
        }
    })
}
</script>
