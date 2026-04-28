<template>
  <main class="app-shell">
    <header class="app-header">

    </header>
    <WaiterNotifications :restaurant-id="1" />
    <router-view />
  </main>
</template>

<script setup>
import axios from "axios";
import WaiterNotifications from "./components/WaiterNotifications.vue";

const logout = async () => {
  try {
    await axios.post("/logout");

    localStorage.clear();
    sessionStorage.clear();
    window.location.href = "/staff";
  } catch (error) {
    console.error("Error cerrando sesión", error);
  }
};

const confirmLogout = () => {
  if (confirm("¿Seguro que deseas cerrar sesión?")) {
    logout();
  }
};
</script>

<style scoped>
.app-shell {
  min-height: 100vh;
  background: radial-gradient(circle at top, rgba(156, 32, 48, 0.24) 0%, #0f172a 52%, #020617 100%);
  color: #f8fbff;
  padding: 20px;
}

.app-header {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 12px;
}

.logout-btn {
  border: 1px solid rgba(248, 251, 255, 0.5);
  border-radius: 10px;
  padding: 8px 14px;
  color: #f8fbff;
  background: rgba(148, 163, 184, 0.16);
  font-weight: 600;
  cursor: pointer;
}

.logout-btn:hover {
  background: rgba(148, 163, 184, 0.3);
}

@media (min-width: 768px) {
  .app-shell {
    padding: 28px;
  }
}
</style>
