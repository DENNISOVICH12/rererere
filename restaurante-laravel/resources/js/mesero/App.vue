<template>
  <div class="app-shell">

    <!-- ── NAVBAR GLOBAL ── -->
    <nav class="navbar">
      <div class="navbar-brand">
        <div class="navbar-logo">🍽</div>
        <div class="navbar-titles">
          <span class="navbar-name">ODER EASY</span>
          <span class="navbar-sub">Panel mesero</span>
        </div>
      </div>

      <div class="navbar-actions">

        <!-- Notificaciones -->
        <div class="notif-wrap" ref="notifWrapRef">
          <button class="nav-btn notif-btn" @click.stop="toggleNotif">
            <span class="nav-btn-icon">🔔</span>
            <span class="nav-btn-label">Notificaciones</span>
            <span v-if="unreadCount" class="notif-badge">{{ unreadCount }}</span>
          </button>

          <transition name="panel-fade">
            <div v-if="notifOpen" class="notif-panel">
              <div class="notif-panel-header">
                <span>Notificaciones</span>
                <button v-if="unreadCount" class="mark-all-btn" @click="markAll">
                  Marcar todo leído
                </button>
              </div>
              <ul v-if="notifications.length" class="notif-list">
                <li
                  v-for="item in notifications"
                  :key="item.id"
                  class="notif-item"
                  :class="{ unread: !item.read_at }"
                >
                  <div class="notif-item-body">
                    <p class="notif-title">{{ item.title }}</p>
                    <p class="notif-meta">
                      #{{ item.payload?.pedido_id }} · Mesa {{ item.payload?.mesa_numero || item.payload?.mesa_id || '-' }}
                      · {{ item.payload?.cliente_nombre || 'Cliente invitado' }}
                    </p>
                  </div>
                  <button v-if="!item.read_at" class="notif-read-btn" @click="markOne(item.id)">
                    Leída
                  </button>
                </li>
              </ul>
              <p v-else class="notif-empty">Sin notificaciones.</p>
            </div>
          </transition>
        </div>

        <!-- Salir -->
        <button class="nav-btn logout-btn" @click="openLogoutConfirm">
          <span class="nav-btn-icon">🔒</span>
          <span class="nav-btn-label">Salir</span>
        </button>
      </div>
    </nav>

    <!-- Contenido de cada ruta -->
    <main class="app-content">
      <router-view />
    </main>

    <!-- Modal cerrar sesión — mismo ConfirmDialog del resto del proyecto -->
    <ConfirmDialog
      :open="showLogoutModal"
      title="Cerrar sesión"
      message="¿Seguro que deseas cerrar sesión?"
      confirm-text="Confirmar"
      cancel-text="Cancelar"
      :loading="logoutLoading"
      @cancel="showLogoutModal = false"
      @confirm="confirmLogout"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { bindWaiterRealtime } from '../echo.js'
import { getNotifications, markNotificationRead, markNotificationsReadAll } from './api.js'
import ConfirmDialog from './components/ConfirmDialog.vue'

// ── Notificaciones ──────────────────────────────────────────
const notifOpen    = ref(false)
const notifWrapRef = ref(null)
const notifications = ref([])
let stopRealtime   = null

const unreadCount = computed(() =>
  notifications.value.filter(n => !n.read_at).length
)

// @click.stop en el botón detiene la propagación hacia el documento,
// así el handleDocClick no recibe ese mismo evento y no cierra el panel
// justo después de abrirlo.
const toggleNotif = () => {
  notifOpen.value = !notifOpen.value
}

// Cierra el panel cuando el click cae fuera del wrapper
const handleDocClick = (e) => {
  if (notifWrapRef.value && !notifWrapRef.value.contains(e.target)) {
    notifOpen.value = false
  }
}

const loadNotifications = async () => {
  try {
    const data = await getNotifications()
    notifications.value = data.data || []
  } catch {}
}

const markOne = async (id) => {
  await markNotificationRead(id)
  notifications.value = notifications.value.map(n =>
    n.id === id ? { ...n, read_at: new Date().toISOString() } : n
  )
}

const markAll = async () => {
  await markNotificationsReadAll()
  const stamp = new Date().toISOString()
  notifications.value = notifications.value.map(n => ({
    ...n, read_at: n.read_at || stamp,
  }))
}

// ── Logout ───────────────────────────────────────────────────
const showLogoutModal = ref(false)
const logoutLoading   = ref(false)

const openLogoutConfirm = () => {
  showLogoutModal.value = true
}

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
    localStorage.clear()
    sessionStorage.clear()
    window.location.href = '/login'
  } catch (e) {
    console.error('Error cerrando sesión', e)
  } finally {
    logoutLoading.value   = false
    showLogoutModal.value = false
  }
}

