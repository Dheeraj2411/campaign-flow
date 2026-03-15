<template>
    <AppLayout title="Templates" subtitle="Reusable message templates for your campaigns">
        <DataTable :columns="columns" :rows="templates.data ?? []" :search-keys="['name']">
            <template #actions>
                <BaseButton variant="admin" size="sm" icon="add" @click="openCreate">New Template</BaseButton>
            </template>

            <template #cell-name="{ row }">
                <div>
                    <p class="text-sm font-semibold text-slate-800">{{ row.name }}</p>
                    <p class="text-xs text-slate-400 truncate max-w-xs mt-0.5">{{ row.body.substring(0, 80) }}{{ row.body.length > 80 ? '…' : '' }}</p>
                </div>
            </template>

            <template #cell-platform="{ row }">
                <BaseBadge :variant="row.platform === 'whatsapp' ? 'success' : row.platform === 'telegram' ? 'info' : 'primary'">
                    {{ row.platform }}
                </BaseBadge>
            </template>

            <template #cell-variables="{ row }">
                <div class="flex flex-wrap gap-1">
                    <code v-for="v in (row.variables ?? [])" :key="v"
                          class="text-xs bg-admin-primary/10 text-admin-primary px-1.5 py-0.5 rounded">{{ '{' + v + '}' }}</code>
                </div>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <button class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-admin-primary transition-colors"
                            @click="editTemplate(row)">
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                    </button>
                    <button class="p-1.5 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-500 transition-colors"
                            @click="confirmDelete(row)">
                        <span class="material-symbols-outlined text-[16px]">delete</span>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Create / Edit Modal -->
        <BaseModal v-model="showForm" :title="editing ? 'Edit Template' : 'New Template'" max-width="lg">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Template Name *</label>
                        <input v-model="form.name" type="text" class="input" placeholder="Welcome Message" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Platform *</label>
                        <select v-model="form.platform" class="input">
                            <option value="whatsapp">WhatsApp</option>
                            <option value="telegram">Telegram</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-semibold text-slate-600">Message Body *</label>
                        <div class="flex gap-1">
                            <button v-for="v in ['name','phone','telegram_username']" :key="v"
                                    class="text-xs px-2 py-0.5 bg-admin-primary/10 text-admin-primary rounded hover:bg-admin-primary/20 transition-colors"
                                    @click="insertVariable(v)">{{ '{' + v + '}' }}</button>
                        </div>
                    </div>
                    <textarea v-model="form.body" ref="bodyRef" rows="6" class="input resize-none font-mono text-sm"
                              placeholder="Hello {name}, thank you for joining us!"></textarea>
                </div>

                <!-- Preview -->
                <div v-if="previewBody" class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-xs font-semibold text-slate-500 mb-2">Preview</p>
                    <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ previewBody }}</p>
                </div>
            </div>

            <template #footer>
                <BaseButton variant="ghost" @click="showForm = false">Cancel</BaseButton>
                <BaseButton variant="admin" :loading="form.processing" @click="submit">
                    {{ editing ? 'Update Template' : 'Create Template' }}
                </BaseButton>
            </template>
        </BaseModal>

        <!-- Delete confirm -->
        <BaseModal v-model="showDelete" title="Delete Template" max-width="sm">
            <p class="text-sm text-slate-600">Delete <strong>{{ deleteTarget?.name }}</strong>?</p>
            <template #footer>
                <BaseButton variant="ghost" @click="showDelete = false">Cancel</BaseButton>
                <BaseButton variant="danger" :loading="deleting" @click="doDelete">Delete</BaseButton>
            </template>
        </BaseModal>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable   from '@/Components/DataTable.vue'
import BaseButton  from '@/Components/BaseButton.vue'
import BaseBadge   from '@/Components/BaseBadge.vue'
import BaseModal   from '@/Components/BaseModal.vue'

const props = defineProps({ templates: { type: Object, default: () => ({ data: [] }) } })

const columns = [
    { key: 'name',      label: 'Template', sortable: true },
    { key: 'platform',  label: 'Platform' },
    { key: 'variables', label: 'Variables' },
]

const showForm    = ref(false)
const showDelete  = ref(false)
const editing     = ref(false)
const deleting    = ref(false)
const deleteTarget = ref(null)
const bodyRef     = ref(null)

const form = useForm({ id: null, name: '', platform: 'whatsapp', body: '' })

const previewBody = computed(() => {
    if (!form.body) return ''
    return form.body.replace('{name}', 'Jane Smith').replace('{phone}', '+1 555 000 1234').replace('{telegram_username}', '@janesmith')
})

const openCreate = () => {
    editing.value = false
    form.reset()
    showForm.value = true
}

const editTemplate = (row) => {
    editing.value = true
    form.id = row.id; form.name = row.name; form.platform = row.platform; form.body = row.body
    showForm.value = true
}

const insertVariable = (v) => {
    const el = bodyRef.value
    if (!el) { form.body += `{${v}}`; return }
    const start = el.selectionStart, end = el.selectionEnd
    form.body = form.body.slice(0, start) + `{${v}}` + form.body.slice(end)
    el.focus()
}

const submit = () => {
    if (editing.value) {
        form.put(route('templates.update', form.id), { onSuccess: () => { showForm.value = false } })
    } else {
        form.post(route('templates.store'), { onSuccess: () => { showForm.value = false; form.reset() } })
    }
}

const confirmDelete = (row) => { deleteTarget.value = row; showDelete.value = true }
const doDelete = () => {
    deleting.value = true
    router.delete(route('templates.destroy', deleteTarget.value.id), { onFinish: () => { deleting.value = false; showDelete.value = false } })
}
</script>
