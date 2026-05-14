<template>
  <div class="notify-wrap">
    <button class="notify-btn" @click="open = !open">
      🔔 Notificaciones
      <span v-if="unreadCount" class="notify-count">{{ unreadCount }}</span>
    </button>

    <div v-if="open" class="notify-panel">
      <header>
        <strong>Bandeja mesero</strong>
        <button v-if="unreadCount" @click="markAll">Marcar todo leído</button>
      </header>

      <ul v-if="notifications.length">
        <li v-for="item in notifications" :key="item.id" :class="{ unread: !item.read_at }">
          <div>
            <p>{{ item.title }}</p>
            <small>#{{ item.payload?.pedido_id }} · Mesa {{ item.payload?.mesa_numero || item.payload?.mesa_id || '-' }} · {{ item.payload?.cliente_nombre || 'Cliente invitado' }}</small>
          </div>
          <button v-if="!item.read_at" @click="markOne(item.id)">Leída</button>
        </li>
      </ul>
      <p v-else class="empty">Sin notificaciones.</p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { bindWaiterRealtime } from '../../echo';
import { getNotifications, markNotificationRead, markNotificationsReadAll } from '../api';

const props = defineProps({
  restaurantId: { type: Number, default: 1 },
});

const open          = ref(false);
const notifications = ref([]);
let stopRealtime    = null;
let pollId = null;

const unreadCount = computed(() =>
  notifications.value.filter((item) => !item.read_at).length
);

const load = async () => {
  try {
    const data = await getNotifications();
    const incoming = data.data || [];
    
    // Solo agregar notificaciones nuevas que no estén ya en la lista
    const existingIds = new Set(notifications.value.map(n => n.id));
    const nuevas = incoming.filter(n => !existingIds.has(n.id));
    
    if (nuevas.length) {
      notifications.value = [...nuevas, ...notifications.value].slice(0, 50);
    }
    
    // Sincronizar read_at de las existentes
    notifications.value = notifications.value.map(n => {
      const updated = incoming.find(i => i.id === n.id);
      return updated ? updated : n;
    });
    
  } catch (e) {
    console.warn('[WaiterNotifications] Error:', e.message);
  }
};

const markOne = async (id) => {
  await markNotificationRead(id);
  notifications.value = notifications.value.map((item) =>
    item.id === id ? { ...item, read_at: new Date().toISOString() } : item
  );
};

const markAll = async () => {
  await markNotificationsReadAll();
  const stamp = new Date().toISOString();
  notifications.value = notifications.value.map((item) => ({
    ...item,
    read_at: item.read_at || stamp,
  }));
};

onMounted(async () => {
  await load();

  // Polling cada 5 segundos como fallback
  pollId = setInterval(() => {
    load();
  }, 3000);

  stopRealtime = bindWaiterRealtime(props.restaurantId, {
    onNotification: (event) => {
      const incoming = event?.notification;
      if (!incoming) return;
      notifications.value = [incoming, ...notifications.value].slice(0, 50);
    },
  });
});

onUnmounted(() => {
  if (stopRealtime) stopRealtime();
  if (pollId) clearInterval(pollId);
});
</script>

<style scoped>
.notify-wrap { position: fixed; top: 16px; right: 16px; z-index: 50; }
.notify-btn { background: #1d4ed8; color: #fff; border: 0; border-radius: 12px; padding: 10px 12px; font-weight: 700; cursor: pointer; }
.notify-count { margin-left: 8px; background: #ef4444; border-radius: 999px; padding: 2px 8px; }
.notify-panel { margin-top: 8px; width: 360px; max-height: 460px; overflow: auto; background: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 10px; }
.notify-panel header { display: flex; justify-content: space-between; margin-bottom: 8px; }
.notify-panel ul { list-style: none; padding: 0; margin: 0; display: grid; gap: 8px; }
.notify-panel li { display: flex; justify-content: space-between; align-items: center; gap: 8px; border: 1px solid #243457; border-radius: 10px; padding: 8px; }
.notify-panel li.unread { border-color: #f59e0b; }
.notify-panel p { margin: 0; }
.notify-panel small { color: #9fb2d9; }
.empty { color: #94a3b8; margin: 0; }
</style>  