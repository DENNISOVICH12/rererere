<template>
  <section class="cliente-card">
    <header>
      <h3>{{ cliente.nombre }}</h3>
      <button class="facturar" :disabled="loading" @click="$emit('facturar', cliente)">
        {{ loading ? 'Facturando...' : 'Facturar cliente' }}
      </button>
    </header>

    <div class="pedidos-grid" v-if="cliente.pedidos.length">
      <PedidoCliente v-for="pedido in cliente.pedidos" :key="pedido.id" :pedido="pedido" />
    </div>
    <p v-else class="empty">Sin pedidos activos.</p>

    <footer>Total individual: <strong>${{ money(cliente.total) }}</strong></footer>
  </section>
</template>

<script setup>
import PedidoCliente from './PedidoCliente.vue';

defineProps({
  cliente: { type: Object, required: true },
  loading: { type: Boolean, default: false },
});

defineEmits(['facturar']);

const money = (value) => Number(value || 0).toFixed(2);
</script>

<style scoped>
.cliente-card { background: #0f1a30; border: 1px solid #243457; border-radius: 14px; padding: 14px; display: grid; gap: 12px; }
header { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
h3 { margin: 0; }
.facturar { border: 0; border-radius: 10px; background: #2563eb; color: #fff; padding: 8px 12px; font-weight: 600; cursor: pointer; }
.facturar:disabled { opacity: .6; cursor: wait; }
.pedidos-grid { display: grid; gap: 10px; }
.empty { color: #9fb3d9; margin: 0; }
footer { border-top: 1px solid #223355; padding-top: 10px; }
</style>
