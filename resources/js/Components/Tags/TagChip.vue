<script setup>
defineProps({
    tag: {
        type: Object,
        required: true
    },
    removable: {
        type: Boolean,
        default: false
    }
});

defineEmits(['remove']);
</script>

<template>
    <span :style="{ backgroundColor: (tag.color || '#94a3b8') + '1A', color: (tag.color || '#64748b'), borderColor: (tag.color || '#cbd5e1') + '33' }"
          class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold tracking-wide uppercase border whitespace-nowrap">
        {{ tag.name }}
        <button v-if="removable" @click.stop.prevent="$emit('remove', tag)" class="hover:opacity-75 focus:outline-none ml-0.5 outline-none transition-opacity">
            <span class="material-symbols-outlined text-[12px] leading-none">close</span>
        </button>
    </span>
</template>
