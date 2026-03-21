<template>
  <div class="flex items-center space-x-2 text-gray-500 text-sm italic py-2 px-1 transition-opacity duration-300">
    <div class="flex space-x-1">
      <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0s;"></div>
      <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
      <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s;"></div>
    </div>
    <span>{{ agentName }} is typing...</span>
  </div>
</template>

<script setup>
defineProps({
  agentName: {
    type: String,
    required: true
  }
});
</script>
