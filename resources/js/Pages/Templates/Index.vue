<template>
    <HeadTitle title="Templates" subtitle="Reusable message templates for your campaigns">
        <DataTable :columns="columns" :rows="templates.data ?? []" :search-keys="['name']">
            <template #actions>
                <div class="flex gap-2">
                    <BaseButton variant="ghost" size="sm" icon="sync" :loading="syncing" @click="syncWithMeta">Sync Status</BaseButton>
                    <BaseButton variant="primary" size="sm" icon="add" :href="route('templates.create')">New Template</BaseButton>
                </div>
            </template>

            <template #cell-name="{ row }">
                <div class="flex items-center gap-3 min-w-0">
                    <div :class="['w-10 h-10 rounded-xl grid place-items-center shrink-0', 
                                 row.platform === 'whatsapp' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600']">
                        <span class="material-symbols-outlined text-[18px]">{{ row.platform === 'whatsapp' ? 'whatsapp' : 'send' }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ row.name }}</p>
                        <p class="text-xs text-gray-500">{{ row.category }} · {{ row.language }}</p>
                    </div>
                </div>
            </template>

            <template #cell-status="{ row }">
                <div class="flex flex-col gap-1 items-start">
                    <BaseBadge :variant="row.status === 'APPROVED' ? 'success' : row.status === 'PENDING' ? 'warning' : row.status === 'REJECTED' ? 'danger' : 'neutral'">
                        {{ row.status }}
                    </BaseBadge>
                    <p v-if="row.status === 'REJECTED' && row.reason" class="text-xs text-red-500 max-w-[150px] leading-tight">
                        "{{ row.reason }}"
                    </p>
                </div>
            </template>

            <template #cell-variables="{ row }">
                <div class="flex flex-wrap gap-1">
                    <code v-for="v in (row.variables ?? [])" :key="v"
                          class="text-xs bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded">{{ '{' + v + '}' }}</code>
                </div>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <button v-if="row.status === 'APPROVED'" class="p-2 rounded-lg hover:bg-indigo-50 text-gray-400 hover:text-indigo-600 transition-colors"
                            title="Send Test"
                            @click="openTestModal(row)">
                        <span class="material-symbols-outlined text-[18px]">send</span>
                    </button>
                    <button class="p-2 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-indigo-600 transition-colors"
                            @click="editTemplate(row)">
                        <span class="material-symbols-outlined text-[18px]">edit</span>
                    </button>
                    <button class="p-2 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-500 transition-colors"
                            @click="confirmDelete(row)">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Create / Edit Modal -->
        <BaseModal v-model="showForm" :title="editing ? 'Edit Template' : 'New Template'" max-width="lg">
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700">Template Name *</label>
                        <input v-model="form.name" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" placeholder="Welcome Message" />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700">Platform *</label>
                        <select v-model="form.platform" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white">
                            <option value="whatsapp">WhatsApp</option>
                            <option value="telegram">Telegram</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-sm font-medium text-gray-700">Message Body *</label>
                        <div class="flex gap-1">
                            <button v-for="v in ['name','phone','telegram_username']" :key="v"
                                    class="text-xs px-2.5 py-1 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 transition-colors font-medium"
                                    @click="insertVariable(v)">{{ '{' + v + '}' }}</button>
                        </div>
                    </div>
                    <textarea v-model="form.body" ref="bodyRef" rows="6" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400 resize-none font-mono"
                              placeholder="Hello {name}, thank you for joining us!"></textarea>
                </div>

                <!-- Preview -->
                <div v-if="previewBody" class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                    <p class="text-xs font-medium text-gray-500 mb-2">Preview</p>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ previewBody }}</p>
                </div>
            </div>

            <template #footer>
                <BaseButton variant="ghost" @click="showForm = false">Cancel</BaseButton>
                <BaseButton variant="primary" :loading="form.processing" @click="submit">
                    {{ editing ? 'Update Template' : 'Create Template' }}
                </BaseButton>
            </template>
        </BaseModal>

        <!-- Delete confirm -->
        <BaseModal v-model="showDelete" title="Delete Template" max-width="sm">
            <p class="text-sm text-gray-600">Delete <strong class="text-gray-900">{{ deleteTarget?.name }}</strong>?</p>
            <template #footer>
                <BaseButton variant="ghost" @click="showDelete = false">Cancel</BaseButton>
                <BaseButton variant="danger" :loading="deleting" @click="doDelete">Delete</BaseButton>
            </template>
        </BaseModal>
        
        <!-- Test Template Modal -->
        <BaseModal v-model="showTestModal" title="Send Test Message" max-width="md">
            <div class="space-y-4">
                <p class="text-sm text-gray-600">Send a test of <strong class="text-gray-900">{{ testTarget?.name }}</strong> to verify the content and variables.</p>
                
                <div v-if="testTarget?.platform === 'both'">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Select Platform *</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" v-model="testForm.platform" value="whatsapp" name="test_platform" class="accent-indigo-600" />
                            <span class="text-sm text-gray-700">WhatsApp</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" v-model="testForm.platform" value="telegram" name="test_platform" class="accent-indigo-600" />
                            <span class="text-sm text-gray-700">Telegram</span>
                        </label>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700">
                        {{ testForm.platform === 'whatsapp' ? 'Phone Number (with country code)' : 'Telegram Chat ID / Username' }} *
                    </label>
                    <input v-model="testForm.destination" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" 
                           :placeholder="testForm.platform === 'whatsapp' ? '919876543210' : '1209990650 or @username'" />
                    <p class="text-xs text-gray-400 mt-1">
                        Variables will be replaced with sample data.
                    </p>
                </div>
            </div>

            <template #footer>
                <BaseButton variant="ghost" @click="showTestModal = false">Cancel</BaseButton>
                <BaseButton variant="primary" :loading="testForm.processing" :disabled="!testForm.destination" @click="submitTest">
                    Send Test
                </BaseButton>
            </template>
        </BaseModal>
    </HeadTitle>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import HeadTitle from '@/Components/HeadTitle.vue'
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
