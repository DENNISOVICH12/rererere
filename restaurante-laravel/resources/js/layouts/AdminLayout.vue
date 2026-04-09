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
      { section: 'GENERAL', label: 'Dashboard', icon: '📊', href: '/admin', match: ['/admin'] },
      { section: 'GENERAL', label: 'Historial de Clientes', icon: '🧠', href: '/admin/clientes/historial', match: ['/admin/clientes/historial'] },
      { section: 'OPERACIÓN', label: 'Gestión de Mesas', icon: '🪑', href: '/admin/mesas', match: ['/admin/mesas'] },
      { section: 'PRODUCCIÓN', label: 'Cocina', icon: '👨‍🍳', href: '/cocina', match: ['/cocina'] },
      { section: 'PRODUCCIÓN', label: 'Barra', icon: '🍹', href: '/barra', match: ['/barra', '/bar'] },
      { section: 'CONFIGURACIÓN', label: 'Gestionar Usuarios', icon: '👥', href: '/usuarios', match: ['/usuarios'] },
      { section: 'CONFIGURACIÓN', label: 'Carta Digital', icon: '📱', href: '/carta-digital', match: ['/carta-digital'] },
    ]),
  },
});
</script>
