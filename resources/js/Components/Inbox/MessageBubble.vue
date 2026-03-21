<script setup>
import { computed } from 'vue';

const props = defineProps({
    message: {
        type: Object,
        required: true,
    }
});

const isOutbound = computed(() => props.message.direction === 'outbound');

const timeFormatter = new Intl.DateTimeFormat('default', {
    hour: '2-digit',
    minute: '2-digit'
});

const formattedTime = computed(() => {
    if (!props.message.created_at) return '';
    return timeFormatter.format(new Date(props.message.created_at));
});

// Interactive metadata processing
const hasButtons = computed(() => !!props.message.metadata?.buttons?.length);
const buttons = computed(() => props.message.metadata?.buttons || []);

const hasList = computed(() => !!props.message.metadata?.list);
const listConfig = computed(() => props.message.metadata?.list || {});

const isInteractiveReply = computed(() => 
    props.message.type === 'button_reply' || props.message.type === 'list_reply'
);

</script>

<template>
    <div class="flex w-full" :class="isOutbound ? 'justify-end' : 'justify-start'">
        <div 
            class="max-w-[75%] relative flex flex-col gap-1"
        >
            <div
                class="px-4 py-2 shadow-sm"
                :class="isOutbound 
                    ? 'bg-blue-500 text-white rounded-2xl rounded-tr-sm' 
                    : isInteractiveReply 
                        ? 'bg-emerald-100 border border-emerald-200 text-emerald-900 rounded-2xl rounded-tl-sm font-bold'
                        : 'bg-white border border-gray-200 text-gray-800 rounded-2xl rounded-tl-sm'"
            >
                <!-- Render Body Content -->
                <div class="text-[15px] whitespace-pre-wrap break-words leading-relaxed">
                    <span v-if="isInteractiveReply" class="material-symbols-outlined text-[14px] align-middle mr-1">reply</span>
                    {{ message.body }}
                </div>
                
                <!-- Time & Status -->
                <div 
                    class="flex items-center space-x-1 mt-1 text-[11px]"
                    :class="isOutbound ? 'justify-end text-blue-100' : (isInteractiveReply ? 'justify-start text-emerald-600' : 'justify-start text-gray-400')"
                >
                    <span>{{ formattedTime }}</span>
                    
                    <template v-if="isOutbound">
                        <span v-if="message.status === 'failed'" class="material-symbols-outlined text-[13px] text-red-200">cancel</span>
                        <span v-else-if="message.status === 'read'" class="material-symbols-outlined text-[13px] text-blue-200">done_all</span>
                        <span v-else-if="message.status === 'delivered'" class="material-symbols-outlined text-[13px]">done_all</span>
                        <span v-else class="material-symbols-outlined text-[13px]">check</span>
                    </template>
                </div>
            </div>

            <!-- Interactivity Render UI (Outbound only or inbound preview) -->
            <div v-if="hasList" class="bg-white border border-gray-200 rounded-2xl rounded-tr-none overflow-hidden shadow-sm mt-1">
                <div v-if="listConfig.header_text" class="px-4 pt-3 pb-1 font-bold text-gray-800 border-b border-gray-50">
                    {{ listConfig.header_text }}
                </div>
                <button class="w-full px-4 py-3 flex items-center justify-center gap-2 text-blue-600 font-bold bg-blue-50 hover:bg-blue-100 transition">
                    <span class="material-symbols-outlined text-[18px]">list</span>
                    {{ listConfig.button_label || 'View Options' }}
                </button>
            </div>

            <div v-if="hasButtons" class="flex flex-col gap-1 mt-1">
                <button 
                    v-for="(btn, idx) in buttons" 
                    :key="idx"
                    class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-blue-600 font-bold shadow-sm hover:bg-gray-50 transition flex items-center justify-center gap-2"
                >
                    <span class="material-symbols-outlined text-[16px]">touch_app</span>
                    {{ btn.title || btn.text || 'Button' }}
                </button>
            </div>
        </div>
    </div>
</template>
