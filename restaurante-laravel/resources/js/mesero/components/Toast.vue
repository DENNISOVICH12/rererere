<template>
  <div v-if="show" class="toast" :class="type">{{ message }}</div>
</template>

<script setup>
defineProps({ show: Boolean, message: String, type: { type: String, default: 'info' } });
</script>

<style scoped>
.toast{ position:fixed; left:50%; transform:translateX(-50%); bottom:18px; background:#1e2b45; color:#eff5ff; padding:10px 14px; border-radius:999px; font-size:13px; z-index:80; }
.error{ background:#6a2330; }
.success{ background:#1d5a3d; }
</style>
