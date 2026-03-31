<template>
  <section class="layout">
    <header class="header">
      <button class="back" @click="router.push({ name: 'mesas' })">← Mesas</button>

      <div>
        <h1>Mesa {{ mesaCodigo }}</h1>
        <p class="subtitle">
          Estado:
          <span class="badge" :class="`estado-${mesaEstado}`">{{ estadoMesaLabel }}</span>
        </p>
      </div>

      <div class="header-actions">
        <button class="refresh" :disabled="loading" @click="loadMesaData">
          {{ loading ? 'Actualizando...' : 'Actualizar' }}
        </button>
        <button
          class="facturar-mesa"
          :disabled="loading || !clientesConPedidos.length || billingWholeTable"
          @click="billWholeTable"
        >
          {{ billingWholeTable ? 'Facturando mesa...' : 'Facturar mesa completa' }}
        </button>
      </div>
    </header>

    <p v-if="error" class="error">{{ error }}</p>

    <div v-if="loading" class="card">Cargando clientes y pedidos de la mesa...</div>

    <template v-else>
      <div v-if="!clientesConPedidos.length" class="card empty">No hay pedidos activos para esta mesa.</div>

      <div class="clientes-grid">
        <ClientePedidoCard
          v-for="cliente in clientesConPedidos"
          :key="cliente.id"
          :cliente="cliente"
          :pedidos="cliente.pedidos"
          :elapsed-text="elapsedMap[cliente.id] || '00:00 min'"
          :timer-tone="timerToneMap[cliente.id] || 'ok'"
          :billing="Boolean(billingMap[cliente.id])"
          :busy="Boolean(busyMap[cliente.id])"
          :can-edit="canEditCliente(cliente)"
          :editing="editingClienteId === cliente.id"
          :draft-items="draftMap[cliente.id] || []"
          :menu-options="menuItems"
          @deliver-group="deliverGroupForCliente"
          @facturar-cliente="billCliente"
          @edit="startEdit"
          @save-edit="handleEditAction(cliente, $event)"
          @cancel-edit="cancelEdit"
          @send-to-kitchen="sendClienteToKitchen"
        />
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ClientePedidoCard from '../components/ClientePedidoCard.vue';
import {
  deliverOrderGroup,
  facturarCliente,
  getMesa,
  getMesaPedidos,
  searchMenuItems,
  sendOrderToKitchen,
  updateOrder,
} from '../api';

const route = useRoute();
const router = useRouter();

const mesa = ref(null);
const pedidos = ref([]);
const loading = ref(false);
const error = ref('');
const now = ref(Date.now());
const editingClienteId = ref(null);
const draftMap = ref({});
const menuItems = ref([]);
const billingMap = ref({});
const busyMap = ref({});
const billingWholeTable = ref(false);
let timerId = null;
let refreshId = null;

const mesaId = computed(() => route.params.id);
const mesaCodigo = computed(() => mesa.value?.codigo ?? mesaId.value);
const mesaEstado = computed(() => mesa.value?.estado ?? 'desconocido');

const estadoMesaLabel = computed(() => {
  const estado = mesaEstado.value;
  if (estado === 'pendiente') return 'Pedidos pendientes';
  if (estado === 'en_uso') return 'En uso';
  if (estado === 'libre') return 'Libre';
  return estado || 'Sin estado';
});

const normalizeItems = (pedido) => {
  const details = pedido.detalle ?? pedido.items ?? [];
  return details.map((item, index) => ({
    key: item.id ?? `${pedido.id}-${index}`,
    menu_item_id: item?.menu_item_id ?? item?.menuItem?.id ?? item?.menu_item?.id ?? null,
    nombre: item?.menu_item?.nombre ?? item?.menuItem?.nombre ?? item?.nombre ?? 'Ítem',
    cantidad: Number(item?.cantidad ?? 1),
    importe: Number(item?.importe ?? item?.precio_unitario ?? item?.precio ?? 0),
    grupo_servicio: item?.grupo_servicio ?? item?.categoria ?? 'plato',
    nota: item?.nota ?? '',
  }));
};

const clientesConPedidos = computed(() => {
  const byCliente = new Map();

  pedidos.value.forEach((pedido) => {
    const clienteId = pedido?.cliente_mesa_id ?? pedido?.cliente_id ?? pedido?.cliente?.id ?? `anon-${pedido.id}`;
    const customerName = pedido?.cliente_nombre || pedido?.cliente_mesa?.nombre || pedido?.cliente?.nombre || 'Cliente invitado';
    if (!byCliente.has(clienteId)) {
      byCliente.set(clienteId, {
        id: clienteId,
        nombre: customerName,
        pedidos: [],
      });
    }

    byCliente.get(clienteId).pedidos.push(pedido);
  });

  return Array.from(byCliente.values()).map((cliente) => ({
    ...cliente,
    pedidos: cliente.pedidos.sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0)),
  }));
});

