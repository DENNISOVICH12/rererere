<template>
  <aside class="global-sidebar" aria-label="Navegación principal">
    <div class="global-sidebar__brand">
      <span class="global-sidebar__icon">🍽️</span>
      <span class="global-sidebar__text">ODER EASY</span>
    </div>

    <nav class="global-sidebar__nav">
      <a
        v-for="item in modules"
        :key="item.label"
        :href="item.href"
        :class="['global-sidebar__link', { 'is-active': isActive(item) }]"
        :title="item.label"
      >
        <span class="global-sidebar__icon">{{ item.icon }}</span>
        <span class="global-sidebar__text">{{ item.label }}</span>
      </a>
    </nav>
  </aside>
</template>

<script setup>
const props = defineProps({
  modules: {
    type: Array,
    default: () => [],
  },
  currentPath: {
    type: String,
    default: () => window.location.pathname,
  },
});

const isActive = (item) => {
  if (typeof item.active === 'function') return item.active(props.currentPath);
  if (Array.isArray(item.match)) return item.match.some((prefix) => props.currentPath.startsWith(prefix));
  return props.currentPath === item.href;
};
</script>
