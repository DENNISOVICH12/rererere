<template>
  <section class="historial-page">
    <header class="panel card">
      <div>
        <p class="eyebrow">Analítica de fidelización</p>
        <h1>Historial y análisis de clientes</h1>
      </div>

      <div class="filters">
        <div class="field search">
          <label>Buscar cliente</label>
          <input v-model="clienteSearch" type="text" placeholder="Nombre, correo o teléfono" @input="searchClientes" />
          <ul v-if="clienteOptions.length" class="suggestions">
            <li v-for="cliente in clienteOptions" :key="cliente.id" @click="selectCliente(cliente)">
              {{ cliente.nombre ?? `${cliente.nombres ?? ''} ${cliente.apellidos ?? ''}`.trim() }}
            </li>
          </ul>
        </div>

        <div class="field">
          <label>Fecha inicio</label>
          <input v-model="filters.date_from" type="date" />
        </div>

        <div class="field">
          <label>Fecha fin</label>
          <input v-model="filters.date_to" type="date" />
        </div>

        <div class="field">
          <label>Mín. pedidos</label>
          <input v-model.number="filters.min_pedidos" type="number" min="1" placeholder="Ej: 3" />
        </div>

        <button class="btn btn-primary" :disabled="!selectedClienteId || loading" @click="loadHistorial">
          {{ loading ? 'Cargando...' : 'Aplicar filtros' }}
        </button>
      </div>
    </header>

    <p v-if="error" class="alert">{{ error }}</p>

    <div v-if="data" class="content-grid">
      <section class="card summary">
        <h2>{{ data.cliente.nombre }}</h2>
        <p class="muted">{{ data.cliente.correo || 'Sin correo' }} · {{ data.cliente.telefono || 'Sin teléfono' }}</p>
        <div class="badges">
          <span class="badge" :class="`badge-${data.clasificacion}`">{{ data.clasificacion }}</span>
        </div>
      </section>

      <section class="metrics-grid">
        <article class="card metric"><p>Total gastado</p><strong>${{ fmtMoney(data.resumen.total_gastado) }}</strong></article>
        <article class="card metric"><p>Pedidos</p><strong>{{ data.resumen.cantidad_pedidos }}</strong></article>
        <article class="card metric"><p>Ticket promedio</p><strong>${{ fmtMoney(data.resumen.ticket_promedio) }}</strong></article>
        <article class="card metric"><p>Frecuencia (días)</p><strong>{{ data.analisis.frecuencia_visitas_dias ?? '-' }}</strong></article>
      </section>

      <section class="card top-products">
        <h3>Top 5 productos</h3>
        <ul>
          <li v-for="item in data.analisis.productos_top" :key="`${item.menu_item_id}-${item.producto}`">
            <span>{{ item.producto }}</span>
            <strong>{{ item.cantidad_total }} uds</strong>
          </li>
          <li v-if="!data.analisis.productos_top.length" class="muted">Sin datos de consumo en el rango.</li>
        </ul>
      </section>

      <section class="card history-table">
        <h3>Historial de pedidos</h3>
        <details v-for="pedido in data.historial" :key="pedido.id" class="order-item">
          <summary>
            <span>#{{ pedido.id }}</span>
            <span>{{ pedido.fecha }} {{ pedido.hora }}</span>
            <span>${{ fmtMoney(pedido.total) }}</span>
          </summary>
          <table>
            <thead><tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr></thead>
            <tbody>
              <tr v-for="(producto, i) in pedido.productos" :key="`${pedido.id}-${i}`">
                <td>{{ producto.nombre }}</td>
                <td>{{ producto.cantidad }}</td>
                <td>${{ fmtMoney(producto.precio) }}</td>
                <td>${{ fmtMoney(producto.importe) }}</td>
              </tr>
            </tbody>
          </table>
        </details>
        <p v-if="!data.historial.length" class="muted">Este cliente no tiene pedidos con los filtros actuales.</p>
      </section>
    </div>
  </section>
</template>

<script setup>
import axios from 'axios';
import { ref } from 'vue';

const clienteSearch = ref('');
const clienteOptions = ref([]);
const selectedClienteId = ref(null);
const filters = ref({ date_from: '', date_to: '', min_pedidos: null });
const loading = ref(false);
const error = ref('');
const data = ref(null);

