<template>
  <main class="app">
    <ActiveOrdersPage
      v-if="view === 'list'"
      :orders="orders"
      :active-filter="filter"
      :elapsed-map="elapsedMap"
      :busy-map="busyMap"
      @edit="openEditor"
      @delete="requestDelete"
      @request-change="requestChange"
      @change-filter="changeFilter"
      @deliver="deliverOrder"
    />

    <EditOrderPage
      v-else-if="view === 'edit' && selectedOrder"
      :order="selectedOrder"
      :saving="savingEdit"
      :sending="sendingToKitchen"
      @back="closeEditor"
      @save="submitEdit"
      @send-to-kitchen="confirmSendToKitchen"
    />

    <ConfirmDialog
      :open="confirm.open"
      :title="confirm.title"
      :message="confirm.message"
      :loading="confirm.loading"
      :confirm-text="confirm.confirmText"
      @cancel="confirm.open = false"
      @confirm="confirm.action"
    />

    <Toast :show="toast.show" :message="toast.message" :type="toast.type" />
  </main>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import {
  deleteOrder,
  getOrder,
  listActiveOrders,
  requestOrderChange,
  sendOrderToKitchen,
  updateOrder,
  updateOrderStatus,
} from './api';
import ActiveOrdersPage from './pages/ActiveOrdersPage.vue';
import EditOrderPage from './pages/EditOrderPage.vue';
import ConfirmDialog from './components/ConfirmDialog.vue';
import Toast from './components/Toast.vue';

const previousOrders = ref([]);
const notifiedReadyIds = new Set();
const orders = ref([]);
const filter = ref('');
const view = ref('list');
const selectedOrder = ref(null);
const busyMap = reactive({});
const savingEdit = ref(false);
const sendingToKitchen = ref(false);
const tick = ref(Date.now());

const toast = reactive({ show: false, message: '', type: 'info' });
const confirm = reactive({ open: false, title: '', message: '', confirmText: 'Confirmar', loading: false, action: () => {} });

const elapsedMap = computed(() => Object.fromEntries(orders.value.map((order) => [order.id, formatElapsed(order.created_at)])));

const showToast = (message, type = 'info') => {
  toast.show = true;
  toast.message = message;
  toast.type = type;
  setTimeout(() => (toast.show = false), 2400);
};
const deliverOrder = async (order) => {
  busyMap[order.id] = true;

  try {
    await updateOrderStatus(order.id, { estado: 'entregado' });
    await loadOrders();
    showToast(`✅ Pedido #${order.id} entregado`, 'success');
  } catch (error) {
    showToast(error?.response?.data?.message || '❌ Error al entregar pedido', 'error');
  } finally {
    busyMap[order.id] = false;
  }
};

const formatElapsed = (createdAt) => {
  const totalSeconds = Math.max(0, Math.floor((tick.value - new Date(createdAt).getTime()) / 1000));
  const h = Math.floor(totalSeconds / 3600);
  const m = Math.floor((totalSeconds % 3600) / 60);
  const s = totalSeconds % 60;
  return h ? `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}` : `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
};

const loadOrders = async () => {
  const newOrders = await listActiveOrders(filter.value);

  // 🔍 Detectar cambios a "listo"
  newOrders.forEach((newOrder) => {
    const prev = previousOrders.value.find(o => o.id === newOrder.id);

    if (
      newOrder.estado === 'listo' &&
      (!prev || prev.estado !== 'listo') &&
      !notifiedReadyIds.has(newOrder.id)
    ) {
      showToast(`🍽 Pedido #${newOrder.id} listo para entregar`, 'success');

      playSound();

      notifiedReadyIds.add(newOrder.id);
    }
  });

  // Guardar estado anterior
  previousOrders.value = newOrders.map(o => ({ ...o }));

  orders.value = newOrders;
};

const playSound = () => {
  const ctx = new (window.AudioContext || window.webkitAudioContext)();
  const osc = ctx.createOscillator();
  const gain = ctx.createGain();

  osc.frequency.value = 800;
  gain.gain.value = 0.05;

  osc.connect(gain);
  gain.connect(ctx.destination);

  osc.start();
  setTimeout(() => {
    osc.stop();
    ctx.close();
  }, 150);
};  

