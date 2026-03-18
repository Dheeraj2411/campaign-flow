<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@/Components/BaseButton.vue';

const emit = defineEmits(['close']);

const form = useForm({
    file: null,
});

const submit = () => {
    form.post(route('contacts.import.process'), {
        onSuccess: () => {
            emit('close');
        },
    });
};

const handleFile = (e) => {
    form.file = e.target.files[0];
};
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                    <span class="material-symbols-outlined text-admin-primary">upload_file</span>
                    Import Contacts
                </h3>
                <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form @submit.prevent="submit" class="p-6 space-y-6">
                <div class="bg-slate-50 p-6 rounded-xl border-2 border-dashed border-slate-200 text-center relative hover:bg-slate-100 transition-colors cursor-pointer">
                    <input
                        type="file"
                        @change="handleFile"
                        class="absolute inset-0 opacity-0 cursor-pointer"
                        accept=".csv"
                        required
                    />
                    <div class="space-y-2">
                        <span class="material-symbols-outlined text-[48px] text-slate-300">file_upload</span>
                        <div v-if="!form.file" class="text-sm text-slate-500">
                            <strong>Click to upload</strong> or drag and drop<br />
                            CSV files only (Max 10MB)
                        </div>
                        <div v-else class="text-sm text-admin-primary font-semibold">
                            {{ form.file.name }}
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 p-4 rounded-lg text-xs text-blue-700 leading-relaxed border border-blue-100">
                    <p class="font-bold mb-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">info</span>
                        CSV Format Requirements:
                    </p>
                    <p>File must include headers: <code>name</code>, <code>phone</code>, <code>telegram_username</code> (optional), and <code>tags</code> (optional, comma-separated).</p>
                </div>

                <div v-if="form.errors.file" class="text-xs text-red-600 bg-red-50 p-3 rounded border border-red-100 italic">
                    {{ form.errors.file }}
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <BaseButton type="button" variant="admin-outline" @click="$emit('close')">Cancel</BaseButton>
                    <BaseButton variant="admin" :loading="form.processing" type="submit">Start Import</BaseButton>
                </div>
            </form>
        </div>
    </div>
</template>