let timer = null;

const searchClientes = () => {
  clearTimeout(timer);
  if (!clienteSearch.value || clienteSearch.value.trim().length < 2) {
    clienteOptions.value = [];
    return;
  }

  timer = setTimeout(async () => {
    const response = await axios.get('/api/clientes', { params: { search: clienteSearch.value.trim() } });
    clienteOptions.value = response.data?.data ?? [];
  }, 250);
};

const selectCliente = (cliente) => {
  selectedClienteId.value = cliente.id;
  clienteSearch.value = cliente.nombre ?? `${cliente.nombres ?? ''} ${cliente.apellidos ?? ''}`.trim();
  clienteOptions.value = [];
  loadHistorial();
};

const loadHistorial = async () => {
  if (!selectedClienteId.value) return;

  loading.value = true;
  error.value = '';
  try {
    const params = {
      date_from: filters.value.date_from || undefined,
      date_to: filters.value.date_to || undefined,
      min_pedidos: filters.value.min_pedidos || undefined,
    };

    const response = await axios.get(`/api/admin/clientes/${selectedClienteId.value}/historial`, { params });
    data.value = response.data?.data ?? null;
  } catch (e) {
    error.value = e?.response?.data?.error?.message ?? 'No fue posible cargar el historial.';
  } finally {
    loading.value = false;
  }
};

const fmtMoney = (v) => Number(v || 0).toFixed(2);
</script>

<style scoped>
.historial-page { display:grid; gap:16px; }
.card { background: rgba(15, 23, 42, 0.78); border: 1px solid rgba(148, 163, 184, 0.24); border-radius: 18px; padding: 18px; color:#e2e8f0; }
.eyebrow { color:#ffd7aa; text-transform: uppercase; letter-spacing:.08em; font-size:12px; margin:0; }
h1,h2,h3 { margin: 0 0 8px; }
.filters { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap:10px; align-items:end; margin-top:12px; }
.field { display:grid; gap:4px; position:relative; }
input { border:1px solid rgba(148,163,184,.3); border-radius:10px; background:#0f172a; color:#fff; padding:10px; }
.content-grid { display:grid; grid-template-columns: repeat(12,1fr); gap:12px; }
.summary { grid-column: span 4; }
.metrics-grid { grid-column: span 8; display:grid; grid-template-columns: repeat(auto-fit,minmax(160px,1fr)); gap:10px; }
.metric strong { font-size:24px; }
.top-products { grid-column: span 4; }
.history-table { grid-column: span 8; }
.badge { text-transform: uppercase; border-radius: 999px; padding:4px 10px; font-size:12px; font-weight:700; }
.badge-vip { background: rgba(239,68,68,.2); color: #fca5a5; }
.badge-frecuente { background: rgba(34,197,94,.2); color: #86efac; }
.badge-ocasional { background: rgba(59,130,246,.2); color: #93c5fd; }
ul { list-style: none; padding:0; margin:0; display:grid; gap:8px; }
ul li { display:flex; justify-content:space-between; }
.order-item { border:1px solid rgba(148,163,184,.18); border-radius:12px; margin-top:10px; overflow:hidden; }
summary { display:flex; justify-content:space-between; gap:8px; padding:10px; cursor:pointer; background: rgba(15,23,42,.9); }
table { width:100%; border-collapse: collapse; }
th, td { padding:10px; border-bottom:1px solid rgba(148,163,184,.14); }
.alert { padding:10px 12px; border-radius:10px; border:1px solid rgba(248,113,113,.4); color:#fecaca; background: rgba(127,29,29,.35); }
.muted { color:#94a3b8; }
.btn { height:40px; }
.btn-primary { background: linear-gradient(145deg, #9c2030, #7a1522); color:#fff; border:none; border-radius:10px; }
.suggestions { position:absolute; top:100%; left:0; right:0; background:#020617; border:1px solid rgba(148,163,184,.3); border-radius:10px; margin-top:4px; z-index:5; max-height:180px; overflow:auto; }
.suggestions li { padding:8px 10px; cursor:pointer; }
.suggestions li:hover { background: rgba(148,163,184,.15); }
@media (max-width: 980px) { .summary,.metrics-grid,.top-products,.history-table { grid-column: span 12; } }
</style>
