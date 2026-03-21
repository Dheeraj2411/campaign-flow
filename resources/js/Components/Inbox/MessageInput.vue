<script setup>
import { ref } from 'vue';

const emit = defineEmits(['send']);
const content = ref('');

const handleKeydown = (e) => {
    // Send message on Enter, but allow Shift+Enter for new line
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        send();
    }
};

const send = () => {
    const text = content.value.trim();
    if (text) {
        emit('send', text);
        content.value = '';
    }
};
</script>

<template>
    <div class="flex items-end space-x-3 bg-white rounded-2xl border border-gray-300 p-2 shadow-sm focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 transition-all duration-200">
        <textarea
            v-model="content"
            @keydown="handleKeydown"
            rows="1"
            placeholder="Type a message... (Shift+Enter for newline)"
            class="flex-1 max-h-32 min-h-[44px] resize-none border-0 bg-transparent focus:ring-0 text-[15px] px-3 py-2.5 placeholder-gray-400"
        ></textarea>
        
        <button 
            @click="send"
            :disabled="!content.trim()"
            class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl w-11 h-11 mb-0.5 flex flex-shrink-0 items-center justify-center transition-colors shadow-sm"
        >
            <span class="material-symbols-outlined text-[22px] ml-0.5">send</span>
        </button>
    </div>
</template>
