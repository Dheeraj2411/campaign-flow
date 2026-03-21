<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="modelValue"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
            >
                <!-- Overlay -->
                <div
                    class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                    @click="closeable && $emit('update:modelValue', false)"
                />

                <div
                    class="relative w-full sm:max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-xl animate-slide-up"
                    :class="maxWidthClass"
                >
                    <!-- Header -->
                    <div v-if="title" class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-slate-100">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">{{ title }}</h2>
                            <p v-if="description" class="text-xs text-slate-400 mt-0.5">{{ description }}</p>
                        </div>
                        <button
                            v-if="closeable"
                            class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition-colors"
                            @click="$emit('update:modelValue', false)"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-5">
                        <slot />
                    </div>

                    <!-- Footer -->
                    <div v-if="$slots.footer" class="px-6 pb-5 pt-1 flex items-center justify-end gap-3">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { watch } from 'vue'

const props = defineProps({
    modelValue:  { type: Boolean, required: true },
    title:       { type: String, default: null },
    description: { type: String, default: null },
    maxWidth:    { type: String, default: 'md' },   // sm | md | lg | xl | 2xl
    closeable:   { type: Boolean, default: true },
})

defineEmits(['update:modelValue'])

const maxWidthClass = {
    sm:  'max-w-sm',
    md:  'max-w-md',
    lg:  'max-w-lg',
    xl:  'max-w-xl',
    '2xl': 'max-w-2xl',
}[props.maxWidth]

// Lock body scroll when open
watch(() => props.modelValue, (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
})
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity .2s ease; }
.modal-enter-from, .modal-leave-to      { opacity: 0; }
</style>
