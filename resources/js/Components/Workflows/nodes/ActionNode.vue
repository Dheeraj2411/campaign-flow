<template>
  <div class="bg-white border-2 border-blue-500 rounded-lg shadow-md w-64 overflow-hidden">
    <Handle type="target" position="top" class="w-3 h-3 bg-blue-500" />
    <div class="bg-blue-500 px-3 py-2 flex items-center gap-2 text-white">
      <span class="material-symbols-outlined text-sm">play_arrow</span>
      <span class="font-bold text-sm">Action</span>
    </div>
    <div class="p-4">
      <h4 class="text-sm font-semibold text-gray-800">{{ data.label || 'Execute Action' }}</h4>
      <p class="text-xs font-medium text-blue-600 mt-1 bg-blue-50 py-1 px-2 rounded inline-block">
        {{ (data.actionType || 'none').replace(/_/g, ' ') }}
      </p>
    </div>
    <Handle type="source" position="bottom" class="w-3 h-3 bg-blue-500" />
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
