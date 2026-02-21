<template>
  <article class="card">
    <header class="row">
      <strong>Pedido #{{ order.id }}</strong>
      <OrderStatusBadge :status="order.estado" />
    </header>
    <p class="timer">{{ elapsedText }}</p>
    <p class="meta">{{ order.items_count }} ítems · Mesa {{ order.mesa || '-' }}</p>
    <p class="meta">Cliente: {{ order.cliente?.nombre || 'Sin cliente' }}</p>
    <div class="actions">
      <button :disabled="busy" @click="$emit('edit', order)">Editar</button>
      <button class="danger" :disabled="busy" @click="$emit('delete', order)">Cancelar</button>
    </div>
  </article>
</template>

<script setup>
import OrderStatusBadge from './OrderStatusBadge.vue';

defineProps({
  order: { type: Object, required: true },
  elapsedText: { type: String, required: true },
  busy: { type: Boolean, default: false },
});

defineEmits(['edit', 'delete']);
</script>

<style scoped>
.card { background: #121a2b; border: 1px solid #29344d; border-radius: 14px; padding: 14px; display: grid; gap: 8px; }
.row { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.timer { margin: 0; color: #7ecbff; font-weight: 600; }
.meta { margin: 0; color: #a8b4ce; font-size: 13px; }
.actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 4px; }
button { background: #2b8cff; color: #061124; border: 0; border-radius: 10px; padding: 12px; font-weight: 600; }
button.danger { background: #ff6f7c; }
button:disabled { opacity: .5; }
</style>
