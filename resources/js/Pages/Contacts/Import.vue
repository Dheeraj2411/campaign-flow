<template>
    <AppLayout title="Import Contacts" subtitle="Upload a CSV file to bulk add contacts">
        <BaseCard class="max-w-xl mx-auto mt-8 p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Upload CSV File</h2>
            <p class="text-sm text-slate-600 mb-6">
                Your CSV should contain <code class="bg-slate-100 text-admin-primary px-1 py-0.5 rounded text-xs">name</code> and <code class="bg-slate-100 text-admin-primary px-1 py-0.5 rounded text-xs">phone</code> columns (case-insensitive).
                Optional columns: <code class="bg-slate-100 text-admin-primary px-1 py-0.5 rounded text-xs">telegram_username</code>.
            </p>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="border-2 border-dashed rounded-2xl p-8 text-center transition-colors"
                     :class="dragActive ? 'border-admin-primary bg-admin-primary/5' : 'border-slate-300 hover:border-admin-highlight bg-slate-50'"
                     @dragover.prevent="dragActive = true"
                     @dragleave.prevent="dragActive = false"
                     @drop.prevent="handleDrop"
                >
                    <span class="material-symbols-outlined text-4xl text-slate-400 mb-3 block">upload_file</span>
                    
                    <div v-if="form.file" class="text-sm text-admin-primary font-medium">
                        {{ form.file.name }} ({{ (form.file.size / 1024).toFixed(1) }} KB)
                        <button type="button" class="text-slate-400 hover:text-red-500 ml-2" @click="form.file = null">
                            <span class="material-symbols-outlined text-[16px] align-middle">close</span>
                        </button>
                    </div>
                    <div v-else>
                        <p class="text-sm font-medium text-slate-900">Drag and drop your file here, or</p>
                        <label class="text-sm text-admin-primary font-semibold hover:text-admin-highlight cursor-pointer mt-1 inline-block">
                            browse from your computer
                            <input type="file" class="hidden" accept=".csv,.txt" @change="handleFileSelect" />
                        </label>
                    </div>
                    <p class="text-xs text-slate-400 mt-2">Max file size: 5MB. Format: .csv</p>
                    <p v-if="form.errors.file" class="text-xs text-red-500 mt-2">{{ form.errors.file }}</p>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <BaseButton variant="ghost" :href="route('contacts.index')">Cancel</BaseButton>
                    <BaseButton variant="admin" :loading="form.processing" :disabled="!form.file" type="submit">
                        Upload & Import
                    </BaseButton>
                </div>
            </form>
        </BaseCard>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseCard    from '@/Components/BaseCard.vue'
import BaseButton  from '@/Components/BaseButton.vue'

const dragActive = ref(false)

const form = useForm({
    file: null,
})

const handleDrop = (e) => {
    dragActive.value = false
    if (e.dataTransfer.files.length) {
        form.file = e.dataTransfer.files[0]
    }
}

const handleFileSelect = (e) => {
    if (e.target.files.length) {
        form.file = e.target.files[0]
    }
}

const submit = () => {
    form.post(route('contacts.import.process'), {
        onSuccess: () => form.reset(),
    })
}
</script>
