<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    campaignId: { type: Number, required: true },
});

const funnel = ref(null);
const loading = ref(true);
const error = ref(null);

onMounted(async () => {
    try {
        const res = await fetch(`/campaigns/${props.campaignId}/funnel`);
        if (!res.ok) throw new Error('Failed to load funnel data');
        funnel.value = await res.json();
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
});

const barWidth = (count) => {
    if (!funnel.value) return '0%';
    const max = funnel.value.sent || 1;
    return Math.max((count / max) * 100, 4) + '%';
};

const rate = (count) => {
    if (!funnel.value || !funnel.value.sent) return '0%';
    return ((count / funnel.value.sent) * 100).toFixed(1) + '%';
};
</script>

<template>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 mb-5 flex items-center">
            <span class="material-symbols-outlined mr-2 text-indigo-500">filter_alt</span>
            Delivery Funnel
        </h3>

        <div v-if="loading" class="text-center py-8 text-gray-400">
            Loading funnel data…
        </div>

        <div v-else-if="error" class="text-center py-6 text-red-500 text-sm">
            {{ error }}
        </div>

        <div v-else-if="funnel" class="space-y-4">
            <!-- Sent -->
            <div class="flex items-center gap-3">
                <span class="w-20 text-sm font-medium text-gray-600 text-right">Sent</span>
                <div class="flex-1 bg-gray-100 rounded-full h-8 overflow-hidden">
                    <div class="bg-blue-500 h-full rounded-full flex items-center justify-end pr-3 text-xs font-semibold text-white transition-all duration-700"
                         :style="{ width: barWidth(funnel.sent) }">
                        {{ funnel.sent }}
                    </div>
                </div>
                <span class="w-14 text-xs text-gray-400 text-right">100%</span>
            </div>

            <!-- Delivered -->
            <div class="flex items-center gap-3">
                <span class="w-20 text-sm font-medium text-green-600 text-right">Delivered</span>
                <div class="flex-1 bg-gray-100 rounded-full h-8 overflow-hidden">
                    <div class="bg-green-500 h-full rounded-full flex items-center justify-end pr-3 text-xs font-semibold text-white transition-all duration-700"
                         :style="{ width: barWidth(funnel.delivered) }">
                        {{ funnel.delivered }}
                    </div>
                </div>
                <span class="w-14 text-xs text-gray-400 text-right">{{ rate(funnel.delivered) }}</span>
            </div>

            <!-- Read -->
            <div class="flex items-center gap-3">
                <span class="w-20 text-sm font-medium text-indigo-600 text-right">Read</span>
                <div class="flex-1 bg-gray-100 rounded-full h-8 overflow-hidden">
                    <div class="bg-indigo-500 h-full rounded-full flex items-center justify-end pr-3 text-xs font-semibold text-white transition-all duration-700"
                         :style="{ width: barWidth(funnel.read) }">
                        {{ funnel.read }}
                    </div>
                </div>
                <span class="w-14 text-xs text-gray-400 text-right">{{ rate(funnel.read) }}</span>
            </div>

            <!-- Failed -->
            <div class="flex items-center gap-3">
                <span class="w-20 text-sm font-medium text-red-600 text-right">Failed</span>
                <div class="flex-1 bg-gray-100 rounded-full h-8 overflow-hidden">
                    <div class="bg-red-500 h-full rounded-full flex items-center justify-end pr-3 text-xs font-semibold text-white transition-all duration-700"
                         :style="{ width: barWidth(funnel.failed) }">
                        {{ funnel.failed }}
                    </div>
                </div>
                <span class="w-14 text-xs text-gray-400 text-right">{{ rate(funnel.failed) }}</span>
            </div>
        </div>
    </div>
</template>
