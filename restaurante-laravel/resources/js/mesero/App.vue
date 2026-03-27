<template>
  <main class="app">
    <MesasView
      v-if="appMode === 'mesas'"
      :mesas="mesas"
      @open-mesa="openMesa"
      @refresh="loadMesas"
    />

    <MesaDetalleView
      v-else-if="appMode === 'mesa-detalle'"
      :mesa="mesaDetalle"
      :creating="creatingCliente"
      :facturando-id="facturandoClienteId"
      @back="goMesas"
      @add-cliente="addClienteToMesa"
      @facturar="facturarClienteMesa"
    />

    <ActiveOrdersPage
      v-else-if="view === 'list'"
      :orders="orders"
      :active-filter="filter"
      :elapsed-map="elapsedMap"
      :busy-map="busyMap"
      @edit="openEditor"
      @delete="requestDelete"
      @request-change="requestChange"
      @change-filter="changeFilter"
      @deliver-group="deliverGroup"
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
  createMesaCliente,
  deleteOrder,
  deliverOrderGroup,
  facturarCliente,
  getMesa,
  getOrder,
  listActiveOrders,
  listMesas,
  requestOrderChange,
  sendOrderToKitchen,
  updateOrder,
} from './api';
import ActiveOrdersPage from './pages/ActiveOrdersPage.vue';
import EditOrderPage from './pages/EditOrderPage.vue';
import MesasView from './pages/MesasView.vue';
import MesaDetalleView from './pages/MesaDetalleView.vue';
import ConfirmDialog from './components/ConfirmDialog.vue';
import Toast from './components/Toast.vue';

const mesas = ref([]);
const mesaDetalle = ref(null);
const creatingCliente = ref(false);
const facturandoClienteId = ref(null);

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

const path = ref(window.location.pathname);
const appMode = computed(() => {
  if (path.value.startsWith('/mesero/mesa/')) return 'mesa-detalle';
  if (path.value === '/mesero') return 'mesas';
  return 'orders';
});

const elapsedMap = computed(() => Object.fromEntries(orders.value.map((order) => [order.id, formatElapsed(order.created_at)])));

const showToast = (message, type = 'info') => {
  toast.show = true;
  toast.message = message;
  toast.type = type;
  setTimeout(() => (toast.show = false), 2400);
};

const loadMesas = async () => {
  mesas.value = await listMesas();
};

const loadMesaDetalle = async () => {
  const mesaId = path.value.split('/').pop();
  if (!mesaId) return;
  mesaDetalle.value = await getMesa(mesaId);
};

const openMesa = (mesa) => {
  const target = `/mesero/mesa/${mesa.id}`;
  window.history.pushState({}, '', target);
  path.value = target;
  loadMesaDetalle();
};

const goMesas = async () => {
  window.history.pushState({}, '', '/mesero');
  path.value = '/mesero';
  mesaDetalle.value = null;
  await loadMesas();
};

const addClienteToMesa = async (payload) => {
  if (!mesaDetalle.value?.id) return;

  try {
    creatingCliente.value = true;
    await createMesaCliente(mesaDetalle.value.id, payload);
    showToast('Cliente agregado a la mesa.', 'success');
    await loadMesaDetalle();
    await loadMesas();
  } catch (error) {
    showToast(error?.response?.data?.message || 'No se pudo agregar el cliente.', 'error');
  } finally {
    creatingCliente.value = false;
  }
};

const facturarClienteMesa = async (cliente) => {
  try {
    facturandoClienteId.value = cliente.id;
    const response = await facturarCliente(cliente.id);
    showToast(`Cuenta facturada por $${Number(response?.data?.total_facturado || 0).toFixed(2)}`, 'success');
    await loadMesaDetalle();
    await loadMesas();
  } catch (error) {
    showToast(error?.response?.data?.message || 'No se pudo facturar el cliente.', 'error');
  } finally {
    facturandoClienteId.value = null;
  }
};

const serviceLabel = (group) => (group === 'bebida' ? 'bebidas' : 'platos');

const deliverGroup = async ({ order, group }) => {
  if (!order?.id || !group) return;

  busyMap[order.id] = true;

  try {
    await deliverOrderGroup(order.id, group);
    await loadOrders();
    showToast(`✅ ${serviceLabel(group)} del pedido #${order.id} entregados`, 'success');
  } catch (error) {
    showToast(error?.response?.data?.message || `❌ Error al entregar ${serviceLabel(group)}`, 'error');
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

  newOrders.forEach((newOrder) => {
    const prev = previousOrders.value.find((o) => o.id === newOrder.id);

    if (newOrder.estado === 'listo' && (!prev || prev.estado !== 'listo') && !notifiedReadyIds.has(newOrder.id)) {
      showToast(`🍽 Pedido #${newOrder.id} listo para entregar`, 'success');
      notifiedReadyIds.add(newOrder.id);
    }
  });

  previousOrders.value = newOrders.map((o) => ({ ...o }));
  orders.value = newOrders;
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
  if (appMode.value === 'mesas') {
    await loadMesas();
  } else if (appMode.value === 'mesa-detalle') {
    await loadMesaDetalle();
  } else {
    await loadOrders();
    intervalId = setInterval(() => (tick.value = Date.now()), 1000);
    pollId = setInterval(loadOrders, 7000);
  }

  window.addEventListener('popstate', async () => {
    path.value = window.location.pathname;
    if (appMode.value === 'mesas') await loadMesas();
    if (appMode.value === 'mesa-detalle') await loadMesaDetalle();
  });
});

onUnmounted(() => {
  clearInterval(intervalId);
  clearInterval(pollId);
});
</script>

<style scoped>
.app { min-height: 100vh; background: #0a1220; color: #eaf1ff; max-width: 1200px; margin: 0 auto; padding: 14px; font-family: Inter, system-ui, sans-serif; }
</style>
