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
      @change-filter="changeFilter"
    />

    <EditOrderPage
      v-else-if="view === 'edit' && selectedOrder"
      :order="selectedOrder"
      :saving="savingEdit"
      @back="closeEditor"
      @save="submitEdit"
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
import { deleteOrder, getOrder, listActiveOrders, updateOrder } from './api';
import ActiveOrdersPage from './pages/ActiveOrdersPage.vue';
import EditOrderPage from './pages/EditOrderPage.vue';
import ConfirmDialog from './components/ConfirmDialog.vue';
import Toast from './components/Toast.vue';

const orders = ref([]);
const filter = ref('');
const view = ref('list');
const selectedOrder = ref(null);
const busyMap = reactive({});
const savingEdit = ref(false);
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

const formatElapsed = (createdAt) => {
  const totalSeconds = Math.max(0, Math.floor((tick.value - new Date(createdAt).getTime()) / 1000));
  const h = Math.floor(totalSeconds / 3600);
  const m = Math.floor((totalSeconds % 3600) / 60);
  const s = totalSeconds % 60;
  return h ? `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}` : `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
};

const loadOrders = async () => {
  orders.value = await listActiveOrders(filter.value);
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
  const prev = [...orders.value];
  const index = orders.value.findIndex((o) => o.id === id);
  if (index >= 0) {
    orders.value[index] = { ...orders.value[index], ...payload, items_count: payload.items.reduce((acc, i) => acc + i.cantidad, 0) };
  }

  try {
    savingEdit.value = true;
    const updated = await updateOrder(id, payload);
    const patchIndex = orders.value.findIndex((o) => o.id === id);
    if (patchIndex >= 0) orders.value[patchIndex] = updated;
    showToast('Pedido actualizado', 'success');
    closeEditor();
  } catch (error) {
    orders.value = prev;
    if (error?.response?.status === 409 && error?.response?.data?.requires_confirmation) {
      confirm.open = true;
      confirm.title = 'Confirmación fuerte requerida';
      confirm.message = error.response.data.message;
      confirm.confirmText = 'Editar de todos modos';
      confirm.action = async () => {
        confirm.loading = true;
        await submitEdit({ id, payload: { ...payload, force_confirmation: true } });
        confirm.loading = false;
        confirm.open = false;
      };
      return;
    }
    showToast(error?.response?.data?.message || 'No se pudo actualizar', 'error');
  } finally {
    savingEdit.value = false;
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
    if (error?.response?.status === 409 && error?.response?.data?.requires_confirmation && !force) {
      confirm.title = 'Confirmación fuerte requerida';
      confirm.message = error.response.data.message;
      confirm.confirmText = 'Cancelar de todos modos';
      confirm.action = () => performDelete(order, true);
      return;
    }
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
