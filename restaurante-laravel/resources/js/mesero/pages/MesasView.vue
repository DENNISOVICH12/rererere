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
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import MesaCard from '../components/MesaCard.vue';
import { listMesas } from '../api.js';
import { bindWaiterRealtime } from '../echo.js';

const router = useRouter();

const mesas = ref([]);
const loading = ref(false);
const error = ref('');
let refreshTimer = null;
let stopRealtime = null;
let lastFetchAt = 0;
let lastSnapshot = '';

const snapshotMesas = (items = []) =>
  JSON.stringify(items.map((mesa) => [mesa.id, mesa.estado, mesa.updated_at, mesa.pedidos_activos]));

const loadMesas = async ({ force = false } = {}) => {
  const now = Date.now();
  if (loading.value) return;
  if (!force && now - lastFetchAt < 4000) return;

  try {
    loading.value = true;
    error.value = '';
    const response = await listMesas();
    const nextSnapshot = snapshotMesas(response);

    if (nextSnapshot !== lastSnapshot) {
      mesas.value = response;
      lastSnapshot = nextSnapshot;
    }

    lastFetchAt = Date.now();
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

onMounted(() => loadMesas({ force: true }));

onMounted(() => {
  stopRealtime = bindWaiterRealtime(1, {
    onNotification: () => {
      if (document.hidden) return;
      loadMesas({ force: true });
    },
  });

  refreshTimer = window.setInterval(() => {
    loadMesas();
  }, 25000);
});

onBeforeUnmount(() => {
  if (stopRealtime) stopRealtime();

  if (!refreshTimer) return;
  window.clearInterval(refreshTimer);
  refreshTimer = null;
});
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
  color: #94a3b8;
}

.refresh {
  border: 1px solid rgba(248, 113, 113, 0.35);
  border-radius: 10px;
  background: linear-gradient(145deg, #9c2030, #7a1522);
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
  gap: 18px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  justify-items: center;
  align-items: center;
}

.error {
  margin: 0;
  padding: 10px 12px;
  border: 1px solid rgba(248, 113, 113, 0.4);
  border-radius: 10px;
  background: rgba(127, 29, 29, 0.35);
  color: #fecaca;
}

.empty {
  margin: 0;
  color: #94a3b8;
}

@media (min-width: 768px) {
  .grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (min-width: 1024px) {
  .grid {
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  }
}
</style>
