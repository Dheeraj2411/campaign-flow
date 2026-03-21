import { defineStore } from 'pinia';
import axios from 'axios';

export const useInboxStore = defineStore('inbox', {
    state: () => ({
        conversations: [],
        activeConversationId: null,
        messages: [],
        loading: false,
        error: null,
        subscribedWorkspace: null,
        agentTyping: null,
        lockedConversations: {},
        echoSubscribed: false,
    }),
    getters: {
        activeConversation: (state) => {
            const list = Array.isArray(state.conversations) ? state.conversations : [];
            return list.find(c => c.id === state.activeConversationId) || null;
        }
    },
    actions: {
        async fetchConversations() {
            if (this.loading) return;
            this.loading = true;
            this.error = null;
            try {
                // we'll pass optional filters if needed, mimicking the user's setup
                const response = await axios.get('/inbox', { headers: { Accept: 'application/json' } });
                const raw = response.data;
                
                // Handle ALL possible response formats
                this.conversations = Array.isArray(raw)
                    ? raw
                    : Array.isArray(raw?.data)
                        ? raw.data
                        : [];

                // Initialize locks
                const list = Array.isArray(this.conversations) ? this.conversations : [];
                list.forEach(c => {
                    if (c.locked_by) {
                        this.lockedConversations[c.id] = {
                            lockedBy: c.locked_by,
                            lockedByName: c.lockedBy?.name || 'an agent'
                        };
                    }
                });
            } catch (error) {
                this.conversations = [];
                this.error = error.response?.data?.message || error.message || 'Failed to load conversations';
                console.error("Error fetching conversations:", error);
            } finally {
                this.loading = false;
            }
        },
        async fetchMessages(conversationId) {
            if (!conversationId || conversationId === 'undefined') return;
            this.loading = true;
            this.error = null;
            try {
                const response = await axios.get(`/inbox/${conversationId}`, { headers: { Accept: 'application/json' } });
                this.messages = response.data.data || response.data || [];
                
                this.activeConversationId = conversationId;
                this.agentTyping = null;
                
                if (response.data.conversation?.locked_by) {
                     this.lockedConversations[conversationId] = {
                         lockedBy: response.data.conversation.locked_by,
                         lockedByName: response.data.conversation.lockedBy?.name || 'an agent'
                     };
                } else {
                     delete this.lockedConversations[conversationId];
                }

                this.markRead(conversationId);
            } catch (error) {
                this.error = error.response?.data?.message || error.message || 'Failed to load messages';
                console.error("Error fetching messages:", error);
            } finally {
                this.loading = false;
            }
        },
        async sendMessage(content) {
            if (!this.activeConversationId || this.activeConversationId === 'undefined' || !content.trim()) return;
            try {
                const response = await axios.post(`/inbox/${this.activeConversationId}/reply`, {
                    body: content
                });
                
                if (response.data && response.data.message) {
                   if (!Array.isArray(this.messages)) this.messages = [];
                   this.messages.push(response.data.message);
                }
            } catch (error) {
                if (error.response?.status === 423) {
                    alert(error.response.data.error || 'Conversation is locked.');
                } else {
                    this.error = error.response?.data?.message || error.message || 'Failed to send message';
                }
                console.error("Error sending message:", error);
            }
        },
        async markRead(conversationId) {
            if (!conversationId || conversationId === 'undefined') return;
            const list = Array.isArray(this.conversations) ? this.conversations : [];
            const conv = list.find(c => c.id === conversationId);
            if (conv) conv.unread_count = 0;
            
            try {
                await axios.post(`/inbox/${conversationId}/status`, { status: conv?.status || 'open' }); 
            } catch (e) {
                console.error("Failed to mark read", e);
            }
        },
        
        async assignConversation(conversationId, agentId) {
            if (!conversationId || conversationId === 'undefined') return;
            try {
                await axios.post(`/conversations/${conversationId}/assign`, { agent_id: agentId });
                const list = Array.isArray(this.conversations) ? this.conversations : [];
                const conv = list.find(c => c.id === conversationId);
                if (conv) conv.assigned_to = agentId;
            } catch (error) {
                this.error = error.response?.data?.message || error.message || 'Failed to assign conversation';
                console.error("Error assigning conversation", error);
            }
        },
        
        async lockConversation(conversationId) {
            if (!conversationId || conversationId === 'undefined') return;
            try {
                await axios.post(`/conversations/${conversationId}/lock`);
                this.lockedConversations[conversationId] = {
                    lockedBy: 'me',
                    lockedByName: 'You'
                };
            } catch (error) {
                if (error.response?.status === 409) {
                    console.warn("Already locked by another agent.");
                } else {
                    this.error = error.response?.data?.message || error.message || 'Failed to lock conversation';
                    console.error("Error locking", error);
                }
            }
        },
        
        async unlockConversation(conversationId) {
            if (!conversationId || conversationId === 'undefined') return;
            try {
                await axios.post(`/conversations/${conversationId}/unlock`);
                delete this.lockedConversations[conversationId];
            } catch (error) {
                this.error = error.response?.data?.message || error.message || 'Failed to unlock conversation';
                console.error("Error unlocking", error);
            }
        },
        
        async sendTypingIndicator(conversationId) {
            if (!conversationId || conversationId === 'undefined') return;
            try {
                await axios.post(`/conversations/${conversationId}/typing`);
            } catch (error) {
                console.error("Error typing", error);
            }
        },

        clearError() {
            this.error = null;
        },

        initializeEcho(workspaceId) {
            if (this.echoSubscribed || !window.Echo || !workspaceId) return;
            this.echoSubscribed = true;
            this.subscribedWorkspace = workspaceId;

            window.Echo.private(`chat.${workspaceId}`)
                .listen('.message.received', (e) => {
                     const message = e.message;
                     const conversationId = e.conversationId;
                     
                     if (this.activeConversationId === conversationId) {
                         this.agentTyping = null;
                         if (!Array.isArray(this.messages)) this.messages = [];
                         const exists = this.messages.find(m => m.id === message.id);
                         if (!exists) {
                             this.messages.push(message);
                         }
                         this.markRead(conversationId);
                     } else {
                         const list = Array.isArray(this.conversations) ? this.conversations : [];
                         const exists = list.find(c => c.id === conversationId);
                         if (exists) {
                             // Update inside the list and move to top
                             exists.unread_count = (exists.unread_count || 0) + 1;
                             exists.last_message_preview = message.body;
                             exists.last_message_at = message.created_at;
                             
                             this.conversations = [
                                 exists,
                                 ...list.filter(c => c.id !== conversationId)
                             ];
                         } else {
                             // Fetch fresh conversations to get new one
                             this.fetchConversations();
                         }
                     }
                })
                .listen('.message.status.updated', (e) => {
                     const msgList = Array.isArray(this.messages) ? this.messages : [];
                     const msg = msgList.find(m => m.id === e.messageId);
                     if (msg) msg.status = e.status;
                })
                .listen('.agent.typing', (e) => {
                     if (this.activeConversationId === e.conversationId) {
                         this.agentTyping = e.userName;
                         if (this.typingTimeout) clearTimeout(this.typingTimeout);
                         this.typingTimeout = setTimeout(() => {
                             this.agentTyping = null;
                         }, 3000);
                     }
                })
                .listen('.conversation.locked', (e) => {
                     if (e.lockedBy === null) {
                         delete this.lockedConversations[e.conversationId];
                     } else {
                         this.lockedConversations[e.conversationId] = {
                             lockedBy: e.lockedBy,
                             lockedByName: e.lockedByName
                         };
                     }
                });
        },
        
        cleanup() {
            try {
                if (window.Echo) {
                    window.Echo.leaveAllChannels();
                }
            } catch(e) {}
            this.echoSubscribed = false;
            this.subscribedWorkspace = null;
            this.conversations = [];
            this.messages = [];
            this.activeConversationId = null;
            this.loading = false;
            this.error = null;
        }
    }
});
