<template>
  <div class="admin-layout">
    <Sidebar :modules="modules" :current-path="currentPath" />
    <main class="admin-layout__content">
      <slot />
    </main>
  </div>
</template>

<script setup>
import Sidebar from '../components/Sidebar.vue';

const props = defineProps({
  currentPath: {
    type: String,
    default: () => window.location.pathname,
  },
  modules: {
    type: Array,
    default: () => ([
      { label: 'Dashboard', icon: '📊', href: '/admin', match: ['/admin'] },
      { label: 'Gestionar Usuarios', icon: '👥', href: '/usuarios', match: ['/usuarios'] },
      { label: 'Cocina', icon: '👨‍🍳', href: '/cocina', match: ['/cocina'] },
      { label: 'Barra', icon: '🍹', href: '/barra', match: ['/barra', '/bar'] },
      { label: 'Pedidos / Mesas', icon: '🧾', href: '/cocina/pedidos', match: ['/cocina/pedidos', '/pedidos'] },
      { label: 'Gestión de Mesas', icon: '🪑', href: '/admin/mesas', match: ['/admin/mesas'] },
      { label: 'Historial de Clientes', icon: '🧠', href: '/admin/clientes/historial', match: ['/admin/clientes/historial'] },
      { label: 'Carta Digital', icon: '📱', href: '/carta-digital', match: ['/carta-digital'] },
    ]),
  },
});
</script>
