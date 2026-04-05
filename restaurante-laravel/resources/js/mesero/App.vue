<template>
  <main class="app-shell">
    <WaiterNotifications :restaurant-id="1" />
    <router-view />
  </main>
</template>

<script setup>
import WaiterNotifications from "./components/WaiterNotifications.vue";
</script>

<style scoped>
.app-shell {
  min-height: 100vh;
  background: radial-gradient(circle at top, rgba(156, 32, 48, 0.24) 0%, #0f172a 52%, #020617 100%);
  color: #f8fbff;
  padding: 20px;
}

@media (min-width: 768px) {
  .app-shell {
    padding: 28px;
  }
}
</style>
