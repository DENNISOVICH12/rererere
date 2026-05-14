<template>
  <section class="layout">
    <header class="header card-shell">
      <button class="btn btn-ghost" @click="router.push({ name: 'mesas' })">← Mesas</button>

      <div>
        <h1>Mesa {{ mesaCodigo }}</h1>
        <p class="subtitle">
          Estado:
          <span class="badge" :class="`estado-${mesaEstado}`">{{ estadoMesaLabel }}</span>
        </p>
        <p v-if="meseroAsignado" class="mesero-asignado">
          👤 Atendida por <strong>{{ meseroAsignado }}</strong>
        </p>
      </div>

      <div class="header-actions">
        <button class="btn btn-secondary" :disabled="loading" @click="loadMesaData">
          {{ loading ? 'Actualizando...' : 'Actualizar' }}
        </button>
        <button
          class="btn btn-success"
          :disabled="loading || !clientesConPedidos.length"
          @click="openCuentaModal()"
        >
          💰 Ver cuenta de mesa
        </button>
      </div>
    </header>

    <p v-if="error" class="error">{{ error }}</p>

    <div v-if="loading" class="card-shell">Cargando clientes y pedidos de la mesa...</div>

    <template v-else>
      <div v-if="!clientesConPedidos.length" class="card-shell empty">No hay pedidos activos para esta mesa.</div>

      <div class="clientes-grid">
        <ClientePedidoCard
          v-for="cliente in clientesConPedidos"
          :key="cliente.id"
          :cliente="cliente"
          :pedidos="cliente.pedidos"
          :elapsed-text="elapsedMap[cliente.id] || '00:00 min'"
          :timer-tone="timerToneMap[cliente.id] || 'ok'"
          :busy="Boolean(busyMap[cliente.id])"
          :can-edit="canEditCliente(cliente)"
          :needs-justification="pedidoNecesitaJustificacion(cliente)"
          :can-send-to-kitchen="canSendToKitchenCliente(cliente)"
          :editing="editingClienteId === cliente.id"
          :draft-items="draftMap[cliente.id] || []"
          :menu-options="menuItems"
          @deliver-group="deliverGroupForCliente"
          @ver-comprobante="abrirComprobante"
          @marcar-pagado="pagarCliente"
          @edit="startEdit"
          @save-edit="handleEditAction(cliente, $event)"
          @cancel-edit="cancelEdit"
          @send-to-kitchen="sendClienteToKitchen"
        />
      </div>
    </template>

    <CuentaModal
      :open="showCuentaModal"
      :cliente="selectedCliente"
      :pedidos="selectedPedidos"
      :paid="selectedPaid"
      :comprobante-url="comprobanteUrl"
      @close="closeCuentaModal"
      @mark-paid="markCuentaPagada"
    />

    <AsignarMeseroModal
      :open="showAsignarModal"
      :mesa-numero="mesaCodigo"
      :ya-asignada="asignarYaAsignada"
      :mesero-actual="asignarMeseroActual"
      :loading="asignarLoading"
      @confirmar="confirmarAsignacion"
      @tomar-relevo="tomarRelevo"
      @cancelar="cancelarAsignacion"
    />

  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ClientePedidoCard from '../components/ClientePedidoCard.vue';
