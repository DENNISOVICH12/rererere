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
  background: radial-gradient(circle at top, #13213f 0%, #0b1220 55%, #080d17 100%);
  color: #f8fbff;
  padding: 20px;
}

@media (min-width: 768px) {
  .app-shell {
    padding: 28px;
  }
}
</style>