// ── Lifecycle ────────────────────────────────────────────────
onMounted(async () => {
  await loadNotifications()
  document.addEventListener('click', handleDocClick)

  stopRealtime = bindWaiterRealtime(1, {
    onNotification: (event) => {
      const incoming = event?.notification
      if (!incoming) return
      notifications.value = [incoming, ...notifications.value].slice(0, 50)
    },
  })
})

onUnmounted(() => {
  document.removeEventListener('click', handleDocClick)
  if (stopRealtime) stopRealtime()
})
</script>

<style scoped>
.app-shell {
  min-height: 100vh;
  background: radial-gradient(
    circle at top,
    rgba(156, 32, 48, 0.22) 0%,
    #0f172a 52%,
    #020617 100%
  );
  color: #f8fbff;
  display: flex;
  flex-direction: column;
}

/* ── NAVBAR ── */
.navbar {
  position: sticky;
  top: 0;
  z-index: 200;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  background: rgba(9, 14, 26, 0.88);
  border-bottom: 1px solid rgba(255, 255, 255, 0.07);
  gap: 12px;
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.navbar-logo {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  background: #9c2030;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}

.navbar-titles {
  display: flex;
  flex-direction: column;
  gap: 1px;
  line-height: 1;
}

.navbar-name {
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.07em;
  color: #f8fbff;
}

.navbar-sub {
  font-size: 10px;
  color: rgba(248, 251, 255, 0.4);
  letter-spacing: 0.03em;
}

.navbar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* ── BOTÓN BASE ── */
.nav-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 13px;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.06);
  color: rgba(248, 251, 255, 0.85);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.18s, border-color 0.18s;
  white-space: nowrap;
}

.nav-btn:hover {
  background: rgba(255, 255, 255, 0.11);
  border-color: rgba(255, 255, 255, 0.18);
}

.nav-btn-icon { font-size: 14px; line-height: 1; }

/* ── NOTIFICACIONES ── */
.notif-wrap { position: relative; }

.notif-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  min-width: 18px;
  height: 18px;
  border-radius: 999px;
  background: #ef4444;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  border: 2px solid #090e1a;
}

.notif-panel {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 340px;
  max-height: 420px;
  overflow-y: auto;
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 14px;
  z-index: 300;
}

.notif-panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 14px 10px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.07);
  font-size: 13px;
  font-weight: 600;
  color: #f8fbff;
  position: sticky;
  top: 0;
  background: #0f172a;
}

.mark-all-btn {
  font-size: 11px;
  font-weight: 500;
  color: rgba(248, 251, 255, 0.45);
  background: none;
  border: none;
  cursor: pointer;
}
.mark-all-btn:hover { color: #f8fbff; }

.notif-list { list-style: none; padding: 0; margin: 0; }

.notif-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 14px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  transition: background 0.15s;
}
.notif-item:last-child { border-bottom: none; }
.notif-item.unread { background: rgba(156, 32, 48, 0.12); }
.notif-item:hover { background: rgba(255, 255, 255, 0.04); }

.notif-item-body { flex: 1; min-width: 0; }

.notif-title {
  margin: 0 0 3px;
  font-size: 13px;
  font-weight: 500;
  color: #f8fbff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.notif-meta {
  margin: 0;
  font-size: 11px;
  color: rgba(248, 251, 255, 0.4);
}

.notif-read-btn {
  flex-shrink: 0;
  font-size: 11px;
  font-weight: 500;
  padding: 4px 9px;
  border-radius: 7px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.06);
  color: rgba(248, 251, 255, 0.7);
  cursor: pointer;
}
.notif-read-btn:hover { background: rgba(255, 255, 255, 0.12); color: #f8fbff; }

.notif-empty {
  padding: 18px 14px;
  margin: 0;
  font-size: 13px;
  color: rgba(248, 251, 255, 0.4);
  text-align: center;
}

/* ── SALIR ── */
.logout-btn {
  border-color: rgba(156, 32, 48, 0.35);
  color: rgba(248, 251, 255, 0.7);
}
.logout-btn:hover {
  background: rgba(156, 32, 48, 0.2);
  border-color: rgba(156, 32, 48, 0.6);
  color: #f8fbff;
}

/* ── TRANSICIÓN PANEL ── */
.panel-fade-enter-active,
.panel-fade-leave-active { transition: opacity 0.16s ease, transform 0.16s ease; }
.panel-fade-enter-from,
.panel-fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* ── CONTENIDO ── */
.app-content {
  flex: 1;
  padding: 24px 20px;
}

/* ── RESPONSIVE ── */
@media (max-width: 500px) {
  .navbar { padding: 0 14px; }
  .nav-btn-label { display: none; }
  .nav-btn { padding: 8px 10px; gap: 0; }
  .notif-panel { width: calc(100vw - 28px); right: -14px; }
  .app-content { padding: 16px 14px; }
}

@media (min-width: 768px) {
  .app-content { padding: 28px; }
}
</style>