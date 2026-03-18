<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/BaseCard.vue';
import BaseButton from '@/Components/BaseButton.vue';

const currentStep = ref(1);

const form = useForm({
    name: '',
    platform: 'whatsapp',
    language: 'en_US',
    category: 'MARKETING',
    type: 'DEFAULT',
    content_structure: {
        header_type: 'TEXT',
        header: '',
        body: '',
        footer: '',
        buttons: [],
        catalog_format: 'CATALOG_MESSAGE', // or MULTI_PRODUCT_MESSAGE
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
    { 
        id: 'MARKETING', 
        name: 'Marketing', 
        icon: 'campaign', 
        description: 'Send promotions, offers, or invitations to increase engagement.' 
    },
];

const nextStep = () => {
    if (currentStep.value < 4) currentStep.value++;
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const addButton = () => {
    if (form.content_structure.buttons.length < 3) {
        form.content_structure.buttons.push({
            type: 'QUICK_REPLY',
            text: 'Click here'
        });
    }
};

const removeButton = (index) => {
    form.content_structure.buttons.splice(index, 1);
};

// Preview Logic
const previewBody = computed(() => {
    if (!form.content_structure.body) return '<span class="text-slate-300 italic">Your message content...</span>';
    return form.content_structure.body.replace(/\{\{(\d+)\}\}/g, '<span class="text-blue-600 bg-blue-50 px-1 rounded font-bold">[$1]</span>');
});

const submit = () => {
    form.post(route('templates.store'));
};

const isStepValid = computed(() => {
    if (currentStep.value === 1) return !!form.category;
    if (currentStep.value === 2) return (form.name.length >= 3 || form.type === 'CATALOGUE') && !!form.language;
    if (currentStep.value === 3) {
        if (form.type === 'CALLING_PERMISSIONS') return !!form.content_structure.business_name;
        return form.content_structure.body.length > 5;
    }
    return true;
});

const getCategoryIcon = (id) => {
    return categories.find(c => c.id === id)?.icon || 'help';
};

const getCategoryName = (id) => {
    return categories.find(c => c.id === id)?.name || id;
};

const insertVariable = () => {
    const nextVar = (form.content_structure.body.match(/\{\{\d+\}\}/g)?.length || 0) + 1;
    form.content_structure.body += ` {{${nextVar}}}`;
};
</script>

<template>
    <AppLayout title="Create Template" subtitle="Submit a new template to Meta for approval">
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
                    
                    <!-- Step 1: Category Selection -->
                    <Transition name="slide-fade" mode="out-in">
                        <div v-if="currentStep === 1" key="step1" class="space-y-6">
                            <h2 class="text-xl font-bold text-slate-800">Set up your template</h2>
                            <!-- Category Display (Static) -->
                            <div class="bg-admin-primary/5 border border-admin-primary/10 rounded-xl p-4 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-admin-primary flex items-center justify-center text-white">
                                    <span class="material-symbols-outlined">campaign</span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-sm">Marketing Template</h3>
                                    <p class="text-[11px] text-slate-500">Promotions, offers, and customer engagement.</p>
                                </div>
                            </div>

                            <!-- Template Type Selection -->
                            <div class="space-y-3 mt-8">
                                <div @click="form.type = 'DEFAULT'"
                                     class="p-5 rounded-xl border-2 cursor-pointer transition-all group relative overflow-hidden"
                                     :class="form.type === 'DEFAULT' ? 'border-admin-primary bg-admin-primary/5' : 'border-slate-100 hover:border-slate-200 bg-white'"
                                >
                                    <div class="flex items-start gap-4">
                                        <div class="mt-1 w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0"
                                             :class="form.type === 'DEFAULT' ? 'border-admin-primary bg-admin-primary' : 'border-slate-300 bg-white'"
                                        >
                                            <div v-if="form.type === 'DEFAULT'" class="w-2 h-2 rounded-full bg-white"></div>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-slate-800 text-sm">Default</h3>
                                            <p class="text-xs text-slate-500 mt-1">Send messages with media and customised buttons to engage your customers.</p>
                                        </div>
                                    </div>
                                </div>

                                <div @click="form.type = 'CATALOGUE'"
                                     class="p-5 rounded-xl border-2 cursor-pointer transition-all group relative overflow-hidden"
                                     :class="form.type === 'CATALOGUE' ? 'border-admin-primary bg-admin-primary/5' : 'border-slate-100 hover:border-slate-200 bg-white'"
                                >
                                    <div class="flex items-start gap-4">
                                        <div class="mt-1 w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0"
                                             :class="form.type === 'CATALOGUE' ? 'border-admin-primary bg-admin-primary' : 'border-slate-300 bg-white'"
                                        >
                                            <div v-if="form.type === 'CATALOGUE'" class="w-2 h-2 rounded-full bg-white"></div>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-slate-800 text-sm">Catalogue</h3>
                                            <p class="text-xs text-slate-500 mt-1">Send messages that drive sales by connecting your product catalogue.</p>
                                        </div>
                                    </div>
                                </div>

                                <div @click="form.type = 'CALLING_PERMISSIONS'"
                                     class="p-5 rounded-xl border-2 cursor-pointer transition-all group relative overflow-hidden"
                                     :class="form.type === 'CALLING_PERMISSIONS' ? 'border-admin-primary bg-admin-primary/5' : 'border-slate-100 hover:border-slate-200 bg-white'"
                                >
                                    <div class="flex items-start gap-4">
                                        <div class="mt-1 w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0"
                                             :class="form.type === 'CALLING_PERMISSIONS' ? 'border-admin-primary bg-admin-primary' : 'border-slate-300 bg-white'"
                                        >
                                            <div v-if="form.type === 'CALLING_PERMISSIONS'" class="w-2 h-2 rounded-full bg-white"></div>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-slate-800 text-sm">Calling permissions request</h3>
                                            <p class="text-xs text-slate-500 mt-1">Ask customers if you can call them on WhatsApp.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Information -->
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

                        <!-- Step 3: Design -->
                        <div v-else-if="currentStep === 3" key="step3" class="space-y-6">
                            <h2 class="text-xl font-bold text-slate-800">Design Your Message</h2>
                            
                            <!-- IF CATALOGUE -->
                            <template v-if="form.type === 'CATALOGUE'">
                                <BaseCard class="p-6">
                                    <div class="flex items-center gap-2 mb-4">
                                        <span class="material-symbols-outlined text-admin-primary">inventory_2</span>
                                        <h3 class="font-bold text-slate-800">Catalogue Format</h3>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <button @click="form.content_structure.catalog_format = 'CATALOG_MESSAGE'"
                                                class="p-4 rounded-xl border-2 text-left transition-all"
                                                :class="form.content_structure.catalog_format === 'CATALOG_MESSAGE' ? 'border-admin-primary bg-admin-primary/5' : 'border-slate-100 hover:border-slate-200'"
                                        >
                                            <div class="font-bold text-sm text-slate-800">Catalogue message</div>
                                            <p class="text-[10px] text-slate-500 mt-1">Include the entire catalogue to give your users a comprehensive view.</p>
                                        </button>
                                        <button @click="form.content_structure.catalog_format = 'MULTI_PRODUCT_MESSAGE'"
                                                class="p-4 rounded-xl border-2 text-left transition-all"
                                                :class="form.content_structure.catalog_format === 'MULTI_PRODUCT_MESSAGE' ? 'border-admin-primary bg-admin-primary/5' : 'border-slate-100 hover:border-slate-200'"
                                        >
                                            <div class="font-bold text-sm text-slate-800">Multi-product message</div>
                                            <p class="text-[10px] text-slate-500 mt-1">Include up to 30 products from the catalogue. Best for collections.</p>
                                        </button>
                                    </div>
                                </BaseCard>

                                <BaseCard class="p-6">
                                    <div class="flex items-center gap-2 mb-4">
                                        <span class="material-symbols-outlined text-admin-primary">chat_bubble</span>
                                        <h3 class="font-bold text-slate-800">Catalogue Body</h3>
                                    </div>
                                    <textarea v-model="form.content_structure.body" 
                                              class="input w-full px-4 py-4 min-h-[100px] bg-slate-50 border-transparent rounded-xl focus:bg-white" 
                                              placeholder="e.g. Hello, check out our latest catalog!"></textarea>
                                </BaseCard>
                            </template>

                            <!-- IF CALLING_PERMISSIONS -->
                            <template v-else-if="form.type === 'CALLING_PERMISSIONS'">
                                <BaseCard class="p-6">
                                    <div class="flex items-center gap-2 mb-4">
                                        <span class="material-symbols-outlined text-admin-primary">business</span>
                                        <h3 class="font-bold text-slate-800">Business Identity</h3>
                                    </div>
                                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Business Name for Calling Request</label>
                                    <input v-model="form.content_structure.business_name" type="text"
                                           class="input w-full px-4 py-3 border-transparent bg-slate-50 rounded-xl focus:bg-white" 
                                           placeholder="e.g. Jasper Market" />
                                </BaseCard>
                                
                                <BaseCard class="p-6 opacity-60 grayscale cursor-not-allowed">
                                    <p class="text-xs text-slate-500">Calling Permission templates have a fixed body and footer content mandated by Meta.</p>
                                </BaseCard>
                            </template>

                            <!-- DEFAULT FLOW -->
                            <template v-else>
                                <!-- Header Section -->
                                <BaseCard class="p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-admin-highlight">title</span>
                                            <h3 class="font-bold text-slate-800">Header <span class="text-[10px] text-slate-400 font-normal uppercase ml-2">(Optional)</span></h3>
                                        </div>
                                        <div class="flex p-0.5 bg-slate-100 rounded-lg text-[10px] font-bold">
                                            <button @click="form.content_structure.header_type = 'TEXT'" 
                                                    class="px-3 py-1.5 rounded-md transition-all"
                                                    :class="form.content_structure.header_type === 'TEXT' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500'">TEXT</button>
                                            <button @click="form.content_structure.header_type = 'IMAGE'" 
                                                    class="px-3 py-1.5 rounded-md transition-all"
                                                    :class="form.content_structure.header_type !== 'TEXT' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500'">MEDIA</button>
                                        </div>
                                    </div>

                                    <Transition name="slide-fade" mode="out-in">
                                        <div v-if="form.content_structure.header_type === 'TEXT'" key="text-header">
                                            <input v-model="form.content_structure.header" type="text" maxlength="60"
                                                   class="input w-full px-4 py-3 border-transparent bg-slate-50 rounded-xl focus:bg-white" 
                                                   placeholder="Add a headline..." 
                                            />
                                            <div class="flex justify-end mt-1 px-1">
                                                <span class="text-[10px]" :class="form.content_structure.header.length > 55 ? 'text-orange-500' : 'text-slate-400'">
                                                    {{ form.content_structure.header.length }}/60
                                                </span>
                                            </div>
                                        </div>
                                        <div v-else key="media-header" class="grid grid-cols-3 gap-3">
                                            <button v-for="type in ['IMAGE', 'VIDEO', 'DOCUMENT']" :key="type"
                                                    @click="form.content_structure.header_type = type"
                                                    class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all"
                                                    :class="form.content_structure.header_type === type ? 'border-admin-primary bg-admin-primary/5 text-admin-primary' : 'border-slate-50 bg-slate-50 text-slate-400 hover:border-slate-200'"
                                            >
                                                <span class="material-symbols-outlined text-xl">{{ {IMAGE:'image', VIDEO:'videocam', DOCUMENT:'description'}[type] }}</span>
                                                <span class="text-[9px] font-bold">{{ type }}</span>
                                            </button>
                                        </div>
                                    </Transition>
                                    <p class="text-[10px] text-slate-400 mt-3 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[12px]">lightbulb</span>
                                        Media headers increase engagement by up to 40%
                                    </p>
                                </BaseCard>

                                <!-- Body Section -->
                                <BaseCard class="p-6 border-l-4 border-admin-primary">
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
                                              placeholder="Hello {{1}}, we've received your order #{{2}}!"
                                    ></textarea>
                                    <div class="flex justify-between items-center mt-2 px-1">
                                        <div class="text-[10px] text-slate-400 italic">Use {{1}} for dynamic data</div>
                                        <span class="text-[10px]" :class="form.content_structure.body.length > 950 ? 'text-red-500' : 'text-slate-400'">
                                            {{ form.content_structure.body.length }}/1024
                                        </span>
                                    </div>
                                </BaseCard>

                                <!-- Footer Section -->
                                <BaseCard class="p-6">
                                    <div class="flex items-center gap-2 mb-4">
                                        <span class="material-symbols-outlined text-slate-400">subtitles</span>
                                        <h3 class="font-bold text-slate-800">Footer <span class="text-[10px] text-slate-400 font-normal uppercase ml-2">(Optional)</span></h3>
                                    </div>
                                    <input v-model="form.content_structure.footer" type="text" maxlength="60"
                                           class="input w-full px-4 py-3 border-transparent bg-slate-50 rounded-xl focus:bg-white italic" 
                                           placeholder="e.g. Reply STOP to opt out" 
                                    />
                                </BaseCard>

                                <!-- Buttons Section -->
                                <BaseCard class="p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-admin-primary">smart_button</span>
                                            <h3 class="font-bold text-slate-800">Buttons <span class="text-[10px] text-slate-400 font-normal uppercase ml-2">(Max 3)</span></h3>
                                        </div>
                                        <button v-if="form.content_structure.buttons.length < 3" @click="addButton" 
                                                class="text-[11px] font-bold text-admin-primary flex items-center gap-1 hover:bg-admin-primary/5 px-2 py-1 rounded transition-colors">
                                            <span class="material-symbols-outlined text-[16px]">add_circle</span> Add Button
                                        </button>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <div v-for="(btn, idx) in form.content_structure.buttons" :key="idx" 
                                             class="flex gap-3 items-center bg-slate-50 p-3 rounded-xl border border-slate-100 group animate-in slide-in-from-bottom-2">
                                            <div class="w-8 h-8 rounded-lg bg-white shadow-sm flex items-center justify-center text-slate-400">
                                                <span class="material-symbols-outlined text-sm">drag_indicator</span>
                                            </div>
                                            <select v-model="btn.type" class="input py-1.5 text-xs rounded-lg border-slate-200">
                                                <option value="QUICK_REPLY">Quick Reply</option>
                                                <option value="PHONE_NUMBER">Phone Call</option>
                                                <option value="URL">Visit Website</option>
                                            </select>
                                            <input v-model="btn.text" type="text" maxlength="25"
                                                   class="input py-1.5 text-xs grow rounded-lg border-slate-200 focus:bg-white" 
                                                   placeholder="Button text" 
                                            />
                                            <button @click="removeButton(idx)" class="text-slate-300 hover:text-red-500 transition-colors">
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>
                                        </div>
                                        <div v-if="form.content_structure.buttons.length === 0" 
                                             class="py-4 text-center text-xs text-slate-400 border-2 border-dashed border-slate-100 rounded-xl italic">
                                            No buttons added yet
                                        </div>
                                    </div>
                                </BaseCard>
                            </template>
                        </div>

                        <!-- Step 4: Review -->
                        <div v-else-if="currentStep === 4" key="step4" class="space-y-6">
                            <h2 class="text-xl font-bold text-slate-800">Review & Submit</h2>
                            
                            <BaseCard class="p-0 overflow-hidden">
                                <div class="bg-slate-50 px-8 py-4 border-b border-slate-100 flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Final Summary</span>
                                    <span class="px-2 py-1 rounded bg-admin-primary/10 text-admin-primary text-[10px] font-bold uppercase tracking-wider">
                                        Ready for Meta
                                    </span>
                                </div>
                                <div class="p-8 grid grid-cols-2 gap-8 line-divider">
                                    <div>
                                        <h4 class="text-[10px] uppercase font-bold text-slate-400 mb-1">Template Name</h4>
                                        <p class="font-bold text-slate-800">{{ form.name }}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-[10px] uppercase font-bold text-slate-400 mb-1">Category</h4>
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[16px] text-admin-primary">{{ getCategoryIcon(form.category) }}</span>
                                            <p class="font-bold text-slate-800">{{ getCategoryName(form.category) }}</p>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-[10px] uppercase font-bold text-slate-400 mb-1">Language</h4>
                                        <p class="font-bold text-slate-800">{{ languages.find(l => l.code === form.language)?.name }}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-[10px] uppercase font-bold text-slate-400 mb-1">Button Count</h4>
                                        <p class="font-bold text-slate-800">{{ form.content_structure.buttons.length }} Buttons</p>
                                    </div>
                                </div>
                            </BaseCard>

                            <div class="bg-blue-50 border border-blue-100 p-6 rounded-2xl flex gap-4">
                                <span class="material-symbols-outlined text-blue-500">verified</span>
                                <div>
                                    <h4 class="font-bold text-blue-900 text-sm">Meta Submission Agreement</h4>
                                    <p class="text-xs text-blue-700 mt-1 leading-relaxed">
                                        By submitting this template, you agree that it follows all WhatsApp Business Messaging Policies. Templates usually take 2-24 hours to be approved by Meta's team.
                                    </p>
                                </div>
                            </div>
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
                            Final Submit for Meta Review
                        </BaseButton>
                    </div>
                </div>

                <!-- Live Preview Column -->
                <div class="lg:col-span-4 lg:sticky lg:top-8">
                    <div class="bg-white rounded-[2.5rem] p-4 shadow-2xl border-[12px] border-slate-800 aspect-[9/18.5] relative overflow-hidden flex flex-col group">
                        <!-- Phone Header -->
                        <div class="h-10 shrink-0 flex items-center justify-between px-6 -mt-2">
                            <span class="text-xs font-bold text-slate-800">9:41</span>
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px] text-slate-800">signal_cellular_4_bar</span>
                                <span class="material-symbols-outlined text-[14px] text-slate-800">wifi</span>
                                <span class="material-symbols-outlined text-[14px] text-slate-800">battery_full</span>
                            </div>
                        </div>

                        <!-- Chat Area -->
                        <div class="flex-1 bg-[#E5DDD5] rounded-t-[1.5rem] overflow-hidden flex flex-col p-3 relative">
                            <!-- Background Pattern Overlay -->
                            <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-repeat: repeat; background-size: 400px;"></div>

                            <div class="relative z-10 w-[85%] animate-in fade-in slide-in-from-left duration-300">
                                <!-- CALLING PERMISSIONS PREVIEW -->
                                <div v-if="form.type === 'CALLING_PERMISSIONS'" class="bg-white rounded-xl shadow-md overflow-hidden flex flex-col p-4 text-center">
                                    <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <span class="material-symbols-outlined text-slate-500 text-2xl">call</span>
                                    </div>
                                    <h4 class="font-bold text-slate-900 text-sm mb-1">Can {{ form.content_structure.business_name || 'Business' }} call you?</h4>
                                    <p class="text-[11px] text-slate-500 leading-tight">You can update your preference anytime in the business profile.</p>
                                    
                                    <div class="mt-4 flex flex-col gap-2">
                                        <div class="p-2 border border-slate-100 rounded-lg text-[#008069] font-bold text-xs flex items-center justify-center gap-2">
                                            Choose preference <span class="material-symbols-outlined text-sm">expand_more</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- CATALOGUE PREVIEW -->
                                <div v-else-if="form.type === 'CATALOGUE'" class="bg-white rounded-xl shadow-md overflow-hidden flex flex-col">
                                    <div class="aspect-video bg-emerald-700/80 flex flex-col items-center justify-center p-4 text-center relative">
                                        <div class="absolute inset-0 opacity-20" style="background-image: url('https://www.transparenttextures.com/patterns/leaf.png')"></div>
                                        <h4 class="text-white font-bold text-sm relative z-10">{{ form.content_structure.business_name || 'Business' }}'s Catalogue</h4>
                                        <p class="text-white/80 text-[10px] relative z-10">Best deals here on WhatsApp!</p>
                                    </div>
                                    <div class="p-3">
                                        <p class="text-[13px] text-slate-800 leading-relaxed">{{ form.content_structure.body || 'Hello, view our latest products below!' }}</p>
                                    </div>
                                    <div class="p-2.5 border-t border-slate-100 flex items-center justify-center gap-2 text-[#008069] font-bold text-[13px]">
                                        <span class="material-symbols-outlined text-[16px]">inventory_2</span>
                                        View catalog
                                    </div>
                                </div>

                                <!-- DEFAULT PREVIEW -->
                                <div v-else class="bg-white rounded-xl shadow-md overflow-hidden flex flex-col">
                                    <!-- Media Header Preview -->
                                    <div v-if="form.content_structure.header_type !== 'TEXT'" 
                                         class="aspect-video bg-slate-100 flex flex-col items-center justify-center gap-2 border-b border-slate-50 transition-all">
                                        <span class="material-symbols-outlined text-slate-300 text-4xl animate-pulse">
                                            {{ {IMAGE:'image', VIDEO:'videocam', DOCUMENT:'description'}[form.content_structure.header_type] || 'image' }}
                                        </span>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ form.content_structure.header_type }} HEADER</span>
                                    </div>

                                    <div class="p-3">
                                        <!-- Header Text -->
                                        <div v-if="form.content_structure.header_type === 'TEXT' && form.content_structure.header" 
                                             class="font-bold text-slate-900 border-b border-slate-50 pb-2 mb-2 break-words leading-tight">
                                            {{ form.content_structure.header }}
                                        </div>
                                        
                                        <!-- Body Content -->
                                        <div class="text-[13px] text-slate-800 leading-relaxed whitespace-pre-wrap break-words" 
                                             v-html="previewBody">
                                        </div>
                                        
                                        <!-- Footer Text -->
                                        <div v-if="form.content_structure.footer" 
                                             class="text-slate-400 text-[11px] mt-2 border-t border-slate-50 pt-2 break-words">
                                            {{ form.content_structure.footer }}
                                        </div>
                                        
                                        <!-- Time Stamp -->
                                        <div class="flex justify-end mt-1">
                                            <span class="text-[10px] text-slate-300 font-medium">9:41 AM</span>
                                        </div>
                                    </div>

                                    <!-- Buttons in Preview -->
                                    <div v-if="form.content_structure.buttons.length" class="border-t border-slate-100 flex flex-col">
                                        <div v-for="(btn, i) in form.content_structure.buttons" :key="i" 
                                             class="p-2.5 border-b border-slate-100 last:border-0 flex items-center justify-center gap-2 text-[#008069] font-bold text-[13px]">
                                            <span v-if="btn.type === 'PHONE_NUMBER'" class="material-symbols-outlined text-[16px]">call</span>
                                            <span v-else-if="btn.type === 'URL'" class="material-symbols-outlined text-[16px]">open_in_new</span>
                                            {{ btn.text || 'Button text' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Home Indicator -->
                        <div class="h-10 shrink-0 flex items-center justify-center">
                            <div class="w-32 h-1 bg-slate-200 rounded-full"></div>
                        </div>

                        <!-- Live Tag -->
                        <div class="absolute top-12 right-2 bg-red-500 text-white text-[8px] font-black uppercase px-2 py-0.5 rounded-full animate-pulse shadow-lg flex items-center gap-1">
                            <span class="w-1 h-1 rounded-full bg-white"></span> Live Preview
                        </div>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <p class="text-[10px] text-slate-400 font-medium max-w-[200px] mx-auto leading-relaxed">
                            Preview is an approximation of how your message will appear on iOS devices.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.input {
    @apply transition-all duration-200 ring-0 focus:ring-0 outline-none border-2 border-slate-100 focus:border-admin-primary/50;
}

.line-divider > div {
    @apply border-b border-slate-50 pb-4;
}

.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(10px);
    opacity: 0;
}

/* Base custom styles matched to app theme */
.bg-admin-primary { background-color: var(--admin-primary, #6366f1); }
.text-admin-primary { color: var(--admin-primary, #6366f1); }
.border-admin-primary { border-color: var(--admin-primary, #6366f1); }
.text-admin-highlight { color: #f59e0b; }
</style>
