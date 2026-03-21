<script setup>
import { computed } from 'vue';

const props = defineProps({
    conversation: {
        type: Object,
        required: true,
    },
    isActive: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['click']);

const contactName = computed(() => props.conversation.contact?.name ?? 'Unknown Contact');
const preview = computed(() => props.conversation.last_message_preview || 'No messages yet');
const isOnline = computed(() => props.conversation.contact?.is_online ?? false);
const unreadCount = computed(() => props.conversation.unread_count || 0);

const formattedDate = computed(() => {
    if (!props.conversation.last_message_at) return '';
    const date = new Date(props.conversation.last_message_at);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
});

</script>

<template>
    <div 
        @click="emit('click')"
        class="px-4 py-3 border-b border-gray-100 cursor-pointer transition duration-150 relative"
        :class="isActive ? 'bg-indigo-50 border-l-4 border-l-indigo-500' : 'hover:bg-gray-50 border-l-4 border-l-transparent'"
    >
        <div class="flex items-center justify-between mb-1">
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold uppercase overflow-hidden shadow-sm">
                        {{ contactName.charAt(0) }}
                    </div>
                    <div v-if="isOnline" class="absolute bottom-0 right-0 w-3 h-3 rounded-full bg-green-500 border-2 border-white"></div>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 truncate w-32" :class="{'font-bold text-gray-900': unreadCount > 0}">
                        {{ contactName }}
                    </h3>
                    <div class="text-[10px] text-gray-400 uppercase tracking-wider h-3 font-medium">
                        {{ conversation.platform }}
                    </div>
                </div>
            </div>
            <div class="text-[11px] text-gray-400 whitespace-nowrap mt-1 self-start">
                {{ formattedDate }}
            </div>
        </div>
        
        <div class="flex justify-between items-center ml-13 pl-[52px]">
            <p class="text-xs text-gray-500 truncate pr-4" :class="{'text-gray-800 font-medium': unreadCount > 0}">
                {{ preview }}
            </p>
            <div v-if="unreadCount > 0" class="bg-indigo-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0 shadow-sm">
                {{ unreadCount }}
            </div>
        </div>
    </div>
</template>
