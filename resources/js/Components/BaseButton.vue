<template>
    <component
        :is="href ? Link : 'button'"
        v-bind="linkProps"
        class="inline-flex items-center justify-center gap-2 font-semibold rounded-xl transition-all duration-150
               focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed
               active:scale-95"
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
    variant:  { type: String, default: 'admin' },   // admin | store | danger | ghost | outline | white
    size:     { type: String, default: 'md' },       // sm | md | lg
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
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2.5 text-sm',
    lg: 'px-6 py-3 text-base',
}[props.size]))

const variantClasses = computed(() => ({
    admin:   'bg-admin-primary text-white hover:bg-admin-primary/90 focus-visible:ring-admin-primary shadow-sm',
    store:   'bg-store-primary text-white hover:bg-store-primary/90 focus-visible:ring-store-primary shadow-sm',
    danger:  'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500 shadow-sm',
    ghost:   'bg-transparent text-slate-600 hover:bg-slate-100 focus-visible:ring-slate-300',
    outline: 'border border-admin-primary text-admin-primary hover:bg-admin-primary/8 focus-visible:ring-admin-primary',
    white:   'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 focus-visible:ring-slate-300 shadow-sm',
}[props.variant]))
</script>