const clienteHoldUntil = (cliente) => {
  const hold = cliente?.pedidos?.[0]?.hold_expires_at;
  return hold ? new Date(hold).getTime() : now.value;
};

const elapsedMap = computed(() => {
  const map = {};
  clientesConPedidos.value.forEach((cliente) => {
    const diffSeconds = Math.max(0, Math.floor((clienteHoldUntil(cliente) - now.value) / 1000));
    const mm = String(Math.floor(diffSeconds / 60)).padStart(2, '0');
    const ss = String(diffSeconds % 60).padStart(2, '0');
    map[cliente.id] = `${mm}:${ss} min`;
  });
  return map;
});

const timerToneMap = computed(() => {
  const map = {};
  clientesConPedidos.value.forEach((cliente) => {
    const remaining = (clienteHoldUntil(cliente) - now.value) / 60000;
    if (remaining > 2) map[cliente.id] = 'ok';
    else if (remaining > 0) map[cliente.id] = 'warning';
    else map[cliente.id] = 'danger';
  });
  return map;
});

const canEditCliente = (cliente) => {
  const pedido = cliente?.pedidos?.[0];
  if (!pedido) return false;
  return isWithinTime(cliente);
};

const loadMenu = async () => {
  if (menuItems.value.length) return;
  menuItems.value = await searchMenuItems('');
};

const isWithinTime = (cliente) => {
  const hold = cliente?.pedidos?.[0]?.hold_expires_at
  if (!hold) return false

  return new Date().getTime() < new Date(hold).getTime()
}

const loadMesaData = async (silent = false) => {
  if (!mesaId.value) return

  if (!silent) {
    loading.value = true
  }

  try {
    const [mesaData, mesaPedidos] = await Promise.all([
      getMesa(mesaId.value),
      getMesaPedidos(mesaId.value)
    ])

    mesa.value = mesaData

    const nuevos = mesaPedidos || []

    if (!pedidos.value.length) {
      pedidos.value = nuevos
    } else {
      nuevos.forEach((nuevo) => {
        const index = pedidos.value.findIndex(p => p.id === nuevo.id)

        if (index !== -1) {
          pedidos.value[index] = nuevo
        } else {
          pedidos.value.push(nuevo)
        }
      })

      pedidos.value = pedidos.value.filter(p =>
        nuevos.some(n => n.id === p.id)
      )
    }

    await loadMenu()

  } catch (err) {
    if (!silent) {
      error.value = err?.response?.data?.message || 'Error cargando datos'
    }
  } finally {
    if (!silent) {
      loading.value = false
    }
  }
}

const startEdit = (cliente) => {
  console.log("EDITANDO", cliente);

  if (!cliente?.pedidos?.length) return;

  const pedido = cliente.pedidos[0];

  editingClienteId.value = cliente.id;

  draftMap.value = {
    ...draftMap.value,
    [cliente.id]: normalizeItems(pedido).map((item) => ({
      key: item.key,
      menu_item_id: item.menu_item_id,
      cantidad: item.cantidad,
      nota: item.nota,
    })),
  };
};

const cancelEdit = () => {
  editingClienteId.value = null;
};

const handleEditAction = async (cliente, payload) => {
  const list = draftMap.value[cliente.id] || [];

  if (payload.mode === 'append') {
    draftMap.value = { ...draftMap.value, [cliente.id]: [...list, payload.item] };
    return;
  }

  if (payload.mode === 'remove') {
    draftMap.value = {
      ...draftMap.value,
      [cliente.id]: list.filter((_, idx) => idx !== payload.index),
    };
    return;
  }

  if (payload.mode !== 'commit') return;

  const pedido = cliente.pedidos[0];
  const items = (draftMap.value[cliente.id] || []).map((item) => ({
    menu_item_id: Number(item.menu_item_id),
    cantidad: Math.max(1, Number(item.cantidad || 1)),
    nota: item.nota || null,
  })).filter((item) => item.menu_item_id);

  if (!items.length) {
    error.value = 'Debes mantener al menos un producto para guardar el pedido.';
    return;
  }

  busyMap.value = { ...busyMap.value, [cliente.id]: true };
  error.value = '';

  try {
    await updateOrder(pedido.id, { items, mesa: mesaCodigo.value });
    editingClienteId.value = null;
    await loadMesaData();
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudo guardar la edición del pedido.';
  } finally {
    busyMap.value = { ...busyMap.value, [cliente.id]: false };
  }
};


