<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import HeadTitle from '@/Components/HeadTitle.vue';
import ConversationList from '@/Components/Inbox/ConversationList.vue';
import ChatWindow from '@/Components/Inbox/ChatWindow.vue';
import { useInboxStore } from '@/stores/inbox';

const store = useInboxStore();
const page = usePage();
const workspaceId = page.props.auth?.user?.active_workspace_id;

const error = ref(null);
const loading = ref(true);

onMounted(async () => {
    try {
        if (workspaceId) {
            store.initializeEcho(workspaceId);
        }
    } catch (e) {
        error.value = e.message || 'Failed to initialize connection to chat server';
    } finally {
        loading.value = false;
    }
});

onUnmounted(() => {
    // Optionally leave channel if navigating away
});

</script>

<template>
    <Head title="Inbox" />
    <HeadTitle title="Inbox" subtitle="Conversations with your contacts">
        <div v-if="loading" class="flex items-center justify-center h-64">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>
        <div v-else-if="error" class="text-center py-16">
            <div class="text-red-500 text-lg font-semibold">{{ error }}</div>
            <button @click="window.location.reload()" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-xl">
                Retry
            </button>
        </div>
        <div v-else class="-m-4 md:-m-6 lg:-m-8 h-[calc(100vh-64px)]">
            <div class="h-full">
                <div class="bg-white overflow-hidden h-full flex border-t border-gray-200">
                    
                    <ConversationList />
                    
                    <ChatWindow :active-conversation-id="store.activeConversationId" />
                    
                </div>
            </div>
        </div>
    </HeadTitle>
</template>
