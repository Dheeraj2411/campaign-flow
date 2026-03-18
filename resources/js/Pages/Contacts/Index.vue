<script setup>
import { ref, watch } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/BaseCard.vue';
import BaseButton from '@/Components/BaseButton.vue';
import DataTable from '@/Components/DataTable.vue';
import ImportModal from '@/Components/Contacts/ImportModal.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    contacts: Object,
    filters: Object,
    allTags: Array,
});

const search = ref(props.filters?.search || '');
const selectedTag = ref(props.filters?.tag || '');
const showImportModal = ref(false);
const showCreateModal = ref(false);

const createForm = useForm({
    name: '',
    phone: '',
    telegram_username: '',
    tags: [],
});

const submitCreate = () => {
    createForm.post(route('contacts.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
};

const showEditModal = ref(false);
const editingContactId = ref(null);
const editForm = useForm({
    name: '',
    phone: '',
    telegram_username: '',
    tags: [],
});

const openEditModal = (contact) => {
    editingContactId.value = contact.id;
    editForm.name = contact.name;
    editForm.phone = contact.phone;
    editForm.telegram_username = contact.telegram_username || '';
    editForm.tags = contact.tags || [];
    showEditModal.value = true;
};

const submitEdit = () => {
    editForm.put(route('contacts.update', editingContactId.value), {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
        }
    });
};

const columns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'phone', label: 'Phone', sortable: true },
    { key: 'telegram_username', label: 'Telegram', sortable: true },
    { key: 'tags', label: 'Tags' },
    { key: 'created_at', label: 'Added', sortable: true },
];

const updateFilters = debounce(() => {
    router.get(route('contacts.index'), {
        search: search.value,
        tag: selectedTag.value,
    }, {
        preserveState: true,
        replace: true,
        only: ['contacts', 'filters', 'allTags'],
    });
}, 300);

watch(search, updateFilters);
watch(selectedTag, updateFilters);

