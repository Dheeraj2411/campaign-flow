<template>
    <AppLayout title="Templates" subtitle="Reusable message templates for your campaigns">
        <DataTable :columns="columns" :rows="templates.data ?? []" :search-keys="['name']">
            <template #actions>
                <div class="flex gap-2">
                    <BaseButton variant="ghost" size="sm" icon="sync" :loading="syncing" @click="syncWithMeta">Sync Status</BaseButton>
                    <BaseButton variant="admin" size="sm" icon="add" :href="route('templates.create')">New Template</BaseButton>
                </div>
            </template>

            <template #cell-name="{ row }">
                <div class="flex items-center gap-3 min-w-0">
                    <div :class="['w-9 h-9 rounded-xl grid place-items-center shadow-sm border border-slate-50 shrink-0 overflow-hidden aspect-square', 
                                 row.platform === 'whatsapp' ? 'bg-green-50 text-green-600' : 'bg-blue-50 text-blue-600']">
                        <span class="material-symbols-outlined text-[18px]">{{ row.platform === 'whatsapp' ? 'whatsapp' : 'send' }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ row.name }}</p>
                        <p class="text-[10px] text-slate-400 uppercase tracking-tight">{{ row.category }} • {{ row.language }}</p>
                    </div>
                </div>
            </template>

            <template #cell-status="{ row }">
                <div class="flex flex-col gap-1 items-start">
                    <BaseBadge :variant="row.status === 'APPROVED' ? 'success' : row.status === 'PENDING' ? 'warning' : row.status === 'REJECTED' ? 'danger' : 'ghost'">
                        {{ row.status }}
                    </BaseBadge>
                    <p v-if="row.status === 'REJECTED' && row.reason" class="text-[10px] text-red-500 max-w-[150px] leading-tight italic">
                        "{{ row.reason }}"
                    </p>
                </div>
            </template>

            <template #cell-variables="{ row }">
                <div class="flex flex-wrap gap-1">
                    <code v-for="v in (row.variables ?? [])" :key="v"
                          class="text-xs bg-admin-primary/10 text-admin-primary px-1.5 py-0.5 rounded">{{ '{' + v + '}' }}</code>
                </div>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <button v-if="row.status === 'APPROVED'" class="p-1.5 rounded-lg hover:bg-admin-primary/10 text-slate-400 hover:text-admin-primary transition-colors"
                            title="Send Test"
                            @click="openTestModal(row)">
                        <span class="material-symbols-outlined text-[16px]">send</span>
                    </button>
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
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
        
        <!-- Test Template Modal -->
        <BaseModal v-model="showTestModal" title="Send Test Message" max-width="md">
            <div class="space-y-4">
                <p class="text-sm text-slate-600">Send a test of <strong>{{ testTarget?.name }}</strong> to verify the content and variables.</p>
                
                <div v-if="testTarget?.platform === 'both'">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Select Platform *</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" v-model="testForm.platform" value="whatsapp" name="test_platform" class="accent-admin-primary" />
                            <span class="text-sm text-slate-700">WhatsApp</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" v-model="testForm.platform" value="telegram" name="test_platform" class="accent-admin-primary" />
                            <span class="text-sm text-slate-700">Telegram</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                        {{ testForm.platform === 'whatsapp' ? 'Phone Number (with country code)' : 'Telegram Chat ID / Username' }} *
                    </label>
                    <input v-model="testForm.destination" type="text" class="input" 
                           :placeholder="testForm.platform === 'whatsapp' ? '919876543210' : '1209990650 or @username'" />
                    <p class="text-[11px] text-slate-400 mt-1">
                        Variables will be replaced with sample data.
                    </p>
                </div>
            </div>

            <template #footer>
                <BaseButton variant="ghost" @click="showTestModal = false">Cancel</BaseButton>
                <BaseButton variant="admin" :loading="testForm.processing" :disabled="!testForm.destination" @click="submitTest">
                    Send Test
                </BaseButton>
            </template>
        </BaseModal>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable   from '@/Components/DataTable.vue'
import BaseButton  from '@/Components/BaseButton.vue'
import BaseBadge   from '@/Components/BaseBadge.vue'
import BaseModal   from '@/Components/BaseModal.vue'

const props = defineProps({ templates: { type: Object, default: () => ({ data: [] }) } })

const columns = [
    { key: 'name',      label: 'Template', sortable: true },
    { key: 'status',    label: 'Status' },
    { key: 'variables', label: 'Variables' },
]

const showForm    = ref(false)
const showDelete  = ref(false)
const editing     = ref(false)
const deleting    = ref(false)
const syncing     = ref(false)
const deleteTarget = ref(null)
const bodyRef     = ref(null)
const showTestModal = ref(false)
const testTarget    = ref(null)

const form = useForm({ id: null, name: '', platform: 'whatsapp', body: '' })
const testForm = useForm({ platform: 'whatsapp', destination: '' })

const previewBody = computed(() => {
    if (!form.body) return ''
    return form.body.replace('{name}', 'Jane Smith').replace('{phone}', '+1 555 000 1234').replace('{telegram_username}', '@janesmith')
})

const openCreate = () => {
    editing.value = false
    form.reset()
    showForm.value = true
}

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('create')) {
        openCreate();
        // optionally clean up the URL to remove the query parameter
        window.history.replaceState({}, '', route('templates.index'));
    }
})

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

const openTestModal = (row) => {
    testTarget.value = row
    testForm.platform = row.platform === 'both' ? 'whatsapp' : row.platform
    testForm.destination = ''
    showTestModal.value = true
}

const submitTest = () => {
    testForm.post(route('templates.test', testTarget.value.id), {
        onSuccess: () => {
            showTestModal.value = false
        }
    })
}

const syncWithMeta = () => {
    syncing.value = true
    router.post(route('templates.sync'), {}, {
        onFinish: () => syncing.value = false
    })
}
</script>
