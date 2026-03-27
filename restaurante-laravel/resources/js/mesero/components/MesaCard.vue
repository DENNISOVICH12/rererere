<template>
  <button class="mesa-card" :class="`estado-${mesa.estado}`" @click="$emit('select', mesa)">
    <span class="mesa-numero">Mesa {{ mesa.codigo }}</span>
    <span class="mesa-meta">{{ mesa.clientes_activos }} clientes</span>
    <span class="mesa-meta">{{ estadoLabel }}</span>
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  mesa: { type: Object, required: true },
});

defineEmits(['select']);

const estadoLabel = computed(() => {
  if (props.mesa.estado === 'pendiente') return '🔴 Pedidos pendientes';
  if (props.mesa.estado === 'en_uso') return '🟡 En uso';
  return '🟢 Libre';
});
</script>

<style scoped>
.mesa-card { border: 0; border-radius: 16px; padding: 16px; text-align: left; color: #fff; min-height: 120px; cursor: pointer; display: grid; gap: 6px; transition: transform .2s ease, box-shadow .2s ease; }
.mesa-card:hover { transform: translateY(-2px); }
.mesa-numero { font-weight: 700; font-size: 1.1rem; }
.mesa-meta { opacity: .95; }
.estado-libre { background: linear-gradient(160deg, #15803d, #166534); box-shadow: 0 8px 20px rgba(21,128,61,.25); }
.estado-en_uso { background: linear-gradient(160deg, #ca8a04, #a16207); box-shadow: 0 8px 20px rgba(161,98,7,.28); }
.estado-pendiente { background: linear-gradient(160deg, #dc2626, #991b1b); box-shadow: 0 8px 20px rgba(153,27,27,.3); }
</style>
