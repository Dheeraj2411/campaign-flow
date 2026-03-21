<template>
    <HeadTitle title="Campaigns" subtitle="Create and manage your messaging campaigns">
        <!-- Stats strip -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 bg-gray-50 rounded-xl">
                        <span class="material-symbols-outlined text-[20px] text-gray-500">all_inclusive</span>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ campaigns.total ?? 0 }}</p>
                <p class="text-sm text-gray-500 mt-1">Total</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 bg-emerald-50 rounded-xl">
                        <span class="material-symbols-outlined text-[20px] text-emerald-600">check_circle</span>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ statusCount('completed') }}</p>
                <p class="text-sm text-gray-500 mt-1">Completed</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 bg-amber-50 rounded-xl">
                        <span class="material-symbols-outlined text-[20px] text-amber-600">pending</span>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ statusCount('scheduled') }}</p>
                <p class="text-sm text-gray-500 mt-1">Scheduled</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 bg-red-50 rounded-xl">
                        <span class="material-symbols-outlined text-[20px] text-red-600">error</span>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ statusCount('failed') }}</p>
                <p class="text-sm text-gray-500 mt-1">Failed</p>
            </div>
        </div>

        <!-- Table -->
        <DataTable
            :columns="columns"
            :rows="campaigns.data ?? []"
            :search-keys="['name']"
        >
            <template #actions>
                <BaseButton variant="primary" size="sm" icon="add" :href="route('campaigns.create')">New Campaign</BaseButton>
            </template>

            <template #cell-name="{ row }">
                <div class="flex items-center gap-3 min-w-0">
                    <div :class="['w-10 h-10 rounded-xl grid place-items-center shrink-0', 
                                 row.platform === 'whatsapp' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600']">
                        <span class="material-symbols-outlined text-[20px]">{{ row.platform === 'whatsapp' ? 'whatsapp' : 'send' }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ row.name }}</p>
                        <p class="text-xs text-gray-500">Launched {{ new Date(row.created_at).toLocaleDateString() }}</p>
                    </div>
                </div>
            </template>

            <template #cell-status="{ row }">
                <BaseBadge :variant="statusVariant(row.status)" :dot="true">
                    {{ row.status }}
                </BaseBadge>
            </template>

            <template #cell-scheduled_at="{ row }">
                <span class="text-sm text-gray-500">{{ row.scheduled_at ? new Date(row.scheduled_at).toLocaleString() : 'Immediate' }}</span>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-2">
                    <Link :href="route('campaigns.show', row.id)"
                          class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-indigo-600 transition-colors">
                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                    </Link>
                    <button v-if="row.status === 'draft' || row.status === 'failed'"
                            class="p-2 rounded-lg hover:bg-red-50 text-gray-500 hover:text-red-500 transition-colors"
                            @click="confirmDelete(row)">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Delete confirm -->
        <BaseModal v-model="showDelete" title="Delete Campaign" max-width="sm">
            <p class="text-sm text-gray-600">
                Delete <strong class="text-gray-900">{{ deleteTarget?.name }}</strong>? This cannot be undone.
            </p>
            <template #footer>
                <BaseButton variant="ghost" @click="showDelete = false">Cancel</BaseButton>
                <BaseButton variant="danger" :loading="deleting" @click="doDelete">Delete</BaseButton>
            </template>
        </BaseModal>
    </HeadTitle>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import HeadTitle from '@/Components/HeadTitle.vue'
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
