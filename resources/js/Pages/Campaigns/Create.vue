<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useContactSegmentStore } from '@/stores/useContactSegmentStore';
import HeadTitle from '@/Components/HeadTitle.vue';
import BaseCard from '@/Components/BaseCard.vue';
import BaseButton from '@/Components/BaseButton.vue';

const props = defineProps({
    contactGroups: Array,
    contactSegments: Array,
    templates: Array,
});

const segmentStore = useContactSegmentStore();

onMounted(async () => {
    try {
        if (!props.contactSegments || props.contactSegments.length === 0) {
            await segmentStore.fetchSegments();
        }
    } catch (e) {
        console.error('Failed to fetch segments:', e);
    }
});

const currentStep = ref(1);

const form = useForm({
    name: '',
    platform: 'whatsapp',
    contact_group_id: 'all',
    contact_segment_id: null,
    template_id: null,
    scheduled_at: null,
});

const filteredTemplates = computed(() => {
    return props.templates.filter(t => t.platform === form.platform);
});

const selectedTemplate = computed(() => {
    return props.templates.find(t => t.id === form.template_id);
});

const nextStep = () => {
    if (currentStep.value < 3) currentStep.value++;
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const submit = () => {
    form.post(route('campaigns.store'));
};
</script>

<template>
    <HeadTitle title="Launch Campaign" subtitle="Reach your audience in 3 easy steps">
        <div class="max-w-4xl mx-auto">
            <!-- Stepper Indicators -->
            <div class="flex items-center justify-center mb-10">
                <div v-for="step in 3" :key="step" class="flex items-center">
                    <div :class="[
                        'w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm transition-all duration-300',
                        currentStep >= step ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'bg-gray-100 text-gray-400'
                    ]">
                        {{ step }}
                    </div>
                    <div v-if="step < 3" :class="[
                        'w-20 h-0.5 mx-2 transition-colors duration-300',
                        currentStep > step ? 'bg-indigo-600' : 'bg-gray-200'
                    ]"></div>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Step 1: Destination -->
                <div v-if="currentStep === 1" class="space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">Who are we reaching?</h3>
                        <div class="space-y-6">
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Campaign Name</label>
                                <input v-model="form.name" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" placeholder="e.g. March Newsletter" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700">Select Platform</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button type="button" @click="form.platform = 'whatsapp'"
                                                :class="['p-4 rounded-xl border-2 transition-all duration-200 text-center flex flex-col items-center justify-center gap-1',
                                                        form.platform === 'whatsapp' ? 'border-emerald-500 bg-emerald-50 text-emerald-600' : 'border-gray-200 hover:border-gray-300 text-gray-500']">
                                            <svg viewBox="0 0 24 24" class="w-6 h-6 fill-current" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.937 3.659 1.432 5.626 1.433h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                            </svg>
                                            <span class="text-sm font-medium">WhatsApp</span>
                                        </button>
                                        <button type="button" @click="form.platform = 'telegram'"
                                                :class="['p-4 rounded-xl border-2 transition-all duration-200 text-center flex flex-col items-center justify-center gap-1',
                                                        form.platform === 'telegram' ? 'border-indigo-600 bg-indigo-50 text-indigo-600' : 'border-gray-200 hover:border-gray-300 text-gray-500']">
                                            <span class="material-symbols-outlined block">send</span>
                                            <span class="text-sm font-medium">Telegram</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700">Target Audience</label>
                                    <select v-model="form.contact_group_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white">
                                        <option v-for="group in contactGroups" :key="group.id" :value="group.id">{{ group.name }}</option>
                                    </select>
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700">Contact Segment</label>
                                    <select v-model="form.contact_segment_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white">
                                        <option value="">None</option>
                                        <option
                                            v-for="segment in props.contactSegments.length ? props.contactSegments : segmentStore.segments"
                                            :key="segment.id"
                                            :value="segment.id"
                                        >
                                            {{ segment.name }}
                                        </option>
                                    </select>
                                    <p class="text-xs text-gray-400 mt-1">Select a pre-defined segment to target filtered contacts.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Content -->
                <div v-if="currentStep === 2" class="space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">Choose your message</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Template List -->
                            <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
                                <div v-for="tpl in filteredTemplates" :key="tpl.id"
                                     @click="form.template_id = tpl.id"
                                     :class="['p-4 rounded-xl border-2 cursor-pointer transition-all duration-200',
                                             form.template_id === tpl.id ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200 hover:border-gray-300']">
                                    <div class="font-semibold text-gray-900">{{ tpl.name }}</div>
                                    <div class="text-xs text-gray-500 mt-1 truncate">{{ tpl.body }}</div>
                                </div>
                                <div v-if="!filteredTemplates.length" class="text-center py-16">
                                    <span class="material-symbols-outlined text-[48px] block mb-3 text-gray-300">description</span>
                                    <h3 class="text-lg font-semibold text-gray-900">No templates found</h3>
                                    <p class="text-sm text-gray-500 mt-1">No approved templates for {{ form.platform }}</p>
                                </div>
                            </div>

                            <!-- Preview -->
                            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200 min-h-[300px]">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Live Preview</label>
                                <div v-if="selectedTemplate" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 text-sm leading-relaxed text-gray-700 whitespace-pre-wrap">
                                    {{ selectedTemplate.body }}
                                </div>
                                <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">
                                    Select a template to preview...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Finalize -->
                <div v-if="currentStep === 3" class="space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">Finalize & Launch</h3>
                        <div class="space-y-6">
                            <div class="bg-indigo-50 p-6 rounded-xl border border-indigo-100">
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500 block mb-1 text-xs font-medium">Campaign</span>
                                        <span class="text-gray-900 font-bold text-lg">{{ form.name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block mb-1 text-xs font-medium">Target</span>
                                        <span class="text-gray-900 font-bold text-lg">{{ contactGroups.find(g => g.id === form.contact_group_id)?.name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block mb-1 text-xs font-medium">Platform</span>
                                        <span class="text-gray-900 font-semibold capitalize">{{ form.platform }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block mb-1 text-xs font-medium">Template</span>
                                        <span class="text-gray-900 font-semibold">{{ selectedTemplate?.name }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Schedule Release (Optional)</label>
                                <input v-model="form.scheduled_at" type="datetime-local" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white" />
                                <p class="text-xs text-gray-400 mt-1">Leave empty to launch immediately after confirmation.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="flex justify-between items-center pt-4">
                    <BaseButton v-if="currentStep > 1" variant="ghost" @click="prevStep">
                        Back
                    </BaseButton>
                    <div v-else></div>

                    <div class="flex gap-3">
                        <BaseButton variant="ghost" :href="route('campaigns.index')">Cancel</BaseButton>
                        <BaseButton v-if="currentStep < 3" variant="primary"
                                    :disabled="currentStep === 1 && !form.name"
                                    @click="nextStep">
                            Continue
                        </BaseButton>
                        <BaseButton v-else variant="primary" @click="submit" :loading="form.processing">
                            {{ form.scheduled_at ? 'Schedule Campaign' : 'Launch Now' }}
                        </BaseButton>
                    </div>
                </div>
            </div>
        </div>
    </HeadTitle>
</template>