import CuentaModal from '../components/CuentaModal.vue';
import AsignarMeseroModal from '../components/AsignarMeseroModal.vue';
import { bindWaiterRealtime } from '../../echo';
import {
  deliverOrderGroup,
  facturarCliente,
  getMesa,
  getMesaPedidos,
  searchMenuItems,
  sendOrderToKitchen,
  updateOrder,
  asignarMesero,
  liberarMesero,
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
const busyMap = ref({});
const paidMap = ref({});
const showCuentaModal = ref(false);
const comprobanteUrl = ref(null);
const selectedCliente = ref(null);
const selectedPedidos = ref([]);

// ── Asignación de mesero ──────────────────────────────────
const showAsignarModal = ref(false);
const asignarYaAsignada = ref(false);
const asignarMeseroActual = ref('');
const asignarLoading = ref(false);
const meseroAsignado = ref('');
// ─────────────────────────────────────────────────────────

const syncing = ref(false);
let timerId = null;
let refreshId = null;
let stopRealtime = null;
let lastSilentSyncAt = 0;
let lastHash = '';

const mesaId = computed(() => route.params.id);
const mesaCodigo = computed(() => mesa.value?.codigo ?? mesaId.value);
const mesaEstado = computed(() => mesa.value?.estado ?? 'desconocido');

// ── Lógica de asignación ──────────────────────────────────
const verificarAsignacion = () => {
  const meseroId = mesa.value?.mesero_id ?? null;
  const meseroNombre = mesa.value?.mesero_nombre ?? null;

  meseroAsignado.value = meseroNombre || '';

  if (meseroId && meseroNombre) {
    // Mesa ya tiene otro mesero — mostrar aviso
    asignarYaAsignada.value = true;
    asignarMeseroActual.value = meseroNombre;
  } else {
    asignarYaAsignada.value = false;
    asignarMeseroActual.value = '';
  }

  showAsignarModal.value = true;
};

const confirmarAsignacion = async () => {
  asignarLoading.value = true;
  try {
    const res = await asignarMesero(mesaId.value);
    meseroAsignado.value = res.mesero_nombre || '';
    showAsignarModal.value = false;
    await loadMesaData(true);
  } catch (err) {
    if (err?.response?.status === 409) {
      const data = err.response.data;
      asignarYaAsignada.value = true;
      asignarMeseroActual.value = data.mesero_nombre || 'Otro mesero';
    }
  } finally {
    asignarLoading.value = false;
  }
};

const tomarRelevo = async () => {
  asignarLoading.value = true;
  try {
    await liberarMesero(mesaId.value);
    const res = await asignarMesero(mesaId.value);
    meseroAsignado.value = res.mesero_nombre || '';
    showAsignarModal.value = false;
    await loadMesaData(true);
  } finally {
    asignarLoading.value = false;
  }
};

const cancelarAsignacion = () => {
  showAsignarModal.value = false;
  router.push({ name: 'mesas' });
};
// ─────────────────────────────────────────────────────────

const sendClienteToKitchen = async (cliente) => {
  const pedido = cliente?.pedidos?.[0];
  if (!pedido?.id) return;

  busyMap.value = { ...busyMap.value, [cliente.id]: true };

  try {
    await sendOrderToKitchen(pedido.id);
    loadMesaData();
  } finally {
    busyMap.value = { ...busyMap.value, [cliente.id]: false };
  }
};

const deliverGroupForCliente = async ({ cliente, order, group }) => {
  if (!order?.id || !group) return;

  busyMap.value = { ...busyMap.value, [cliente.id]: true };

  try {
    await deliverOrderGroup(order.id, group);
    loadMesaData();
  } finally {
    busyMap.value = { ...busyMap.value, [cliente.id]: false };
  }
};

const openCuentaModal = (cliente = null) => {
  if (cliente) {
    selectedCliente.value = cliente;
    selectedPedidos.value = cliente?.pedidos ?? [];
  } else {
    selectedCliente.value = { id: `mesa-${mesaId.value}`, nombre: `Mesa ${mesaCodigo.value}` };
    selectedPedidos.value = clientesConPedidos.value.flatMap((item) => item.pedidos || []);
  }

  showCuentaModal.value = true;
};

const closeCuentaModal = async () => {
  const id = selectedCliente.value?.id;
  const wasPaid = id && paidMap.value[id];

  showCuentaModal.value = false;
  comprobanteUrl.value = null;
  selectedCliente.value = null;
  selectedPedidos.value = [];

  // Si se cerró después de cobrar, limpiar pedidos y liberar mesa si quedó vacía
  if (wasPaid) {
    const isNumericId = Number.isInteger(Number(id));
    if (isNumericId) {
      pedidos.value = pedidos.value.filter(
        (p) => String(p.cliente_id) !== String(id)
      );
    } else {
      pedidos.value = [];
    }
    lastHash = '';
    await new Promise(r => setTimeout(r, 500));
    await loadMesaData(true);
    if (!pedidos.value.length) {
      try { await liberarMesero(mesaId.value); meseroAsignado.value = ''; } catch (_) {}
    }
  }
};

// ── Comprobante directo desde la tarjeta ─────────────────
const abrirComprobante = async (cliente) => {
  const id = cliente?.id;
  if (!id || !Number.isInteger(Number(id))) return;

  // Si ya tenemos la URL cacheada la abrimos directo
  if (paidMap.value[id] && comprobanteUrl.value) {
    window.open(comprobanteUrl.value, '_blank');
    return;
  }

  // Buscar comprobante existente del cliente
  try {
    const res = await fetch(`/api/mesero/clientes/${id}/comprobante-url`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    if (res.ok) {
      const data = await res.json();
      if (data?.url) {
        window.open(data.url, '_blank');
        return;
      }
    }
  } catch (_) {}

  // Si no hay comprobante aún, mostrar la cuenta normal
  openCuentaModal(cliente);
};

// ── Pago directo desde la tarjeta ────────────────────────
const pagarCliente = async (cliente) => {
  const id = cliente?.id;
  if (!id || !Number.isInteger(Number(id))) return;
  if (busyMap.value[id]) return;

  busyMap.value = { ...busyMap.value, [id]: true };

  try {
    const res = await facturarCliente(id);
    if (res?.comprobante_url) {
      comprobanteUrl.value = res.comprobante_url;
    }
    paidMap.value = { ...paidMap.value, [id]: true };

    // Limpiar pedidos del cliente
    pedidos.value = pedidos.value.filter(
      (p) => String(p.cliente_id) !== String(id)
    );

    lastHash = '';
    await new Promise(r => setTimeout(r, 500));
    await loadMesaData(true);

    if (!pedidos.value.length) {
      try { await liberarMesero(mesaId.value); meseroAsignado.value = ''; } catch (_) {}
    }

    // Abrir comprobante automáticamente
    if (res?.comprobante_url) {
      window.open(res.comprobante_url, '_blank');
    }
  } finally {
    busyMap.value = { ...busyMap.value, [id]: false };
  }
};

const selectedPaid = computed(() => {
  const id = selectedCliente.value?.id;
  return Boolean(id && paidMap.value[id]);
});

const markCuentaPagada = async () => {
  const id = selectedCliente.value?.id;
  if (!id) return;

  const isNumericId = Number.isInteger(Number(id));

  if (isNumericId) {
    const res = await facturarCliente(id);
    if (res?.comprobante_url) {
      comprobanteUrl.value = res.comprobante_url;
    }
  }

  paidMap.value = { ...paidMap.value, [id]: true };
  // NO cerramos el modal — el mesero lo cierra después de mostrar el comprobante

  if (isNumericId) {
    pedidos.value = pedidos.value.filter(
      (p) => String(p.cliente_id) !== String(id)
    );
  } else {
    pedidos.value = [];
  }

  lastHash = '';
  await new Promise(r => setTimeout(r, 500));
  await loadMesaData(true);

  // Si ya no quedan pedidos activos, liberar el mesero de la mesa
  if (!pedidos.value.length) {
    try { await liberarMesero(mesaId.value); meseroAsignado.value = ''; } catch (_) {}
  }
};

const canEditCliente = (cliente) => {
  const pedido = cliente?.pedidos?.[0];
  if (!pedido) return false;
  if (pedido?.can_be_edited === false) return false;
  // El mesero/admin puede editar siempre, con justificación si está fuera de ventana
  const bloqueados = ['entregado', 'facturado', 'cancelado'];
  return !bloqueados.includes(pedido?.estado);
};

const pedidoNecesitaJustificacion = (cliente) => {
  const pedido = cliente?.pedidos?.[0];
  if (!pedido) return false;
  // Necesita justificación si ya no está en ventana de retención
  return pedido?.estado !== 'retenido' || !pedido?.hold_expires_at ||
    new Date().getTime() >= new Date(pedido?.hold_expires_at).getTime();
};

const canSendToKitchenCliente = (cliente) => {
  const pedido = cliente?.pedidos?.[0];
  if (!pedido) return false;
  if (pedido?.can_send_to_kitchen === false) return false;
  return true;
};

const estadoMesaLabel = computed(() => {
  const estado = mesaEstado.value;
  if (estado === 'pendiente') return 'Pedidos pendientes';
  if (estado === 'en_uso') return 'En uso';
  if (estado === 'libre') return 'Libre';
  return estado || 'Sin estado';
});

const buildHash = (items = []) =>
  items.map(p => `${p.id}-${p.updated_at || ''}-${p.estado || ''}`).join('|');

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
    const clienteId = pedido?.cliente_id ?? `anon-${pedido.id}`;
    const customerName = pedido?.cliente_nombre || 'Cliente invitado';

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

const loadMesaData = async (silent = false) => {
  if (!mesaId.value || syncing.value) return;

  syncing.value = true;
  if (!silent) loading.value = true;
  error.value = '';

  try {
    const [mesaData, nuevos] = await Promise.all([
      getMesa(mesaId.value),
      getMesaPedidos(mesaId.value),
    ]);

    mesa.value = mesaData;
    meseroAsignado.value = mesaData?.mesero_nombre || '';

    const hash = buildHash(nuevos);
    if (hash !== lastHash) {
      pedidos.value = nuevos;
      lastHash = hash;
    }

    if (!menuItems.value.length) {
      searchMenuItems('').then(items => { menuItems.value = items; });
    }

  } catch (err) {
    if (!silent) {
      error.value = err?.response?.data?.message || 'Error cargando datos';
    }
  } finally {
    syncing.value = false;
    if (!silent) loading.value = false;
  }
};

const startEdit = (cliente) => {
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

  const items = (draftMap.value[cliente.id] || [])
    .map((item) => ({
      menu_item_id: Number(item.menu_item_id),
      cantidad: Math.max(1, Number(item.cantidad || 1)),
      nota: item.nota || null,
    }))
    .filter((item) => item.menu_item_id);

  busyMap.value = { ...busyMap.value, [cliente.id]: true };

  try {
    await updateOrder(pedido.id, {
      items,
      mesa_id: Number(mesaId.value) || null,
      justificacion: payload.justificacion || null,
    });
    editingClienteId.value = null;
    loadMesaData();
  } finally {
    busyMap.value = { ...busyMap.value, [cliente.id]: false };
  }
};

onMounted(async () => {
  await loadMesaData();
  verificarAsignacion();

  timerId = setInterval(() => {
    now.value = Date.now();
  }, 2000);

  setTimeout(() => {
    stopRealtime = bindWaiterRealtime(1, {
      onNotification: () => {
        const ts = Date.now();
        if (ts - lastSilentSyncAt < 1200) return;
        lastSilentSyncAt = ts;

        if (!editingClienteId.value && !document.hidden) {
          loadMesaData(true);
        }
      }
    });
  }, 400);

  refreshId = setInterval(() => {
    if (!editingClienteId.value && !document.hidden) {
      loadMesaData(true);
    }
  }, 4000);
});

onUnmounted(() => {
  if (stopRealtime) stopRealtime();
  if (timerId) clearInterval(timerId);
  if (refreshId) clearInterval(refreshId);
});

watch(() => route.params.id, () => loadMesaData());
</script>

<style scoped>
.layout {
  font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  display: grid;
  gap: 18px;
}
.card-shell {
  border-radius: 18px;
  padding: 16px;
  border: 1px solid rgba(148, 163, 184, 0.24);
  background: linear-gradient(160deg, rgba(17, 24, 39, 0.86), rgba(9, 14, 28, 0.92));
  box-shadow: 0 8px 22px rgba(2, 6, 23, 0.28);
}
.header {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 12px;
}
.header h1 { margin: 0; color: #f8fbff; font-size: 1.3rem; font-weight: 600; }
.header-actions { display: flex; gap: 10px; }
.mesero-asignado {
  margin: 4px 0 0;
  font-size: 0.82rem;
  color: #fbbf24;
}
.mesero-asignado strong { color: #fde68a; }
.btn {
  border: 0;
  border-radius: 11px;
  padding: 10px 14px;
  font-weight: 600;
  color: #f8fafc;
  transition: transform 150ms ease, opacity 150ms ease, filter 150ms ease;
}
.btn:hover:not(:disabled) { transform: translateY(-1px); filter: brightness(1.06); }
.btn:disabled { opacity: 0.55; cursor: not-allowed; transform: none; filter: none; }
.btn-ghost { background: #1f2f52; }
.btn-secondary { background: #1d4ed8; }
.btn-success { background: #059669; }
.subtitle { margin: 6px 0 0; color: #b8c6e8; font-size: .92rem; }
.error {
  margin: 0;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(127, 29, 29, 0.38);
  border: 1px solid rgba(248, 113, 113, 0.45);
  color: #fecaca;
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
  font-size: 0.8rem;
  font-weight: 600;
}
.estado-pendiente { background: rgba(107, 114, 128, 0.28); color: #e5e7eb; }
.estado-en_uso { background: rgba(59, 130, 246, 0.22); color: #bfdbfe; }
.estado-listo { background: rgba(249, 115, 22, 0.24); color: #fed7aa; }
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
  .btn-secondary,
  .btn-success { flex: 1; }
}
</style>