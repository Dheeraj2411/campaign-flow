<template>
    <AppLayout title="Workspace Settings" subtitle="Manage your API credentials and workspace details">
        <div class="space-y-6 max-w-2xl">

            <!-- General Settings -->
            <BaseCard class="p-6">
                <h3 class="text-lg font-semibold text-slate-800 mb-4">Workspace Details</h3>
                <form @submit.prevent="saveGeneral" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Workspace Name</label>
                        <input v-model="generalForm.name" type="text" class="input" :class="{ 'border-red-400': generalForm.errors.name }" required />
                        <p v-if="generalForm.errors.name" class="text-xs text-red-500 mt-1">{{ generalForm.errors.name }}</p>
                    </div>
                    <div class="flex justify-end pt-2">
                        <BaseButton variant="admin" :loading="generalForm.processing" type="submit">Save Name</BaseButton>
                    </div>
                </form>
            </BaseCard>
            
            <!-- Team Invitations -->
            <BaseCard class="p-6">
                <div class="flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-admin-highlight text-2xl">group_add</span>
                    <h3 class="text-lg font-semibold text-slate-800">Invite Team Member</h3>
                </div>
                <form @submit.prevent="sendInvitation" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Email Address</label>
                            <input v-model="inviteForm.email" type="email" class="input" :class="{ 'border-red-400': inviteForm.errors.email }" placeholder="colleague@example.com" required />
                            <p v-if="inviteForm.errors.email" class="text-xs text-red-500 mt-1">{{ inviteForm.errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Role</label>
                            <select v-model="inviteForm.role" class="input">
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <BaseButton variant="admin" :loading="inviteForm.processing" type="submit">Send Invitation</BaseButton>
                    </div>
                </form>
            </BaseCard>

            <!-- WhatsApp API Credentials -->
            <BaseCard class="p-6 border-l-4 border-l-green-500">
                <div class="flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-green-500 text-2xl">chat</span>
                    <h3 class="text-lg font-semibold text-slate-800">WhatsApp API Credentials</h3>
                </div>

                <div class="bg-blue-50 border border-blue-100 p-4 rounded-lg mb-6 text-sm text-blue-700 flex gap-3">
                    <span class="material-symbols-outlined shrink-0 text-blue-500">info</span>
                    <div>
                        <p class="font-semibold mb-1">Permanent Access Token Guide</p>
                        <p>To ensure your campaigns don't stop unexpectedly, use a <strong>System User</strong> permanent token. Temporary tokens expire after 24 hours.</p>
                        <ol class="list-decimal ml-4 mt-2 space-y-1 text-xs">
                            <li>Go to Meta Business Settings -> System Users</li>
                            <li>Add a new System User and assign your WhatsApp Asset</li>
                            <li>Click "Generate New Token" and select the <code>whatsapp_business_messaging</code> scope</li>
                        </ol>
                    </div>
                </div>

                <form @submit.prevent="saveSettings" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Phone Number ID</label>
                        <input v-model="settingsForm.whatsapp_phone_number_id" type="text" class="input" :class="{ 'border-red-400': settingsForm.errors.whatsapp_phone_number_id }" placeholder="e.g. 1029384756" />
                        <p v-if="settingsForm.errors.whatsapp_phone_number_id" class="text-xs text-red-500 mt-1">{{ settingsForm.errors.whatsapp_phone_number_id }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">WhatsApp Business Account ID</label>
                        <input v-model="settingsForm.whatsapp_business_account_id" type="text" class="input" :class="{ 'border-red-400': settingsForm.errors.whatsapp_business_account_id }" placeholder="e.g. 9876543210" />
                        <p v-if="settingsForm.errors.whatsapp_business_account_id" class="text-xs text-red-500 mt-1">{{ settingsForm.errors.whatsapp_business_account_id }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Permanent Access Token</label>
                        <input v-model="settingsForm.whatsapp_access_token" type="password" class="input" :class="{ 'border-red-400': settingsForm.errors.whatsapp_access_token }" placeholder="EAA..." />
                        <p v-if="settingsForm.errors.whatsapp_access_token" class="text-xs text-red-500 mt-1">{{ settingsForm.errors.whatsapp_access_token }}</p>
                    </div>
                    
                    <div class="bg-slate-50 p-4 rounded-lg mt-4 text-sm text-slate-600">
                        <p class="font-semibold mb-1">Webhook Setup:</p>
                        <p>Configure Meta App Webhook URL: <code class="bg-slate-200 px-1 rounded">{{ $page.props.app_url }}/whatsapp/webhook</code></p>
                        <p>Verify Token: <code class="bg-slate-200 px-1 rounded">campaignflow_secret</code></p>
                    </div>
                    
                    <div class="flex justify-end pt-2">
                        <BaseButton variant="admin" :loading="settingsForm.processing" type="submit">Save Settings</BaseButton>
                    </div>
                </form>
            </BaseCard>

            <!-- Telegram API Credentials -->
            <BaseCard class="p-6 border-l-4 border-l-blue-500">
                <div class="flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-blue-500 text-2xl">send</span>
                    <h3 class="text-lg font-semibold text-slate-800">Telegram Bot API</h3>
                </div>
                <form @submit.prevent="saveSettings" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Bot Token</label>
                        <input v-model="settingsForm.telegram_bot_token" type="password" class="input" :class="{ 'border-red-400': settingsForm.errors.telegram_bot_token }" placeholder="123456789:AAH..." />
                        <p v-if="settingsForm.errors.telegram_bot_token" class="text-xs text-red-500 mt-1">{{ settingsForm.errors.telegram_bot_token }}</p>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-lg mt-4 text-sm text-slate-600">
                        <p class="font-semibold mb-1">Webhook Setup:</p>
                        <p>Run this command in your browser to set Telegram webhook:</p>
                        <code class="bg-slate-200 block p-2 rounded mt-1 break-all text-xs">
                            https://api.telegram.org/bot{YOUR_BOT_TOKEN}/setWebhook?url={{ $page.props.app_url }}/telegram/webhook/{{ $page.props.auth.workspace.slug }}
                        </code>
                    </div>

                    <div class="bg-blue-50 border border-blue-100 p-4 rounded-lg mt-4 text-sm text-blue-700">
                        <p class="font-semibold mb-1">Customer Onboarding Link:</p>
                        <p class="mb-2">Share this link with customers so they can connect their Telegram safely:</p>
                        <div class="flex items-center gap-2">
                            <code class="bg-white border border-blue-200 px-2 py-1 rounded grow break-all text-xs">
                                {{ $page.props.app_url }}/connect/{{ $page.props.auth.workspace.slug }}
                            </code>
                            <button @click="copyLink($page.props.app_url + '/connect/' + $page.props.auth.workspace.slug)" 
                                    class="p-1 px-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors text-xs font-semibold">
                                Copy
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <BaseButton variant="admin" :loading="settingsForm.processing" type="submit">Save Settings</BaseButton>
                    </div>
                </form>
            </BaseCard>

        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseCard from '@/Components/BaseCard.vue'
import BaseButton from '@/Components/BaseButton.vue'

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
