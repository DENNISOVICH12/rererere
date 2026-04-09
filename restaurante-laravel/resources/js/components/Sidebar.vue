<template>
  <aside class="global-sidebar" aria-label="Navegación principal">
    <div class="global-sidebar__brand">
      <span class="global-sidebar__icon">🍽️</span>
      <span class="global-sidebar__text">ODER EASY</span>
    </div>

    <nav class="global-sidebar__nav">
      <section
        v-for="section in groupedModules"
        :key="section.title"
        class="global-sidebar__section"
      >
        <h2 class="global-sidebar__section-title">{{ section.title }}</h2>
        <div class="global-sidebar__section-links">
          <a
            v-for="item in section.items"
            :key="item.label"
            :href="item.href"
            :class="['global-sidebar__link', { 'is-active': isActive(item) }]"
            :title="item.label"
          >
            <span class="global-sidebar__icon">{{ item.icon }}</span>
            <span class="global-sidebar__text">{{ item.label }}</span>
          </a>
        </div>
      </section>
    </nav>

    <form method="POST" action="/logout" class="global-sidebar__logout-form">
      <input type="hidden" name="_token" :value="csrfToken">
      <button type="submit" class="global-sidebar__logout" data-logout title="Cerrar sesión">
        <span class="global-sidebar__icon">⏻</span>
        <span class="global-sidebar__text">Cerrar sesión</span>
      </button>
    </form>
  </aside>
</template>

<script setup>
import { computed } from 'vue';

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

const groupedModules = computed(() => {
  const groups = [];

  props.modules.forEach((item) => {
    const sectionTitle = item.section || 'GENERAL';
    const existingGroup = groups.find((group) => group.title === sectionTitle);

    if (existingGroup) {
      existingGroup.items.push(item);
      return;
    }

    groups.push({
      title: sectionTitle,
      items: [item],
    });
  });

  return groups;
});

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
</script>
