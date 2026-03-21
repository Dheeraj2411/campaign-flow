<script setup>
import { ref, onMounted } from 'vue';
import { useContactSegmentStore } from '@/stores/useContactSegmentStore';
import HeadTitle from '@/Components/HeadTitle.vue';
import BaseCard from '@/Components/BaseCard.vue';
import BaseButton from '@/Components/BaseButton.vue';

const segmentStore = useContactSegmentStore();
const name = ref('');
const conditionKey = ref('name');
const conditionOperator = ref('equals');
const conditionValue = ref('');

onMounted(async () => {
    await segmentStore.fetchSegments();
});

const createSegment = async () => {
    try {
        await segmentStore.createSegment({
            name: name.value,
            conditions: [{ field: conditionKey.value, operator: conditionOperator.value, value: conditionValue.value }],
        });
        name.value = '';
        conditionKey.value = 'name';
        conditionOperator.value = 'equals';
        conditionValue.value = '';
    } catch (e) {
        alert('Failed to create segment: ' + segmentStore.error);
    }
};

const deleteSegment = async (segmentId) => {
    if (!confirm('Delete this segment?')) {
        return;
    }
    await segmentStore.deleteSegment(segmentId);
};
</script>

<template>
    <HeadTitle title="Contact Segments" subtitle="Define contact groups with rules for campaigns">
        <div class="max-w-4xl mx-auto space-y-6">
            <BaseCard class="p-6">
                <h3 class="text-lg font-bold mb-4">Create new segment</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Segment Name</label>
                        <input v-model="name" type="text" class="input w-full" placeholder="High value customers" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Condition</label>
                        <div class="flex flex-col md:flex-row gap-2">
                            <input v-model="conditionKey" type="text" class="input w-full md:w-1/3 h-10" placeholder="field" />
                            <select v-model="conditionOperator" class="input w-full md:w-1/3 h-10">
                                <option value="equals">equals</option>
                                <option value="contains">contains</option>
                                <option value="not_equals">not equals</option>
                            </select>
                            <input v-model="conditionValue" type="text" class="input w-full md:w-1/3 h-10" placeholder="value" />
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <BaseButton @click="createSegment">Save Segment</BaseButton>
                </div>
            </BaseCard>

            <BaseCard class="p-0 overflow-hidden">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-bold">Current segments</h3>
                </div>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Conditions</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="segment in segmentStore.segments" :key="segment.id" class="border-b hover:bg-slate-50">
                                <td class="px-6 py-4">{{ segment.name }}</td>
                                <td class="px-6 py-4">{{ segment.conditions }}</td>
                                <td class="px-6 py-4">
                                    <BaseButton variant="danger" size="sm" @click="deleteSegment(segment.id)">Delete</BaseButton>
                                </td>
                            </tr>
                            <tr v-if="!segmentStore.segments.length">
                                <td colspan="3" class="px-6 py-8 text-center text-slate-400">No segments yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </BaseCard>
        </div>
    </HeadTitle>
</template>
