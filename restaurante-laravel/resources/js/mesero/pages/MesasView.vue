<template>
  <section class="layout">
    <header class="topbar">
      <div>
        <h1>Mapa de Mesas</h1>
        <p>Gestiona clientes y pedidos por mesa, con facturación individual.</p>
      </div>
      <button class="refresh" @click="loadMesas">Actualizar</button>
    </header>

    <div class="grid">
      <MesaCard
        v-for="mesa in mesas"
        :key="mesa.id"
        :mesa="mesa"
        @select="openMesa(mesa)"
      />
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import MesaCard from '../components/MesaCard.vue'
import { listMesas } from '../api.js'

const mesas = ref([])

const loadMesas = async () => {
  try {
    const data = await listMesas()
    console.log('MESAS:', data)
    mesas.value = data
  } catch (error) {
    console.error('ERROR MESAS:', error)
  }
}

const openMesa = (mesa) => {
  console.log('Mesa seleccionada:', mesa)
}

onMounted(() => {
  loadMesas()
})
</script>

<style scoped>
.layout { display: grid; gap: 16px; }

.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.topbar p {
  margin: 4px 0 0;
  color: #9db0d8;
}

.refresh {
  border: 0;
  border-radius: 10px;
  background: #1d4ed8;
  color: #fff;
  padding: 10px 14px;
  font-weight: 600;
}

.grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

@media (min-width: 768px) {
  .grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (min-width: 1024px) {
  .grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }
}
</style>