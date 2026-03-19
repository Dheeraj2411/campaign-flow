<script setup>
import { ref, onMounted } from 'vue';
import { useContactSegmentStore } from '@/stores/useContactSegmentStore';
import AppLayout from '@/Layouts/AppLayout.vue';
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
    <AppLayout title="Contact Segments" subtitle="Define contact groups with rules for campaigns">
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
                        <div class="flex gap-2">
                            <input v-model="conditionKey" type="text" class="input w-1/3" placeholder="field" />
                            <select v-model="conditionOperator" class="input w-1/3">
                                <option value="equals">equals</option>
                                <option value="contains">contains</option>
                                <option value="not_equals">not equals</option>
                            </select>
                            <input v-model="conditionValue" type="text" class="input w-1/3" placeholder="value" />
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <BaseButton @click="createSegment">Save Segment</BaseButton>
                </div>
            </BaseCard>

            <BaseCard class="p-6">
                <h3 class="text-lg font-bold mb-4">Current segments</h3>
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Name</th>
                            <th class="py-2">Conditions</th>
                            <th class="py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="segment in segmentStore.segments" :key="segment.id" class="border-b">
                            <td class="py-2">{{ segment.name }}</td>
                            <td class="py-2">{{ segment.conditions }}</td>
                            <td class="py-2">
                                <BaseButton variant="danger" size="sm" @click="deleteSegment(segment.id)">Delete</BaseButton>
                            </td>
                        </tr>
                        <tr v-if="!segmentStore.segments.length">
                            <td colspan="3" class="py-4 text-center text-slate-400">No segments yet.</td>
                        </tr>
                    </tbody>
                </table>
            </BaseCard>
        </div>
    </AppLayout>
</template>
