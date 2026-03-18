<template>
    <AppLayout title="Inbox" subtitle="Manage your team messaging">
        <div class="chat-layout">

            <!-- ═══════════════════════════════════════════════════ -->
            <!-- LEFT SIDEBAR: Conversation List                     -->
            <!-- ═══════════════════════════════════════════════════ -->
            <div :class="['chat-sidebar', activeConversation ? 'mobile-hidden' : '']">

                <!-- Platform & Filter Header -->
                <div class="p-4 border-b border-slate-200 bg-white shadow-sm z-10 space-y-3">
                    <!-- Platform Toggle (WhatsApp / Telegram) -->
                    <div class="flex p-1 bg-slate-100/80 rounded-lg shrink-0 border border-slate-200/60">
                        <button @click="setFilter('platform', 'whatsapp')" 
                                :style="(filters.platform === 'whatsapp' || !filters.platform) ? 'color: #25D366;' : ''"
                                :class="['flex-1 py-1.5 text-xs font-bold rounded-md transition-all', (filters.platform === 'whatsapp' || !filters.platform) ? 'bg-white shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700']">
                            WhatsApp
                        </button>
                        <button @click="setFilter('platform', 'telegram')" 
                                :style="filters.platform === 'telegram' ? 'color: #0088cc;' : ''"
                                :class="['flex-1 py-1.5 text-xs font-bold rounded-md transition-all', filters.platform === 'telegram' ? 'bg-white shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700']">
                            Telegram
                        </button>
                    </div>
                    
                    <!-- Custom Engagement & Status Filters -->
                    <div class="flex flex-col sm:flex-row gap-2 shrink-0">
                        <!-- Engagement Dropdown -->
                        <div class="relative flex-1" ref="dropdownEngagementRef">
                            <button @click.stop="dropdownEngagementOpen = !dropdownEngagementOpen" 
                                    class="w-full flex items-center justify-between text-xs h-9 py-0 pl-3 pr-2 border border-slate-200 rounded-lg bg-slate-50 hover:bg-slate-100 focus:bg-white focus:ring-2 focus:ring-admin-primary/20 focus:border-admin-primary transition-all text-slate-700 shadow-sm outline-none">
                                <span class="truncate">
                                    {{ !filters.engagement ? 'All Engagement' : (filters.engagement === 'needs_reply' ? 'Needs Reply 🔴' : 'Replied ✅') }}
                                </span>
                                <span class="material-symbols-outlined text-slate-400 text-base transition-transform duration-200" :class="{'rotate-180': dropdownEngagementOpen}">expand_more</span>
                            </button>
                            <Transition name="fade">
                                <div v-if="dropdownEngagementOpen" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 shadow-lg rounded-lg py-1 text-xs">
                                    <button @click="setFilter('engagement', ''); dropdownEngagementOpen = false" class="w-full text-left px-3 py-2 hover:bg-slate-50 transition-colors" :class="{'bg-admin-primary/5 text-admin-primary font-semibold': !filters.engagement}">All Engagement</button>
                                    <button @click="setFilter('engagement', 'needs_reply'); dropdownEngagementOpen = false" class="w-full text-left px-3 py-2 hover:bg-slate-50 transition-colors" :class="{'bg-admin-primary/5 text-admin-primary font-semibold': filters.engagement === 'needs_reply'}">Needs Reply 🔴</button>
                                    <button @click="setFilter('engagement', 'replied'); dropdownEngagementOpen = false" class="w-full text-left px-3 py-2 hover:bg-slate-50 transition-colors" :class="{'bg-admin-primary/5 text-admin-primary font-semibold': filters.engagement === 'replied'}">Replied ✅</button>
                                </div>
                            </Transition>
                        </div>

                        <!-- Status Dropdown -->
                        <div class="relative flex-1" ref="dropdownStatusRef">
                            <button @click.stop="dropdownStatusOpen = !dropdownStatusOpen" 
                                    class="w-full flex items-center justify-between text-xs h-9 py-0 pl-3 pr-2 border border-slate-200 rounded-lg bg-slate-50 hover:bg-slate-100 focus:bg-white focus:ring-2 focus:ring-admin-primary/20 focus:border-admin-primary transition-all text-slate-700 shadow-sm outline-none">
                                <span class="truncate capitalize">{{ filters.status || 'All Status' }}</span>
                                <span class="material-symbols-outlined text-slate-400 text-base transition-transform duration-200" :class="{'rotate-180': dropdownStatusOpen}">expand_more</span>
                            </button>
                            <Transition name="fade">
                                <div v-if="dropdownStatusOpen" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 shadow-lg rounded-lg py-1 text-xs">
                                    <button @click="setFilter('status', ''); dropdownStatusOpen = false" class="w-full text-left px-3 py-2 hover:bg-slate-50 transition-colors" :class="{'bg-admin-primary/5 text-admin-primary font-semibold': !filters.status}">All Status</button>
                                    <button @click="setFilter('status', 'open'); dropdownStatusOpen = false" class="w-full text-left px-3 py-2 hover:bg-slate-50 transition-colors" :class="{'bg-admin-primary/5 text-admin-primary font-semibold': filters.status === 'open'}">Open</button>
                                    <button @click="setFilter('status', 'pending'); dropdownStatusOpen = false" class="w-full text-left px-3 py-2 hover:bg-slate-50 transition-colors" :class="{'bg-admin-primary/5 text-admin-primary font-semibold': filters.status === 'pending'}">Pending</button>
                                    <button @click="setFilter('status', 'closed'); dropdownStatusOpen = false" class="w-full text-left px-3 py-2 hover:bg-slate-50 transition-colors" :class="{'bg-admin-primary/5 text-admin-primary font-semibold': filters.status === 'closed'}">Closed</button>
                                </div>
                            </Transition>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
                        <input type="text" v-model="searchQuery" placeholder="Search..." class="input h-9 w-full pl-9 text-sm border-slate-200 bg-slate-50 focus:bg-white" />
                    </div>
                </div>

                <!-- Conversation List -->
                <div class="flex-1 overflow-y-auto">
                    <div v-for="conv in filteredConversations" :key="conv.id" 
                         @click="selectConversation(conv)"
                         :class="['p-4 border-b border-slate-100 cursor-pointer hover:bg-slate-100 transition-colors relative', activeConversation?.id === conv.id ? 'bg-indigo-50/50 border-l-4 border-l-admin-primary' : 'border-l-4 border-transparent']">
                        
                        <!-- Unread Badge -->
                        <div v-if="conv.unread_count > 0" class="absolute right-4 top-1/2 -translate-y-1/2 bg-admin-alert h-5 min-w-[20px] px-1.5 flex items-center justify-center text-[10px] font-bold text-white rounded-full shadow-md z-20">
                            {{ conv.unread_count }}
                        </div>
                        <div v-else-if="conv.last_incoming_at > conv.last_outgoing_at" class="absolute right-4 top-1/2 -translate-y-1/2 w-2.5 h-2.5 bg-admin-alert rounded-full animate-pulse shadow-sm" title="Needs Reply"></div>

                        <!-- Name & Time -->
                        <div class="flex justify-between items-start mb-1">
                            <span class="font-bold text-slate-800 text-sm">{{ conv.contact?.name || 'Unknown' }}</span>
                            <span class="text-[10px] text-slate-400 font-medium uppercase">{{ formatDate(conv.last_message_at) }}</span>
                        </div>
                        
                        <!-- Last Message Preview -->
                        <p class="text-xs text-slate-500 truncate pr-6 mb-1">
                            {{ conv.last_message_preview || 'No messages yet' }}
                        </p>

                        <!-- Assignee -->
                        <div class="flex items-center gap-2 mt-2">
                             <div v-if="conv.assignee" class="flex items-center gap-1 bg-slate-200/50 px-1.5 py-0.5 rounded text-[10px] text-slate-600 border border-slate-200">
                                 <span class="material-symbols-outlined text-[12px]">person</span>
                                 {{ conv.assignee.name.split(' ')[0] }}
                             </div>
                             <div v-else class="text-[10px] text-slate-400 italic">Unassigned</div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredConversations.length === 0" class="p-12 flex flex-col items-center text-slate-400 text-center">
                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                            <span class="material-symbols-outlined text-3xl opacity-30">inbox</span>
                        </div>
                        <p class="font-medium text-sm text-slate-500">No conversations here</p>
                        <p class="text-xs mt-1">Try adjusting your filters</p>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════ -->
            <!-- RIGHT AREA: Chat Window                              -->
            <!-- ═══════════════════════════════════════════════════ -->
            <div class="chat-main" :class="{'hidden md:flex': !activeConversation}">
                <template v-if="activeConversation">

                    <!-- Chat Header -->
                    <div class="h-auto md:h-16 border-b border-slate-200 bg-white flex flex-wrap md:flex-nowrap items-center px-3 md:px-4 py-2 md:py-0 shadow-sm z-30 w-full justify-between gap-2">
                        <div class="flex items-center gap-2 md:gap-3 min-w-0">
                            <!-- Mobile back button -->
                            <button @click="activeConversation = null" class="md:hidden p-1.5 rounded-lg hover:bg-slate-100 text-slate-500 shrink-0">
                                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                            </button>
                            <!-- Avatar -->
                            <div class="relative">
                                <div class="w-10 h-10 rounded-full bg-slate-100 text-admin-primary flex items-center justify-center font-bold text-lg border border-slate-200">
                                    {{ activeConversation.contact?.name?.charAt(0) || '?' }}
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-white rounded-full flex items-center justify-center shadow-sm p-0.5">
                                    <span class="material-symbols-outlined text-[10px]" :class="activeConversation.platform === 'whatsapp' ? 'text-green-500' : 'text-blue-500'">
                                        {{ activeConversation.platform === 'whatsapp' ? 'chat' : 'send' }}
                                    </span>
                                </div>
                            </div>
                            <!-- Name & Status -->
                            <div>
                                <div class="flex items-center gap-2 leading-tight">
                                    <h3 class="font-bold text-slate-800 text-sm">{{ activeConversation.contact?.name }}</h3>
                                    <!-- Green dot = WebSocket connected, Red dot = disconnected (polling active) -->
                                    <div :class="['w-2 h-2 rounded-full shadow-sm', isConnected ? 'bg-green-500 animate-pulse' : 'bg-amber-400']" 
                                         :title="isConnected ? 'Real-time connected' : 'Polling mode (auto-refresh every 10s)'"></div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-px rounded font-medium border border-slate-200 truncate max-w-[120px]">
                                        {{ activeConversation.contact?.phone || activeConversation.contact?.telegram_username }}
                                    </span>
                                    <span :class="['text-[10px] px-1.5 py-px rounded font-bold uppercase', activeConversation.status === 'open' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                                        {{ activeConversation.status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Header Actions -->
                        <div class="flex items-center gap-2 md:gap-3 ml-auto shrink-0">
                            <!-- Assignee Selector -->
                            <div class="hidden sm:flex items-center gap-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter hidden lg:inline">Assigned to:</span>
                                <select @change="assignConversation($event.target.value)" :value="activeConversation.assigned_to" class="text-xs h-8 border-slate-200 rounded-md bg-slate-50 py-0 pr-8 min-w-[100px] lg:min-w-[120px]">
                                    <option :value="null">Unassigned</option>
                                    <option v-for="user in teamMembers" :key="user.id" :value="user.id">{{ user.name }}</option>
                                </select>
                            </div>

                            <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>

                            <!-- Close / Re-open Chat -->
                            <button @click="toggleStatus" class="btn h-8 py-0 px-2 md:px-3 text-xs flex items-center gap-1" :class="activeConversation.status === 'open' ? 'btn-outline' : 'btn-success text-white'">
                                <span class="material-symbols-outlined text-sm">{{ activeConversation.status === 'open' ? 'check_circle' : 'restore' }}</span>
                                <span class="hidden sm:inline">{{ activeConversation.status === 'open' ? 'Close Chat' : 'Re-open' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Messages Area -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-4" ref="messagesContainer">
                        <!-- Loading more older messages -->
                        <div v-if="loadingMore" class="flex justify-center py-4">
                             <div class="flex items-center gap-2 text-xs text-slate-400 italic bg-slate-50 px-3 py-1 rounded-full border border-slate-100">
                                <div class="animate-spin w-3 h-3 border-2 border-slate-200 border-t-admin-primary rounded-full"></div>
                                Loading history...
                             </div>
                        </div>

                        <!-- End of history indicator -->
                        <div v-if="!hasMoreMessages && !hasMoreNotes && messages.length > 0 && !loadingMessages" class="flex justify-center py-8">
                             <div class="flex items-center gap-2 text-[10px] text-slate-300 uppercase font-bold tracking-widest bg-slate-50/50 px-4 py-1.5 rounded-full border border-dashed border-slate-200">
                                <span class="material-symbols-outlined text-sm">history</span>
                                Reached the beginning of the chat
                             </div>
                        </div>

                        <!-- Loading initial batch -->
                        <div v-if="loadingMessages" class="flex justify-center py-8">
                            <div class="animate-spin w-6 h-6 border-2 border-slate-300 border-t-admin-primary rounded-full"></div>
                        </div>

                        <div v-for="msg in messages" :key="msg.id" 
                             :class="['flex w-full', msg.direction === 'outbound' ? 'justify-end' : 'justify-start']">
                            
                            <!-- Internal Note Style -->
                            <div v-if="msg.is_note" class="w-full flex justify-center my-2">
                                <div class="bg-amber-50 border border-amber-100 text-amber-800 text-xs px-4 py-2 rounded-lg max-w-[80%] shadow-sm relative italic">
                                    <div class="flex items-center gap-2 mb-1 font-bold opacity-70">
                                        <span class="material-symbols-outlined text-[14px]">sticky_note_2</span>
                                        INTERNAL NOTE  |  {{ msg.user?.name || 'User' }}
                                    </div>
                                    {{ msg.body }}
                                    <div class="text-[9px] text-amber-600/50 mt-1 text-right">{{ formatTime(msg.created_at) }}</div>
                                </div>
                            </div>

                            <!-- Regular Message Style -->
                            <div v-else :class="[
                                'max-w-[75%] rounded-2xl px-4 py-2.5 shadow-sm text-sm border',
                                msg.direction === 'outbound' ? 'bg-admin-primary text-white border-admin-primary rounded-br-sm' : 'bg-white border-slate-200 text-slate-800 rounded-bl-sm'
                            ]">
                                <!-- Image -->
                                <div v-if="msg.type === 'image'" class="mb-2">
                                    <img :src="msg.media_url" class="rounded-lg max-h-60 object-cover cursor-pointer hover:opacity-90 transition-opacity" @click="openMedia(msg.media_url)"/>
                                </div>
                                <!-- Video -->
                                <div v-else-if="msg.type === 'video'" class="mb-2">
                                    <video :src="msg.media_url" controls class="rounded-lg max-h-60 w-full"></video>
                                </div>
                                <!-- Document -->
                                <div v-else-if="msg.type === 'document'" class="mb-2 flex items-center gap-2 p-2 bg-black/5 rounded-lg border border-black/10">
                                    <span class="material-symbols-outlined">description</span>
                                    <span class="truncate max-w-[150px] font-medium">{{ msg.caption || 'Document' }}</span>
                                    <a :href="msg.media_url" target="_blank" class="material-symbols-outlined text-sm hover:text-indigo-500">download</a>
                                </div>

                                <!-- Text Body -->
                                <p v-if="msg.body && msg.type === 'text'" class="whitespace-pre-wrap leading-relaxed" v-text="msg.body"></p>
                                <p v-else-if="msg.caption" class="whitespace-pre-wrap leading-relaxed italic text-xs mt-1" v-text="msg.caption"></p>
                                
                                <!-- Timestamp & Status -->
                                <div :class="['text-[10px] mt-1 text-right flex items-center justify-end gap-1', msg.direction === 'outbound' ? 'text-indigo-200/80' : 'text-slate-400']">
                                    {{ formatTime(msg.sent_at) }}
                                    <span v-if="msg.direction === 'outbound'" class="material-symbols-outlined text-[14px]">
                                        {{ getStatusIcon(msg.status) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- No messages yet -->
                        <div v-if="!loadingMessages && messages.length === 0" class="flex flex-col items-center justify-center py-12 text-slate-400">
                            <span class="material-symbols-outlined text-4xl mb-2 opacity-30">chat_bubble_outline</span>
                            <p class="text-sm">No messages yet. Start the conversation!</p>
                        </div>
                    </div>

                    <!-- Input Area -->
                    <div class="bg-white border-t border-slate-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-20">
                        <!-- Reply / Note Tabs -->
                        <div class="flex border-b border-slate-100">
                             <button @click="inputMode = 'reply'" :class="['px-6 py-2.5 text-xs font-bold transition-all border-b-2', inputMode === 'reply' ? 'border-admin-primary text-admin-primary' : 'border-transparent text-slate-400 hover:text-slate-600']">REPLY</button>
                             <button @click="inputMode = 'note'" :class="['px-6 py-2.5 text-xs font-bold transition-all border-b-2', inputMode === 'note' ? 'border-amber-500 text-amber-600 bg-amber-50' : 'border-transparent text-slate-400 hover:text-slate-600']">INTERNAL NOTE</button>
                        </div>

                        <div class="p-4">
                            <!-- File Preview -->
                            <div v-if="selectedFile" class="mb-3 flex items-center gap-3 p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                 <div v-if="selectedFileType === 'image'" class="w-12 h-12 rounded bg-slate-200 overflow-hidden">
                                     <img :src="selectedFilePreview" class="w-full h-full object-cover" />
                                 </div>
                                 <div v-else class="w-12 h-12 rounded bg-indigo-100 flex items-center justify-center text-admin-primary">
                                     <span class="material-symbols-outlined">description</span>
                                 </div>
                                 <div class="flex-1 text-xs text-slate-600">
                                     <p class="font-bold truncate">{{ selectedFile.name }}</p>
                                     <p class="text-[10px] uppercase font-medium opacity-50">{{ selectedFileType }}</p>
                                 </div>
                                 <button @click="clearFile" class="text-slate-400 hover:text-red-500 material-symbols-outlined text-sm">close</button>
                            </div>

                            <!-- Reply Form -->
                            <form @submit.prevent="handleSubmit" class="flex gap-2 items-end">
                                <template v-if="inputMode === 'reply'">
                                    <input type="file" ref="fileInput" class="hidden" @change="handleFileSelect" accept="image/*,video/*,.pdf,.doc,.docx" />
                                    <button type="button" @click="$refs.fileInput.click()" class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-500 transition-all active:scale-95" :disabled="sending">
                                        <span class="material-symbols-outlined">attach_file</span>
                                    </button>
                                    
                                    <div class="flex-1 relative">
                                        <textarea 
                                            ref="messageInput"
                                            v-model="newMessage" 
                                            rows="1" 
                                            @keydown.enter.exact.prevent="handleSubmit" 
                                            class="input w-full bg-slate-50/50 border-slate-200 py-2.5 resize-none transition-all focus:bg-white" 
                                            :placeholder="selectedFile ? 'Add a caption...' : 'Type a message...'" 
                                            :disabled="sending"
                                        ></textarea>
                                        <button type="button" @click="showCanned = !showCanned" class="absolute right-2 bottom-2 text-slate-400 hover:text-indigo-500">
                                            <span class="material-symbols-outlined text-lg">bolt</span>
                                        </button>
                                        
                                        <!-- Canned Responses Quick Picker -->
                                        <div v-if="showCanned" class="absolute bottom-full left-0 w-full mb-2 bg-white border border-slate-200 rounded-lg shadow-xl z-50 overflow-hidden max-h-60 overflow-y-auto">
                                            <div v-for="res in cannedResponses" :key="res.id" @click="insertCanned(res.content)" class="p-3 text-xs border-b border-slate-50 hover:bg-slate-50 cursor-pointer transition-colors">
                                                <div class="font-bold text-indigo-600 mb-1">/{{ res.shortcut }}</div>
                                                <div class="text-slate-600 truncate opacity-80">{{ res.content }}</div>
                                            </div>
                                            <div v-if="cannedResponses.length === 0" class="p-4 text-center text-xs text-slate-400 italic">No templates found</div>
                                        </div>
                                    </div>
                                    
                                    <BaseButton variant="admin" type="submit" :loading="sending" :disabled="(!newMessage.trim() && !selectedFile) || sending" class="h-10 px-6 rounded-xl shadow-indigo-200/50">Send</BaseButton>
                                </template>
                                
                                <template v-else>
                                    <!-- Note Mode -->
                                    <textarea 
                                        ref="noteInput"
                                        v-model="newNote" 
                                        rows="2" 
                                        class="input flex-1 bg-amber-50/30 border-amber-200 focus:border-amber-400 focus:ring-amber-200 py-2.5 resize-none text-amber-900 placeholder-amber-400" 
                                        placeholder="Internal team note (visible only to you and your team)..."
                                    ></textarea>
                                    <BaseButton variant="danger" type="submit" :loading="sendingNote" :disabled="!newNote.trim() || sendingNote" class="h-10 px-6 bg-amber-500 hover:bg-amber-600 border-amber-500 rounded-xl">Add Note</BaseButton>
                                </template>
                            </form>
                        </div>
                    </div>
                </template>
                
                <!-- Empty State (No conversation selected) -->
                <div v-else class="flex-1 flex flex-col items-center justify-center text-slate-400 p-12 text-center">
                    <div class="w-32 h-32 bg-slate-50 rounded-full flex items-center justify-center mb-6 shadow-inner ring-4 ring-white border border-slate-100">
                         <span class="material-symbols-outlined text-6xl text-slate-200">mark_chat_unread</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-600 mb-2">Workspace Inbox</h3>
                    <p class="text-sm max-w-sm text-slate-400">Collaborate with your team members in real-time. Select a conversation to start providing support.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseButton from '@/Components/BaseButton.vue'

// ─── PROPS ──────────────────────────────────────────────────
const props = defineProps({
    conversations: Object,   // Paginated conversations from the server
    filters: Object,         // Current active filters
    teamMembers: Array,      // Workspace team members for assignment
    cannedResponses: Array,  // Quick-reply templates
})

// Get auth data from Inertia's shared data (guaranteed to be available)
const page = usePage()
const workspaceId = computed(() => page.props.auth?.user?.active_workspace_id)

// ─── REACTIVE STATE ─────────────────────────────────────────
const localConversations = ref([...props.conversations.data])
const activeConversation = ref(null)
const messages = ref([])
const loadingMessages = ref(false)
const newMessage = ref('')
const newNote = ref('')
const sending = ref(false)
const sendingNote = ref(false)
const inputMode = ref('reply')
const showCanned = ref(false)
const searchQuery = ref('')
const filters = ref({ ...props.filters })
const isConnected = ref(false)
const hasMoreMessages = ref(false)
const hasMoreNotes = ref(false)
const loadingMore = ref(false)

// Chat state persistence cache
const chatCache = ref({})

// Custom dropdown states
const dropdownEngagementOpen = ref(false)
const dropdownStatusOpen = ref(false)

// Template refs
const messagesContainer = ref(null)
const messageInput = ref(null)
const noteInput = ref(null)
const fileInput = ref(null)
const dropdownEngagementRef = ref(null)
const dropdownStatusRef = ref(null)

// File handling state
const selectedFile = ref(null)
const selectedFileType = ref('text')
const selectedFilePreview = ref(null)

// Polling timer (fallback when WebSocket is down)
let pollingTimer = null
let conversationPollingTimer = null

// ─── COMPUTED ───────────────────────────────────────────────

/**
 * Filter conversations locally by search query.
 */
const filteredConversations = computed(() => {
    if (!searchQuery.value.trim()) return localConversations.value
    
    const query = searchQuery.value.toLowerCase()
    return localConversations.value.filter(conv => {
        const name = conv.contact?.name?.toLowerCase() || ''
        const phone = conv.contact?.phone?.toLowerCase() || ''
        const preview = conv.last_message_preview?.toLowerCase() || ''
        return name.includes(query) || phone.includes(query) || preview.includes(query)
    })
})

// ─── FILTERING ──────────────────────────────────────────────

/**
 * Navigate to the inbox with updated filters.
 * Uses Inertia.js partial reload to only refresh conversations.
 */
const setFilter = (key, value) => {
    const freshFilters = { ...props.filters, [key]: value }
    
    // Persist filters to sessionStorage
    sessionStorage.setItem('inbox_filters', JSON.stringify(freshFilters))
    
    router.visit(route('inbox'), {
        data: freshFilters,
        preserveState: true,
        preserveScroll: true,
        only: ['conversations', 'filters']
    })
}

// ─── CONVERSATION ACTIONS ──────────────────────────────────

/**
 * Assign a conversation to a team member.
 */
const assignConversation = async (userId) => {
    if (!activeConversation.value) return
    try {
        await axios.post(route('inbox.assign', activeConversation.value.id), { user_id: userId })
        activeConversation.value.assigned_to = userId
        
        // Update sidebar display
        const idx = localConversations.value.findIndex(c => c.id === activeConversation.value.id)
        if (idx !== -1) {
            localConversations.value[idx].assignee = props.teamMembers.find(u => u.id == userId)
        }
    } catch (e) { 
        console.error('Assignment failed', e) 
    }
}

/**
 * Toggle conversation status between open and closed.
 */
const toggleStatus = async () => {
    if (!activeConversation.value) return
    const newStatus = activeConversation.value.status === 'open' ? 'closed' : 'open'
    try {
        await axios.post(route('inbox.status', activeConversation.value.id), { status: newStatus })
        activeConversation.value.status = newStatus
    } catch (e) { 
        console.error('Status update failed', e) 
    }
}

/**
 * Insert a canned response into the message input.
 */
const insertCanned = (content) => {
    newMessage.value = content
    showCanned.value = false
    nextTick(() => messageInput.value?.focus())
}

// ─── FILE HANDLING ──────────────────────────────────────────

const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (!file) return

    selectedFile.value = file
    const type = file.type
    if (type.startsWith('image/')) selectedFileType.value = 'image'
    else if (type.startsWith('video/')) selectedFileType.value = 'video'
    else selectedFileType.value = 'document'

    if (selectedFileType.value === 'image') {
        const reader = new FileReader()
        reader.onload = (e) => selectedFilePreview.value = e.target.result
        reader.readAsDataURL(file)
    }
}

const clearFile = () => {
    selectedFile.value = null
    selectedFileType.value = 'text'
    selectedFilePreview.value = null
    if (fileInput.value) fileInput.value.value = ''
    nextTick(() => messageInput.value?.focus())
}

const openMedia = (url) => { window.open(url, '_blank') }

// ─── MESSAGING ──────────────────────────────────────────────

const handleSubmit = () => {
    if (inputMode.value === 'note') addInternalNote()
    else sendMessage()
}

/**
 * Select and load a conversation's messages.
 */
const selectConversation = async (conv) => {
    // 1. Save current state to cache
    if (activeConversation.value) {
        chatCache.value[activeConversation.value.id] = {
            messages: [...messages.value],
            hasMoreMessages: hasMoreMessages.value,
            hasMoreNotes: hasMoreNotes.value,
            scrollTop: messagesContainer.value?.scrollTop || 0
        }
    }

    activeConversation.value = conv

    // Clear unread badge in the sidebar
    const c = localConversations.value.find(x => x.id === conv.id)
    if (c) c.unread_count = 0

    // 2. Try to restore from cache
    const cached = chatCache.value[conv.id]
    if (cached) {
        messages.value = [...cached.messages]
        hasMoreMessages.value = cached.hasMoreMessages
        hasMoreNotes.value = cached.hasMoreNotes
        
        // Restore scroll position instantly
        nextTick(() => {
            if (messagesContainer.value) {
                messagesContainer.value.scrollTop = cached.scrollTop
            }
            messageInput.value?.focus()
            setupScrollListener()
        })
        
        // Optional: you could fetch NEW messages here to sync, 
        // but polling/websockets will handle it anyway.
        return
    }

    // 3. Full load if not cached
    loadingMessages.value = true
    hasMoreMessages.value = false
    hasMoreNotes.value = false
    
    try {
        // Fetch initial batch of messages and notes
        const [msgResponse, notesResponse] = await Promise.all([
            axios.get(route('inbox.show', conv.id)),
            axios.get(route('inbox.notes', conv.id)),
        ])

        hasMoreMessages.value = msgResponse.data.has_more
        hasMoreNotes.value = notesResponse.data.has_more

        const mixed = [
            ...msgResponse.data.messages.map(m => ({ ...m, is_note: false })),
            ...notesResponse.data.notes.map(n => ({ ...n, is_note: true, sent_at: n.created_at }))
        ].sort((a, b) => new Date(a.sent_at || a.created_at) - new Date(b.sent_at || b.created_at))
        
        messages.value = mixed
        nextTick(() => {
            messageInput.value?.focus()
            setupScrollListener()
        })
    } catch (error) {
        console.error('Failed to load conversation details', error)
    } finally {
        loadingMessages.value = false
        // Scroll to bottom after loading is finished and DOM is updated
        scrollToBottom()
    }

    // 4. Persist selection to sessionStorage
    sessionStorage.setItem('last_active_conversation_id', conv.id)
}

/**
 * Load older messages and notes when scrolling up.
 */
const loadMoreMessages = async () => {
    if (!activeConversation.value || loadingMore.value || (!hasMoreMessages.value && !hasMoreNotes.value)) return

    const oldestItem = messages.value[0]
    if (!oldestItem) return

    const beforeTimestamp = oldestItem.sent_at || oldestItem.created_at
    loadingMore.value = true

    // Capture scroll position before loading
    const container = messagesContainer.value
    const previousHeight = container.scrollHeight
    const previousScrollTop = container.scrollTop

    try {
        const [msgResponse, notesResponse] = await Promise.all([
            hasMoreMessages.value ? axios.get(route('inbox.show', activeConversation.value.id), { params: { before: beforeTimestamp } }) : Promise.resolve({ data: { messages: [], has_more: false } }),
            hasMoreNotes.value ? axios.get(route('inbox.notes', activeConversation.value.id), { params: { before: beforeTimestamp } }) : Promise.resolve({ data: { notes: [], has_more: false } })
        ])

        hasMoreMessages.value = msgResponse.data.has_more
        hasMoreNotes.value = notesResponse.data.has_more

        const olderMixed = [
            ...msgResponse.data.messages.map(m => ({ ...m, is_note: false })),
            ...notesResponse.data.notes.map(n => ({ ...n, is_note: true, sent_at: n.created_at }))
        ].sort((a, b) => new Date(a.sent_at || a.created_at) - new Date(b.sent_at || b.created_at))

        if (olderMixed.length > 0) {
            messages.value = [...olderMixed, ...messages.value]
            
            // Sync with cache
            if (activeConversation.value && chatCache.value[activeConversation.value.id]) {
                const cached = chatCache.value[activeConversation.value.id]
                cached.messages = [...messages.value]
                cached.hasMoreMessages = hasMoreMessages.value
                cached.hasMoreNotes = hasMoreNotes.value
            }

            // Adjust scroll to prevent jumping
            nextTick(() => {
                container.scrollTop = container.scrollHeight - previousHeight + previousScrollTop
            })
        }
    } catch (e) {
        console.error('Failed to load more messages', e)
    } finally {
        loadingMore.value = false
    }
}

/**
 * Attach scroll listener to the messages container.
 */
const setupScrollListener = () => {
    if (messagesContainer.value) {
        messagesContainer.value.addEventListener('scroll', handleScroll)
    }
}

const handleScroll = () => {
    const container = messagesContainer.value
    if (container && container.scrollTop === 0) {
        loadMoreMessages()
    }
}

/**
 * Add an internal note (not visible to the customer).
 */
const addInternalNote = async () => {
    if (!newNote.value.trim() || !activeConversation.value) return
    const body = newNote.value
    newNote.value = ''
    sendingNote.value = true
    try {
        const { data } = await axios.post(route('inbox.notes.store', activeConversation.value.id), { body })
        if (data.success) {
            const newNote = { ...data.note, is_note: true }
            messages.value.push(newNote)
            
            // Sync with cache
            if (activeConversation.value && chatCache.value[activeConversation.value.id]) {
                chatCache.value[activeConversation.value.id].messages.push(newNote)
            }
            
            scrollToBottom()
        }
    } catch (e) { 
        console.error('Note creation failed', e)
        newNote.value = body  // Restore on fail
    } finally { 
        sendingNote.value = false 
        nextTick(() => noteInput.value?.focus())
    }
}

/**
 * Send a reply message (text, image, video, or document).
 * Creates an optimistic "sending" message first, then sends via API in background.
 */
const sendMessage = async () => {
    if ((!newMessage.value.trim() && !selectedFile.value) || !activeConversation.value) return
    
    const body = newMessage.value
    const isMedia = !!selectedFile.value
    const mediaType = isMedia ? selectedFileType.value : 'text'
    const conversationId = activeConversation.value.id
    
    // Optimistic UI: show message immediately with "sending" status
    const tempMsg = {
        id: 'temp-' + Date.now(),
        direction: 'outbound',
        type: mediaType,
        body: body,
        media_url: selectedFilePreview.value,
        caption: isMedia ? body : null,
        status: 'sending',
        sent_at: new Date().toISOString(),
        is_note: false
    }
    messages.value.push(tempMsg)
    const previewBackup = selectedFilePreview.value
    
    // Clear input and re-enable immediately — don't wait for server
    newMessage.value = ''
    clearFile()
    scrollToBottom()
    nextTick(() => messageInput.value?.focus())

    // Move conversation to top of sidebar immediately
    moveConversationToTop(conversationId, tempMsg.sent_at, truncate(body || `[${mediaType}]`, 100))

    // Fire-and-forget: send to server in background
    try {
        const payload = {
            body: isMedia ? null : body,
            type: mediaType,
            media_url: isMedia ? 'https://placehold.co/600x400?text=Uploaded+Media' : null,
            caption: isMedia ? body : null
        }
        const { data } = await axios.post(route('inbox.reply', conversationId), payload)
        
        if (data.success) {
            const realMsg = { ...data.message, is_note: false }
            // Replace the temp message with the real one from the server
            const index = messages.value.findIndex(m => m.id === tempMsg.id)
            if (index !== -1) {
                messages.value[index] = realMsg
                if (isMedia) messages.value[index].media_url = previewBackup
            }

            // Sync with cache
            const cached = chatCache.value[conversationId]
            if (cached) {
                const cachedIdx = cached.messages.findIndex(m => m.id === tempMsg.id)
                if (cachedIdx !== -1) {
                    cached.messages[cachedIdx] = realMsg
                    if (isMedia) cached.messages[cachedIdx].media_url = previewBackup
                }
            }
        }
    } catch (error) {
        // Mark message as failed — user can see the (!) icon
        const m = messages.value.find(m => m.id === tempMsg.id)
        if (m) m.status = 'failed'

        // Sync with cache
        const cached = chatCache.value[conversationId]
        if (cached) {
            const cachedM = cached.messages.find(m => m.id === tempMsg.id)
            if (cachedM) cachedM.status = 'failed'
        }
    }
}


// ─── HELPERS ────────────────────────────────────────────────

/** Truncate a string to a max length. */
const truncate = (str, len) => {
    if (!str) return ''
    return str.length > len ? str.substring(0, len) + '...' : str
}

/** Format a date for the sidebar (today = time, otherwise = date). */
const formatDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    const now = new Date()
    if (date.toDateString() === now.toDateString()) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    }
    return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
}

