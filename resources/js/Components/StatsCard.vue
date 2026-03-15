<template>
    <div class="card flex items-start gap-4">
        <div class="p-3 rounded-xl shrink-0" :class="iconBg">
            <span class="material-symbols-outlined text-[22px]" :class="iconColor"
                  style="font-variation-settings: 'FILL' 1">{{ icon }}</span>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-slate-500 truncate">{{ label }}</p>
            <p class="text-2xl font-black text-slate-900 mt-0.5 tabular-nums">
                <span v-if="prefix" class="text-base font-semibold text-slate-400 mr-0.5">{{ prefix }}</span>
                {{ formattedValue }}
                <span v-if="suffix" class="text-sm font-medium text-slate-400 ml-0.5">{{ suffix }}</span>
            </p>
            <div v-if="change !== null" class="flex items-center gap-1 mt-1">
                <span
                    class="material-symbols-outlined text-[14px]"
                    :class="change >= 0 ? 'text-emerald-500' : 'text-red-500'"
                    style="font-variation-settings: 'FILL' 1"
                >
                    {{ change >= 0 ? 'trending_up' : 'trending_down' }}
                </span>
                <span class="text-xs font-semibold" :class="change >= 0 ? 'text-emerald-600' : 'text-red-600'">
                    {{ Math.abs(change) }}%
                </span>
                <span class="text-xs text-slate-400">{{ changePeriod }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    label:        { type: String, required: true },
    value:        { type: [Number, String], required: true },
    icon:         { type: String, default: 'analytics' },
    color:        { type: String, default: 'primary' },   // primary | success | warning | danger | info
    prefix:       { type: String, default: null },
    suffix:       { type: String, default: null },
    change:       { type: Number, default: null },
    changePeriod: { type: String, default: 'vs last month' },
})

const colorMap = {
    primary: { bg: 'bg-admin-primary/10', icon: 'text-admin-primary' },
    success: { bg: 'bg-emerald-100',      icon: 'text-emerald-600'   },
    warning: { bg: 'bg-amber-100',         icon: 'text-amber-600'    },
    danger:  { bg: 'bg-red-100',           icon: 'text-red-600'      },
    info:    { bg: 'bg-blue-100',          icon: 'text-blue-600'     },
    violet:  { bg: 'bg-admin-highlight/15', icon: 'text-admin-highlight' },
}

const iconBg    = computed(() => colorMap[props.color]?.bg    ?? colorMap.primary.bg)
const iconColor = computed(() => colorMap[props.color]?.icon  ?? colorMap.primary.icon)

const formattedValue = computed(() => {
    if (typeof props.value === 'number') {
        if (props.value >= 1_000_000) return (props.value / 1_000_000).toFixed(1) + 'M'
        if (props.value >= 1_000)     return (props.value / 1_000).toFixed(1) + 'K'
        return props.value.toLocaleString()
    }
    return props.value
})
</script>
