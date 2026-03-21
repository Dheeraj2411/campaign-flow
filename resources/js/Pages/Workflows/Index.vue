<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import HeadTitle from '@/Components/HeadTitle.vue';
import BaseCard from '@/Components/BaseCard.vue';
import BaseButton from '@/Components/BaseButton.vue';
import FlowBuilder from '@/Components/Workflows/FlowBuilder.vue';

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
    is_active: true,
    flow_data: { nodes: [], edges: [] },
    trigger_type: ''
});

const resetForm = () => {
    form.value = {
        name: '',
        is_active: true,
        flow_data: { nodes: [], edges: [] },
        trigger_type: ''
    };
    editingId.value = null;
    showForm.value = false;
};

const triggerOptions = [
    { value: 'message_received', label: 'Message Received' },
    { value: 'new_contact', label: 'New Contact' },
    { value: 'conversation_closed', label: 'Conversation Closed' },
    { value: 'campaign_replied', label: 'Campaign Replied' },
    { value: 'tag_added', label: 'Contact Tag Added' },
];

const handleSaveFlow = (flowPayload) => {
    form.value.flow_data = flowPayload;
    
    const triggerNode = flowPayload.nodes?.find(n => n.type === 'trigger');
    if (triggerNode && triggerNode.data?.triggerType) {
        form.value.trigger_type = triggerNode.data.triggerType;
        form.value.trigger = triggerNode.data.triggerType; // fallback for current backend validation if strictly needed
    }

    if (!form.value.name.trim()) return alert('Workflow needs a name');

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
        is_active: workflow.is_active,
        flow_data: workflow.flow_data || { nodes: [], edges: [] },
    };
    editingId.value = workflow.id;
    showForm.value = true;
};

const deleteWorkflow = (workflow) => {
    if (!confirm(`Are you sure you want to delete workflow "${workflow.name}"?`)) return;
    router.delete(route('workflows.destroy', workflow.id), { preserveScroll: true });
};

const toggleWorkflow = (workflow) => {
    router.post(route('workflows.toggle', workflow.id), {}, { preserveScroll: true });
};

const triggerLabel = (value) => {
    return triggerOptions.find(t => t.value === value)?.label || value;
};

const error = ref(null);
const loading = ref(true);

import { onMounted } from 'vue';
onMounted(async () => {
    try {
        // Init
    } catch(e) {
        error.value = e.message || 'Failed to initialize workflows';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <HeadTitle title="Workflows" subtitle="Automate engagement using visual flow builder">
        <div v-if="loading" class="flex items-center justify-center h-64">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>
        <div v-else-if="error" class="text-center py-16">
            <div class="text-red-500 text-lg font-semibold">{{ error }}</div>
            <button @click="window.location.reload()" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-xl">
                Retry
            </button>
        </div>
        <div v-else class="max-w-7xl mx-auto space-y-6 pb-20 mt-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Automation Workflows</h2>
                    <p class="text-sm text-gray-500 mt-1">Found {{ workflows.length }} workflow(s) in your workspace.</p>
                </div>
                <BaseButton v-if="!showForm" @click="showForm = true" class="px-5 shadow-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700">
                    <span class="material-symbols-outlined text-sm mr-1">add</span>
                    Create Visual Flow
                </BaseButton>
            </div>

            <div v-if="showForm" class="bg-gray-50 p-6 rounded-2xl border border-gray-200 shadow-sm relative">
                <div class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex flex-col md:flex-row md:items-center gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Workflow Name</label>
                            <input v-model="form.name" type="text" class="border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 w-full md:w-80" placeholder="e.g. VIP Onboarding Flow" />
                        </div>
                        <div class="md:pt-6">
                            <label class="flex items-center gap-2 cursor-pointer bg-white border border-gray-200 px-3 py-2 rounded-lg shadow-sm hover:bg-gray-50 transition">
                                <input v-model="form.is_active" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer" />
                                <span class="text-sm font-semibold text-gray-800 select-none">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <h3 class="text-lg font-bold mb-3 text-gray-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-500">account_tree</span>
                    Visual Canvas
                </h3>
                
                <FlowBuilder :load="form.flow_data" @save="handleSaveFlow" @cancel="resetForm" />
            </div>

            <BaseCard v-if="!showForm" class="overflow-hidden border border-gray-200 shadow-sm">
                <div class="overflow-x-auto rounded-lg border border-gray-200 m-1">
                    <table class="min-w-full divide-y divide-gray-200 text-left">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Workflow Name</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Trigger</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Nodes</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Executions</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="wf in workflows" :key="wf.id" class="hover:bg-indigo-50/30 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center mr-3">
                                            <span class="material-symbols-outlined text-[18px]">account_tree</span>
                                        </div>
                                        <span class="font-bold text-gray-900">{{ wf.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs rounded-lg font-medium">
                                        {{ triggerLabel(wf.trigger) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <span title="Nodes" class="inline-flex items-center gap-1 text-gray-600 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-100">
                                        <span class="material-symbols-outlined text-[14px]">grid_view</span> 
                                        {{ wf.flow_data?.nodes?.length || 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-gray-600 border-x border-gray-50">
                                    {{ wf.executions_count ?? 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button 
                                        @click="toggleWorkflow(wf)"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase transition"
                                        :class="wf.is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                                    >
                                        {{ wf.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    <button @click="editWorkflow(wf)" class="text-indigo-600 hover:text-indigo-900 transition font-semibold">Edit</button>
                                    <button @click="deleteWorkflow(wf)" class="text-red-500 hover:text-red-700 transition font-semibold">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="!workflows.length">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center py-6">
                                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                            <span class="material-symbols-outlined text-3xl text-gray-300">account_tree</span>
                                        </div>
                                        <p class="text-lg font-medium text-gray-500">No workflows created yet</p>
                                        <p class="text-sm mt-1">Get started by creating your first visual flow.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </BaseCard>
        </div>
    </HeadTitle>
</template>
