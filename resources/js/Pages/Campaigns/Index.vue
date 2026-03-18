<template>
    <AppLayout title="Campaigns" subtitle="Create and manage your messaging campaigns">
        <!-- Stats strip -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                <div class="flex items-center gap-3 mb-2">
                    <span class="material-symbols-outlined text-[20px] text-slate-400 bg-slate-50 p-2 rounded-lg">all_inclusive</span>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total</span>
                </div>
                <p class="text-2xl font-black text-slate-800">{{ campaigns.total ?? 0 }}</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                <div class="flex items-center gap-3 mb-2">
                    <span class="material-symbols-outlined text-[20px] text-emerald-500 bg-emerald-50 p-2 rounded-lg">check_circle</span>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Completed</span>
                </div>
                <p class="text-2xl font-black text-emerald-700">{{ statusCount('completed') }}</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                <div class="flex items-center gap-3 mb-2">
                    <span class="material-symbols-outlined text-[20px] text-amber-500 bg-amber-50 p-2 rounded-lg">pending</span>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Scheduled</span>
                </div>
                <p class="text-2xl font-black text-amber-700">{{ statusCount('scheduled') }}</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                <div class="flex items-center gap-3 mb-2">
                    <span class="material-symbols-outlined text-[20px] text-red-500 bg-red-50 p-2 rounded-lg">error</span>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Failed</span>
                </div>
                <p class="text-2xl font-black text-red-700">{{ statusCount('failed') }}</p>
            </div>
        </div>

        <!-- Table -->
        <DataTable
            :columns="columns"
            :rows="campaigns.data ?? []"
            :search-keys="['name']"
        >
            <template #actions>
                <BaseButton variant="admin" size="sm" icon="add" :href="route('campaigns.create')">New Campaign</BaseButton>
            </template>

            <template #cell-name="{ row }">
                <div class="flex items-center gap-3 min-w-0">
                    <div :class="['w-10 h-10 rounded-xl grid place-items-center shadow-sm border border-slate-50 shrink-0 overflow-hidden aspect-square', 
                                 row.platform === 'whatsapp' ? 'bg-green-50 text-green-600' : 'bg-blue-50 text-blue-600']">
                        <span class="material-symbols-outlined text-[20px]">{{ row.platform === 'whatsapp' ? 'whatsapp' : 'send' }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800">{{ row.name }}</p>
                        <p class="text-[10px] text-slate-400 uppercase tracking-tight">Launched on {{ new Date(row.created_at).toLocaleDateString() }}</p>
                    </div>
                </div>
            </template>

            <template #cell-status="{ row }">
                <BaseBadge :variant="statusVariant(row.status)" :dot="true" class="font-bold uppercase text-[10px] tracking-widest px-3 py-1">
                    {{ row.status }}
                </BaseBadge>
            </template>

            <template #cell-scheduled_at="{ row }">
                <span class="text-xs font-medium text-slate-500">{{ row.scheduled_at ? new Date(row.scheduled_at).toLocaleString() : 'Immediate' }}</span>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-2">
                    <Link :href="route('campaigns.show', row.id)"
                          class="p-2 rounded-xl bg-slate-50 hover:bg-admin-primary/10 text-slate-400 hover:text-admin-primary transition-all shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                    </Link>
                    <button v-if="row.status === 'draft' || row.status === 'failed'"
                            class="p-2 rounded-xl bg-slate-50 hover:bg-red-50 text-slate-400 hover:text-red-500 transition-all shadow-sm"
                            @click="confirmDelete(row)">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Delete confirm -->
        <BaseModal v-model="showDelete" title="Delete Campaign" max-width="sm">
            <p class="text-sm text-slate-600">
                Delete <strong>{{ deleteTarget?.name }}</strong>? This cannot be undone.
            </p>
            <template #footer>
                <BaseButton variant="ghost" @click="showDelete = false">Cancel</BaseButton>
                <BaseButton variant="danger" :loading="deleting" @click="doDelete">Delete</BaseButton>
            </template>
        </BaseModal>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable   from '@/Components/DataTable.vue'
import BaseButton  from '@/Components/BaseButton.vue'
import BaseBadge   from '@/Components/BaseBadge.vue'
import BaseModal   from '@/Components/BaseModal.vue'

const props = defineProps({
    campaigns: { type: Object, default: () => ({ data: [], total: 0 }) },
})

const columns = [
    { key: 'name',         label: 'Campaign',    sortable: true },
    { key: 'platform',     label: 'Platform' },
    { key: 'status',       label: 'Status',      sortable: true },
    { key: 'scheduled_at', label: 'Scheduled',   sortable: true },
]

const showDelete   = ref(false)
const deleteTarget = ref(null)
const deleting     = ref(false)

const statusCount   = (s) => (props.campaigns.data ?? []).filter(c => c.status === s).length
const statusVariant = (s) => ({ draft: 'neutral', scheduled: 'info', running: 'warning', completed: 'success', failed: 'danger' }[s] ?? 'neutral')

const confirmDelete = (row) => { deleteTarget.value = row; showDelete.value = true }
const doDelete = () => {
    deleting.value = true
    router.delete(route('campaigns.destroy', deleteTarget.value.id), {
        onFinish: () => { deleting.value = false; showDelete.value = false }
    })
}
</script>
