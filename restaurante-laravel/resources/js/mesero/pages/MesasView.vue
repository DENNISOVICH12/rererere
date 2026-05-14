<template>
  <section class="layout">

    <!-- Encabezado de sección -->
    <header class="section-header">
      <div class="section-copy">
        <h1 class="section-title">Mapa de Mesas</h1>
        <p class="section-sub">Gestiona clientes y pedidos por mesa.</p>
      </div>

      <button
        class="refresh-btn"
        :class="{ spinning: loading }"
        :disabled="loading"
        @click="loadMesas({ force: true })"
      >
        <span class="refresh-icon">⟳</span>
        Actualizar
      </button>
    </header>

    <p v-if="error" class="error-msg">{{ error }}</p>

    <div class="grid">
      <MesaCard
        v-for="mesa in mesas"
        :key="mesa.id"
        :mesa="mesa"
        @select="openMesa"
      />
    </div>

    <p v-if="!loading && !mesas.length" class="empty-msg">
      No hay mesas disponibles.
    </p>

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
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import MesaCard from '../components/MesaCard.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import { listMesas } from '../api.js'
import { bindWaiterRealtime } from '../../echo.js'

const router = useRouter()

const mesas          = ref([])
const loading        = ref(false)
const error          = ref('')
const showLogoutModal = ref(false)
const logoutLoading  = ref(false)
let refreshTimer     = null
let stopRealtime     = null

const loadMesas = async ({ force = false } = {}) => {
  if (loading.value) return
  try {
    loading.value = true
    error.value   = ''
    mesas.value   = await listMesas()
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudieron cargar las mesas.'
  } finally {
    loading.value = false
  }
}

const openLogoutConfirm  = () => { if (!logoutLoading.value) showLogoutModal.value = true }
const closeLogoutConfirm = () => { if (!logoutLoading.value) showLogoutModal.value = false }

const confirmLogout = async () => {
  if (logoutLoading.value) return
  try {
    logoutLoading.value = true
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
    await fetch('/logout', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
      credentials: 'same-origin',
    })
    window.location.href = '/staff'
  } finally {
    logoutLoading.value  = false
    showLogoutModal.value = false
  }
}

const openMesa = (mesa) => {
  if (!mesa?.id) return
  router.push({ name: 'mesa-detalle', params: { id: mesa.numero } })
}

onMounted(() => {
  loadMesas({ force: true })

  stopRealtime = bindWaiterRealtime(1, {
    onNotification: () => {
      if (!document.hidden) loadMesas({ force: true })
    },
  })

  refreshTimer = window.setInterval(() => loadMesas(), 30000)
})

onBeforeUnmount(() => {
  if (stopRealtime) stopRealtime()
  if (refreshTimer) { window.clearInterval(refreshTimer); refreshTimer = null }
})
</script>

<style scoped>
.layout {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* ── ENCABEZADO DE SECCIÓN ── */
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.section-copy { min-width: 0; }

.section-title {
  margin: 0;
  font-size: clamp(1.2rem, 3.5vw, 1.6rem);
  font-weight: 700;
  color: #f8fbff;
  line-height: 1.15;
}

.section-sub {
  margin: 4px 0 0;
  font-size: 13px;
  color: rgba(248, 251, 255, 0.45);
}

/* Botón actualizar */
.refresh-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 16px;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.06);
  color: rgba(248, 251, 255, 0.8);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.18s, border-color 0.18s;
  flex-shrink: 0;
  white-space: nowrap;
}

.refresh-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.11);
  border-color: rgba(255, 255, 255, 0.2);
}

.refresh-btn:disabled { opacity: 0.55; cursor: not-allowed; }

.refresh-icon {
  font-size: 15px;
  display: inline-block;
  transition: transform 0.4s ease;
}

.refresh-btn.spinning .refresh-icon {
  animation: spin 0.7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ── GRID ── */
.grid {
  display: grid;
  gap: 16px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  justify-items: center;
  align-items: center;
}

/* ── ESTADOS ── */
.error-msg {
  margin: 0;
  padding: 10px 14px;
  border: 1px solid rgba(248, 113, 113, 0.35);
  border-radius: 10px;
  background: rgba(127, 29, 29, 0.3);
  color: #fecaca;
  font-size: 13px;
}

.empty-msg {
  margin: 0;
  color: rgba(248, 251, 255, 0.4);
  font-size: 13px;
}

/* ── RESPONSIVE ── */
@media (min-width: 640px) {
  .grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
}

@media (min-width: 768px) {
  .grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}

@media (min-width: 1024px) {
  .grid { grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); }
}

@media (max-width: 480px) {
  .section-header { flex-direction: column; align-items: flex-start; gap: 10px; }
  .refresh-btn { width: 100%; justify-content: center; }
}
</style>