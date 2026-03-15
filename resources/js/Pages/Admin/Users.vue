<template>
    <AdminLayout title="User Management" subtitle="Manage all registered user accounts">

        <DataTable
            :columns="columns"
            :rows="users.data"
            :search-keys="['name', 'email']"
            :page-size="50"
        >
            <template #cell-name="{ row }">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-admin-primary to-admin-highlight
                                flex items-center justify-center text-white text-xs font-bold shrink-0">
                        {{ row.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ row.name }}</p>
                        <p class="text-xs text-slate-400">{{ row.email }}</p>
                    </div>
                </div>
            </template>

            <template #cell-workspace="{ row }">
                <span class="text-sm text-slate-600">{{ row.active_workspace?.name ?? '—' }}</span>
            </template>

            <template #cell-plan="{ row }">
                <div class="flex items-center gap-2 group">
                    <BaseBadge :variant="planVariant(row.active_workspace?.plan)" size="sm" class="capitalize">
                        {{ row.active_workspace?.plan ?? 'free' }}
                    </BaseBadge>
                    <button v-if="row.active_workspace" @click="openPlanModal(row)"
                            class="opacity-0 group-hover:opacity-100 p-1 text-slate-400 hover:text-admin-primary transition-all rounded-full hover:bg-admin-primary/10">
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                    </button>
                </div>
            </template>

            <template #cell-is_active="{ row }">
                <div class="flex items-center gap-2">
                    <button
                        @click="toggleUser(row)"
                        :disabled="row.id === 1"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-admin-primary/30"
                        :class="row.is_active ? 'bg-emerald-500' : 'bg-slate-300'"
                    >
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                              :class="row.is_active ? 'translate-x-6' : 'translate-x-1'" />
                    </button>
                    <span class="text-xs font-medium" :class="row.is_active ? 'text-emerald-600' : 'text-red-500'">
                        {{ row.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </template>

            <template #cell-created_at="{ row }">
                <span class="text-xs text-slate-400">{{ formatDate(row.created_at) }}</span>
            </template>
        </DataTable>

        <!-- Confirm toggle modal -->
        <BaseModal v-model="showConfirm" :title="confirmTarget?.is_active ? 'Deactivate User' : 'Activate User'" max-width="sm">
            <p class="text-sm text-slate-600">
                Are you sure you want to {{ confirmTarget?.is_active ? 'deactivate' : 'activate' }}
                <strong>{{ confirmTarget?.name }}</strong>?
                <span v-if="confirmTarget?.is_active" class="block mt-1 text-red-500 text-xs">
                    They will be logged out immediately and won't be able to access the platform.
                </span>
            </p>
            <template #footer>
                <BaseButton variant="ghost" @click="showConfirm = false">Cancel</BaseButton>
                <BaseButton :variant="confirmTarget?.is_active ? 'danger' : 'admin'" :loading="toggling" @click="doToggle">
                    {{ confirmTarget?.is_active ? 'Deactivate' : 'Activate' }}
                </BaseButton>
            </template>
        </BaseModal>

        <!-- Change Plan Modal -->
        <BaseModal v-model="showPlanModal" title="Change Workspace Plan" max-width="sm">
            <div class="space-y-4">
                <p class="text-sm text-slate-600">
                    Select a new plan for <strong>{{ planTarget?.name }}</strong>'s workspace ({{ planTarget?.active_workspace?.name }}).
                </p>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Select Plan</label>
                    <select v-model="planForm.plan" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-admin-primary/20 focus:border-admin-primary transition-all outline-none text-sm text-slate-800">
                        <option value="free">Free Tier</option>
                        <option value="pro">Pro Plan</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
            </div>
            <template #footer>
                <BaseButton variant="ghost" @click="showPlanModal = false">Cancel</BaseButton>
                <BaseButton variant="admin" :loading="planForm.processing" @click="submitPlanChange">Save Changes</BaseButton>
            </template>
        </BaseModal>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout   from '@/Layouts/AdminLayout.vue'
import DataTable     from '@/Components/DataTable.vue'
import BaseButton    from '@/Components/BaseButton.vue'
import BaseBadge     from '@/Components/BaseBadge.vue'
import BaseModal     from '@/Components/BaseModal.vue'

const props = defineProps({
    users: { type: Object, default: () => ({ data: [] }) },
})

const columns = [
    { key: 'name',       label: 'User',      sortable: true },
    { key: 'workspace',  label: 'Workspace' },
    { key: 'plan',       label: 'Plan' },
    { key: 'is_active',  label: 'Status' },
    { key: 'created_at', label: 'Joined',    sortable: true },
]

const showConfirm   = ref(false)
const confirmTarget = ref(null)
const toggling      = ref(false)

const planVariant = (p) => ({ free: 'neutral', pro: 'primary', enterprise: 'success' }[p] ?? 'neutral')

const toggleUser = (user) => {
    confirmTarget.value = user
    showConfirm.value = true
}

const doToggle = () => {
    toggling.value = true
    router.post(route('admin.users.toggle', confirmTarget.value.id), {}, {
        onFinish: () => { toggling.value = false; showConfirm.value = false },
    })
}

const showPlanModal = ref(false)
const planTarget    = ref(null)
const planForm      = useForm({ plan: 'free' })

const openPlanModal = (user) => {
    planTarget.value = user
    planForm.plan    = user.active_workspace?.plan ?? 'free'
    showPlanModal.value = true
}

const submitPlanChange = () => {
    planForm.put(route('admin.users.plan', planTarget.value.id), {
        onSuccess: () => { showPlanModal.value = false },
    })
}

const formatDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>
