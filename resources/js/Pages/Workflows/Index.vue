<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/BaseCard.vue';
import BaseButton from '@/Components/BaseButton.vue';

const props = defineProps({
    workflows: {
        type: Array,
        default: () => [],
    },
});

const showForm = ref(false);
const editingId = ref(null);
const form = ref({
    name: '',
    trigger: 'new_contact',
    conditions: [],
    actions: [{ type: 'send_campaign_message', campaign_id: '' }],
    is_active: true,
});

const triggerOptions = [
    { value: 'new_contact', label: 'New Contact Created' },
    { value: 'contact_replied', label: 'Contact Replied' },
    { value: 'contact_opted_in', label: 'Contact Opted In' },
    { value: 'tag_added', label: 'Tag Added' },
];

const actionTypeOptions = [
    { value: 'send_campaign_message', label: 'Send Campaign Message' },
    { value: 'tag_contact', label: 'Tag Contact' },
];

const resetForm = () => {
    form.value = {
        name: '',
        trigger: 'new_contact',
        conditions: [],
        actions: [{ type: 'send_campaign_message', campaign_id: '' }],
        is_active: true,
    };
    editingId.value = null;
    showForm.value = false;
};

const saveWorkflow = () => {
    if (editingId.value) {
        router.put(route('workflows.update', editingId.value), form.value, {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    } else {
        router.post(route('workflows.store'), form.value, {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    }
};

const editWorkflow = (workflow) => {
    form.value = {
        name: workflow.name,
        trigger: workflow.trigger,
        conditions: workflow.conditions || [],
        actions: workflow.actions || [],
        is_active: workflow.is_active,
    };
    editingId.value = workflow.id;
    showForm.value = true;
};

const toggleWorkflow = (workflow) => {
    router.post(route('workflows.toggle', workflow.id), {}, {
        preserveScroll: true,
    });
};

const deleteWorkflow = (workflow) => {
    if (!confirm(`Delete workflow "${workflow.name}"?`)) return;

    router.delete(route('workflows.destroy', workflow.id), {
        preserveScroll: true,
    });
};

const addAction = () => {
    form.value.actions.push({ type: 'send_campaign_message', campaign_id: '' });
};

const removeAction = (index) => {
    form.value.actions.splice(index, 1);
};

const triggerLabel = (value) => {
    return triggerOptions.find(t => t.value === value)?.label || value;
};
</script>

<template>
    <AppLayout title="Workflows" subtitle="Automate actions when events occur">
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">{{ workflows.length }} workflow{{ workflows.length !== 1 ? 's' : '' }}</p>
                </div>
                <BaseButton v-if="!showForm" @click="showForm = true">+ New Workflow</BaseButton>
                <BaseButton v-else variant="secondary" @click="resetForm">Cancel</BaseButton>
            </div>

            <!-- Create / Edit Form -->
            <BaseCard v-if="showForm" class="p-6">
                <h3 class="text-lg font-bold mb-4">{{ editingId ? 'Edit Workflow' : 'Create Workflow' }}</h3>

                <div class="space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Workflow Name</label>
                        <input v-model="form.name" type="text" class="input w-full" placeholder="Welcome new contacts" />
                    </div>

                    <!-- Trigger -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Trigger Event</label>
                        <select v-model="form.trigger" class="input w-full">
                            <option v-for="t in triggerOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-2">Actions</label>
                        <div v-for="(action, idx) in form.actions" :key="idx" class="flex items-center gap-2 mb-2">
                            <select v-model="action.type" class="input flex-1">
                                <option v-for="a in actionTypeOptions" :key="a.value" :value="a.value">{{ a.label }}</option>
                            </select>
                            <input
                                v-if="action.type === 'send_campaign_message'"
                                v-model="action.campaign_id"
                                type="text"
                                class="input flex-1"
                                placeholder="Campaign ID"
                            />
                            <input
                                v-if="action.type === 'tag_contact'"
                                v-model="action.tag"
                                type="text"
                                class="input flex-1"
                                placeholder="Tag name"
                            />
                            <button @click="removeAction(idx)" class="text-red-500 hover:text-red-700 text-sm" v-if="form.actions.length > 1">✕</button>
                        </div>
                        <button @click="addAction" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">+ Add Action</button>
                    </div>

                    <!-- Active toggle -->
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.is_active" type="checkbox" class="rounded text-indigo-600" />
                        <span class="text-sm text-slate-600">Active immediately</span>
                    </label>

                    <BaseButton @click="saveWorkflow">{{ editingId ? 'Update Workflow' : 'Create Workflow' }}</BaseButton>
                </div>
            </BaseCard>

            <!-- Workflows list -->
            <BaseCard class="p-6">
                <h3 class="text-lg font-bold mb-4">Your Workflows</h3>

                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Name</th>
                            <th class="py-2">Trigger</th>
                            <th class="py-2">Actions</th>
                            <th class="py-2">Runs</th>
                            <th class="py-2">Status</th>
                            <th class="py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="wf in workflows" :key="wf.id" class="border-b hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="py-3 font-medium">{{ wf.name }}</td>
                            <td class="py-3 text-sm text-slate-500">{{ triggerLabel(wf.trigger) }}</td>
                            <td class="py-3 text-sm text-slate-500">{{ (wf.actions || []).length }} action{{ (wf.actions || []).length !== 1 ? 's' : '' }}</td>
                            <td class="py-3 text-sm text-slate-500">{{ wf.executions_count ?? 0 }}</td>
                            <td class="py-3">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer"
                                    :class="wf.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'"
                                    @click="toggleWorkflow(wf)"
                                >
                                    {{ wf.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="py-3 text-right space-x-2">
                                <button @click="editWorkflow(wf)" class="text-indigo-600 hover:text-indigo-800 text-sm">Edit</button>
                                <button @click="deleteWorkflow(wf)" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!workflows.length">
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No workflows yet. Create one to automate actions.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </BaseCard>
        </div>
    </AppLayout>
</template>
