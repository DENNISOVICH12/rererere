<script setup>
import {ref, onMounted} from 'vue'
import axios from 'axios'

const orders = ref([])

async function loadOrders() {
  const res = await axios.get('http://192.168.80.14:8000/api/orders')
  orders.value = res.data
}

onMounted(() => {
  loadOrders()
  setInterval(loadOrders, 4000) // refresca cada 4s
})
</script>

<template>
  <h1>🧾 Pedidos en Cocina</h1>

  <div v-for="order in orders" :key="order.id" class="order-card">
    <h3>Pedido #{{ order.id }}</h3>
    <p>Estado: {{ order.status }}</p>
    <ul>
      <li v-for="item in order.items">
        {{ item.menu_item.nombre }} x {{ item.quantity }}
      </li>
    </ul>
  </div>
</template>
