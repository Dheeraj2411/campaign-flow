<script setup>
import { ref, watch, nextTick } from 'vue';
import { useInboxStore } from '@/stores/inbox';
import MessageBubble from './MessageBubble.vue';
import MessageInput from './MessageInput.vue';
import ConversationHeader from './ConversationHeader.vue';
import TypingIndicator from './TypingIndicator.vue';

const props = defineProps({
    activeConversationId: Number
});

const store = useInboxStore();
const messageContainer = ref(null);
let typingTimer = null;

watch(() => store.messages.length, () => {
    scrollToBottom();
});

watch(() => props.activeConversationId, () => {
    scrollToBottom();
});

const scrollToBottom = () => {
    nextTick(() => {
        if (messageContainer.value) {
            messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
        }
    });
};

const handleSend = (content) => {
    store.sendMessage(content);
};

const handleFocus = () => {
    if (props.activeConversationId != null && props.activeConversationId !== 'undefined') {
        store.lockConversation(props.activeConversationId);
    }
};

const handleBlur = () => {
    if (props.activeConversationId != null && props.activeConversationId !== 'undefined') {
        store.unlockConversation(props.activeConversationId);
    }
};

const handleKeydown = () => {
    if (props.activeConversationId != null && props.activeConversationId !== 'undefined') {
        if (!typingTimer) {
            store.sendTypingIndicator(props.activeConversationId);
        }
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            typingTimer = null;
        }, 2000);
    }
};
</script>

<template>
    <div class="flex flex-col h-full bg-slate-50 relative" :class="activeConversationId ? 'w-full md:flex-1' : 'hidden md:flex md:flex-1'">
        <template v-if="activeConversationId">
            <ConversationHeader :conversation="store.activeConversation" />
            
            <div 
                ref="messageContainer" 
                class="flex-1 p-6 overflow-y-auto space-y-6"
            >
                <div v-if="store.loading && store.messages.length === 0" class="flex justify-center py-10">
                    <span class="text-sm text-gray-500 animate-pulse">Loading messages...</span>
                </div>
                
                <MessageBubble 
                    v-for="msg in store.messages" 
                    :key="msg.id" 
                    :message="msg" 
                />

                <TypingIndicator v-if="store.agentTyping" :agentName="store.agentTyping" />
            </div>

            <div class="p-4 bg-gray-50 border-t border-gray-200" @focusin="handleFocus" @focusout="handleBlur" @keydown="handleKeydown">
                <MessageInput @send="handleSend" />
            </div>
        </template>
        
        <div v-else class="flex-1 flex items-center justify-center text-gray-400 flex-col">
            <span class="material-symbols-outlined text-6xl mb-4 text-gray-300">chat_bubble_outline</span>
            <p class="text-lg font-medium text-gray-500">Select a conversation to start chatting</p>
        </div>
    </div>
</template>
