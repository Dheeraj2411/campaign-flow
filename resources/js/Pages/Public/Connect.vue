<template>
    <div class="min-h-screen bg-slate-50 flex flex-col items-center justify-center p-6 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-blue-50 via-slate-50 to-indigo-50">
        <div class="max-w-md w-full">
            <!-- Logo area -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-2xl shadow-sm border border-slate-100 mb-4 animate-bounce-slow">
                   <span class="material-symbols-outlined text-admin-primary text-3xl" style="font-variation-settings: 'FILL' 1">forum</span>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Connect with {{ workspace.name }}</h2>
                <p class="text-slate-500 mt-2">Join our Telegram bot to receive real-time updates and notifications.</p>
            </div>

            <!-- Main Card -->
            <div class="bg-white p-8 rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-admin-primary/5 rounded-full -mr-16 -mt-16"></div>
                
                <div v-if="botUsername" class="space-y-6 relative">
                    <!-- Instruction -->
                    <div class="space-y-2">
                        <div class="flex items-center gap-3 text-sm font-semibold text-slate-700">
                            <span class="w-6 h-6 rounded-full bg-admin-primary text-white flex items-center justify-center text-xs">1</span>
                            Open Telegram
                        </div>
                        <div class="flex items-center gap-3 text-sm font-semibold text-slate-700">
                            <span class="w-6 h-6 rounded-full bg-admin-primary text-white flex items-center justify-center text-xs">2</span>
                            Press the "START" button
                        </div>
                    </div>

                    <!-- Connect Button -->
                    <a :href="'https://t.me/' + botUsername" 
                       target="_blank"
                       class="flex items-center justify-center gap-3 w-full bg-[#24A1DE] hover:bg-[#1E8EBB] text-white py-4 rounded-2xl font-bold transition-all transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-blue-200">
                        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .33z"/>
                        </svg>
                        Connect Telegram
                    </a>

                    <!-- QR Code Separator -->
                    <div class="relative py-4">
                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-100"></div></div>
                        <div class="relative flex justify-center"><span class="bg-white px-4 text-xs font-medium text-slate-400 uppercase tracking-widest">or scan QR</span></div>
                    </div>

                    <!-- QR Code Placeholder (Using a clean API for demo) -->
                    <div class="flex flex-col items-center gap-4">
                        <div class="p-3 bg-white border-2 border-slate-50 rounded-2xl shadow-inner">
                            <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=https://t.me/' + botUsername" 
                                 :alt="workspace.name + ' QR Code'"
                                 class="w-40 h-40" />
                        </div>
                        <p class="text-[11px] text-slate-400 text-center px-4">Scan this QR code with your phone camera to open Telegram instantly.</p>
                    </div>
                </div>

                <!-- Error State -->
                <div v-else class="text-center py-8">
                    <span class="material-symbols-outlined text-amber-500 text-5xl mb-4">warning</span>
                    <p class="text-slate-700 font-semibold text-lg">Bot Not Connected</p>
                    <p class="text-slate-500 text-sm mt-2">The administrator hasn't configured the Telegram bot for this workspace yet.</p>
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center mt-8 text-xs text-slate-400 flex items-center justify-center gap-1.5">
                Powered by <span class="font-bold text-admin-primary tracking-tight">CampaignFlow</span>
            </p>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({
    workspace: { type: Object, required: true },
    botUsername: { type: String, default: null }
})
</script>

<style scoped>
.animate-bounce-slow {
    animation: bounce 3s infinite;
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(-5%);
        animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
    }
    50% {
        transform: translateY(0);
        animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
    }
}
</style>