const sendClienteToKitchen = async (cliente) => {
  const pedido = cliente?.pedidos?.[0];
  if (!pedido?.id) return;

  busyMap.value = { ...busyMap.value, [cliente.id]: true };
  error.value = '';

  try {
    await sendOrderToKitchen(pedido.id);
    editingClienteId.value = null;
    await loadMesaData();
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudo enviar el pedido a cocina.';
  } finally {
    busyMap.value = { ...busyMap.value, [cliente.id]: false };
  }
};

const deliverGroupForCliente = async ({ order, group }) => {
  console.log("FUNCION EJECUTADA", order, group);

  if (!order?.id || !group) {
    console.log("SALIO POR VALIDACION");
    return;
  }

  busyMap.value = { ...busyMap.value, [order.id]: true };

  try {
    const grupoBackend =
      group === 'plato' || group === 'platos'
        ? 'plato'
        : group === 'bebida' || group === 'bebidas'
        ? 'bebida'
        : null;

    if (!grupoBackend) {
      console.error('Grupo inválido:', group);
      return;
    }

    console.log("ENVIANDO A BACKEND:", order.id, grupoBackend);

    await deliverOrderGroup(order.id, grupoBackend);

    console.log("RESPUESTA OK");

    await loadMesaData();

  } catch (err) {
    console.error("ERROR:", err);
    error.value = err?.response?.data?.message || 'No se pudo entregar';
  } finally {
    busyMap.value = { ...busyMap.value, [order.id]: false };
  }
};
const billCliente = async (cliente) => {
  billingMap.value = { ...billingMap.value, [cliente.id]: true };
  error.value = '';
  try {
    await facturarCliente(cliente.id);
    await loadMesaData();
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudo facturar al cliente.';
  } finally {
    billingMap.value = { ...billingMap.value, [cliente.id]: false };
  }
};

const billWholeTable = async () => {
  const clientes = clientesConPedidos.value;
  if (!clientes.length) return;

  billingWholeTable.value = true;
  error.value = '';

  try {
    await Promise.all(clientes.map((cliente) => facturarCliente(cliente.id)));
    await loadMesaData();
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudo facturar la mesa completa.';
  } finally {
    billingWholeTable.value = false;
  }
};

watch(() => route.params.id, () => loadMesaData(false));

onMounted(() => {
  loadMesaData(false);
  timerId = window.setInterval(() => {
    now.value = Date.now();
  }, 1000);
  refreshId = window.setInterval(() => {
  if (!editingClienteId.value) {
    loadMesaData(true)
  }
}, 5000)
});

onUnmounted(() => {
  if (timerId) window.clearInterval(timerId);
  if (refreshId) window.clearInterval(refreshId);
});
</script>

<style scoped>
.layout { display: grid; gap: 18px; }
.header {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 12px;
}
.header-actions { display: flex; gap: 10px; }
.back,
.refresh,
.facturar-mesa {
  border: 0;
  border-radius: 12px;
  padding: 10px 14px;
  font-weight: 600;
  color: #fff;
}
.back { background: #1f2f52; }
.refresh { background: #1d4ed8; }
.facturar-mesa { background: #0f766e; }
.refresh:disabled,
.facturar-mesa:disabled { opacity: 0.7; }
.subtitle { margin: 6px 0 0; color: #b8c6e8; }
.error {
  margin: 0;
  padding: 10px 12px;
  border-radius: 10px;
  background: #450a0a;
  border: 1px solid #7f1d1d;
  color: #fecaca;
}
.card {
  border-radius: 16px;
  padding: 16px;
  border: 1px solid rgba(148, 163, 184, 0.25);
  background: rgba(15, 23, 42, 0.72);
}
.empty { color: #9db0d8; }
.clientes-grid {
  display: grid;
  gap: 14px;
  grid-template-columns: 1fr;
}
.badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 0.82rem;
  font-weight: 600;
}
.estado-pendiente { background: rgba(107, 114, 128, 0.28); color: #e5e7eb; }
.estado-en_uso { background: rgba(250, 204, 21, 0.28); color: #fde68a; }
.estado-listo { background: rgba(59, 130, 246, 0.28); color: #bfdbfe; }
.estado-entregado,
.estado-libre { background: rgba(16, 185, 129, 0.28); color: #bbf7d0; }

@media (min-width: 768px) {
  .clientes-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (min-width: 1180px) {
  .clientes-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 760px) {
  .header { grid-template-columns: 1fr; }
  .header-actions { width: 100%; }
  .refresh,
  .facturar-mesa { flex: 1; }
}
</style>
