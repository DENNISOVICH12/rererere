<template>
  <button class="mesa-card" :class="stateClass" @click="$emit('select', mesa)">
    <span class="mesa-numero">{{ mesaNumero }}</span>
    <span class="mesa-titulo">Mesa {{ mesaNumero }}</span>
    <span class="mesa-estado">{{ estadoLabel }}</span>
    <span class="mesa-meta">{{ mesa.pedidos_activos_count ?? 0 }} pedidos activos</span>
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  mesa: { type: Object, required: true },
});

defineEmits(['select']);

const isLibre = computed(() => props.mesa.estado === 'libre');
const mesaNumero = computed(() => props.mesa.numero ?? props.mesa.codigo ?? props.mesa.id ?? '-');

const stateClass = computed(() => (isLibre.value ? 'estado-libre' : 'estado-ocupada'));

const estadoLabel = computed(() => (isLibre.value ? '🟢 Libre' : '🔴 Ocupada'));
</script>

<style scoped>
.mesa-card {
  width: clamp(120px, 17vw, 160px);
  aspect-ratio: 1;
  border-radius: 50%;
  border: 3px solid transparent;
  color: #f8fafc;
  margin-inline: auto;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  text-align: center;
  transition: all 0.2s ease;
}

.mesa-card:hover {
  transform: scale(1.05);
  box-shadow: 0 16px 28px rgba(15, 23, 42, 0.35);
}

.mesa-numero {
  font-size: clamp(2rem, 3.2vw, 2.6rem);
  line-height: 1;
  font-weight: 800;
  letter-spacing: 0.02em;
}

.mesa-titulo {
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  opacity: 0.95;
  font-weight: 600;
}

.mesa-estado {
  font-size: 0.82rem;
  font-weight: 700;
}

.mesa-meta {
  font-size: 0.74rem;
  opacity: 0.9;
}

.estado-libre {
  background: radial-gradient(circle at 30% 28%, #4ade80 0%, #22c55e 42%, #15803d 100%);
  border-color: #86efac;
  box-shadow: 0 12px 24px rgba(22, 163, 74, 0.4);
}

.estado-libre:hover {
  background: radial-gradient(circle at 30% 28%, #6ee7b7 0%, #34d399 45%, #22c55e 100%);
}

.estado-ocupada {
  background: radial-gradient(circle at 30% 28%, #b91c1c 0%, #7f1d1d 48%, #450a0a 100%);
  border-color: #ef4444;
  box-shadow: 0 12px 24px rgba(220, 38, 38, 0.4), 0 0 18px rgba(239, 68, 68, 0.3);
}

.estado-ocupada:hover {
  background: radial-gradient(circle at 30% 28%, #dc2626 0%, #991b1b 48%, #4c0519 100%);
  box-shadow: 0 12px 28px rgba(220, 38, 38, 0.5), 0 0 24px rgba(248, 113, 113, 0.4);
}
</style>
