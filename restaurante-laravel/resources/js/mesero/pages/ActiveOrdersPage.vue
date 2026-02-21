<template>
  <section>
    <h1>Pedidos activos</h1>
    <div class="filters">
      <button v-for="chip in chips" :key="chip.value" :class="{ active: activeFilter === chip.value }" @click="$emit('change-filter', chip.value)">{{ chip.label }}</button>
    </div>
    <div class="list">
      <OrderCard
        v-for="order in orders"
        :key="order.id"
        :order="order"
        :elapsed-text="elapsedMap[order.id] || '00:00'"
        :busy="Boolean(busyMap[order.id])"
        @edit="$emit('edit', order)"
        @delete="$emit('delete', order)"
      />
      <p v-if="!orders.length" class="empty">Sin pedidos activos en este filtro.</p>
    </div>
  </section>
</template>

<script setup>
import OrderCard from '../components/OrderCard.vue';

defineProps({
  orders: Array,
  activeFilter: String,
  elapsedMap: Object,
  busyMap: Object,
});

defineEmits(['edit', 'delete', 'change-filter']);

const chips = [
  { value: '', label: 'Todos' },
  { value: 'pendiente', label: 'Pendiente' },
  { value: 'preparando', label: 'En preparación' },
  { value: 'listo', label: 'Listo' },
];
</script>

<style scoped>
h1{ margin: 0 0 12px; font-size: 22px; }
.filters{ display:flex; gap:8px; overflow:auto; padding-bottom:8px; }
.filters button{ border:1px solid #32415f; background:#121a2b; color:#d9e6ff; border-radius:999px; padding:9px 12px; white-space:nowrap; }
.filters button.active{ background:#2b8cff; color:#061124; border-color:#2b8cff; }
.list{ display:grid; gap:10px; }
.empty{ color:#9eacc9; text-align:center; padding:30px 0; }
</style>
