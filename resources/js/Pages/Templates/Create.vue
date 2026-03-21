<script setup>
import { ref, computed, watch } from 'vue';
import DOMPurify from 'dompurify';
import { useForm, Link } from '@inertiajs/vue3';
import HeadTitle from '@/Components/HeadTitle.vue';
import BaseCard from '@/Components/BaseCard.vue';
import BaseButton from '@/Components/BaseButton.vue';

const currentStep = ref(1);

const form = useForm({
    name: '',
    platform: 'whatsapp',
    language: 'en_US',
    category: 'MARKETING',
    type: 'DEFAULT',
    message_type: 'text',
    interactive_config: {
        buttons: [],
        header_text: '',
        button_label: 'Options',
        sections: []
    },
    content_structure: {
        header_type: 'TEXT',
        header: '',
        body: '',
        footer: '',
        buttons: [],
        catalog_format: 'CATALOG_MESSAGE',
        business_name: 'Your Business',
    }
});

const languages = [
    { code: 'en_US', name: 'English (US)' },
    { code: 'en_GB', name: 'English (UK)' },
    { code: 'hi', name: 'Hindi' },
    { code: 'es', name: 'Spanish' },
    { code: 'pt_BR', name: 'Portuguese (BR)' },
];

const categories = [
    { id: 'MARKETING', name: 'Marketing', icon: 'campaign', description: 'Send promotions, offers.' },
];

const messageTypes = [
    { id: 'text', label: 'Text', icon: 'chat' },
    { id: 'image', label: 'Image', icon: 'image' },
    { id: 'document', label: 'Document', icon: 'description' },
    { id: 'interactive_buttons', label: 'Reply Buttons', icon: 'smart_button' },
    { id: 'interactive_list', label: 'List Menu', icon: 'list_alt' },
];

const nextStep = () => { if (currentStep.value < 4) currentStep.value++; };
const prevStep = () => { if (currentStep.value > 1) currentStep.value--; };

const addReplyButton = () => {
    if (form.interactive_config.buttons.length < 3) {
        form.interactive_config.buttons.push({ id: `btn_${Date.now()}`, title: 'New Button' });
    }
};
const removeReplyButton = (idx) => form.interactive_config.buttons.splice(idx, 1);

const addSection = () => {
    form.interactive_config.sections.push({ title: 'New Section', rows: [] });
};
const removeSection = (idx) => form.interactive_config.sections.splice(idx, 1);

const addSectionRow = (sIdx) => {
    form.interactive_config.sections[sIdx].rows.push({
        id: `row_${Date.now()}`,
        title: 'Item Title',
        description: 'Description'
    });
};
const removeSectionRow = (sIdx, rIdx) => {
    form.interactive_config.sections[sIdx].rows.splice(rIdx, 1);
};

const previewBody = computed(() => {
    if (!form.content_structure.body) return '<span class="text-slate-300 italic">Your message content...</span>';
    return form.content_structure.body.replace(/\{\{(\d+)\}\}/g, '<span class="text-blue-600 bg-blue-50 px-1 rounded font-bold">[$1]</span>');
});
const sanitizedPreviewBody = computed(() => DOMPurify.sanitize(previewBody.value));

const submit = () => form.post(route('templates.store'));

const isStepValid = computed(() => {
    if (currentStep.value === 1) return !!form.category;
    if (currentStep.value === 2) return form.name.length >= 3;
    if (currentStep.value === 3) return form.content_structure.body.length > 5;
    return true;
});

const getCategoryIcon = (id) => categories.find(c => c.id === id)?.icon || 'help';
const getCategoryName = (id) => categories.find(c => c.id === id)?.name || id;

const insertVariable = () => {
    const nextVar = (form.content_structure.body.match(/\{\{\d+\}\}/g)?.length || 0) + 1;
    form.content_structure.body += ` {{${nextVar}}}`;
};
</script>

