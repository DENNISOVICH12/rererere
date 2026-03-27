<template>
  <section class="layout">
    <header class="topbar">
      <div>
        <h1>Mapa de Mesas</h1>
        <p>Gestiona clientes y pedidos por mesa, con facturación individual.</p>
      </div>
      <button class="refresh" :disabled="loading" @click="loadMesas">
        {{ loading ? 'Actualizando...' : 'Actualizar' }}
      </button>
    </header>

    <p v-if="error" class="error">{{ error }}</p>

    <div class="grid">
      <MesaCard
        v-for="mesa in mesas"
        :key="mesa.id"
        :mesa="mesa"
        @select="openMesa"
      />
    </div>

    <p v-if="!loading && !mesas.length" class="empty">No hay mesas disponibles.</p>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import MesaCard from '../components/MesaCard.vue';
import { listMesas } from '../api.js';

const router = useRouter();

const mesas = ref([]);
const loading = ref(false);
const error = ref('');

const loadMesas = async () => {
  try {
    loading.value = true;
    error.value = '';
    mesas.value = await listMesas();
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudieron cargar las mesas.';
  } finally {
    loading.value = false;
  }
};

const openMesa = (mesa) => {
  if (!mesa?.id) return;

  router.push({
    name: 'mesa-detalle',
    params: { id: mesa.id },
  });
};

onMounted(loadMesas);
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

.refresh:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.error {
  margin: 0;
  padding: 10px 12px;
  border: 1px solid #7f1d1d;
  border-radius: 10px;
  background: #450a0a;
  color: #fecaca;
}

.empty {
  margin: 0;
  color: #9db0d8;
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
