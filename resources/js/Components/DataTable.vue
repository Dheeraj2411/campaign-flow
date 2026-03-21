<template>
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

        <!-- Table header: search + actions -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-4 border-b border-gray-100">
            <div class="relative w-full sm:w-64">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]">search</span>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search..."
                    class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400"
                />
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <slot name="actions" />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap"
                            :class="col.sortable ? 'cursor-pointer hover:text-indigo-600 select-none' : ''"
                            @click="col.sortable && sort(col.key)"
                        >
                            <div class="flex items-center gap-1">
                                {{ col.label }}
                                <span v-if="col.sortable" class="material-symbols-outlined text-[14px] text-gray-300"
                                      :class="{ 'text-indigo-600': sortKey === col.key }">
                                    {{ sortKey === col.key && sortDir === 'desc' ? 'arrow_downward' : 'arrow_upward' }}
                                </span>
                            </div>
                        </th>
                        <th v-if="$slots.rowActions" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="!paginatedRows.length">
                        <td :colspan="columns.length + 1" class="px-6 py-16 text-center">
                            <span class="material-symbols-outlined text-[48px] block mb-3 text-gray-300">inbox</span>
                            <h3 class="text-lg font-semibold text-gray-900">No items yet</h3>
                            <p class="text-sm text-gray-500 mt-1">Get started by creating your first one</p>
                        </td>
                    </tr>
                    <tr
                        v-for="(row, idx) in paginatedRows"
                        :key="row.id ?? idx"
                        class="hover:bg-gray-50 transition-colors duration-150"
                    >
                        <td v-for="col in columns" :key="col.key" class="px-6 py-4 text-sm text-gray-900">
                            <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                                <span :class="col.class">{{ row[col.key] ?? '—' }}</span>
                            </slot>
                        </td>
                        <td v-if="$slots.rowActions" class="px-6 py-4 text-right">
                            <slot name="rowActions" :row="row" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between px-6 py-3 border-t border-gray-100">
            <p class="text-xs text-gray-500">
                Showing {{ ((currentPage - 1) * pageSize) + 1 }}–{{ Math.min(currentPage * pageSize, filteredRows.length) }}
                of {{ filteredRows.length }}
            </p>
            <div class="flex items-center gap-1">
                <button
                    class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                    :disabled="currentPage === 1"
                    @click="currentPage--"
                >
                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                </button>
                <span class="text-xs font-medium text-gray-600 px-2">{{ currentPage }} / {{ totalPages }}</span>
                <button
                    class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                    :disabled="currentPage === totalPages"
                    @click="currentPage++"
                >
                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    columns:  { type: Array, required: true },
    rows:     { type: Array, default: () => [] },
    pageSize: { type: Number, default: 15 },
    searchKeys: { type: Array, default: () => [] },
})

const searchQuery = ref('')
const currentPage = ref(1)
const sortKey     = ref(null)
const sortDir     = ref('asc')

const filteredRows = computed(() => {
    let data = [...props.rows]
    if (searchQuery.value && props.searchKeys.length) {
        const q = searchQuery.value.toLowerCase()
        data = data.filter(row =>
            props.searchKeys.some(k => String(row[k] ?? '').toLowerCase().includes(q))
        )
    }
    if (sortKey.value) {
        data.sort((a, b) => {
            const av = a[sortKey.value] ?? '', bv = b[sortKey.value] ?? ''
            return sortDir.value === 'asc'
                ? String(av).localeCompare(String(bv))
                : String(bv).localeCompare(String(av))
        })
    }
    return data
})

const totalPages   = computed(() => Math.max(1, Math.ceil(filteredRows.value.length / props.pageSize)))
const paginatedRows = computed(() => {
    const start = (currentPage.value - 1) * props.pageSize
    return filteredRows.value.slice(start, start + props.pageSize)
})

const sort = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortDir.value = 'asc'
    }
    currentPage.value = 1
}
</script>
