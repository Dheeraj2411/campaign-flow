<template>
    <AppLayout title="Workspace Settings" subtitle="Manage your API credentials and workspace details">
        <div class="space-y-6 max-w-2xl">

            <!-- General Settings -->
            <BaseCard class="p-6">
                <h3 class="text-lg font-semibold text-slate-800 mb-4">Workspace Details</h3>
                <form @submit.prevent="saveGeneral" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Workspace Name</label>
                        <input v-model="generalForm.name" type="text" class="input" required />
                    </div>
                    <div class="flex justify-end pt-2">
                        <BaseButton variant="admin" :loading="generalForm.processing" type="submit">Save Name</BaseButton>
                    </div>
                </form>
            </BaseCard>

            <!-- WhatsApp API Credentials -->
            <BaseCard class="p-6 border-l-4 border-l-green-500">
                <div class="flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-green-500 text-2xl">chat</span>
                    <h3 class="text-lg font-semibold text-slate-800">WhatsApp API Credentials</h3>
                </div>
                <form @submit.prevent="saveSettings" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Phone Number ID</label>
                        <input v-model="settingsForm.whatsapp_phone_number_id" type="text" class="input" placeholder="e.g. 1029384756" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Permanent Access Token</label>
                        <input v-model="settingsForm.whatsapp_access_token" type="password" class="input" placeholder="EAA..." />
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
                        <input v-model="settingsForm.telegram_bot_token" type="password" class="input" placeholder="123456789:AAH..." />
                    </div>

                    <div class="bg-slate-50 p-4 rounded-lg mt-4 text-sm text-slate-600">
                        <p class="font-semibold mb-1">Webhook Setup:</p>
                        <p>Run this command in your browser to set Telegram webhook:</p>
                        <code class="bg-slate-200 block p-2 rounded mt-1 break-all text-xs">
                            https://api.telegram.org/bot{YOUR_BOT_TOKEN}/setWebhook?url={{ $page.props.app_url }}/telegram/webhook/{{ $page.props.auth.workspace.slug }}
                        </code>
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
    whatsapp_access_token:    props.workspace.settings?.whatsapp_access_token || '',
    telegram_bot_token:       props.workspace.settings?.telegram_bot_token || '',
})

const saveGeneral = () => {
    generalForm.put(route('settings.update.general'), { preserveScroll: true })
}

const saveSettings = () => {
    settingsForm.put(route('settings.update.api'), { preserveScroll: true })
}
</script>
