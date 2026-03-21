<template>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700">Tags</h3>
            <button
                class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 transition-colors"
                @click="showForm = true"
            >
                <span class="material-symbols-outlined text-[16px]">add</span>
                New Tag
            </button>
        </div>

        <!-- Inline create/edit form -->
        <div v-if="showForm" class="flex items-center gap-2">
            <input
                ref="nameInput"
                v-model="form.name"
                type="text"
                class="flex-1 rounded-lg border-slate-200 text-sm placeholder:text-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Tag name…"
                maxlength="50"
                @keydown.enter="save"
                @keydown.escape="cancel"
            />
            <input
                v-model="form.color"
                type="color"
                class="h-8 w-8 cursor-pointer rounded border-0 p-0"
            />
            <button
                class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 transition-colors"
                @click="save"
            >
                {{ editingId ? 'Update' : 'Add' }}
            </button>
            <button
                class="rounded-lg px-2 py-1.5 text-xs text-slate-400 hover:text-slate-600 transition-colors"
                @click="cancel"
            >
                Cancel
            </button>
        </div>

        <!-- Tag list -->
        <ul class="space-y-1.5">
            <li
                v-for="tag in tags"
                :key="tag.id"
                class="group flex items-center justify-between rounded-lg px-3 py-2 hover:bg-slate-50 transition-colors"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="inline-block h-2.5 w-2.5 rounded-full"
                        :style="{ backgroundColor: tag.color }"
                    />
                    <span class="text-sm text-slate-700">{{ tag.name }}</span>
                </div>

                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                        class="p-1 rounded hover:bg-slate-200 text-slate-400 hover:text-slate-600"
                        @click="startEdit(tag)"
                        title="Rename"
                    >
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                    </button>
                    <button
                        class="p-1 rounded hover:bg-red-50 text-slate-400 hover:text-red-500"
                        @click="remove(tag)"
                        title="Delete"
                    >
                        <span class="material-symbols-outlined text-[16px]">delete</span>
                    </button>
                </div>
            </li>
        </ul>

        <p v-if="!tags.length" class="text-xs text-slate-400 text-center py-4">
            No tags yet — create one above.
        </p>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import axios from 'axios'

const tags       = ref([])
const showForm   = ref(false)
const editingId  = ref(null)
const nameInput  = ref(null)
const form       = ref({ name: '', color: '#6366f1' })

async function fetchTags() {
    const { data } = await axios.get('/tags')
    tags.value = data
}

async function save() {
    if (!form.value.name.trim()) return

    if (editingId.value) {
        const { data } = await axios.put(`/tags/${editingId.value}`, form.value)
        const idx = tags.value.findIndex(t => t.id === editingId.value)
        if (idx !== -1) tags.value[idx] = data
    } else {
        const { data } = await axios.post('/tags', form.value)
        tags.value.push(data)
    }

    cancel()
}

function startEdit(tag) {
    editingId.value = tag.id
    form.value = { name: tag.name, color: tag.color }
    showForm.value = true
    nextTick(() => nameInput.value?.focus())
}

function cancel() {
    showForm.value = false
    editingId.value = null
    form.value = { name: '', color: '#6366f1' }
}

async function remove(tag) {
    if (!confirm(`Delete tag "${tag.name}"?`)) return
    await axios.delete(`/tags/${tag.id}`)
    tags.value = tags.value.filter(t => t.id !== tag.id)
}

onMounted(fetchTags)
</script>