/** Format a date/time for message timestamps. */
const formatTime = (dateString) => {
    if (!dateString) return ''
    return new Date(dateString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

/** Get the Material icon name for a message status. */
const getStatusIcon = (status) => {
    switch (status) {
        case 'sent':       return 'check'
        case 'delivered':  return 'done_all'
        case 'read':       return 'visibility'
        case 'pending':
        case 'sending':    return 'schedule'
        default:           return 'error'
    }
}

/** Scroll the messages container to the bottom. */
const scrollToBottom = (behavior = 'auto') => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTo({
                top: messagesContainer.value.scrollHeight + 1000,
                behavior: behavior
            })
        }
    })
}

/** Move a conversation to the top of the sidebar list. */
const moveConversationToTop = (convId, lastMessageAt, preview) => {
    const idx = localConversations.value.findIndex(c => c.id === convId)
    if (idx !== -1) {
        const c = localConversations.value.splice(idx, 1)[0]
        c.last_message_at = lastMessageAt
        c.last_message_preview = preview
        localConversations.value.unshift(c)
    }
}

// ─── POLLING FALLBACK ──────────────────────────────────────
// When WebSocket (Reverb) is not connected, we poll the server
// every 10 seconds to keep messages in sync.

/**
 * Poll the current conversation for new messages.
 * Only runs when WebSocket is NOT connected.
 */
