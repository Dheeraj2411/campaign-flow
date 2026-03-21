<template>
  <div class="bg-white border-2 border-purple-500 rounded-lg shadow-md w-48 overflow-hidden mx-auto relative mb-2">
    <Handle type="target" position="top" class="w-3 h-3 bg-purple-500" />
    <div class="bg-purple-500 px-3 py-1.5 flex items-center justify-center gap-1.5 text-white">
      <span class="material-symbols-outlined text-sm">schedule</span>
      <span class="font-bold text-xs uppercase tracking-wider">Delay</span>
    </div>
    <div class="p-3 text-center bg-purple-50">
      <p class="text-sm font-bold text-purple-900">{{ data.minutes || 0 }} min</p>
    </div>
    <Handle type="source" position="bottom" class="w-3 h-3 bg-purple-500" />
  </div>
</template>

<script setup>
import { Handle } from '@vue-flow/core';
defineProps({
  data: {
    type: Object,
    required: true,
  }
});
</script>
