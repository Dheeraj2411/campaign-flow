<template>
    <AppLayout title="New Campaign" subtitle="Send a message campaign to your contacts">

        <!-- Progress Steps -->
        <div class="flex items-center gap-2 mb-8 overflow-x-auto pb-2">
            <template v-for="(s, i) in steps" :key="i">
                <div class="flex items-center gap-2 shrink-0">
                    <div class="flex items-center gap-2 cursor-pointer" @click="goToStep(i)">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                             :class="stepClass(i)">
                            <span v-if="currentStep > i" class="material-symbols-outlined text-[16px]">check</span>
                            <span v-else>{{ i + 1 }}</span>
                        </div>
                        <span class="text-xs font-medium hidden sm:block"
                              :class="currentStep === i ? 'text-admin-primary' : currentStep > i ? 'text-slate-500' : 'text-slate-300'">
                            {{ s.label }}
                        </span>
                    </div>
                    <div v-if="i < steps.length - 1" class="h-px w-8 shrink-0"
                         :class="currentStep > i ? 'bg-admin-primary' : 'bg-slate-200'" />
                </div>
            </template>
        </div>

        <!-- Step Panels -->
        <div class="max-w-2xl">
            <!-- Step 1: Name + Platform -->
            <div v-if="currentStep === 0" class="card animate-slide-up">
                <h2 class="text-base font-semibold text-slate-800 mb-4">Campaign Details</h2>
                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Campaign Name *</label>
                        <input v-model="form.name" type="text" class="input"
                               placeholder="Summer Sale Announcement" required />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-3">Messaging Platform *</label>
                        <div class="grid grid-cols-3 gap-3">
                            <div v-for="p in platforms" :key="p.value"
                                 class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                 :class="form.platform === p.value
                                     ? 'border-admin-primary bg-admin-primary/6'
                                     : 'border-slate-200 hover:border-slate-300'"
                                 @click="form.platform = p.value">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                     :class="p.bg">
                                    <span class="material-symbols-outlined text-[22px]" :class="p.color">{{ p.icon }}</span>
                                </div>
                                <span class="text-xs font-semibold text-slate-700">{{ p.label }}</span>
                                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                                     :class="form.platform === p.value ? 'border-admin-primary bg-admin-primary' : 'border-slate-300'">
                                    <span v-if="form.platform === p.value" class="material-symbols-outlined text-white text-[12px]">check</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Select contacts -->
            <div v-if="currentStep === 1" class="card animate-slide-up">
                <h2 class="text-base font-semibold text-slate-800 mb-2">Select Contacts</h2>
                <p class="text-xs text-slate-400 mb-5">Choose which contacts will receive this campaign</p>
                <div class="space-y-3">
                    <div
                        v-for="g in contactGroups"
                        :key="g.id"
                        class="flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all"
                        :class="form.contact_group_id === g.id
                            ? 'border-admin-primary bg-admin-primary/6'
                            : 'border-slate-200 hover:border-slate-300'"
                        @click="form.contact_group_id = g.id"
                    >
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-slate-800">{{ g.name }}</p>
                            <p class="text-xs text-slate-400">{{ g.count }} contacts</p>
                        </div>
                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                             :class="form.contact_group_id === g.id ? 'border-admin-primary bg-admin-primary' : 'border-slate-300'">
                            <span v-if="form.contact_group_id === g.id" class="material-symbols-outlined text-white text-[12px]">check</span>
                        </div>
                    </div>
                    <div v-if="!contactGroups.length" class="py-8 text-center text-slate-400 text-sm">
                        No contact groups yet — <Link :href="route('contacts.index')" class="text-admin-primary font-medium">add contacts first</Link>
                    </div>
                </div>
            </div>

            <!-- Step 3: Compose message -->
            <div v-if="currentStep === 2" class="card animate-slide-up">
                <h2 class="text-base font-semibold text-slate-800 mb-4">Compose Message</h2>

                <!-- Template picker -->
                <div v-if="templates.length" class="mb-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Use a Template</label>
                    <select class="input" @change="applyTemplate($event.target.value)">
                        <option value="">— Start fresh —</option>
                        <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Message Body *</label>
                    <textarea v-model="form.body" rows="6" class="input resize-none"
                              placeholder="Hello {name}&#10;&#10;We have a special offer for you today..."></textarea>
                    <p class="text-xs text-slate-400 mt-1.5">
                        Use <code class="bg-slate-100 px-1 rounded text-admin-primary">{name}</code>,
                        <code class="bg-slate-100 px-1 rounded text-admin-primary">{phone}</code> as personalisation variables.
                    </p>
                </div>

                <!-- Live preview -->
                <div v-if="previewBody" class="mt-4 p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <p class="text-xs font-semibold text-slate-500 mb-2">Preview (Sample Contact)</p>
                    <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ previewBody }}</p>
                </div>
            </div>

            <!-- Step 4: Schedule -->
            <div v-if="currentStep === 3" class="card animate-slide-up">
                <h2 class="text-base font-semibold text-slate-800 mb-4">Schedule</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div v-for="opt in scheduleOptions" :key="opt.value"
                             class="flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                             :class="scheduleMode === opt.value
                                 ? 'border-admin-primary bg-admin-primary/6'
                                 : 'border-slate-200 hover:border-slate-300'"
                             @click="scheduleMode = opt.value; if(opt.value==='now') form.scheduled_at = null">
                            <span class="material-symbols-outlined text-[22px]"
                                  :class="scheduleMode === opt.value ? 'text-admin-primary' : 'text-slate-400'">
                                {{ opt.icon }}
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-700">{{ opt.label }}</p>
                                <p class="text-xs text-slate-400">{{ opt.desc }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="scheduleMode === 'later'">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Scheduled Date & Time</label>
                        <input v-model="form.scheduled_at" type="datetime-local" class="input"
                               :min="minDateTime" />
                    </div>
                </div>
            </div>

            <!-- Step 5: Review -->
            <div v-if="currentStep === 4" class="card animate-slide-up">
                <h2 class="text-base font-semibold text-slate-800 mb-5">Review & Launch</h2>
                <dl class="space-y-4">
                    <div class="flex items-start justify-between py-3 border-b border-slate-100">
                        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Campaign</dt>
                        <dd class="text-sm font-semibold text-slate-800">{{ form.name || '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between py-3 border-b border-slate-100">
                        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Platform</dt>
                        <dd class="text-sm font-semibold capitalize text-slate-800">{{ form.platform || '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between py-3 border-b border-slate-100">
                        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Schedule</dt>
                        <dd class="text-sm font-semibold text-slate-800">{{ form.scheduled_at ? form.scheduled_at : 'Send immediately' }}</dd>
                    </div>
                    <div class="py-3 border-b border-slate-100">
                        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Message Preview</dt>
                        <dd class="text-sm text-slate-700 whitespace-pre-wrap bg-slate-50 p-3 rounded-xl">{{ form.body || '—' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Nav buttons -->
            <div class="flex items-center justify-between mt-5">
                <BaseButton v-if="currentStep > 0" variant="white" icon="arrow_back" @click="currentStep--">Back</BaseButton>
                <div v-else />
                <BaseButton
                    v-if="currentStep < steps.length - 1"
                    variant="admin"
                    icon="arrow_forward"
                    @click="nextStep"
                >Continue</BaseButton>
                <BaseButton
                    v-else
                    variant="admin"
                    icon="send"
                    :loading="form.processing"
                    @click="launch"
                >Launch Campaign</BaseButton>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseButton  from '@/Components/BaseButton.vue'

const props = defineProps({
    contacts:      { type: Array, default: () => [] },
    contactGroups: { type: Array, default: () => [] },
    templates:     { type: Array, default: () => [] },
})

const currentStep  = ref(0)
const scheduleMode = ref('now')

const steps = [
    { label: 'Details'  },
    { label: 'Contacts' },
    { label: 'Message'  },
    { label: 'Schedule' },
    { label: 'Review'   },
]

const platforms = [
    { value: 'whatsapp', label: 'WhatsApp', icon: 'chat',  bg: 'bg-emerald-100', color: 'text-emerald-600' },
    { value: 'telegram', label: 'Telegram', icon: 'send',  bg: 'bg-blue-100',    color: 'text-blue-600'   },
    { value: 'both',     label: 'Both',     icon: 'forum', bg: 'bg-violet-100',  color: 'text-violet-600' },
]

const scheduleOptions = [
    { value: 'now',   label: 'Send Now',    desc: 'Dispatch immediately', icon: 'bolt' },
    { value: 'later', label: 'Schedule',    desc: 'Pick a date & time',   icon: 'schedule' },
]

const form = useForm({
    name:             '',
    platform:         'whatsapp',
    contact_group_id: null,
    body:             '',
    scheduled_at:     null,
})

const minDateTime = computed(() => new Date(Date.now() + 5 * 60 * 1000).toISOString().slice(0, 16))

const previewBody = computed(() => {
    if (!form.body) return ''
    return form.body.replace('{name}', 'John Doe').replace('{phone}', '+1 234 567 8900')
})

const stepClass = (i) => {
    if (currentStep.value > i) return 'bg-admin-primary text-white'
    if (currentStep.value === i) return 'bg-admin-primary text-white ring-4 ring-admin-primary/20'
    return 'bg-slate-100 text-slate-400'
}

const goToStep = (i) => { if (i < currentStep.value) currentStep.value = i }

const nextStep = () => { if (currentStep.value < steps.length - 1) currentStep.value++ }

const applyTemplate = (id) => {
    const t = props.templates.find(t => t.id == id)
    if (t) form.body = t.body
}

const launch = () => {
    form.post(route('campaigns.store'), { onSuccess: () => {} })
}
</script>
