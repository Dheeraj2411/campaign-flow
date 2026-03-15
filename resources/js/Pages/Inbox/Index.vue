<template>
    <AppLayout title="Inbox" subtitle="Manage your incoming messages">
        <div class="flex h-[calc(100vh-140px)] bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Left Sidebar: Conversations List -->
            <div class="w-1/3 border-r border-slate-200 flex flex-col bg-slate-50">
                <div class="p-4 border-b border-slate-200 bg-white shadow-sm z-10">
                    <input type="text" placeholder="Search conversations..." class="input w-full" />
                </div>
                <div class="flex-1 overflow-y-auto">
                    <div v-for="conv in localConversations" :key="conv.id" 
                         @click="selectConversation(conv)"
                         :class="['p-4 border-b border-slate-100 cursor-pointer hover:bg-slate-100 transition-colors', activeConversation?.id === conv.id ? 'bg-indigo-50 border-l-4 border-l-admin-primary' : 'border-l-4 border-transparent']">
                        <div class="flex justify-between items-start mb-1">
                            <span class="font-semibold text-slate-800">{{ conv.contact.name }}</span>
                            <span class="text-xs text-slate-400 font-medium">{{ formatDate(conv.last_message_at) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm text-slate-600">
                            <span class="truncate flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm" :class="conv.platform === 'whatsapp' ? 'text-green-500' : 'text-blue-500'">
                                    {{ conv.platform === 'whatsapp' ? 'chat' : 'send' }}
                                </span>
                                {{ conv.contact.phone || conv.contact.telegram_username }}
                            </span>
                            <span v-if="conv.unread_count > 0" class="bg-admin-alert text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">
                                {{ conv.unread_count }}
                            </span>
                        </div>
                    </div>
                    <div v-if="localConversations.length === 0" class="p-12 flex flex-col items-center text-slate-400">
                        <span class="material-symbols-outlined text-4xl mb-2 opacity-50">inbox</span>
                        <p>No conversations yet.</p>
                    </div>
                </div>
            </div>

            <!-- Right Area: Chat Window -->
            <div class="flex-1 flex flex-col bg-[#F9FBF9] relative">
                <template v-if="activeConversation">
                    <!-- Chat Header -->
                    <div class="h-16 border-b border-slate-200 bg-white flex items-center px-6 shadow-sm z-10 w-full justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 text-admin-primary flex items-center justify-center font-bold text-lg">
                                {{ activeConversation.contact.name.charAt(0) }}
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-800 leading-tight">{{ activeConversation.contact.name }}</h3>
                                <p class="text-xs text-slate-500 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px]" :class="activeConversation.platform === 'whatsapp' ? 'text-green-500' : 'text-blue-500'">
                                        {{ activeConversation.platform === 'whatsapp' ? 'chat' : 'send' }}
                                    </span>
                                    {{ activeConversation.platform === 'whatsapp' ? 'WhatsApp' : 'Telegram' }} • {{ activeConversation.contact.phone || activeConversation.contact.telegram_username }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Messages Area -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-4" ref="messagesContainer">
                        <div v-if="loadingMessages" class="flex justify-center py-4">
                            <span class="material-symbols-outlined animate-spin text-admin-primary">progress_activity</span>
                        </div>
                        <template v-else>
                            <div class="text-center my-4 opacity-50 text-xs text-slate-500">
                                This is the start of your conversation with {{ activeConversation.contact.name }}
                            </div>
                            <div v-for="msg in messages" :key="msg.id" 
                                 :class="['flex w-full', msg.direction === 'outbound' ? 'justify-end' : 'justify-start']">
                                <div :class="[
                                    'max-w-[75%] rounded-2xl px-4 py-2.5 shadow-sm text-sm',
                                    msg.direction === 'outbound' ? 'bg-admin-primary text-white rounded-br-sm' : 'bg-white border border-slate-200 text-slate-800 rounded-bl-sm'
                                ]">
                                    <p class="whitespace-pre-wrap leading-relaxed" v-text="msg.body"></p>
                                    <div :class="['text-[10px] mt-1 text-right flex items-center justify-end gap-1', msg.direction === 'outbound' ? 'text-indigo-200' : 'text-slate-400']">
                                        {{ formatTime(msg.sent_at) }}
                                        <span v-if="msg.direction === 'outbound'" class="material-symbols-outlined text-[14px]">
                                            {{ msg.status === 'sent' ? 'check' : (msg.status === 'delivered' ? 'done_all' : 'error') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Message Input -->
                    <div class="p-4 bg-white border-t border-slate-200 shadow-sm z-10">
                        <form @submit.prevent="sendMessage" class="flex gap-2">
                            <input v-model="newMessage" type="text" class="input flex-1 bg-slate-50/50" placeholder="Type a message..." :disabled="sending"/>
                            <BaseButton variant="admin" type="submit" :loading="sending" :disabled="!newMessage.trim() || sending" class="px-6">Send</BaseButton>
                        </form>
                    </div>
                </template>
                <div v-else class="flex-1 flex flex-col items-center justify-center text-slate-400">
                    <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                         <span class="material-symbols-outlined text-4xl text-slate-300">forum</span>
                    </div>
                    <p class="text-lg font-medium text-slate-500">Select a conversation</p>
                    <p class="text-sm">Choose a contact from the list to start messaging</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
    conversations: {
        type: Object,
        required: true
    },
    auth: {
        type: Object,
        required: true
    }
})

const localConversations = ref([...props.conversations.data])
const activeConversation = ref(null)
const messages = ref([])
const loadingMessages = ref(false)
const newMessage = ref('')
const sending = ref(false)
const messagesContainer = ref(null)

// Watch for prop changes (e.g., Inertia reload)
watch(() => props.conversations, (newVal) => {
    localConversations.value = [...newVal.data]
}, { deep: true })

const formatDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    const now = new Date()
    if (date.toDateString() === now.toDateString()) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    }
    return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
}