const changeFilter = async (value) => {
  filter.value = value;
  await loadOrders();
};

const openEditor = async (order) => {
  selectedOrder.value = await getOrder(order.id);
  view.value = 'edit';
};

const closeEditor = () => {
  view.value = 'list';
  selectedOrder.value = null;
};

const submitEdit = async ({ id, payload }) => {
  try {
    savingEdit.value = true;
    const updated = await updateOrder(id, payload);
    const patchIndex = orders.value.findIndex((o) => o.id === id);
    if (patchIndex >= 0) orders.value[patchIndex] = updated;
    selectedOrder.value = updated;
    showToast('Pedido actualizado. Cuando esté listo, confírmalo para cocina.', 'success');
  } catch (error) {
    showToast(error?.response?.data?.message || 'No se pudo actualizar', 'error');
  } finally {
    savingEdit.value = false;
  }
};

const confirmSendToKitchen = (order) => {
  confirm.open = true;
  confirm.title = `Enviar pedido #${order.id} a cocina`;
  confirm.message = 'Una vez enviado a cocina, ya no podrá modificarse con el flujo normal.';
  confirm.confirmText = 'Confirmar y enviar';
  confirm.action = () => performSendToKitchen(order.id);
};

const performSendToKitchen = async (orderId) => {
  confirm.loading = true;
  sendingToKitchen.value = true;

  try {
    const updated = await sendOrderToKitchen(orderId);
    const patchIndex = orders.value.findIndex((o) => o.id === orderId);
    if (patchIndex >= 0) orders.value[patchIndex] = updated;
    selectedOrder.value = updated;
    showToast('Pedido enviado a cocina correctamente.', 'success');
    confirm.open = false;
    closeEditor();
    await loadOrders();
  } catch (error) {
    showToast(error?.response?.data?.message || 'No se pudo enviar a cocina', 'error');
  } finally {
    confirm.loading = false;
    sendingToKitchen.value = false;
  }
};

const requestChange = async (order) => {
  busyMap[order.id] = true;

  try {
    const updated = await requestOrderChange(order.id, {});
    orders.value = orders.value.map((item) => (item.id === order.id ? updated : item));
    showToast('Solicitud de cambio registrada. Pedido retenido hasta atención del mesero.', 'success');
  } catch (error) {
    showToast(error?.response?.data?.message || 'No se pudo registrar la solicitud de cambio.', 'error');
  } finally {
    busyMap[order.id] = false;
  }
};

const requestDelete = (order) => {
  confirm.open = true;
  confirm.title = `Cancelar pedido #${order.id}`;
  confirm.message = `Estado actual: ${order.estado}. Esta acción eliminará el pedido.`;
  confirm.confirmText = 'Sí, cancelar';
  confirm.action = () => performDelete(order, false);
};

const performDelete = async (order, force) => {
  confirm.loading = true;
  busyMap[order.id] = true;
  const previous = [...orders.value];
  orders.value = orders.value.filter((o) => o.id !== order.id);

  try {
    await deleteOrder(order.id, force ? { force_confirmation: true } : {});
    showToast('Pedido cancelado', 'success');
    confirm.open = false;
  } catch (error) {
    orders.value = previous;
    showToast(error?.response?.data?.message || 'No se pudo cancelar', 'error');
  } finally {
    confirm.loading = false;
    busyMap[order.id] = false;
  }
};

let intervalId;
let pollId;
onMounted(async () => {
  await loadOrders();
  intervalId = setInterval(() => (tick.value = Date.now()), 1000);
  pollId = setInterval(loadOrders, 7000);
});

onUnmounted(() => {
  clearInterval(intervalId);
  clearInterval(pollId);
});
</script>

<style scoped>
.app { min-height: 100vh; background: #0a1220; color: #eaf1ff; max-width: 760px; margin: 0 auto; padding: 14px; font-family: Inter, system-ui, sans-serif; }
</style>
