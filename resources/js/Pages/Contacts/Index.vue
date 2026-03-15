<template>
    <AppLayout title="Contacts" subtitle="Manage your customer contact list">

        <DataTable
            :columns="columns"
            :rows="contacts.data"
            :search-keys="['name','phone','telegram_username']"
            :page-size="20"
        >
            <!-- Header actions -->
            <template #actions>
                <BaseButton variant="white" size="sm" icon="upload_file" :href="route('contacts.import')">Import CSV</BaseButton>
                <BaseButton variant="admin" size="sm" icon="add" @click="showCreate = true">Add Contact</BaseButton>
            </template>

            <!-- Custom cells -->
            <template #cell-name="{ row }">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-admin-primary to-admin-highlight
                                flex items-center justify-center text-white text-xs font-bold shrink-0">
                        {{ row.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ row.name }}</p>
                        <p v-if="row.telegram_username" class="text-xs text-slate-400">@{{ row.telegram_username }}</p>
                    </div>
                </div>
            </template>

            <template #cell-tags="{ row }">
                <div class="flex flex-wrap gap-1">
                    <BaseBadge v-for="tag in (row.tags ?? [])" :key="tag" variant="primary">{{ tag }}</BaseBadge>
                </div>
            </template>

            <template #cell-created_at="{ row }">
                <span class="text-xs text-slate-400">{{ row.created_at }}</span>
            </template>

            <!-- Row actions -->
            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <button class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-admin-primary transition-colors"
                            @click="editContact(row)">
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
        <BaseModal v-model="showCreate" :title="editing ? 'Edit Contact' : 'Add Contact'" max-width="md">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Full Name *</label>
                    <input v-model="form.name" type="text" class="input" placeholder="John Doe" required />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Phone Number *</label>
                    <input v-model="form.phone" type="tel" class="input" placeholder="+1 234 567 8900" required />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Telegram Username</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">@</span>
                        <input v-model="form.telegram_username" type="text" class="input pl-7" placeholder="username" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tags</label>
                    <input v-model="tagsInput" type="text" class="input" placeholder="vip, new-customer (comma-separated)" />
                </div>
            </form>
            <template #footer>
                <BaseButton variant="ghost" @click="showCreate = false">Cancel</BaseButton>
                <BaseButton variant="admin" :loading="form.processing" @click="submit">
                    {{ editing ? 'Update' : 'Add Contact' }}
                </BaseButton>
            </template>
        </BaseModal>

        <!-- Delete confirm modal -->
        <BaseModal v-model="showDelete" title="Delete Contact" max-width="sm">
            <p class="text-sm text-slate-600">
                Are you sure you want to delete <strong>{{ deleteTarget?.name }}</strong>?
                This action cannot be undone.
            </p>
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

const props = defineProps({
    contacts: { type: Object, default: () => ({ data: [] }) },
})

const columns = [
    { key: 'name',     label: 'Name',     sortable: true },
    { key: 'phone',    label: 'Phone',    sortable: true },
    { key: 'tags',     label: 'Tags' },
    { key: 'created_at', label: 'Added',  sortable: true },
]

const showCreate  = ref(false)
const showDelete  = ref(false)
const editing     = ref(false)
const deleting    = ref(false)
const deleteTarget = ref(null)

const form = useForm({
    id:                 null,
    name:               '',
    phone:              '',
    telegram_username:  '',
    tags:               [],
})

const tagsInput = computed({
    get: () => form.tags.join(', '),
    set: (v) => { form.tags = v.split(',').map(t => t.trim()).filter(Boolean) },
})

const editContact = (row) => {
    editing.value = true
    form.id = row.id
    form.name = row.name
    form.phone = row.phone
    form.telegram_username = row.telegram_username ?? ''
    form.tags = row.tags ?? []
    showCreate.value = true
}

const submit = () => {
    if (editing.value) {
        form.put(route('contacts.update', form.id), { onSuccess: () => { showCreate.value = false; form.reset() } })
    } else {
        form.post(route('contacts.store'), { onSuccess: () => { showCreate.value = false; form.reset() } })
    }
}

const confirmDelete = (row) => { deleteTarget.value = row; showDelete.value = true }
const doDelete = () => {
    deleting.value = true
    router.delete(route('contacts.destroy', deleteTarget.value.id), {
        onFinish: () => { deleting.value = false; showDelete.value = false }
    })
}
</script>