const deleteContact = (id) => {
    if (confirm('Delete this contact?')) {
        router.delete(route('contacts.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout title="Contacts" subtitle="Manage your subscribers and leads">
        <template #actions>
            <div class="flex flex-col sm:flex-row gap-2">
                <BaseButton variant="admin-outline" @click="showImportModal = true">
                    <span class="material-symbols-outlined mr-1">upload_file</span>
                    Import CSV
                </BaseButton>
                <BaseButton variant="admin" @click="showCreateModal = true">
                    <span class="material-symbols-outlined mr-1">add</span>
                    New Contact
                </BaseButton>
            </div>
        </template>

        <div v-if="props.lastImportError" class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3 shadow-sm">
            <span class="material-symbols-outlined text-red-500">error</span>
            <div class="flex-1">
                <h4 class="text-sm font-bold text-red-800">Background Import Failed</h4>
                <p class="text-xs text-red-600 mt-0.5">{{ props.lastImportError }}</p>
                <div class="mt-3 flex gap-4">
                    <button @click="showImportModal = true" class="text-xs font-bold text-red-700 hover:text-red-900 flex items-center gap-1 bg-red-100/50 px-2 py-1 rounded transition-all">
                        <span class="material-symbols-outlined text-[14px]">upload_file</span>
                        Try Again
                    </button>
                    <button @click="router.post(route('contacts.clear-import-error'))" class="text-xs font-semibold text-red-400 hover:text-red-600 transition-colors">
                        Dismiss Error
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <!-- Advanced Filters -->
            <div class="flex flex-wrap gap-4 items-end bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                <div class="w-full sm:w-64">
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Search</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                        <input v-model="search" type="text" placeholder="Name or phone..." class="input pl-9" />
                    </div>
                </div>

                <div class="w-full sm:w-48">
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Filter by Tag</label>
                    <select v-model="selectedTag" class="input">
                        <option value="">All Tags</option>
                        <option v-for="tag in allTags" :key="tag" :value="tag">{{ tag }}</option>
                    </select>
                </div>
            </div>

            <BaseCard class="p-0 overflow-hidden">
                <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[600px]">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50">
                            <th v-for="col in columns" :key="col.key" class="p-4 text-left font-semibold text-slate-600 uppercase tracking-tight text-xs">
                                {{ col.label }}
                            </th>
                            <th class="p-4 text-right font-semibold text-slate-600 uppercase tracking-tight text-xs">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="contact in contacts.data" :key="contact.id" class="hover:bg-slate-50 transition-colors">
                            <td class="p-4 font-medium text-slate-800">{{ contact.name }}</td>
                            <td class="p-4 text-slate-600">{{ contact.phone }}</td>
                            <td class="p-4 text-slate-600">
                                <span v-if="contact.telegram_username" class="flex items-center gap-1 text-blue-600 font-medium">
                                    <span class="material-symbols-outlined text-[16px]">alternate_email</span>
                                    {{ contact.telegram_username }}
                                </span>
                                <span v-else class="text-slate-300">—</span>
                            </td>
                            <td class="p-4">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="tag in contact.tags" :key="tag" class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-[10px] font-bold uppercase tracking-wide border border-slate-200">
                                        {{ tag }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 text-slate-400 text-xs">
                                {{ new Date(contact.created_at).toLocaleDateString() }}
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button @click="openEditModal(contact)" class="p-1 text-slate-300 hover:text-admin-primary transition-colors">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button @click="deleteContact(contact.id)" class="p-1 text-slate-300 hover:text-red-500 transition-colors">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!contacts.data.length">
                            <td colspan="6" class="p-12 text-center text-slate-400 italic">
                                No contacts found matching criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>

                <!-- Simple Pagination -->
                <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs">
                    <div class="text-slate-400">
                        Showing {{ contacts.from }} to {{ contacts.to }} of {{ contacts.total }} contacts
                    </div>
                    <div class="flex gap-2">
                        <BaseButton v-for="link in contacts.links" :key="link.label"
                            :variant="link.active ? 'admin' : 'admin-outline'"
                            class="px-3 py-1 text-[10px]"
                            :disabled="!link.url"
                            @click="link.url && router.visit(link.url, { preserveState: true })"
                        >
                            <span v-html="link.label"></span>
                        </BaseButton>
                    </div>
                </div>
            </BaseCard>
        </div>

        <ImportModal v-if="showImportModal" @close="showImportModal = false" />

        <!-- Create Contact Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <span class="material-symbols-outlined text-admin-primary">person_add</span>
                        New Contact
                    </h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Name</label>
                        <input v-model="createForm.name" type="text" class="input" placeholder="John Doe" required />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Phone Number</label>
                        <input v-model="createForm.phone" type="text" class="input" placeholder="+1234567890" required />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Telegram Username (Optional)</label>
                        <input v-model="createForm.telegram_username" type="text" class="input" placeholder="johndoe" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Tags (Optional, comma separated)</label>
                        <input type="text" class="input" placeholder="VIP, Lead" @input="createForm.tags = $event.target.value.split(',').map(t => t.trim())" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <BaseButton type="button" variant="admin-outline" @click="showCreateModal = false">Cancel</BaseButton>
                        <BaseButton variant="admin" :loading="createForm.processing" type="submit">Create Contact</BaseButton>
                    </div>
                </form>
            </div>
        </div>
        <!-- Edit Contact Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <span class="material-symbols-outlined text-admin-primary">edit</span>
                        Edit Contact
                    </h3>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Name</label>
                        <input v-model="editForm.name" type="text" class="input" placeholder="John Doe" required />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Phone Number</label>
                        <input v-model="editForm.phone" type="text" class="input" placeholder="+1234567890" required />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Telegram Username (Optional)</label>
                        <input v-model="editForm.telegram_username" type="text" class="input" placeholder="johndoe" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Tags (Optional, comma separated)</label>
                        <input type="text" class="input" placeholder="VIP, Lead" :value="editForm.tags.join(', ')" @input="editForm.tags = $event.target.value.split(',').map(t => t.trim()).filter(t => t)" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <BaseButton type="button" variant="admin-outline" @click="showEditModal = false">Cancel</BaseButton>
                        <BaseButton variant="admin" :loading="editForm.processing" type="submit">Update Contact</BaseButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
