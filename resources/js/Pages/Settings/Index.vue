<template>
    <HeadTitle title="Workspace Settings" subtitle="Manage your API credentials and workspace details">
        <div v-if="loading" class="flex items-center justify-center h-64">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>
        <div v-else-if="error" class="text-center py-16">
            <div class="text-red-500 text-lg font-semibold">{{ error }}</div>
            <button @click="window.location.reload()" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-xl">
                Retry
            </button>
        </div>
        <div v-else class="space-y-6 max-w-2xl">

            <!-- General Settings -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Workspace Details</h3>
                <form @submit.prevent="saveGeneral" class="space-y-4">
                    <div class="space-y-1">
                        <label for="workspace_name" class="block text-sm font-medium text-gray-700">Workspace Name</label>
                        <input id="workspace_name" name="workspace_name" v-model="generalForm.name" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" :class="{ 'border-red-400': generalForm.errors.name }" required />
                        <p v-if="generalForm.errors.name" class="text-xs text-red-500 mt-1">{{ generalForm.errors.name }}</p>
                    </div>
                    <div class="flex justify-end pt-2">
                        <BaseButton variant="primary" :loading="generalForm.processing" type="submit">Save Name</BaseButton>
                    </div>
                </form>
            </div>
            
            <!-- Team Invitations -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-indigo-600 text-xl">group_add</span>
                    <h3 class="text-base font-semibold text-gray-900">Invite Team Member</h3>
                </div>
                <form @submit.prevent="sendInvitation" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="invite_email" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input id="invite_email" name="invite_email" v-model="inviteForm.email" type="email" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" :class="{ 'border-red-400': inviteForm.errors.email }" placeholder="colleague@pingos.com" required />
                            <p v-if="inviteForm.errors.email" class="text-xs text-red-500 mt-1">{{ inviteForm.errors.email }}</p>
                        </div>
                        <div class="space-y-1">
                            <label for="invite_role" class="block text-sm font-medium text-gray-700">Role</label>
                            <select id="invite_role" name="invite_role" v-model="inviteForm.role" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white">
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <BaseButton variant="primary" :loading="inviteForm.processing" type="submit">Send Invitation</BaseButton>
                    </div>
                </form>
            </div>

            <!-- Chatbot Settings -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 border-l-4 border-l-purple-500">
                <div class="flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-purple-500 text-xl">smart_toy</span>
                    <h3 class="text-base font-semibold text-gray-900">AI Auto-Reply Chatbot</h3>
                </div>
                
                <form @submit.prevent="saveBotConfig" class="space-y-5">
                    
                    <div class="flex items-center">
                        <label for="bot_is_enabled" class="flex items-center cursor-pointer">
                            <input id="bot_is_enabled" name="bot_is_enabled" type="checkbox" v-model="botForm.is_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700">Enable AI Chatbot</span>
                        </label>
                    </div>

                    <div class="space-y-1">
                        <label for="bot_openai_api_key" class="block text-sm font-medium text-gray-700">OpenAI API Key</label>
                        <input id="bot_openai_api_key" name="bot_openai_api_key" v-model="botForm.openai_api_key" type="password" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all bg-white placeholder-gray-400" placeholder="sk-..." />
                        <p class="text-[11px] text-gray-400 mt-1">Leave blank to keep existing key.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="bot_model" class="block text-sm font-medium text-gray-700">Model</label>
                            <select id="bot_model" name="bot_model" v-model="botForm.model" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none bg-white">
                                <option value="gpt-4o-mini">gpt-4o-mini</option>
                                <option value="gpt-4o">gpt-4o</option>
                                <option value="gpt-3.5-turbo">gpt-3.5-turbo</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label for="bot_escalate_keyword" class="block text-sm font-medium text-gray-700">Escalation Keyword</label>
                            <input id="bot_escalate_keyword" name="bot_escalate_keyword" v-model="botForm.escalate_keyword" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all bg-white" placeholder="e.g. human" />
                            <p class="text-[11px] text-gray-400 mt-1">Hands off to human agent if typed.</p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label for="bot_system_prompt" class="block text-sm font-medium text-gray-700">System Prompt</label>
                        <textarea id="bot_system_prompt" name="bot_system_prompt" v-model="botForm.system_prompt" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all bg-white placeholder-gray-400" placeholder="You are a helpful assistant."></textarea>
                    </div>

                    <div class="space-y-1">
                        <label for="bot_trigger_keywords" class="block text-sm font-medium text-gray-700">Trigger Keywords</label>
                        <input id="bot_trigger_keywords" name="bot_trigger_keywords" v-model="botForm.trigger_keywords" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all bg-white" placeholder="pricing, feature, help" />
                        <p class="text-[11px] text-gray-400 mt-1">Comma-separated. Leave empty to always reply.</p>
                    </div>

                    <div class="flex items-center">
                        <label for="bot_off_hours_only" class="flex items-center cursor-pointer">
                            <input id="bot_off_hours_only" name="bot_off_hours_only" type="checkbox" v-model="botForm.off_hours_only" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700">Only reply during off-hours</span>
                        </label>
                    </div>

                    <div class="flex justify-end pt-2">
                        <BaseButton variant="primary" :loading="botSaving" type="submit">Save Chatbot Settings</BaseButton>
                    </div>
                </form>
            </div>

            <!-- WhatsApp API Credentials -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 border-l-4 border-l-emerald-500">
                <div class="flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-emerald-500 text-xl">chat</span>
                    <h3 class="text-base font-semibold text-gray-900">WhatsApp API Credentials</h3>
                </div>

                <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl mb-6 text-sm text-blue-700 flex gap-3">
                    <span class="material-symbols-outlined shrink-0 text-blue-500">info</span>
                    <div>
                        <p class="font-semibold mb-1">Permanent Access Token Guide</p>
                        <p>To ensure your campaigns don't stop unexpectedly, use a <strong>System User</strong> permanent token. Temporary tokens expire after 24 hours.</p>
                        <ol class="list-decimal ml-4 mt-2 space-y-1 text-xs">
                            <li>Go to Meta Business Settings -> System Users</li>
                            <li>Add a new System User and assign your WhatsApp Asset</li>
                            <li>Click "Generate New Token" and select the <code class="bg-blue-100 px-1 rounded">whatsapp_business_messaging</code> scope</li>
                        </ol>
                    </div>
                </div>

                <form @submit.prevent="saveSettings" class="space-y-4">
                    <div class="space-y-1">
                        <label for="whatsapp_phone_number_id" class="block text-sm font-medium text-gray-700">Phone Number ID</label>
                        <input id="whatsapp_phone_number_id" name="whatsapp_phone_number_id" v-model="settingsForm.whatsapp_phone_number_id" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" :class="{ 'border-red-400': settingsForm.errors.whatsapp_phone_number_id }" placeholder="e.g. 1029384756" />
                        <p v-if="settingsForm.errors.whatsapp_phone_number_id" class="text-xs text-red-500 mt-1">{{ settingsForm.errors.whatsapp_phone_number_id }}</p>
                    </div>
                    <div class="space-y-1">
                        <label for="whatsapp_business_account_id" class="block text-sm font-medium text-gray-700">WhatsApp Business Account ID</label>
                        <input id="whatsapp_business_account_id" name="whatsapp_business_account_id" v-model="settingsForm.whatsapp_business_account_id" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" :class="{ 'border-red-400': settingsForm.errors.whatsapp_business_account_id }" placeholder="e.g. 9876543210" />
                        <p v-if="settingsForm.errors.whatsapp_business_account_id" class="text-xs text-red-500 mt-1">{{ settingsForm.errors.whatsapp_business_account_id }}</p>
                    </div>
                    <div class="space-y-1">
                        <label for="whatsapp_access_token" class="block text-sm font-medium text-gray-700">Permanent Access Token</label>
                        <input id="whatsapp_access_token" name="whatsapp_access_token" v-model="settingsForm.whatsapp_access_token" type="password" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" :class="{ 'border-red-400': settingsForm.errors.whatsapp_access_token }" placeholder="EAA..." />
                        <p v-if="settingsForm.errors.whatsapp_access_token" class="text-xs text-red-500 mt-1">{{ settingsForm.errors.whatsapp_access_token }}</p>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-xl mt-4 text-sm text-gray-600 border border-gray-200">
                        <p class="font-semibold mb-1">Webhook Setup:</p>
                        <p>Configure Meta App Webhook URL: <code class="bg-gray-200 px-1.5 py-0.5 rounded text-xs">{{ $page.props.app_url }}/whatsapp/webhook</code></p>
                        <p class="mt-1">Verify Token: <code class="bg-gray-200 px-1.5 py-0.5 rounded text-xs">pingos_secret</code></p>
                    </div>
                    
                    <div class="flex justify-end pt-2">
                        <BaseButton variant="primary" :loading="settingsForm.processing" type="submit">Save Settings</BaseButton>
                    </div>
                </form>
            </div>

            <!-- Telegram API Credentials -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 border-l-4 border-l-blue-500">
                <div class="flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-blue-500 text-xl">send</span>
                    <h3 class="text-base font-semibold text-gray-900">Telegram Bot API</h3>
                </div>
                <form @submit.prevent="saveSettings" class="space-y-4">
                    <div class="space-y-1">
                        <label for="telegram_bot_token" class="block text-sm font-medium text-gray-700">Bot Token</label>
                        <input id="telegram_bot_token" name="telegram_bot_token" v-model="settingsForm.telegram_bot_token" type="password" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white placeholder-gray-400" :class="{ 'border-red-400': settingsForm.errors.telegram_bot_token }" placeholder="123456789:AAH..." />
                        <p v-if="settingsForm.errors.telegram_bot_token" class="text-xs text-red-500 mt-1">{{ settingsForm.errors.telegram_bot_token }}</p>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-xl mt-4 text-sm text-gray-600 border border-gray-200">
                        <p class="font-semibold mb-1">Webhook Setup:</p>
                        <p>Run this command in your browser to set Telegram webhook:</p>
                        <code class="bg-gray-200 block p-2 rounded-lg mt-1 break-all text-xs">
                            https://api.telegram.org/bot{YOUR_BOT_TOKEN}/setWebhook?url={{ $page.props.app_url }}/telegram/webhook/{{ $page.props.auth.workspace.slug }}
                        </code>
                    </div>

                    <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl mt-4 text-sm text-blue-700">
                        <p class="font-semibold mb-1">Customer Onboarding Link:</p>
                        <p class="mb-2">Share this link with customers so they can connect their Telegram safely:</p>
                        <div class="flex items-center gap-2">
                            <code class="bg-white border border-blue-200 px-3 py-1.5 rounded-lg grow break-all text-xs">
                                {{ $page.props.app_url }}/connect/{{ $page.props.auth.workspace.slug }}
                            </code>
                            <button @click="copyLink($page.props.app_url + '/connect/' + $page.props.auth.workspace.slug)" 
                                    class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-xs font-medium shrink-0">
                                Copy
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <BaseButton variant="primary" :loading="settingsForm.processing" type="submit">Save Settings</BaseButton>
                    </div>
                </form>
            </div>

        </div>
    </HeadTitle>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import HeadTitle from '@/Components/HeadTitle.vue'
