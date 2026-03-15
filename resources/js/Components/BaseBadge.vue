<template>
    <span class="badge" :class="variantClass">
        <span v-if="dot" class="w-1.5 h-1.5 rounded-full" :class="dotClass" />
        <slot />
    </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    variant: { type: String, default: 'neutral' }, // success|warning|danger|info|primary|neutral
    dot:     { type: Boolean, default: false },
})

const variantClass = computed(() => ({
    success: 'badge-success',
    warning: 'badge-warning',
    danger:  'badge-danger',
    info:    'badge-info',
    primary: 'badge-primary',
    neutral: 'badge-neutral',
}[props.variant]))

const dotClass = computed(() => ({
    success: 'bg-emerald-500',
    warning: 'bg-amber-500',
    danger:  'bg-red-500',
    info:    'bg-blue-500',
    primary: 'bg-admin-primary',
    neutral: 'bg-slate-400',
}[props.variant]))
</script>