<template>
    <HeadTitle title="Create Template" subtitle="Submit a new template to Meta for approval">
        <div class="max-w-6xl mx-auto">
            
            <!-- Stepper Header -->
            <div class="mb-8 overflow-hidden">
                <div class="flex items-center justify-between relative px-2">
                    <div class="absolute h-0.5 bg-slate-200 left-0 right-0 top-1/2 -translate-y-1/2 z-0"></div>
                    <div v-for="step in 4" :key="step" class="relative z-10 flex flex-col items-center">
                        <div 
                            class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 font-bold border-4"
                            :class="[
                                currentStep >= step ? 'bg-admin-primary border-admin-primary text-white scale-110 shadow-lg' : 'bg-white border-slate-200 text-slate-400',
                                currentStep === step ? 'ring-4 ring-admin-primary/20' : ''
                            ]"
                        >
                            <span v-if="currentStep > step" class="material-symbols-outlined text-sm">check</span>
                            <span v-else>{{ step }}</span>
                        </div>
                        <span class="text-[10px] uppercase font-bold mt-2 tracking-wider" 
                              :class="currentStep >= step ? 'text-admin-primary' : 'text-slate-400'">
                            {{ ['Category', 'Details', 'Design', 'Review'][step-1] }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Wizard Content -->
                <div class="lg:col-span-8 space-y-6">
                    
                    <Transition name="slide-fade" mode="out-in">
                        <div v-if="currentStep === 1" key="step1" class="space-y-6">
                            <h2 class="text-xl font-bold text-slate-800">Set up your template</h2>
                            <div class="bg-admin-primary/5 border border-admin-primary/10 rounded-xl p-4 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-admin-primary flex items-center justify-center text-white">
                                    <span class="material-symbols-outlined">campaign</span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-sm">Marketing Template</h3>
                                    <p class="text-[11px] text-slate-500">Promotions, offers, and customer engagement.</p>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="currentStep === 2" key="step2" class="space-y-6">
                            <h2 class="text-xl font-bold text-slate-800">Template Details</h2>
                            <BaseCard class="p-8 space-y-6">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Template Name</label>
                                    <input v-model="form.name" type="text" 
                                           class="input text-lg w-full px-4 py-3 rounded-xl border-slate-200 focus:border-admin-primary" 
                                           placeholder="e.g. order_confirmation_new" 
                                    />
                                    <div class="mt-2 flex items-center gap-2 text-[11px] text-slate-400">
                                        <span class="material-symbols-outlined text-[14px]">info</span>
                                        Only lowercase, numbers and underscores. e.g. "seasonal_promo_2024"
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Language</label>
                                    <select v-model="form.language" class="input w-full px-4 py-3 rounded-xl border-slate-200">
                                        <option v-for="lang in languages" :key="lang.code" :value="lang.code">{{ lang.name }}</option>
                                    </select>
                                </div>
                            </BaseCard>
                        </div>

                        <div v-else-if="currentStep === 3" key="step3" class="space-y-6">
                            <h2 class="text-xl font-bold text-slate-800">Design Your Message</h2>
                            
                            <!-- MSG TYPE SELECTOR -->
                            <BaseCard class="p-2 border-0 bg-transparent shadow-none">
                                <div class="flex overflow-x-auto gap-2 border-b border-gray-200 pb-2">
                                    <button 
                                        v-for="type in messageTypes" 
                                        :key="type.id"
                                        @click="form.message_type = type.id"
                                        class="px-4 py-2 font-semibold text-sm rounded-t-lg transition whitespace-nowrap flex items-center justify-center gap-2"
                                        :class="form.message_type === type.id ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:bg-gray-100'"
                                    >
                                        <span class="material-symbols-outlined text-sm">{{ type.icon }}</span>
                                        {{ type.label }}
                                    </button>
                                </div>
                            </BaseCard>

                            <!-- STANDARD BODY FIELD -->
                            <BaseCard class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-admin-primary" style="font-variation-settings: 'FILL' 1">chat_bubble</span>
                                        <h3 class="font-bold text-slate-800">Message Body</h3>
                                    </div>
                                    <button @click="insertVariable" 
                                            class="text-[11px] font-bold text-admin-primary flex items-center gap-1 hover:bg-admin-primary/5 px-3 py-1.5 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-[16px]">add</span> Add Variable
                                    </button>
                                </div>
                                <textarea v-model="form.content_structure.body" 
                                          class="input w-full px-4 py-4 min-h-[160px] bg-slate-50 border-transparent rounded-xl focus:bg-white leading-relaxed" 
                                          placeholder="Type your message text here..."
                                ></textarea>
                            </BaseCard>

                            <!-- REPLY BUTTONS BUILDER -->
                            <BaseCard v-if="form.message_type === 'interactive_buttons'" class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-indigo-500">smart_button</span>
                                        <h3 class="font-bold text-slate-800">Reply Buttons <span class="text-xs font-normal text-gray-400">(Max 3)</span></h3>
                                    </div>
                                    <button v-if="form.interactive_config.buttons.length < 3" @click="addReplyButton" 
                                            class="text-xs font-bold text-indigo-600 flex items-center gap-1 hover:bg-indigo-50 px-3 py-1.5 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-sm">add_circle</span> Add Button
                                    </button>
                                </div>
                                <div class="space-y-3">
                                    <div v-for="(btn, idx) in form.interactive_config.buttons" :key="idx" class="flex items-center gap-3 bg-gray-50 p-3 rounded-lg border border-gray-200">
                                        <input v-model="btn.id" type="text" placeholder="ID (e.g. YES_BTN)" class="border-gray-300 rounded text-sm w-1/3" />
                                        <input v-model="btn.title" type="text" placeholder="Title (e.g. Yes Please!)" class="border-gray-300 rounded text-sm w-2/3" />
                                        <button @click="removeReplyButton(idx)" class="text-gray-400 hover:text-red-500"><span class="material-symbols-outlined text-lg">delete</span></button>
                                    </div>
                                    <div v-if="!form.interactive_config.buttons.length" class="text-xs text-gray-500 text-center py-4 bg-gray-50 rounded border border-dashed border-gray-300">
                                        No buttons added yet. Click "+ Add Button" above.
                                    </div>
                                </div>
                            </BaseCard>

                            <!-- LIST MENU BUILDER -->
                            <BaseCard v-if="form.message_type === 'interactive_list'" class="p-6">
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="material-symbols-outlined text-indigo-500">list_alt</span>
                                    <h3 class="font-bold text-slate-800">List Menu Settings</h3>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Header Text</label>
                                        <input v-model="form.interactive_config.header_text" type="text" class="border-gray-300 rounded w-full text-sm" placeholder="e.g. Available Categories" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">List Trigger Button</label>
                                        <input v-model="form.interactive_config.button_label" type="text" class="border-gray-300 rounded w-full text-sm" placeholder="e.g. View Options" />
                                    </div>
                                </div>

                                <div class="flex items-center justify-between mb-3 border-t pt-4 border-gray-100">
                                    <h4 class="font-bold text-gray-800 text-sm">Menu Sections</h4>
                                    <button @click="addSection" class="text-xs font-bold text-indigo-600 flex items-center gap-1 hover:bg-indigo-50 px-2 py-1 rounded">
                                        <span class="material-symbols-outlined text-sm">add_circle</span> Add Section
                                    </button>
                                </div>

                                <div class="space-y-4">
                                    <div v-for="(section, sIdx) in form.interactive_config.sections" :key="sIdx" class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                        <div class="flex items-center gap-3 mb-3">
                                            <input v-model="section.title" type="text" class="border-gray-300 rounded font-bold text-sm w-full" placeholder="Section Title" />
                                            <button @click="removeSection(sIdx)" class="text-red-500 hover:text-red-700"><span class="material-symbols-outlined">delete</span></button>
                                        </div>

                                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-2">Rows / Items</p>
                                        <div class="space-y-2 mb-3">
                                            <div v-for="(row, rIdx) in section.rows" :key="rIdx" class="grid grid-cols-12 gap-2 items-center bg-white p-2 rounded border border-gray-100 shadow-sm">
                                                <div class="col-span-3">
                                                    <input v-model="row.id" type="text" placeholder="Row ID" class="w-full border-gray-300 rounded text-xs py-1" />
                                                </div>
                                                <div class="col-span-4">
                                                    <input v-model="row.title" type="text" placeholder="Title" class="w-full border-gray-300 rounded text-xs py-1 font-bold" />
                                                </div>
                                                <div class="col-span-4">
                                                    <input v-model="row.description" type="text" placeholder="Description (Optional)" class="w-full border-gray-300 rounded text-xs py-1 text-gray-500" />
                                                </div>
                                                <div class="col-span-1 text-right">
                                                    <button @click="removeSectionRow(sIdx, rIdx)" class="text-gray-400 hover:text-red-500"><span class="material-symbols-outlined text-sm">close</span></button>
                                                </div>
                                            </div>
                                        </div>
                                        <button @click="addSectionRow(sIdx)" class="text-xs font-semibold text-indigo-600 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">add</span> Add Row
                                        </button>
                                    </div>
                                    <div v-if="!form.interactive_config.sections.length" class="text-xs text-gray-400 italic">No sections created.</div>
                                </div>
                            </BaseCard>

                        </div>

                        <!-- Step 4: Review -->
                        <div v-else-if="currentStep === 4" key="step4" class="space-y-6">
                            <h2 class="text-xl font-bold text-slate-800">Review & Submit</h2>
                            <BaseCard class="p-0 overflow-hidden">
                                <div class="bg-slate-50 px-8 py-4 border-b border-slate-100 flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Final Summary</span>
                                    <span class="px-2 py-1 rounded bg-admin-primary/10 text-admin-primary text-[10px] font-bold uppercase tracking-wider">
                                        Ready
                                    </span>
                                </div>
                                <div class="p-8 grid grid-cols-2 gap-8 line-divider">
                                    <div>
                                        <h4 class="text-[10px] uppercase font-bold text-slate-400 mb-1">Template Name</h4>
                                        <p class="font-bold text-slate-800">{{ form.name }}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-[10px] uppercase font-bold text-slate-400 mb-1">Language</h4>
                                        <p class="font-bold text-slate-800">{{ languages.find(l => l.code === form.language)?.name }}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-[10px] uppercase font-bold text-slate-400 mb-1">Message Type</h4>
                                        <p class="font-bold text-slate-800">{{ form.message_type.toUpperCase() }}</p>
                                    </div>
                                </div>
                            </BaseCard>
                        </div>
                    </Transition>

                    <!-- Nav Buttons -->
                    <div class="flex justify-between items-center pt-4 border-t border-slate-100">
                        <BaseButton v-if="currentStep > 1" variant="ghost" @click="prevStep" class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back
                        </BaseButton>
                        <div v-else></div>

                        <BaseButton 
                            v-if="currentStep < 4" 
                            variant="admin" 
                            @click="nextStep" 
                            :disabled="!isStepValid"
                            class="px-8 flex items-center gap-2 shadow-lg shadow-admin-primary/20"
                        >
                            Continue <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                        </BaseButton>
                        <BaseButton 
                            v-else 
                            variant="admin" 
                            @click="submit" 
                            :loading="form.processing"
                            class="px-10 bg-gradient-to-r from-admin-primary to-admin-highlight border-0 shadow-xl shadow-admin-primary/25"
                        >
                            Final Submit
                        </BaseButton>
                    </div>
                </div>

                <!-- Live Preview Column -->
                <div class="lg:col-span-4 lg:sticky lg:top-8">
                    <div class="bg-white rounded-[2.5rem] p-4 shadow-2xl border-[12px] border-slate-800 aspect-[9/18.5] relative overflow-hidden flex flex-col group">
                        
                        <div class="flex-1 bg-[#E5DDD5] rounded-t-[1.5rem] overflow-hidden flex flex-col p-3 relative mt-4">
                            <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-repeat: repeat;"></div>

                            <div class="relative z-10 w-[85%]">
                                <div class="bg-white rounded-xl shadow-md overflow-hidden flex flex-col">
                                    <div class="p-3">
                                        <div v-if="form.message_type === 'interactive_list' && form.interactive_config.header_text" class="font-bold text-slate-900 border-b border-slate-50 pb-2 mb-2 break-words leading-tight">
                                            {{ form.interactive_config.header_text }}
                                        </div>
                                        <div class="text-[13px] text-slate-800 leading-relaxed whitespace-pre-wrap break-words" v-html="sanitizedPreviewBody"></div>
                                        <div class="text-slate-400 text-[11px] mt-2 border-t border-slate-50 pt-2 break-words">Placeholder Footer</div>
                                    </div>
                                </div>
                                
                                <!-- Render Interactive Elements Preview -->
                                <div v-if="form.message_type === 'interactive_list'" class="bg-white mt-1 rounded-xl shadow-sm text-blue-500 font-bold p-3 text-center text-sm flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-sm">list</span>
                                    {{ form.interactive_config.button_label || 'Options' }}
                                </div>

                                <div v-if="form.message_type === 'interactive_buttons' && form.interactive_config.buttons.length" class="mt-1 space-y-1">
                                    <div v-for="btn in form.interactive_config.buttons" :key="btn.id" class="bg-white rounded-xl shadow-sm text-blue-500 font-semibold p-2.5 text-center text-sm">
                                        {{ btn.title || 'Button' }}
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </HeadTitle>
</template>

<style scoped>
.input {
    @apply transition-all duration-200 ring-0 focus:ring-0 outline-none border-2 border-slate-100 focus:border-admin-primary/50;
}
.line-divider > div { @apply border-b border-slate-50 pb-4; }
.slide-fade-enter-active { transition: all 0.3s ease-out; }
.slide-fade-leave-active { transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1); }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(10px); opacity: 0; }
.bg-admin-primary { background-color: var(--admin-primary, #6366f1); }
.text-admin-primary { color: var(--admin-primary, #6366f1); }
.border-admin-primary { border-color: var(--admin-primary, #6366f1); }
.text-admin-highlight { color: #f59e0b; }
</style>