const pollMessages = async () => {
    if (isConnected.value || !activeConversation.value) return

    try {
        const { data } = await axios.get(route('inbox.show', activeConversation.value.id))
        const serverMessages = data.messages

        // Filter out messages we already have locally to find NEW messages
        const localIds = new Set(messages.value.filter(m => !m.is_note).map(m => m.id))
        const newMessages = serverMessages.filter(m => !localIds.has(m.id))

        if (newMessages.length > 0) {
            const newerMixed = newMessages.map(m => ({ ...m, is_note: false }))
            messages.value = [...messages.value, ...newerMixed]
            
            // Sync with cache
            if (activeConversation.value && chatCache.value[activeConversation.value.id]) {
                chatCache.value[activeConversation.value.id].messages = [...messages.value]
            }
            
            scrollToBottom()
        } else {
            // Just update statuses of existing messages found in the latest batch
            for (const serverMsg of serverMessages) {
                const localMsg = messages.value.find(m => m.id === serverMsg.id)
                if (localMsg && localMsg.status !== serverMsg.status) {
                    localMsg.status = serverMsg.status
                }
            }

            // Sync statuses with cache
            if (activeConversation.value && chatCache.value[activeConversation.value.id]) {
                chatCache.value[activeConversation.value.id].messages = [...messages.value]
            }
        }

        // Update conversation metadata
        if (data.conversation) {
            activeConversation.value.status = data.conversation.status
            activeConversation.value.unread_count = 0
        }
    } catch (e) {
        console.warn('Polling messages failed:', e.message)
    }
}

