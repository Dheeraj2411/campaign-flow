<template>
    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium" :class="variantClass">
        <span v-if="dot" class="w-1.5 h-1.5 rounded-full" :class="dotClass" />
        <slot />
    </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    variant: { type: String, default: 'neutral' },
    dot:     { type: Boolean, default: false },
})

const variantClass = computed(() => ({
    success: 'bg-emerald-100 text-emerald-700',
    warning: 'bg-amber-100 text-amber-700',
    danger:  'bg-red-100 text-red-700',
    info:    'bg-indigo-100 text-indigo-700',
    primary: 'bg-indigo-100 text-indigo-700',
    neutral: 'bg-gray-100 text-gray-700',
}[props.variant]))

const dotClass = computed(() => ({
    success: 'bg-emerald-500',
    warning: 'bg-amber-500',
    danger:  'bg-red-500',
    info:    'bg-indigo-500',
    primary: 'bg-indigo-500',
    neutral: 'bg-gray-400',
}[props.variant]))
</script>
