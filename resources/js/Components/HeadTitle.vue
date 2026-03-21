<template>
    <Head :title="title" />
    <Teleport to="#header-content" v-if="mounted">
        <h1 class="text-lg font-bold text-gray-900">{{ title }}</h1>
        <p v-if="subtitle" class="text-sm text-gray-500">{{ subtitle }}</p>
    </Teleport>
    <slot />
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: null,
    },
});

const mounted = ref(false);
onMounted(() => {
    mounted.value = true;
});
</script>