/**
 * Poll the conversation list for sidebar updates.
 * Refreshes the full list from the server every 15 seconds.
 */
const pollConversations = () => {
    if (isConnected.value) return

    router.reload({
        only: ['conversations'],
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // Keep the updated data without losing active selection
        }
    })
}

/**
 * Start polling timers (only when WebSocket is down).
 */
const startPolling = () => {
    stopPolling()  // Clear any existing timers first
    pollingTimer = setInterval(pollMessages, 5000)              // Messages every 5s
    conversationPollingTimer = setInterval(pollConversations, 8000) // Conversations every 8s
    console.log('[Inbox] Polling started (WebSocket not available)')
}

/**
 * Stop polling timers (when WebSocket connects).
 */
const stopPolling = () => {
    if (pollingTimer) { clearInterval(pollingTimer); pollingTimer = null }
    if (conversationPollingTimer) { clearInterval(conversationPollingTimer); conversationPollingTimer = null }
}

// ─── REAL-TIME (WebSocket via Laravel Echo) ─────────────────

/**
 * Handle an incoming real-time MessageReceived event.
 * Updates both the chat area and the sidebar.
 */
const handleRealtimeMessage = (e) => {
    const incomingMessage = e.message
    if (!incomingMessage) return

    // If we're viewing this conversation, add the message to the chat
    if (activeConversation.value?.id === incomingMessage.conversation_id) {
        // Guard against duplicates
        if (!messages.value.find(m => m.id === incomingMessage.id)) {
            messages.value.push({ ...incomingMessage, is_note: false })
            scrollToBottom()
        }
    }

    // Also update cache for this conversation if it exists
    const cached = chatCache.value[incomingMessage.conversation_id]
    if (cached) {
        if (!cached.messages.find(m => m.id === incomingMessage.id)) {
            cached.messages.push({ ...incomingMessage, is_note: false })
        }
    }

    // Update the sidebar
    const idx = localConversations.value.findIndex(c => c.id === incomingMessage.conversation_id)
    if (idx !== -1) {
        const c = localConversations.value.splice(idx, 1)[0]
        c.last_message_at = incomingMessage.sent_at
        c.last_message_preview = truncate(incomingMessage.body || `[${incomingMessage.type}]`, 100)
        // Increment unread only if we're not viewing this conversation
        if (activeConversation.value?.id !== c.id) {
            c.unread_count = (c.unread_count || 0) + 1
        }
        localConversations.value.unshift(c)
    } else {
        // New conversation — reload the list from server
        router.reload({ only: ['conversations'] })
    }
}