const formatTime = (dateString) => {
    return new Date(dateString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight + 100
        }
    })
}

const selectConversation = async (conv) => {
    activeConversation.value = conv
    
    // Clear unread count locally
    const c = localConversations.value.find(x => x.id === conv.id)
    if (c) c.unread_count = 0

    loadingMessages.value = true
    try {
        const { data } = await axios.get(route('inbox.show', conv.id))
        messages.value = data.messages
        scrollToBottom()
    } catch (error) {
        console.error('Failed to load messages', error)
    } finally {
        loadingMessages.value = false
    }
}

const sendMessage = async () => {
    if (!newMessage.value.trim() || !activeConversation.value) return

    const body = newMessage.value
    newMessage.value = ''
    sending.value = true

    // Optimistically add message
    const tempMsg = {
        id: 'temp-' + Date.now(),
        direction: 'outbound',
        body: body,
        status: 'sending',
        sent_at: new Date().toISOString()
    }
    messages.value.push(tempMsg)
    scrollToBottom()

    try {
        const { data } = await axios.post(route('inbox.reply', activeConversation.value.id), {
            body: body
        })
        
        if (data.success) {
            // Replace temp msg with real msg
            const index = messages.value.findIndex(m => m.id === tempMsg.id)
            if (index !== -1) {
                messages.value[index] = data.message
            }
            
            // Move conversation to top
            const convIndex = localConversations.value.findIndex(c => c.id === activeConversation.value.id)
            if (convIndex !== -1) {
                const c = localConversations.value.splice(convIndex, 1)[0]
                c.last_message_at = data.message.sent_at
                localConversations.value.unshift(c)
            }
        } else {
            console.error('Backend returned error:', data.error);
            const index = messages.value.findIndex(m => m.id === tempMsg.id)
            if (index !== -1) messages.value[index].status = 'failed'
        }
    } catch (error) {
        console.error('Send message request failed', error)
        const index = messages.value.findIndex(m => m.id === tempMsg.id)
        if (index !== -1) messages.value[index].status = 'failed'
    } finally {
        sending.value = false
        scrollToBottom()
    }
}

// Websocket Listeners for Real-Time Incoming Messages
onMounted(() => {
    if (window.Echo && props.auth.user?.active_workspace_id) {
        window.Echo.private(`workspace.${props.auth.user.active_workspace_id}`)
            .listen('MessageReceived', (e) => {
                const incomingMessage = e.message;
                const convId = incomingMessage.conversation_id;
                
                // If this is the active conversation, append message
                if (activeConversation.value && activeConversation.value.id === convId) {
                    messages.value.push(incomingMessage)
                    scrollToBottom()
                    
                    // Call backend to clear unread tally on server
                    axios.get(route('inbox.show', convId))
                }
                
                // Reorder / update conversations list
                const idx = localConversations.value.findIndex(c => c.id === convId)
                if (idx !== -1) {
                    const c = localConversations.value.splice(idx, 1)[0]
                    c.last_message_at = incomingMessage.sent_at
                    
                    if (!activeConversation.value || activeConversation.value.id !== convId) {
                        c.unread_count = (c.unread_count || 0) + 1
                    }
                    localConversations.value.unshift(c)
                } else {
                    // New conversation, trigger full list reload
                    router.reload({ only: ['conversations'] })
                }
            });
    }
})

onUnmounted(() => {
    if (window.Echo && props.auth.user?.active_workspace_id) {
        window.Echo.leave(`workspace.${props.auth.user.active_workspace_id}`)
    }
})
</script>
