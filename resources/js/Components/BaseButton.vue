<template>
    <component
        :is="href ? Link : 'button'"
        v-bind="linkProps"
        class="inline-flex items-center justify-center gap-2 font-medium rounded-xl transition-all duration-200
               focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
        :class="[sizeClasses, variantClasses]"
        :disabled="disabled || loading"
        @click="!href && $emit('click', $event)"
    >
        <span v-if="loading" class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span>
        <span v-else-if="icon" class="material-symbols-outlined text-[18px]">{{ icon }}</span>
        <slot />
    </component>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    variant:  { type: String, default: 'primary' },
    size:     { type: String, default: 'md' },
    icon:     { type: String, default: null },
    href:     { type: String, default: null },
    method:   { type: String, default: 'get' },
    disabled: { type: Boolean, default: false },
    loading:  { type: Boolean, default: false },
})

defineEmits(['click'])

const linkProps = computed(() => props.href
    ? { href: props.href, method: props.method, as: 'button', preserveState: true }
    : {}
)

const sizeClasses = computed(() => ({
    sm: 'px-4 py-2 text-xs min-h-[36px]',
    md: 'px-4 py-2.5 text-sm min-h-[40px]',
    lg: 'px-6 py-3 text-base min-h-[48px]',
}[props.size]))

const variantClasses = computed(() => ({
    primary:         'bg-indigo-600 hover:bg-indigo-700 text-white focus-visible:ring-indigo-500 shadow-sm hover:shadow-md',
    secondary:       'bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 focus-visible:ring-gray-300 shadow-sm',
    danger:          'bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 focus-visible:ring-red-500',
    ghost:           'bg-transparent text-gray-600 hover:bg-gray-100 focus-visible:ring-gray-300',
    outline:         'border border-indigo-600 text-indigo-600 hover:bg-indigo-50 focus-visible:ring-indigo-500',
    // Legacy aliases for backward compatibility
    admin:           'bg-indigo-600 hover:bg-indigo-700 text-white focus-visible:ring-indigo-500 shadow-sm hover:shadow-md',
    'admin-outline': 'bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 focus-visible:ring-gray-300 shadow-sm',
    store:           'bg-indigo-600 hover:bg-indigo-700 text-white focus-visible:ring-indigo-500 shadow-sm hover:shadow-md',
    white:           'bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 focus-visible:ring-gray-300 shadow-sm',
}[props.variant]))
</script>