/**
 * Handle an incoming real-time MessageStatusUpdated event.
 * Updates the tick marks (sent/delivered/read) on messages.
 */
const handleRealtimeStatusUpdate = (e) => {
    const updatedMsg = e.message
    if (!updatedMsg) return

    const index = messages.value.findIndex(m => m.id === updatedMsg.id)
    if (index !== -1) {
        messages.value[index].status = updatedMsg.status
    }

    // Also update cache if it exists
    const cached = chatCache.value[updatedMsg.conversation_id]
    if (cached) {
        const cachedIdx = cached.messages.findIndex(m => m.id === updatedMsg.id)
        if (cachedIdx !== -1) {
            cached.messages[cachedIdx].status = updatedMsg.status
        }
    }
}

// ─── LIFECYCLE ──────────────────────────────────────────────

// Sync conversation list when Inertia reloads
watch(() => props.conversations, (newVal) => {
    if (newVal?.data) {
        localConversations.value = [...newVal.data]
    }
}, { deep: true })

const setupWebSockets = () => {
    // Try to set up WebSocket (Echo / Reverb)
    let echoSetupSuccess = false

    if (window.Echo && workspaceId.value) {
        try {
            const echoConnection = window.Echo.connector.pusher.connection

            // Track connection state
            const updateConnectionStatus = () => {
                const wasConnected = isConnected.value
                isConnected.value = echoConnection.state === 'connected'

                // Start/stop polling based on current connection state
                if (isConnected.value) {
                    if (!wasConnected) console.log('[Inbox] WebSocket connected — stopping polling')
                    stopPolling()
                } else {
                    if (wasConnected) console.log('[Inbox] WebSocket disconnected — starting polling fallback')
                    startPolling() // Always ensure polling is running if we are not connected
                }
            }

            echoConnection.bind('state_change', updateConnectionStatus)
            updateConnectionStatus()

            // Subscribe to the workspace channel
            const channel = `workspace.${workspaceId.value}`
            console.log('[Inbox] Subscribing to channel:', channel)

            window.Echo.private(channel)
                .listen('MessageReceived', handleRealtimeMessage)
                .listen('MessageStatusUpdated', handleRealtimeStatusUpdate)

            echoSetupSuccess = true
        } catch (e) {
            console.warn('[Inbox] Echo setup failed:', e.message)
        }
    }

    // If Echo is not available or setup failed, start polling immediately
    if (!echoSetupSuccess) {
        console.log('[Inbox] Echo not available — using polling fallback')
        startPolling()
    }
    
    // Setup click-outside listener for custom dropdowns
    document.addEventListener('click', closeDropdowns)
}

