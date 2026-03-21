<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:modelValue']);

const actionsList = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const actionTypeOptions = [
    { value: 'send_campaign_message', label: 'Send Campaign Message' },
    { value: 'send_template', label: 'Send WhatsApp Template' },
    { value: 'assign_conversation', label: 'Assign Conversation' },
    { value: 'change_status', label: 'Change Conversation Status' },
    { value: 'delay', label: 'Wait / Delay' },
    { value: 'webhook', label: 'Trigger Webhook' },
    { value: 'tag_contact', label: 'Add Tag to Contact' }
];

const addActionBlock = () => {
    actionsList.value = [
        ...actionsList.value,
        { type: 'send_campaign_message', campaign_id: '' }
    ];
};

const removeActionBlock = (index) => {
    const newActions = [...actionsList.value];
    newActions.splice(index, 1);
    actionsList.value = newActions;
};

// UI helpers
const getTypeColor = (type) => {
    const map = {
        send_campaign_message: 'border-blue-500',
        send_template: 'border-green-500',
        assign_conversation: 'border-purple-500',
        change_status: 'border-yellow-500',
        delay: 'border-orange-500',
        webhook: 'border-indigo-500',
        tag_contact: 'border-teal-500'
    };
    return map[type] || 'border-gray-400';
};
</script>

<template>
    <div class="space-y-4">
        <label class="block text-sm font-semibold text-gray-700">Workflow Actions</label>
        
        <div v-if="actionsList.length === 0" class="text-sm text-red-500 font-medium">
            You must add at least one action.
        </div>

        <div class="space-y-3 relative">
            <!-- Draw connecting lines behind blocks -->
            <div v-if="actionsList.length > 1" class="absolute top-4 bottom-4 left-[1.125rem] w-0.5 bg-gray-200 z-0"></div>
            
            <div 
                v-for="(action, idx) in actionsList" 
                :key="idx" 
                class="relative z-10 flex items-start gap-4"
            >
                <div class="flex-none w-9 h-9 mt-1 rounded-full bg-slate-100 border-2 text-slate-500 flex items-center justify-center font-bold text-sm shadow-sm"
                     :class="getTypeColor(action.type)">
                    {{ idx + 1 }}
                </div>
                
                <div class="flex-1 bg-white p-4 border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition">
                    <div class="flex justify-between items-center mb-4">
                        <select 
                            v-model="action.type" 
                            class="text-sm font-semibold text-gray-800 bg-gray-50 border-gray-300 rounded-md py-1.5 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            <option v-for="opt in actionTypeOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        
                        <button 
                            @click="removeActionBlock(idx)" 
                            class="text-gray-400 hover:text-red-500 transition"
                            title="Delete Action"
                        >
                            <span class="material-symbols-outlined text-[20px]">delete</span>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <!-- Dynamic fields based on action type -->
                        <div v-if="action.type === 'send_campaign_message'" class="grid grid-cols-1 gap-2">
                            <label class="text-xs text-gray-500 font-medium">Campaign ID</label>
                            <input v-model="action.campaign_id" type="number" class="text-sm rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g. 1" />
                        </div>
                        
                        <div v-if="action.type === 'send_template'" class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 font-medium mb-1">Template Name</label>
                                <input v-model="action.template_name" type="text" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="hello_world" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 font-medium mb-1">Language Code</label>
                                <input v-model="action.language" type="text" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="en_US" />
                            </div>
                        </div>

                        <div v-if="action.type === 'assign_conversation'" class="grid grid-cols-1 gap-2">
                            <label class="text-xs text-gray-500 font-medium">User ID to target</label>
                            <input v-model="action.user_id" type="number" class="text-sm rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g. 1" />
                        </div>

                        <div v-if="action.type === 'change_status'" class="grid grid-cols-1 gap-2">
                            <label class="text-xs text-gray-500 font-medium">New Status</label>
                            <select v-model="action.status" class="text-sm rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="open">Open</option>
                                <option value="pending">Pending</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        
                        <div v-if="action.type === 'delay'" class="grid grid-cols-1 gap-2">
                            <label class="text-xs text-gray-500 font-medium">Wait Time (Minutes)</label>
                            <input v-model="action.minutes" type="number" class="text-sm rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="5" />
                        </div>

                        <div v-if="action.type === 'webhook'" class="grid grid-cols-1 gap-2">
                            <label class="text-xs text-gray-500 font-medium">Webhook URL (POST)</label>
                            <input v-model="action.url" type="url" class="text-sm rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="https://api.pingos.com/hook" />
                        </div>

                        <div v-if="action.type === 'tag_contact'" class="grid grid-cols-1 gap-2">
                            <label class="text-xs text-gray-500 font-medium">Tag to Add</label>
                            <input v-model="action.tag" type="text" class="text-sm rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="vip_customer" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button 
            @click="addActionBlock" 
            class="mt-4 w-full py-2 bg-gray-50 hover:bg-gray-100 border border-gray-300 border-dashed rounded-xl text-sm font-semibold text-gray-600 flex items-center justify-center transition"
        >
            <span class="material-symbols-outlined text-[18px] mr-1">add_circle</span>
            Add another action
        </button>
    </div>
</template>
