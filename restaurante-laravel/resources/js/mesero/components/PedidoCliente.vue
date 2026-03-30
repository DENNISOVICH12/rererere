<template>
  <article class="pedido-item">
    <header>
      <strong>{{ heading }}</strong>
    </header>
    <p class="cliente">Cliente: {{ customerName }}</p>
    <ul>
      <li v-for="item in pedido.items" :key="item.id">
        <span>{{ item.cantidad }} x {{ item.nombre }}</span>
        <small>${{ money(item.importe) }}</small>
      </li>
    </ul>
    <footer>Total: ${{ money(pedido.total) }}</footer>
  </article>
</template>

<script setup>
const props = defineProps({
  pedido: { type: Object, required: true },
  showOrderId: { type: Boolean, default: true },
  title: { type: String, default: 'Orden activa' },
});

const customerName = props.pedido?.cliente_nombre || props.pedido?.cliente?.nombre || 'Cliente invitado';
const heading = props.showOrderId ? customerName : props.title;

const money = (value) => Number(value || 0).toFixed(2);
</script>

<style scoped>
.pedido-item { background: #131f36; border: 1px solid #243457; border-radius: 12px; padding: 10px; }
header { display: flex; justify-content: space-between; margin-bottom: 8px; }
ul { list-style: none; padding: 0; margin: 0; display: grid; gap: 6px; }
li { display: flex; justify-content: space-between; color: #dbe8ff; }
footer { margin-top: 8px; font-weight: 600; }
.cliente { margin: 0 0 8px; color: #a8b4ce; font-size: 13px; }
</style>
