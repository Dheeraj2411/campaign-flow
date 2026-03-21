<script setup>
import { computed, ref, onMounted } from 'vue';
import { useInboxStore } from '@/stores/inbox';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    conversation: {
        type: Object,
        default: () => ({})
    }
});

const store = useInboxStore();
const page = usePage();
const currentUser = computed(() => page.props.auth.user);

const contactName = computed(() => props.conversation?.contact?.name || 'Unknown Contact');
const contactPhone = computed(() => props.conversation?.contact?.phone || props.conversation?.contact?.telegram_username || '');
const status = computed(() => props.conversation?.status || 'open');
const currentAssignee = computed(() => props.conversation?.assigned_to || '');

// Bot state
const isBotActive = computed(() => props.conversation?.bot_active ?? true);
const hasBotEscalated = computed(() => !!props.conversation?.bot_escalated_at);

const toggleBot = async () => {
    if (!props.conversation?.id) return;
    try {
        const res = await axios.post(`/conversations/${props.conversation.id}/toggle-bot`);
        // eslint-disable-next-line vue/no-mutating-props
        props.conversation.bot_active = res.data.bot_active;
    } catch(e) {
        console.error('Failed to toggle bot', e);
    }
};

// Multi-Agent locks
const lockData = computed(() => store.lockedConversations[props.conversation?.id]);
const isLockedByOther = computed(() => lockData.value && lockData.value.lockedBy !== currentUser.value.id && lockData.value.lockedBy !== 'me');
const isLockedByMe = computed(() => lockData.value && (lockData.value.lockedBy === currentUser.value.id || lockData.value.lockedBy === 'me'));

const goBack = () => {
    store.activeConversationId = null;
};

const workspaceUsers = ref([]);
const fetchUsers = async () => {
    try {
        const response = await axios.get('/api/workspace/users');
        workspaceUsers.value = response.data.data || response.data || [];
    } catch (e) {
        // Fallback gracefully 
    }
};

onMounted(() => {
    fetchUsers();
});

const handleAssign = (event) => {
    const agentId = event.target.value;
    if (props.conversation?.id) {
        store.assignConversation(props.conversation.id, agentId || null);
    }
};
</script>

<template>
    <div v-if="conversation">
        <!-- Lock Banner -->
        <div v-if="isLockedByOther" class="w-full bg-red-100 text-red-700 text-sm font-medium px-6 py-2 flex items-center justify-center">
            <span class="material-symbols-outlined text-sm mr-2">lock</span>
            Being replied to by {{ lockData.lockedByName }} — view only
        </div>
        <div v-else-if="isLockedByMe" class="w-full bg-green-100 text-green-700 text-sm font-medium px-6 py-2 flex items-center justify-center">
            <span class="material-symbols-outlined text-sm mr-2">edit</span>
            You are replying
        </div>

        <div class="px-6 py-4 border-b border-gray-200 bg-white flex flex-col md:flex-row justify-between md:items-center shadow-sm z-10 gap-3 md:gap-0">
            <div class="flex items-center">
                <button class="md:hidden mr-3 p-1 rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 transition" @click="goBack">
                    <span class="material-symbols-outlined text-[20px] block">arrow_back</span>
                </button>
                <div class="w-11 h-11 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-lg uppercase shadow-sm mr-4 shrink-0">
                    {{ contactName.charAt(0) }}
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        {{ contactName }}
                        
                        <!-- BOT BADGES -->
                        <span v-if="hasBotEscalated && !isBotActive" 
                              @click="toggleBot" 
                              class="cursor-pointer uppercase tracking-wider font-bold text-[10px] px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 flex items-center gap-1 hover:bg-orange-200 transition"
                              title="Click to re-enable bot">
                            <span class="material-symbols-outlined text-[12px]">smart_toy</span>
                            Escalated — Bot Off
                        </span>
                        <span v-else-if="isBotActive" 
                              @click="toggleBot" 
                              class="cursor-pointer uppercase tracking-wider font-bold text-[10px] px-2 py-0.5 rounded-full bg-green-100 text-green-700 flex items-center gap-1 hover:bg-green-200 transition"
                              title="Click to disable bot">
                            <span class="material-symbols-outlined text-[12px]">smart_toy</span>
                            Bot Active
                        </span>
                        <span v-else 
                              @click="toggleBot" 
                              class="cursor-pointer uppercase tracking-wider font-bold text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 flex items-center gap-1 hover:bg-gray-200 transition"
                              title="Click to enable bot">
                            <span class="material-symbols-outlined text-[12px]">smart_toy</span>
                            Bot Disabled
                        </span>
                    </h3>
                    <p class="text-xs text-gray-500 flex items-center mt-0.5">
                        <span class="material-symbols-outlined text-[14px] mr-1 text-gray-400">call</span>
                        {{ contactPhone }}
                        <span class="mx-3 text-gray-300">|</span>
                        <span class="uppercase tracking-wider font-bold text-[10px] px-2 py-0.5 rounded-full" :class="{
                            'bg-green-100 text-green-700': status === 'open',
                            'bg-yellow-100 text-yellow-700': status === 'pending',
                            'bg-gray-100 text-gray-600': status === 'closed'
                        }">
                            {{ status }}
                        </span>
                    </p>
                </div>
            </div>
            
            <div class="flex items-center space-x-3 mt-3 md:mt-0">
                <select 
                    @change="handleAssign" 
                    :value="currentAssignee"
                    class="text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-gray-700 font-medium py-2"
                >
                    <option value="">Unassigned</option>
                    <option v-for="user in workspaceUsers" :key="user.id" :value="user.id">
                        {{ user.name || user.email }}
                    </option>
                </select>
                
                <select class="text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-gray-700 font-medium py-2 capitalize">
                    <option value="open">Open</option>
                    <option value="pending">Pending</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
        </div>
    </div>
</template>
