<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useContactSegmentStore } from '@/stores/useContactSegmentStore';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/BaseCard.vue';
import BaseButton from '@/Components/BaseButton.vue';

const props = defineProps({
    contactGroups: Array,
    contactSegments: Array,
    templates: Array,
});

const segmentStore = useContactSegmentStore();

onMounted(async () => {
    if (!props.contactSegments || props.contactSegments.length === 0) {
        await segmentStore.fetchSegments();
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
    <AppLayout title="Launch Campaign" subtitle="Reach your audience in 3 easy steps">
        <div class="max-w-4xl mx-auto">
            <!-- Stepper Indicators -->
            <div class="flex items-center justify-center mb-10">
                <div v-for="step in 3" :key="step" class="flex items-center">
                    <div :class="[
                        'w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300',
                        currentStep >= step ? 'bg-admin-primary text-white shadow-lg shadow-admin-primary/30' : 'bg-slate-100 text-slate-400'
                    ]">
                        {{ step }}
                    </div>
                    <div v-if="step < 3" :class="[
                        'w-20 h-0.5 mx-2',
                        currentStep > step ? 'bg-admin-primary' : 'bg-slate-100'
                    ]"></div>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Step 1: Destination -->
                <div v-if="currentStep === 1" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <BaseCard class="p-8">
                        <h3 class="text-xl font-bold text-slate-800 mb-6">Who are we reaching?</h3>
                        <div class="space-y-6">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Campaign Name</label>
                                <input v-model="form.name" type="text" class="input py-3 text-lg" placeholder="e.g. March Newsletter" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Select Platform</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button type="button" @click="form.platform = 'whatsapp'"
                                                :class="['p-4 rounded-xl border-2 transition-all text-center flex flex-col items-center justify-center gap-1',
                                                        form.platform === 'whatsapp' ? 'border-[#25D366] bg-[#25D366]/5 text-[#25D366]' : 'border-slate-100 hover:border-slate-200 text-slate-500']">
                                            <svg viewBox="0 0 24 24" class="w-6 h-6 fill-current" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.937 3.659 1.432 5.626 1.433h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                            </svg>
                                            WhatsApp
                                        </button>
                                        <button type="button" @click="form.platform = 'telegram'"
                                                :class="['p-4 rounded-xl border-2 transition-all text-center flex flex-col items-center justify-center gap-1',
                                                        form.platform === 'telegram' ? 'border-admin-primary bg-admin-primary/5 text-admin-primary' : 'border-slate-100 hover:border-slate-200 text-slate-500']">
                                            <span class="material-symbols-outlined block">send</span>
                                            Telegram
                                        </button>
                                    </div>
                                </div>

                                                        <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Target Audience</label>
                                    <select v-model="form.contact_group_id" class="input py-3">
                                        <option v-for="group in contactGroups" :key="group.id" :value="group.id">{{ group.name }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Contact Segment</label>
                                    <select v-model="form.contact_segment_id" class="input py-3">
                                        <option value="">None</option>
                                        <option
                                            v-for="segment in props.contactSegments.length ? props.contactSegments : segmentStore.segments"
                                            :key="segment.id"
                                            :value="segment.id"
                                        >
                                            {{ segment.name }}
                                        </option>
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-2 italic">Select a pre-defined segment to target filtered contacts.</p>
                                </div>
                            </div>
                        </div>
                    </BaseCard>
                </div>

                <!-- Step 2: Content -->
                <div v-if="currentStep === 2" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-500">
                    <BaseCard class="p-8">
                        <h3 class="text-xl font-bold text-slate-800 mb-6">Choose your message</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Template List -->
                            <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
                                <div v-for="tpl in filteredTemplates" :key="tpl.id"
                                     @click="form.template_id = tpl.id"
                                     :class="['p-4 rounded-xl border-2 cursor-pointer transition-all',
                                             form.template_id === tpl.id ? 'border-admin-primary bg-admin-primary/5' : 'border-slate-100 hover:border-slate-200']">
                                    <div class="font-bold text-slate-800">{{ tpl.name }}</div>
                                    <div class="text-xs text-slate-400 mt-1 truncate">{{ tpl.body }}</div>
                                </div>
                                <div v-if="!filteredTemplates.length" class="text-center py-10 text-slate-400 italic">
                                    No approved templates found for {{ form.platform }}.
                                </div>
                            </div>

                            <!-- Preview -->
                            <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100 min-h-[300px]">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Live Preview</label>
                                <div v-if="selectedTemplate" class="bg-white p-4 rounded-lg shadow-sm border border-slate-100 text-sm leading-relaxed text-slate-700 whitespace-pre-wrap">
                                    {{ selectedTemplate.body }}
                                </div>
                                <div v-else class="h-full flex items-center justify-center text-slate-300 italic text-sm">
                                    Select a template to preview...
                                </div>
                            </div>
                        </div>
                    </BaseCard>
                </div>

                <!-- Step 3: Finalize -->
                <div v-if="currentStep === 3" class="space-y-6 animate-in fade-in zoom-in-95 duration-500">
                    <BaseCard class="p-8">
                        <h3 class="text-xl font-bold text-slate-800 mb-6">Finalize & Launch</h3>
                        <div class="space-y-6">
                            <div class="bg-admin-primary/5 p-6 rounded-2xl border border-admin-primary/10">
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-slate-400 block mb-1 uppercase text-[10px] font-bold">Campaign</span>
                                        <span class="text-slate-800 font-bold text-lg">{{ form.name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block mb-1 uppercase text-[10px] font-bold">Target</span>
                                        <span class="text-slate-800 font-bold text-lg">{{ contactGroups.find(g => g.id === form.contact_group_id)?.name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block mb-1 uppercase text-[10px] font-bold">Platform</span>
                                        <span class="text-slate-800 font-bold capitalize">{{ form.platform }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block mb-1 uppercase text-[10px] font-bold">Template</span>
                                        <span class="text-slate-800 font-bold">{{ selectedTemplate?.name }}</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">Schedule Release (Optional)</label>
                                <input v-model="form.scheduled_at" type="datetime-local" class="input py-3" />
                                <p class="text-[10px] text-slate-400 mt-2 italic">Leave empty to launch immediately after confirmation.</p>
                            </div>
                        </div>
                    </BaseCard>
                </div>

                <!-- Navigation -->
                <div class="flex justify-between items-center pt-4">
                    <BaseButton v-if="currentStep > 1" variant="ghost" @click="prevStep">
                        Back
                    </BaseButton>
                    <div v-else></div>

                    <div class="flex gap-3">
                        <BaseButton variant="ghost" :href="route('campaigns.index')">Cancel</BaseButton>
                        <BaseButton v-if="currentStep < 3" variant="admin"
                                    :disabled="currentStep === 1 && !form.name"
                                    @click="nextStep">
                            Continue
                        </BaseButton>
                        <BaseButton v-else variant="admin" @click="submit" :loading="form.processing">
                            {{ form.scheduled_at ? 'Schedule Campaign' : 'Launch Now' }}
                        </BaseButton>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
