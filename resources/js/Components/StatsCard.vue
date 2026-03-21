<template>
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 flex flex-col gap-4">
        <div class="flex items-start gap-4">
            <div class="p-3 rounded-xl shrink-0" :class="iconBg">
                <span class="material-symbols-outlined text-[22px]" :class="iconColor"
                      style="font-variation-settings: 'FILL' 1">{{ icon }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-500 mb-0.5">{{ label }}</p>
                <p class="text-3xl font-bold text-gray-900 tabular-nums flex items-baseline gap-1">
                    <span v-if="prefix" class="text-base font-semibold text-gray-400">{{ prefix }}</span>
                    {{ displayValue }}
                    <span v-if="isObject && value.limit !== -1" class="text-xs font-medium text-gray-400">/ {{ value.limit }}</span>
                    <span v-else-if="isObject && value.limit === -1" class="text-xs font-medium text-gray-400">/ ∞</span>
                    <span v-if="suffix" class="text-sm font-medium text-gray-400 ml-0.5">{{ suffix }}</span>
                </p>
            </div>
        </div>

        <!-- Progress Bar for Usage Objects -->
        <div v-if="isObject && value.limit !== -1" class="space-y-1.5">
            <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                <div 
                    class="h-full rounded-full transition-all duration-1000"
                    :class="[progressColor]"
                    :style="{ width: Math.min(value.percent, 100) + '%' }"
                ></div>
            </div>
            <div class="flex justify-between items-center text-xs">
                <span class="font-medium" :class="value.percent > 90 ? 'text-red-500' : 'text-gray-400'">{{ value.percent }}% Used</span>
                <span v-if="value.percent > 90" class="text-red-500 font-medium animate-pulse">Running Low</span>
            </div>
        </div>
        
        <div v-if="change !== null" class="flex items-center gap-1">
            <span
                class="material-symbols-outlined text-[14px]"
                :class="change >= 0 ? 'text-emerald-500' : 'text-red-500'"
                style="font-variation-settings: 'FILL' 1"
            >
                {{ change >= 0 ? 'trending_up' : 'trending_down' }}
            </span>
            <span class="text-xs font-medium" :class="change >= 0 ? 'text-emerald-600' : 'text-red-600'">
                {{ Math.abs(change) }}%
            </span>
            <span class="text-xs text-gray-400">{{ changePeriod }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    label:        { type: String, required: true },
    value:        { type: [Number, String, Object], required: true },
    icon:         { type: String, default: 'analytics' },
    color:        { type: String, default: 'primary' },
    prefix:       { type: String, default: null },
    suffix:       { type: String, default: null },
    change:       { type: Number, default: null },
    changePeriod: { type: String, default: 'vs last month' },
})

const isObject = computed(() => typeof props.value === 'object' && props.value !== null)

const displayValue = computed(() => {
    if (isObject.value) {
        return props.value.current.toLocaleString()
    }
    
    if (typeof props.value === 'number') {
        if (props.value >= 1_000_000) return (props.value / 1_000_000).toFixed(1) + 'M'
        if (props.value >= 1_000)     return (props.value / 1_000).toFixed(1) + 'K'
        return props.value.toLocaleString()
    }
    return props.value
})

const colorMap = {
    primary: { bg: 'bg-indigo-50',   icon: 'text-indigo-600',  bar: 'bg-indigo-600' },
    success: { bg: 'bg-emerald-50',  icon: 'text-emerald-600', bar: 'bg-emerald-500' },
    warning: { bg: 'bg-amber-50',    icon: 'text-amber-600',   bar: 'bg-amber-500' },
    danger:  { bg: 'bg-red-50',      icon: 'text-red-600',     bar: 'bg-red-500' },
    info:    { bg: 'bg-blue-50',     icon: 'text-blue-600',    bar: 'bg-blue-500' },
    violet:  { bg: 'bg-violet-50',   icon: 'text-violet-600',  bar: 'bg-violet-500' },
}

const iconBg    = computed(() => colorMap[props.color]?.bg    ?? colorMap.primary.bg)
const iconColor = computed(() => colorMap[props.color]?.icon  ?? colorMap.primary.icon)
const progressColor = computed(() => {
    if (isObject.value && props.value.percent > 90) return 'bg-red-500'
    return colorMap[props.color]?.bar ?? colorMap.primary.bar
})
</script>
