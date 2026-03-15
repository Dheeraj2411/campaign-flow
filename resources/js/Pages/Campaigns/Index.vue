<template>
    <AppLayout title="Campaigns" subtitle="Create and manage your messaging campaigns">

        <!-- Stats strip -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <div class="card py-3 px-4 flex items-center gap-3">
                <span class="material-symbols-outlined text-[20px] text-slate-400">all_inclusive</span>
                <div><p class="text-xs text-slate-400">Total</p><p class="text-lg font-black text-slate-800">{{ campaigns.total ?? 0 }}</p></div>
            </div>
            <div class="card py-3 px-4 flex items-center gap-3">
                <span class="material-symbols-outlined text-[20px] text-emerald-500">check_circle</span>
                <div><p class="text-xs text-slate-400">Completed</p><p class="text-lg font-black text-emerald-700">{{ statusCount('completed') }}</p></div>
            </div>
            <div class="card py-3 px-4 flex items-center gap-3">
                <span class="material-symbols-outlined text-[20px] text-amber-500">pending</span>
                <div><p class="text-xs text-slate-400">Scheduled</p><p class="text-lg font-black text-amber-700">{{ statusCount('scheduled') }}</p></div>
            </div>
            <div class="card py-3 px-4 flex items-center gap-3">
                <span class="material-symbols-outlined text-[20px] text-red-400">error</span>
                <div><p class="text-xs text-slate-400">Failed</p><p class="text-lg font-black text-red-700">{{ statusCount('failed') }}</p></div>
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
                <div>
                    <p class="text-sm font-semibold text-slate-800">{{ row.name }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ row.contacts_count ?? 0 }} contacts</p>
                </div>
            </template>

            <template #cell-platform="{ row }">
                <div class="flex items-center gap-1.5">
                    <span class="w-5 h-5 rounded flex items-center justify-center shrink-0"
                          :class="row.platform === 'whatsapp' ? 'bg-emerald-100' : row.platform === 'telegram' ? 'bg-blue-100' : 'bg-violet-100'">
                        <span class="material-symbols-outlined text-[12px]"
                              :class="row.platform === 'whatsapp' ? 'text-emerald-600' : row.platform === 'telegram' ? 'text-blue-600' : 'text-violet-600'">
                            {{ row.platform === 'whatsapp' ? 'chat' : row.platform === 'telegram' ? 'send' : 'forum' }}
                        </span>
                    </span>
                    <span class="text-xs capitalize text-slate-600">{{ row.platform }}</span>
                </div>
            </template>

            <template #cell-status="{ row }">
                <BaseBadge :variant="statusVariant(row.status)" :dot="true">{{ row.status }}</BaseBadge>
            </template>

            <template #cell-scheduled_at="{ row }">
                <span class="text-xs text-slate-400">{{ row.scheduled_at ?? 'Immediate' }}</span>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <Link :href="route('campaigns.show', row.id)"
                          class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-admin-primary transition-colors">
                        <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                    </Link>
                    <button v-if="row.status === 'draft'"
                            class="p-1.5 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-500 transition-colors"
                            @click="confirmDelete(row)">
                        <span class="material-symbols-outlined text-[16px]">delete</span>
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
