<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    contactId: {
        type: Number,
        required: true
    }
});

const timeline = ref([]);
const loading = ref(true);
const error = ref(null);

const fetchTimeline = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get(`/contacts/${props.contactId}/timeline`);
        timeline.value = response.data;
    } catch (err) {
        console.error('Failed to fetch contact timeline:', err);
        error.value = 'Could not load activity timeline.';
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchTimeline();
});

const getIcon = (type) => {
    if (type === 'campaign') return 'campaign';
    if (type === 'message') return 'chat';
    if (type === 'workflow') return 'bolt';
    return 'event';
};

const getIconClass = (type) => {
    if (type === 'campaign') return 'bg-blue-100 text-blue-600';
    if (type === 'message') return 'bg-emerald-100 text-emerald-600';
    if (type === 'workflow') return 'bg-purple-100 text-purple-600';
    return 'bg-gray-100 text-gray-600';
};

const formatTime = (isoString) => {
    const d = new Date(isoString);
    const now = new Date();
    const diffMs = now - d;
    
    // Within 24 hours -> print time (e.g. 10:30 AM)
    if (diffMs < 24 * 60 * 60 * 1000) {
        return 'Today at ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    // Otherwise -> print Date + Time
    return d.toLocaleDateString([], { month: 'short', day: 'numeric' }) + 
           ' at ' + 
           d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <div class="h-full flex flex-col">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-slate-800 text-base">Activity Timeline</h3>
            <button @click="fetchTimeline" class="text-indigo-600 hover:text-indigo-800 transition" title="Refresh">
                <span class="material-symbols-outlined text-sm">refresh</span>
            </button>
        </div>

        <div v-if="loading" class="flex-1 flex justify-center items-center py-12">
            <div class="w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
        </div>
        
        <div v-else-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm border border-red-100">
            {{ error }}
        </div>

        <div v-else-if="timeline.length === 0" class="flex-1 flex flex-col justify-center items-center py-12 text-slate-400">
            <span class="material-symbols-outlined text-4xl mb-2 opacity-30">history</span>
            <p class="text-sm">No recent activity for this contact.</p>
        </div>

        <div v-else class="relative space-y-4 before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-200 before:to-transparent">
            
            <div v-for="(event, index) in timeline" :key="index" class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                <!-- Icon -->
                <div class="absolute left-0 left-0 md:left-1/2 -translate-x-1/2 w-10 h-10 rounded-full border-4 border-white shadow flex items-center justify-center bg-white z-10" :class="getIconClass(event.type)">
                    <span class="material-symbols-outlined text-lg">{{ getIcon(event.type) }}</span>
                </div>
                
                <!-- Card -->
                <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] ml-14 md:ml-0 p-4 rounded-xl shadow-sm border border-slate-200 bg-white hover:border-indigo-200 hover:shadow-md transition">
                    <p class="text-sm text-slate-800 font-medium mb-1">
                        {{ event.description }}
                    </p>
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">
                        {{ formatTime(event.occurred_at) }}
                    </p>
                </div>
            </div>

        </div>
    </div>
</template>