onMounted(() => {
    // Restore previous filters if not already provided in URL
    const hasUrlFilters = Object.values(usePage().props.filters || {}).some(v => !!v)
    if (!hasUrlFilters) {
        const savedFilters = sessionStorage.getItem('inbox_filters')
        if (savedFilters) {
            try {
                const parsed = JSON.parse(savedFilters)
                // Filter out empty values and only visit if there are actual filters to apply
                const activeFilters = Object.fromEntries(Object.entries(parsed).filter(([_, v]) => !!v))
                if (Object.keys(activeFilters).length > 0) {
                    router.visit(route('inbox'), { 
                        data: activeFilters, 
                        replace: true,
                        preserveScroll: true 
                    })
                }
            } catch (e) {
                console.error('Failed to parse saved filters', e)
            }
        }
    }

    // Restore previous conversation if available
    const savedId = sessionStorage.getItem('last_active_conversation_id')
    if (savedId) {
        const conv = localConversations.value.find(c => c.id == savedId)
        if (conv) selectConversation(conv)
    }

    setupWebSockets()
})

onUnmounted(() => {
    // Clean up WebSocket subscription
    if (window.Echo && workspaceId.value) {
        window.Echo.leave(`workspace.${workspaceId.value}`)
    }
    // Clean up polling timers
    stopPolling()
    // Clean up click-outside listener
    document.removeEventListener('click', closeDropdowns)
})

// Close dropdowns when clicking outside
const closeDropdowns = (e) => {
    if (dropdownEngagementOpen.value && dropdownEngagementRef.value && !dropdownEngagementRef.value.contains(e.target)) {
        dropdownEngagementOpen.value = false
    }
    if (dropdownStatusOpen.value && dropdownStatusRef.value && !dropdownStatusRef.value.contains(e.target)) {
        dropdownStatusOpen.value = false
    }
}
</script>
