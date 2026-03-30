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
            <small>#{{ item.payload?.pedido_id }} · Mesa {{ item.payload?.mesa || '-' }} · {{ item.payload?.cliente_nombre || 'Cliente invitado' }}</small>
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
import { createWaiterEcho } from '../echo';
import { getNotifications, markNotificationRead, markNotificationsReadAll } from '../api';

const props = defineProps({
  restaurantId: { type: Number, default: 1 },
});

const open = ref(false);
const notifications = ref([]);
let channel = null;

const unreadCount = computed(() => notifications.value.filter((item) => !item.read_at).length);

const load = async () => {
  const data = await getNotifications();
  notifications.value = data.data || [];
};

const markOne = async (id) => {
  await markNotificationRead(id);
  notifications.value = notifications.value.map((item) => (item.id === id ? { ...item, read_at: new Date().toISOString() } : item));
};

const markAll = async () => {
  await markNotificationsReadAll();
  const stamp = new Date().toISOString();
  notifications.value = notifications.value.map((item) => ({ ...item, read_at: item.read_at || stamp }));
};

onMounted(async () => {
  await load();

  channel = createWaiterEcho(props.restaurantId);
  channel?.listen('.waiter.notification.created', (event) => {
    const incoming = event?.notification;
    if (!incoming) return;
    notifications.value = [incoming, ...notifications.value].slice(0, 50);
  });
});

onUnmounted(() => {
  if (channel?.stopListening) channel.stopListening('.waiter.notification.created');
});
</script>

<style scoped>
.notify-wrap { position: fixed; top: 16px; right: 16px; z-index: 50; }
.notify-btn { background: #1d4ed8; color: #fff; border: 0; border-radius: 12px; padding: 10px 12px; font-weight: 700; }
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
