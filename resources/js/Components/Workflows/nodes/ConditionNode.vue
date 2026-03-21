<template>
  <div class="bg-white border-2 border-yellow-500 rounded-lg shadow-md w-64 overflow-hidden relative mb-4">
    <Handle type="target" position="top" class="w-3 h-3 bg-yellow-500" />
    <div class="bg-yellow-500 px-3 py-2 flex items-center gap-2 text-white">
      <span class="material-symbols-outlined text-sm">filter_alt</span>
      <span class="font-bold text-sm">Condition</span>
    </div>
    <div class="p-4">
      <h4 class="text-sm font-semibold text-gray-800">{{ data.label || 'Check Condition' }}</h4>
      <div v-if="data.field" class="text-xs text-gray-500 mt-2 flex flex-col gap-1 bg-yellow-50 p-2 rounded">
        <span><strong class="text-gray-700">If</strong> {{ data.field }}</span>
        <span><strong class="text-gray-700">{{ data.operator }}</strong> {{ data.value }}</span>
      </div>
      <p v-else class="text-xs text-gray-400 mt-1 italic">Not configured</p>
    </div>
    
    <!-- YES Handle -->
    <Handle type="source" position="bottom" id="yes" class="w-3 h-3 bg-green-500 !left-[25%]" />
    <div class="absolute bottom-[-22px] left-[25%] -translate-x-1/2 text-[10px] font-bold text-green-600 bg-white px-1 rounded border border-green-200 z-10">YES</div>
    
    <!-- NO Handle -->
    <Handle type="source" position="bottom" id="no" class="w-3 h-3 bg-red-500 !left-[75%]" />
    <div class="absolute bottom-[-22px] left-[75%] -translate-x-1/2 text-[10px] font-bold text-red-600 bg-white px-1 rounded border border-red-200 z-10">NO</div>
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
