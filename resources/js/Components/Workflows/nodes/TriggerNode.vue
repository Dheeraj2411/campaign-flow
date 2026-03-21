<template>
  <div class="bg-white border-2 border-green-500 rounded-lg shadow-md w-64 overflow-hidden">
    <div class="bg-green-500 px-3 py-2 flex items-center gap-2 text-white">
      <span class="material-symbols-outlined text-sm">bolt</span>
      <span class="font-bold text-sm">Trigger</span>
    </div>
    <div class="p-4">
      <h4 class="text-sm font-semibold text-gray-800">{{ data.label || 'Workflow Trigger' }}</h4>
      <p class="text-xs text-gray-500 mt-1 uppercase">{{ data.triggerType || 'Select Trigger' }}</p>
    </div>
    <Handle type="source" position="bottom" class="w-3 h-3 bg-green-500" />
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
