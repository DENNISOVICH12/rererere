<template>
  <article class="pedido-item">
    <header>
      <strong>Pedido #{{ pedido.id }}</strong>
      <span class="badge">{{ pedido.estado }}</span>
    </header>
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
defineProps({ pedido: { type: Object, required: true } });

const money = (value) => Number(value || 0).toFixed(2);
</script>

<style scoped>
.pedido-item { background: #131f36; border: 1px solid #243457; border-radius: 12px; padding: 10px; }
header { display: flex; justify-content: space-between; margin-bottom: 8px; }
ul { list-style: none; padding: 0; margin: 0; display: grid; gap: 6px; }
li { display: flex; justify-content: space-between; color: #dbe8ff; }
.badge { text-transform: capitalize; font-size: .8rem; background: #1f2f52; padding: 2px 8px; border-radius: 999px; }
footer { margin-top: 8px; font-weight: 600; }
</style>
