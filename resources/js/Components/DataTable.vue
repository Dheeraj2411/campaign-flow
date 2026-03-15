<template>
    <div class="card p-0 overflow-hidden">

        <!-- Table header: search + actions -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-4 border-b border-slate-100">
            <div class="relative w-full sm:w-64">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search..."
                    class="input pl-9 py-2 text-sm"
                />
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <slot name="actions" />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50">
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide whitespace-nowrap"
                            :class="col.sortable ? 'cursor-pointer hover:text-admin-primary select-none' : ''"
                            @click="col.sortable && sort(col.key)"
                        >
                            <div class="flex items-center gap-1">
                                {{ col.label }}
                                <span v-if="col.sortable" class="material-symbols-outlined text-[14px] text-slate-300"
                                      :class="{ 'text-admin-primary': sortKey === col.key }">
                                    {{ sortKey === col.key && sortDir === 'desc' ? 'arrow_downward' : 'arrow_upward' }}
                                </span>
                            </div>
                        </th>
                        <th v-if="$slots.rowActions" class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!paginatedRows.length">
                        <td :colspan="columns.length + 1" class="px-4 py-12 text-center text-slate-400">
                            <span class="material-symbols-outlined text-[40px] block mb-2 text-slate-200">inbox</span>
                            No records found
                        </td>
                    </tr>
                    <tr
                        v-for="(row, idx) in paginatedRows"
                        :key="row.id ?? idx"
                        class="border-b border-slate-50 hover:bg-slate-50 transition-colors"
                    >
                        <td v-for="col in columns" :key="col.key" class="px-4 py-3 text-slate-700">
                            <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                                <span :class="col.class">{{ row[col.key] ?? '—' }}</span>
                            </slot>
                        </td>
                        <td v-if="$slots.rowActions" class="px-4 py-3 text-right">
                            <slot name="rowActions" :row="row" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between px-4 py-3 border-t border-slate-100">
            <p class="text-xs text-slate-400">
                Showing {{ ((currentPage - 1) * pageSize) + 1 }}–{{ Math.min(currentPage * pageSize, filteredRows.length) }}
                of {{ filteredRows.length }}
            </p>
            <div class="flex items-center gap-1">
                <button
                    class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                    :disabled="currentPage === 1"
                    @click="currentPage--"
                >
                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                </button>
                <span class="text-xs font-medium text-slate-600 px-2">{{ currentPage }} / {{ totalPages }}</span>
                <button
                    class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
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
    columns:  { type: Array, required: true },   // [{ key, label, sortable? }]
    rows:     { type: Array, default: () => [] },
    pageSize: { type: Number, default: 15 },
    searchKeys: { type: Array, default: () => [] }, // which keys to search across
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
