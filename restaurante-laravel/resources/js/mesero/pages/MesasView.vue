<template>
  <section class="layout">
    <header class="topbar">
      <div>
        <h1>Mapa de Mesas</h1>
        <p>Gestiona clientes y pedidos por mesa, con facturación individual.</p>
      </div>
      <button class="refresh" @click="$emit('refresh')">Actualizar</button>
    </header>

    <div class="grid">
      <MesaCard v-for="mesa in mesas" :key="mesa.id" :mesa="mesa" @select="$emit('open-mesa', mesa)" />
    </div>
  </section>
</template>

<script setup>
import MesaCard from '../components/MesaCard.vue';

defineProps({ mesas: { type: Array, default: () => [] } });
defineEmits(['open-mesa', 'refresh']);
</script>

<style scoped>
.layout { display: grid; gap: 16px; }
.topbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; }
.topbar p { margin: 4px 0 0; color: #9db0d8; }
.refresh { border: 0; border-radius: 10px; background: #1d4ed8; color: #fff; padding: 10px 14px; font-weight: 600; }
.grid { display: grid; gap: 12px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
@media (min-width: 768px) { .grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (min-width: 1024px) { .grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); } }
</style>
