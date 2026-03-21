<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:modelValue']);

// Using a computed setter for deep updates
const conditions = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const fieldOptions = [
    { value: 'contact.tags', label: 'Tag' },
    { value: 'conversation.status', label: 'Status' },
    { value: 'campaign_id', label: 'Campaign' }
];

const operatorOptions = [
    { value: 'equals', label: 'Is / Equals' },
    { value: 'not_equals', label: 'Is Not' },
    { value: 'contains', label: 'Contains' }
];

const addCondition = () => {
    conditions.value = [
        ...conditions.value, 
        { field: 'contact.tags', operator: 'contains', value: '' }
    ];
};

const removeCondition = (index) => {
    const newConditions = [...conditions.value];
    newConditions.splice(index, 1);
    conditions.value = newConditions;
};
</script>

<template>
    <div class="space-y-3">
        <label class="block text-sm font-semibold text-gray-700">Conditions (Optional match)</label>
        
        <div v-if="conditions.length === 0" class="text-sm text-gray-500 bg-gray-50 border border-gray-200 border-dashed rounded-lg p-4 text-center">
            No conditions. This workflow will trigger every time the event happens.
        </div>
        
        <div class="space-y-2">
            <div 
                v-for="(condition, idx) in conditions" 
                :key="idx" 
                class="flex items-center gap-3 bg-white p-3 border border-gray-200 rounded-lg shadow-sm group transition hover:border-indigo-300"
            >
                <div class="flex-none font-medium text-xs text-gray-400 w-8 text-center uppercase tracking-wide">
                    {{ idx === 0 ? 'If' : 'And' }}
                </div>
                
                <select v-model="condition.field" class="flex-1 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option v-for="opt in fieldOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                    </option>
                </select>
                
                <select v-model="condition.operator" class="flex-1 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option v-for="opt in operatorOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                    </option>
                </select>
                
                <input 
                    v-model="condition.value" 
                    type="text" 
                    class="flex-1 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 placeholder-gray-400" 
                    placeholder="Enter value" 
                />
                
                <button 
                    @click="removeCondition(idx)" 
                    class="flex-none text-gray-400 hover:text-red-500 transition px-2"
                    title="Remove condition"
                >
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
        </div>

        <button 
            @click="addCondition" 
            class="mt-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center transition"
        >
            <span class="material-symbols-outlined text-sm mr-1">add</span>
            Add Condition
        </button>
    </div>
</template>
