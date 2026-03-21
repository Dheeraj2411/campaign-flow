<script setup>
import { onMounted } from 'vue';
import { useInboxStore } from '@/stores/inbox';
import ConversationItem from './ConversationItem.vue';

const store = useInboxStore();

onMounted(() => {
    store.fetchConversations();
});

const selectConversation = (id) => {
    store.fetchMessages(id);
};
</script>

<template>
    <div class="border-r border-gray-200 bg-white flex flex-col h-full" :class="store.activeConversationId ? 'hidden md:flex md:w-1/3' : 'w-full md:w-1/3'">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Inbox</h2>
            <div v-if="store.loading" class="text-sm text-indigo-500 font-medium animate-pulse">Loading...</div>
        </div>
        
        <div class="flex-1 overflow-y-auto">
            <template v-if="store.conversations.length">
                <ConversationItem
                    v-for="conv in store.conversations"
                    :key="conv.id"
                    :conversation="conv"
                    :is-active="store.activeConversationId === conv.id"
                    @click="selectConversation(conv.id)"
                />
            </template>
            <div v-else class="p-8 text-center text-gray-400">
                <span class="material-symbols-outlined text-4xl mb-3 text-gray-300">inbox</span>
                <p class="text-sm">No active conversations.</p>
            </div>
        </div>
    </div>
</template>
