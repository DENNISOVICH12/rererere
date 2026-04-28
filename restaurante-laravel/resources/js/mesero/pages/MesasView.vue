<template>
  <section class="layout">
    <header class="topbar">
  <div>
    <h1>Mapa de Mesas</h1>
    <p>Gestiona clientes y pedidos por mesa, con facturación individual.</p>
  </div>

  <div class="actions">

    <!-- Botones -->
    <div class="buttons">
      <button class="refresh" :disabled="loading" @click="loadMesas">
        ⟳ Actualizar
      </button>

      <button class="logout" @click="openLogoutConfirm">
        🔒 Salir
      </button>
    </div>

  </div>
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

    <ConfirmDialog
      :open="showLogoutModal"
      title="Cerrar sesión"
      message="¿Seguro que deseas cerrar sesión?"
      confirm-text="Confirmar"
      cancel-text="Cancelar"
      :loading="logoutLoading"
      @cancel="closeLogoutConfirm"
      @confirm="confirmLogout"
    />
  </section>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import MesaCard from '../components/MesaCard.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import { listMesas } from '../api.js';
import { bindWaiterRealtime } from '../echo.js';

const router = useRouter();

const mesas = ref([]);
const loading = ref(false);
const error = ref('');
const showLogoutModal = ref(false);
const logoutLoading = ref(false);
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

const openLogoutConfirm = () => {
  if (logoutLoading.value) return;
  showLogoutModal.value = true;
};

const closeLogoutConfirm = () => {
  if (logoutLoading.value) return;
  showLogoutModal.value = false;
};

const confirmLogout = async () => {
  if (logoutLoading.value) return;

  try {
    logoutLoading.value = true;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    await fetch('/logout', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json',
      },
      credentials: 'same-origin',
    });

    window.location.href = '/staff';
  } finally {
    logoutLoading.value = false;
    showLogoutModal.value = false;
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
.layout {
  display: grid;
  gap: 16px;
}

.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap; /* 👈 clave para responsive */
}
.actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.notify {
  position: relative;
  background: #2563eb;
  padding: 8px 14px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
  color: #fff;
}

.badge {
  position: absolute;
  top: -6px;
  right: -6px;
  background: #ef4444;
  color: #fff;
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 999px;
}

.buttons {
  display: flex;
  gap: 8px;
}
.topbar-copy {
  min-width: 0;
}

.topbar p {
  margin: 4px 0 0;
  color: #94a3b8;
}

.topbar-actions {
  display: inline-flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
}

.action-btn {
  border: 1px solid transparent;
  border-radius: 12px;
  color: #fff;
  padding: 10px 14px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
}

.action-btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.action-btn:focus-visible {
  outline: 2px solid rgba(251, 191, 36, 0.8);
  outline-offset: 2px;
}

.action-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.refresh {
  border: none;
  border-radius: 10px;
  background: linear-gradient(145deg, #9c2030, #7a1522);
  color: #fff;
  padding: 8px 12px;
  font-weight: 600;
  cursor: pointer;
}


.logout {
  border: none;
  border-radius: 10px;
  background: linear-gradient(145deg, #dc2626, #991b1b);
  color: #fff;
  padding: 8px 12px;
  font-weight: 600;
  cursor: pointer;
}

.btn-icon {
  font-size: 1rem;
  line-height: 1;
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

@media (max-width: 767px) {
  .topbar {
    flex-direction: column;
    align-items: stretch;
  }

  .topbar-actions {
    width: 100%;
    justify-content: flex-start;
  }
}

@media (max-width: 480px) {
  .topbar-actions {
    justify-content: space-between;
  }

  .action-btn {
    padding: 10px;
  }

  .logout .btn-text {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }
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
@media (max-width: 600px) {
  .actions {
    width: 100%;
    justify-content: space-between;
  }

  .buttons {
    flex: 1;
    justify-content: flex-end;
  }
}
</style>