import BaseButton from '@/Components/BaseButton.vue'
import axios from 'axios'

const props = defineProps({
    workspace: {
        type: Object,
        required: true
    }
})

const generalForm = useForm({
    name: props.workspace.name,
})

const settingsForm = useForm({
    whatsapp_phone_number_id: props.workspace.settings?.whatsapp_phone_number_id || '',
    whatsapp_business_account_id: props.workspace.settings?.whatsapp_business_account_id || '',
    whatsapp_access_token:    props.workspace.settings?.whatsapp_access_token || '',
    telegram_bot_token:       props.workspace.settings?.telegram_bot_token || '',
})

const inviteForm = useForm({
    email: '',
    role: 'member',
})

const botForm = ref({
    is_enabled: false,
    openai_api_key: '',
    model: 'gpt-4o-mini',
    system_prompt: '',
    trigger_keywords: '',
    off_hours_only: false,
    escalate_keyword: 'human'
});
const botSaving = ref(false);

const error = ref(null)
const loading = ref(true)

onMounted(async () => {
    try {
        const res = await axios.get('/chatbot/config');
        if (res.data) {
            botForm.value.is_enabled = res.data.is_enabled;
            botForm.value.model = res.data.model || 'gpt-4o-mini';
            botForm.value.system_prompt = res.data.system_prompt || '';
            botForm.value.trigger_keywords = (res.data.trigger_keywords || []).join(', ');
            botForm.value.off_hours_only = res.data.off_hours_only;
            botForm.value.escalate_keyword = res.data.escalate_keyword || 'human';
        }
    } catch(e) {
        error.value = e.response?.data?.message || e.message || 'Something went wrong loading settings'
    } finally {
        loading.value = false
    }
});

const saveBotConfig = async () => {
    botSaving.value = true;
    const data = { ...botForm.value };
    data.trigger_keywords = data.trigger_keywords.split(',').map(s => s.trim()).filter(Boolean);
    
    // Don't modify if left empty securely
    if (!data.openai_api_key) delete data.openai_api_key;

    try {
        await axios.put('/chatbot/config', data);
        alert('Chatbot config saved');
        botForm.value.openai_api_key = ''; 
    } catch(e) {
        alert('Failed to save chatbot config');
    }
    botSaving.value = false;
};

const saveGeneral = () => {
    generalForm.put(route('settings.update.general'), { preserveScroll: true })
}

const saveSettings = () => {
    settingsForm.put(route('settings.update.api'), { preserveScroll: true })
}

const sendInvitation = () => {
    inviteForm.post(route('workspaces.invite', props.workspace.id), {
        preserveScroll: true,
        onSuccess: () => {
            inviteForm.reset()
        }
    })
}

const copyLink = (link) => {
    navigator.clipboard.writeText(link)
    alert('Link copied to clipboard!')
}
</script>
