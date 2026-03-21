<script setup>
import { ref, watch } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import HeadTitle from '@/Components/HeadTitle.vue';
import BaseCard from '@/Components/BaseCard.vue';
import BaseButton from '@/Components/BaseButton.vue';
import ImportModal from '@/Components/Contacts/ImportModal.vue';
import TagChip from '@/Components/Tags/TagChip.vue';
import ActivityTimeline from '@/Components/Contacts/ActivityTimeline.vue';
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
const showingContact = ref(null);

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
    editForm.tags = contact.tags?.map(t => typeof t === 'object' ? t.name : t) || [];
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
        only: ['contacts', 'filters'],
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

const error = ref(null);
const loading = ref(true);

import { onMounted } from 'vue';
onMounted(async () => {
    try {
        // Standard Inertia load
    } catch(e) {
        error.value = e.message || 'Error initializing contacts view';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <HeadTitle title="Contacts" subtitle="Manage your subscribers and leads">
        <template #actions>
            <div class="flex flex-col sm:flex-row gap-2">
                <BaseButton variant="secondary" @click="showImportModal = true">
                    <span class="material-symbols-outlined mr-1 text-[18px]">upload_file</span>
                    Import CSV
                </BaseButton>
                <BaseButton variant="primary" @click="showCreateModal = true">
                    <span class="material-symbols-outlined mr-1 text-[18px]">add</span>
                    New Contact
                </BaseButton>
            </div>
        </template>

        <div v-if="loading" class="flex items-center justify-center h-64">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>
        <div v-else-if="error" class="text-center py-16">
            <div class="text-red-500 text-lg font-semibold">{{ error }}</div>
            <button @click="window.location.reload()" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-xl">
                Retry
            </button>
        </div>
        <div v-else>

        <div v-if="$page.props.lastImportError" class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-red-500">error</span>
            <div class="flex-1">
                <h4 class="text-sm font-semibold text-red-800">Background Import Failed</h4>
                <p class="text-xs text-red-600 mt-0.5">{{ $page.props.lastImportError }}</p>
                <div class="mt-3 flex gap-4">
                    <button @click="showImportModal = true" class="text-xs font-medium text-red-700 hover:text-red-900 flex items-center gap-1 bg-red-100 px-3 py-1.5 rounded-lg transition-all">
                        <span class="material-symbols-outlined text-[14px]">upload_file</span>
                        Try Again
                    </button>
                    <button @click="router.post(route('contacts.clear-import-error'))" class="text-xs font-medium text-red-400 hover:text-red-600 transition-colors">
                        Dismiss
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <!-- Search & Tag Filters -->
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm">
                <div class="flex flex-wrap gap-4 items-center justify-between mb-4">
                    <div class="w-full sm:w-80">
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]">search</span>
                            <input v-model="search" type="text" placeholder="Search by name or phone..." class="w-full text-sm pl-9 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" />
                        </div>
                    </div>
                </div>

                <!-- Tag Filter Bar -->
                <div class="pt-3 border-t border-gray-100 flex items-center gap-2 overflow-x-auto pb-1 hide-scrollbar">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider mr-2 shrink-0">Tags:</span>
                    <button 
                        @click="selectedTag = ''"
                        class="shrink-0 px-4 h-9 flex items-center justify-center rounded-full text-xs font-medium border transition-all duration-200"
                        :class="selectedTag === '' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-500 border-gray-300 hover:border-gray-400'"
                    >
                        All
                    </button>
                    <button 
                        v-for="tag in allTags" 
                        :key="tag.id" 
                        @click="selectedTag = tag.name"
                        class="shrink-0 transition-transform active:scale-95"
                    >
                        <TagChip 
                            :tag="tag" 
                            :class="selectedTag === tag.name ? 'ring-2 ring-indigo-500 ring-offset-2' : 'opacity-70 hover:opacity-100'" 
                        />
                    </button>
                </div>
            </div>

            <!-- Contacts Table -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th v-for="col in columns" :key="col.key" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ col.label }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="contact in contacts.data" :key="contact.id" 
                                class="hover:bg-gray-50 transition-colors duration-150 cursor-pointer group"
                                @click="showingContact = contact"
                            >
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ contact.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ contact.phone }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <span v-if="contact.telegram_username" class="flex items-center gap-1 text-indigo-600 font-medium">
                                        <span class="material-symbols-outlined text-[16px]">alternate_email</span>
                                        {{ contact.telegram_username }}
                                    </span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <template v-if="contact.tags && contact.tags.length">
                                            <TagChip 
                                                v-for="(t, idx) in contact.tags.filter(t => typeof t === 'object')" 
                                                :key="'obj-'+t.id" 
                                                :tag="t" 
                                            />
                                            <TagChip 
                                                v-for="(t, idx) in contact.tags.filter(t => typeof t === 'string')" 
                                                :key="'str-'+idx" 
                                                :tag="{ name: t, color: '#94a3b8' }" 
                                            />
                                        </template>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-400">
                                    {{ new Date(contact.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button @click.stop="openEditModal(contact)" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-indigo-600 transition-colors" title="Edit">
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </button>
                                        <button @click.stop="deleteContact(contact.id)" class="p-2 rounded-lg hover:bg-red-50 text-gray-500 hover:text-red-500 transition-colors" title="Delete">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!contacts.data.length">
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <span class="material-symbols-outlined text-[48px] block mb-3 text-gray-300">contacts</span>
                                    <h3 class="text-lg font-semibold text-gray-900">No contacts yet</h3>
                                    <p class="text-sm text-gray-500 mt-1">Get started by adding your first contact</p>
                                    <BaseButton variant="primary" size="sm" class="mt-4" @click="showCreateModal = true">Add Contact</BaseButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-3 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-3">
                    <p class="text-xs text-gray-500">
                        Showing {{ contacts.from || 0 }} to {{ contacts.to || 0 }} of {{ contacts.total }} contacts
                    </p>
                    <div class="flex gap-1">
                        <BaseButton v-for="link in contacts.links" :key="link.label"
                            :variant="link.active ? 'primary' : 'secondary'"
                            size="sm"
                            class="px-3 py-1 text-xs"
                            :disabled="!link.url"
                            @click="link.url && router.visit(link.url, { preserveState: true })"
                        >
                            <span v-html="link.label"></span>
                        </BaseButton>
                    </div>
                </div>
            </div>
        </div>

        <ImportModal v-if="showImportModal" @close="showImportModal = false" />

        <!-- Create Contact Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm">
            <div class="w-full sm:max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-lg overflow-hidden border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-600">person_add</span>
                        New Contact
                    </h3>
                    <button @click="showCreateModal = false" class="p-2 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
                <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input v-model="createForm.name" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" placeholder="John Doe" required />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input v-model="createForm.phone" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" placeholder="+1234567890" required />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700">Telegram Username (Optional)</label>
                        <input v-model="createForm.telegram_username" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" placeholder="johndoe" />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700">Tags (Optional, comma separated)</label>
                        <input type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" placeholder="VIP, Lead" @input="createForm.tags = $event.target.value.split(',').map(t => t.trim()).filter(Boolean)" />
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <BaseButton type="button" variant="secondary" @click="showCreateModal = false">Cancel</BaseButton>
                        <BaseButton variant="primary" :loading="createForm.processing" type="submit">Create Contact</BaseButton>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Contact Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm">
            <div class="w-full sm:max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-lg overflow-hidden border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-600">edit</span>
                        Edit Contact
                    </h3>
                    <button @click="showEditModal = false" class="p-2 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
                <form @submit.prevent="submitEdit" class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input v-model="editForm.name" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" required />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input v-model="editForm.phone" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" required />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700">Telegram Username (Optional)</label>
                        <input v-model="editForm.telegram_username" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700">Tags (Optional, comma separated)</label>
                        <input type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" placeholder="VIP, Lead" :value="editForm.tags.join(', ')" @input="editForm.tags = $event.target.value.split(',').map(t => t.trim()).filter(Boolean)" />
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <BaseButton type="button" variant="secondary" @click="showEditModal = false">Cancel</BaseButton>
                        <BaseButton variant="primary" :loading="editForm.processing" type="submit">Update Contact</BaseButton>
                    </div>
                </form>
            </div>
        </div>

        <!-- Contact Detail Drawer -->
        <div v-if="showingContact" class="fixed inset-0 z-[60] flex justify-end bg-black/30 backdrop-blur-[2px] transition-opacity" @click.self="showingContact = null">
            <div class="w-full max-w-md bg-white h-full shadow-lg flex flex-col transform transition-transform border-l border-gray-200" style="animation: slide-in-right 0.25s ease-out;">
                <!-- Drawer Header -->
                <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-start">
                    <div class="flex gap-4 items-center">
                        <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xl uppercase">
                            {{ showingContact.name.substring(0,2) }}
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">{{ showingContact.name }}</h2>
                            <p class="text-sm text-gray-500 flex items-center gap-1 mt-0.5">
                                <span class="material-symbols-outlined text-[14px]">call</span>
                                {{ showingContact.phone }}
                            </p>
                        </div>
                    </div>
                    <button @click="showingContact = null" class="p-2 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
                
                <!-- Tags Section -->
                <div class="px-6 py-4 border-b border-gray-100" v-if="showingContact.tags && showingContact.tags.length">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Applied Tags</p>
                    <div class="flex flex-wrap gap-1.5">
                        <TagChip 
                            v-for="(t, idx) in showingContact.tags.filter(t => typeof t === 'object')" 
                            :key="'drawer-'+t.id" 
                            :tag="t" 
                        />
                        <TagChip 
                            v-for="(t, idx) in showingContact.tags.filter(t => typeof t === 'string')" 
                            :key="'drawer-str-'+idx" 
                            :tag="{ name: t, color: '#94a3b8' }" 
                        />
                    </div>
                </div>

                <!-- Timeline Section -->
                <div class="p-6 overflow-y-auto flex-1 h-full pb-20">
                    <ActivityTimeline :contactId="showingContact.id" />
                </div>
            </div>
        </div>
        </div>
    </HeadTitle>
</template>

<style scoped>
@keyframes slide-in-right {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
