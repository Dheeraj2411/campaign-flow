<template>
    <AdminLayout title="Payment Transactions" subtitle="Review and activate pending plan upgrades">

        <DataTable
            :columns="columns"
            :rows="transactions.data"
            :search-keys="['workspace.name', 'gateway', 'plan']"
            :page-size="50"
        >
            <template #cell-workspace="{ row }">
                <span class="text-sm font-medium text-slate-700">{{ row.workspace?.name ?? '—' }}</span>
            </template>

            <template #cell-plan="{ row }">
                <BaseBadge :variant="row.plan === 'pro' ? 'primary' : 'success'" size="sm" class="capitalize">
                    {{ row.plan }}
                </BaseBadge>
            </template>

            <template #cell-gateway="{ row }">
                <BaseBadge :variant="row.gateway === 'razorpay' ? 'info' : 'primary'" size="sm">
                    {{ row.gateway }}
                </BaseBadge>
            </template>

            <template #cell-amount="{ row }">
                <span class="text-sm font-medium text-slate-700">₹{{ (row.amount / 100).toLocaleString() }}</span>
            </template>

            <template #cell-status="{ row }">
                <BaseBadge :variant="row.status === 'completed' ? 'success' : row.status === 'failed' ? 'danger' : 'warning'" :dot="true" size="sm">
                    {{ row.status }}
                </BaseBadge>
            </template>

            <template #cell-created_at="{ row }">
                <span class="text-xs text-slate-400">{{ formatDate(row.created_at) }}</span>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <template v-if="row.status === 'completed' && !row.activated_at">
                        <BaseButton variant="admin" size="sm" @click="confirmActivate(row)">
                            Activate Plan
                        </BaseButton>
                    </template>
                    <template v-else-if="row.activated_at">
                        <BaseBadge variant="success" size="sm">
                            <span class="material-symbols-outlined text-[12px] mr-0.5">check</span>
                            Activated
                        </BaseBadge>
                    </template>
                    <template v-else>
                        <span class="text-xs text-slate-400">—</span>
                    </template>
                </div>
            </template>
        </DataTable>

        <!-- Confirm activate modal -->
        <BaseModal v-model="showConfirm" title="Activate Plan" max-width="sm">
            <p class="text-sm text-slate-600">
                Activate <strong class="capitalize">{{ activateTarget?.plan }}</strong> plan for workspace
                <strong>{{ activateTarget?.workspace?.name }}</strong>?
            </p>
            <p class="text-xs text-slate-400 mt-2">
                Payment of ₹{{ activateTarget ? (activateTarget.amount / 100).toLocaleString() : 0 }}
                via {{ activateTarget?.gateway }} has been verified.
            </p>
            <template #footer>
                <BaseButton variant="ghost" @click="showConfirm = false">Cancel</BaseButton>
                <BaseButton variant="admin" :loading="activating" @click="doActivate">Activate Now</BaseButton>
            </template>
        </BaseModal>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router }   from '@inertiajs/vue3'
import AdminLayout   from '@/Layouts/AdminLayout.vue'
import DataTable     from '@/Components/DataTable.vue'
import BaseButton    from '@/Components/BaseButton.vue'
import BaseBadge     from '@/Components/BaseBadge.vue'
import BaseModal     from '@/Components/BaseModal.vue'

const props = defineProps({
    transactions: { type: Object, default: () => ({ data: [] }) },
})

const columns = [
    { key: 'workspace',  label: 'Workspace' },
    { key: 'plan',       label: 'Plan',      sortable: true },
    { key: 'gateway',    label: 'Gateway' },
    { key: 'amount',     label: 'Amount',    sortable: true },
    { key: 'status',     label: 'Status',    sortable: true },
    { key: 'created_at', label: 'Date',      sortable: true },
]

const showConfirm    = ref(false)
const activateTarget = ref(null)
const activating     = ref(false)

const confirmActivate = (txn) => {
    activateTarget.value = txn
    showConfirm.value = true
}

const doActivate = () => {
    activating.value = true
    router.post(route('admin.transactions.activate', activateTarget.value.id), {}, {
        onFinish: () => { activating.value = false; showConfirm.value = false },
    })
}

const formatDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
