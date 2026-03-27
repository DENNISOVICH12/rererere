<template>
  <section class="layout">
    <header class="header">
      <button class="back" @click="router.push({ name: 'mesas' })">← Mesas</button>

      <div>
        <h1>Mesa {{ mesaCodigo }}</h1>
        <p class="subtitle">
          Estado: <span class="badge" :class="`estado-${mesaEstado}`">{{ estadoMesaLabel }}</span>
        </p>
      </div>

      <button class="refresh" :disabled="loading" @click="loadMesaData">
        {{ loading ? 'Actualizando...' : 'Actualizar' }}
      </button>
    </header>

    <p v-if="error" class="error">{{ error }}</p>

    <div v-if="loading" class="card">Cargando pedidos de la mesa...</div>

    <template v-else>
      <div v-if="!pedidos.length" class="card empty">No hay pedidos activos para esta mesa.</div>

      <section
        v-for="group in groupedPedidos"
        :key="group.estado"
        class="group"
      >
        <h2>
          {{ estadoPedidoLabel(group.estado) }}
          <small>({{ group.pedidos.length }})</small>
        </h2>

        <div class="orders-grid">
          <article v-for="pedido in group.pedidos" :key="pedido.id" class="card order-card">
            <header class="order-head">
              <strong>Pedido #{{ pedido.id }}</strong>
              <span class="badge" :class="`estado-${normalizeEstado(pedido.estado)}`">
                {{ estadoPedidoLabel(pedido.estado) }}
              </span>
            </header>

            <ul class="items">
              <li v-for="item in normalizeItems(pedido)" :key="item.key" class="item-row">
                <div>
                  <p class="item-title">{{ item.nombre }}</p>
                  <p class="item-meta">x{{ item.cantidad }} · {{ item.grupo }}</p>
                  <p v-if="item.nota" class="item-note">Nota: {{ item.nota }}</p>
                </div>
                <strong class="item-price">${{ formatPrice(item.importe) }}</strong>
              </li>
            </ul>
          </article>
        </div>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { getMesa, getMesaPedidos } from '../api';

const route = useRoute();
const router = useRouter();

const mesa = ref(null);
const pedidos = ref([]);
const loading = ref(false);
const error = ref('');

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

const normalizeEstado = (estado) => (estado || '').toString().toLowerCase().replace(/\s+/g, '_');

const estadoPedidoLabel = (estado) => {
  const key = normalizeEstado(estado);

  if (key === 'pendiente') return 'Pendiente';
  if (key === 'preparando' || key === 'en_preparacion') return 'En preparación';
  if (key === 'listo') return 'Listo';
  if (key === 'entregado') return 'Entregado';

  return estado || 'Sin estado';
};

const groupedPedidos = computed(() => {
  const grouped = pedidos.value.reduce((acc, pedido) => {
    const estado = normalizeEstado(pedido.estado) || 'sin_estado';

    if (!acc[estado]) acc[estado] = [];
    acc[estado].push(pedido);

    return acc;
  }, {});

  return Object.entries(grouped)
    .map(([estado, estadoPedidos]) => ({ estado, pedidos: estadoPedidos }))
    .sort((a, b) => a.estado.localeCompare(b.estado));
});

const formatPrice = (value) => Number(value || 0).toFixed(2);

const normalizeItems = (pedido) => {
  const details = pedido.detalle ?? pedido.items ?? [];

  return details.map((item, index) => ({
    key: item.id ?? `${pedido.id}-${index}`,
    nombre: item?.menu_item?.nombre ?? item?.menuItem?.nombre ?? item?.nombre ?? 'Ítem',
    cantidad: Number(item?.cantidad ?? 1),
    importe: Number(item?.importe ?? item?.precio_unitario ?? 0),
    grupo: item?.grupo_servicio ?? item?.categoria ?? 'sin grupo',
    nota: item?.nota ?? '',
  }));
};

const loadMesaData = async () => {
  if (!mesaId.value) return;

  loading.value = true;
  error.value = '';

  try {
    const [mesaData, mesaPedidos] = await Promise.all([
      getMesa(mesaId.value),
      getMesaPedidos(mesaId.value),
    ]);

    mesa.value = mesaData;
    pedidos.value = mesaPedidos || [];
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudo cargar el detalle de la mesa.';
  } finally {
    loading.value = false;
  }
};

watch(() => route.params.id, loadMesaData);
onMounted(loadMesaData);
</script>

<style scoped>
.layout { display: grid; gap: 16px; }
.header {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 12px;
}
.back,
.refresh {
  border: 0;
  border-radius: 10px;
  padding: 10px 14px;
  font-weight: 600;
  color: #fff;
}
.back { background: #1f2f52; }
.refresh { background: #1d4ed8; }
.refresh:disabled { opacity: 0.7; }
.subtitle { margin: 6px 0 0; color: #b8c6e8; }
.error {
  margin: 0;
  padding: 10px 12px;
  border-radius: 10px;
  background: #450a0a;
  border: 1px solid #7f1d1d;
  color: #fecaca;
}
.group { display: grid; gap: 10px; }
.group h2 { margin: 0; font-size: 1rem; color: #dbe7ff; }
.group h2 small { color: #9db0d8; font-weight: 500; }
.orders-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}
.card {
  background: #0f1a31;
  border: 1px solid #263559;
  border-radius: 14px;
  padding: 14px;
}
.empty { color: #9db0d8; }
.order-card { display: grid; gap: 12px; }
.order-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}
.items { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
.item-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  border-top: 1px solid #233251;
  padding-top: 10px;
}
.item-title { margin: 0; font-weight: 600; }
.item-meta { margin: 4px 0 0; color: #9db0d8; font-size: 0.9rem; }
.item-note { margin: 4px 0 0; color: #facc15; font-size: 0.85rem; }
.item-price { white-space: nowrap; }
.badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 0.82rem;
  font-weight: 600;
}
.estado-pendiente { background: rgba(185, 28, 28, 0.25); color: #fecaca; }
.estado-preparando,
.estado-en_preparacion { background: rgba(161, 98, 7, 0.28); color: #fde68a; }
.estado-listo { background: rgba(21, 128, 61, 0.25); color: #bbf7d0; }
.estado-entregado,
.estado-libre { background: rgba(59, 130, 246, 0.22); color: #bfdbfe; }
.estado-en_uso { background: rgba(161, 98, 7, 0.28); color: #fde68a; }
</style>
