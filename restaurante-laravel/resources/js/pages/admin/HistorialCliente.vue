<template>
  <section class="historial-page">
    <header class="card panel">
      <div class="panel-head">
        <div>
          <p class="eyebrow">Analítica de fidelización</p>
          <h1>Historial y análisis de clientes</h1>
          <p class="muted">Panel inteligente para segmentar, entender y fidelizar clientes.</p>
        </div>
        <button class="btn btn-secondary" :disabled="loadingClientes" @click="loadClientes">
          {{ loadingClientes ? 'Actualizando...' : 'Actualizar' }}
        </button>
      </div>

      <div class="filters-grid">
        <div class="field field-search">
          <label>Búsqueda</label>
          <input v-model.trim="filters.search" type="text" placeholder="Nombre o correo" @input="onFilterChange" />
        </div>

        <div class="field">
          <label>Registro desde</label>
          <input v-model="filters.registro_desde" type="date" @change="onFilterChange" />
        </div>

        <div class="field">
          <label>Registro hasta</label>
          <input v-model="filters.registro_hasta" type="date" @change="onFilterChange" />
        </div>

        <div class="field">
          <label>Última visita desde</label>
          <input v-model="filters.ultima_visita_desde" type="date" @change="onFilterChange" />
        </div>

        <div class="field">
          <label>Tipo de cliente</label>
          <select v-model="filters.segmento" @change="onFilterChange">
            <option value="">Todos</option>
            <option value="vip">VIP</option>
            <option value="frecuente">Frecuente</option>
            <option value="nuevo">Nuevo</option>
            <option value="inactivo">Inactivo</option>
          </select>
        </div>

        <div class="field">
          <label>Nuevos en</label>
          <select v-model="filters.nuevos_en" @change="onFilterChange">
            <option value="">Sin filtro</option>
            <option value="7">Últimos 7 días</option>
            <option value="30">Últimos 30 días</option>
          </select>
        </div>

        <div class="field">
          <label>Más de X pedidos</label>
          <input v-model.number="filters.min_pedidos" type="number" min="0" placeholder="Ej: 10" @change="onFilterChange" />
        </div>

        <div class="field">
          <label>Gasto mínimo</label>
          <input v-model.number="filters.gasto_min" type="number" min="0" step="0.01" placeholder="0" @change="onFilterChange" />
        </div>

        <div class="field">
          <label>Gasto máximo</label>
          <input v-model.number="filters.gasto_max" type="number" min="0" step="0.01" placeholder="Sin límite" @change="onFilterChange" />
        </div>

        <div class="field">
          <label>Ordenar por</label>
          <select v-model="filters.sort" @change="onFilterChange">
            <option value="nombre_asc">Nombre A-Z</option>
            <option value="nombre_desc">Nombre Z-A</option>
            <option value="registro_desc">Registro más reciente</option>
            <option value="registro_asc">Registro más antiguo</option>
            <option value="pedidos_desc">Más pedidos</option>
            <option value="pedidos_asc">Menos pedidos</option>
            <option value="gasto_desc">Mayor gasto</option>
            <option value="gasto_asc">Menor gasto</option>
          </select>
        </div>

        <div class="field field-actions">
          <button class="btn btn-secondary" @click="resetFilters">Limpiar filtros</button>
        </div>
      </div>
    </header>

    <p v-if="error" class="alert alert-error">{{ error }}</p>

    <section class="cards-grid">
      <article
        v-for="cliente in clientes"
        :key="cliente.id"
        class="card cliente-card"
        :class="`cliente-${cliente.tipo_cliente}`"
        @click="openDetail(cliente)"
      >
        <div class="cliente-top">
          <h3>{{ cliente.nombre }}</h3>
          <span class="badge" :class="`badge-${cliente.tipo_cliente}`">{{ labelTipo(cliente.tipo_cliente) }}</span>
        </div>
        <p class="muted truncate">{{ cliente.correo || 'Sin correo' }}</p>

        <dl class="cliente-metrics">
          <div>
            <dt>Pedidos</dt>
            <dd>{{ cliente.cantidad_pedidos }}</dd>
          </div>
          <div>
            <dt>Total gastado</dt>
            <dd>${{ fmtMoney(cliente.total_gastado) }}</dd>
          </div>
          <div>
            <dt>Promedio</dt>
            <dd>${{ fmtMoney(cliente.promedio) }}</dd>
          </div>
          <div>
            <dt>Última visita</dt>
            <dd>{{ formatDate(cliente.ultima_visita) }}</dd>
          </div>
        </dl>
      </article>

      <article v-if="!loadingClientes && !clientes.length" class="card empty-state">
        <h3>Sin resultados con filtros actuales</h3>
        <p class="muted">Prueba ajustando la búsqueda o limpiando filtros.</p>
      </article>
    </section>

    <transition name="fade">
      <div v-if="showDetail" class="overlay" @click.self="closeDetail">
        <aside class="detail-panel card">
          <header class="detail-head">
            <div>
              <h2>{{ detailData?.cliente?.nombre || selectedCliente?.nombre }}</h2>
              <p class="muted">{{ detailData?.cliente?.correo || selectedCliente?.correo || 'Sin correo' }}</p>
            </div>
            <button class="btn btn-secondary" @click="closeDetail">Cerrar</button>
          </header>

          <p v-if="loadingDetail" class="muted">Cargando detalle...</p>
          <p v-else-if="detailError" class="alert alert-error">{{ detailError }}</p>

          <template v-else-if="detailData">
            <section class="detail-section">
              <h4>Información básica</h4>
              <p class="muted">Fecha de registro: {{ formatDate(selectedCliente?.fecha_registro) }}</p>
              <p class="muted">Última visita: {{ formatDate(detailData.resumen.ultima_visita) }}</p>
            </section>

            <section class="detail-metrics">
              <article class="card metric"><p>Total gastado</p><strong>${{ fmtMoney(detailData.resumen.total_gastado) }}</strong></article>
              <article class="card metric"><p>Cantidad de pedidos</p><strong>{{ detailData.resumen.cantidad_pedidos }}</strong></article>
              <article class="card metric"><p>Promedio por pedido</p><strong>${{ fmtMoney(detailData.resumen.ticket_promedio) }}</strong></article>
              <article class="card metric"><p>Frecuencia visitas</p><strong>{{ detailData.analisis.frecuencia_visitas_dias ?? '-' }} días</strong></article>
            </section>

            <section class="detail-section">
              <h4>Productos más consumidos</h4>
              <ul class="simple-list">
                <li v-for="item in detailData.analisis.productos_top" :key="`${item.menu_item_id}-${item.producto}`">
                  <span>{{ item.producto }}</span>
                  <strong>{{ item.cantidad_total }} uds</strong>
                </li>
                <li v-if="!detailData.analisis.productos_top.length" class="muted">Sin datos en el periodo.</li>
              </ul>
            </section>

            <section class="detail-section">
              <h4>Historial de pedidos</h4>
              <details v-for="pedido in detailData.historial" :key="pedido.id" class="order-item">
                <summary>
                  <span>#{{ pedido.id }}</span>
                  <span>{{ pedido.fecha }} {{ pedido.hora }}</span>
                  <span>${{ fmtMoney(pedido.total) }}</span>
                </summary>
                <ul class="products-list">
                  <li v-for="(prod, i) in pedido.productos" :key="`${pedido.id}-${i}`">
                    <span>{{ prod.nombre }} x{{ prod.cantidad }}</span>
                    <strong>${{ fmtMoney(prod.importe) }}</strong>
                  </li>
                </ul>
              </details>
              <p v-if="!detailData.historial.length" class="muted">No hay pedidos para este cliente.</p>
            </section>
          </template>
        </aside>
      </div>
    </transition>
  </section>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';

const clientes = ref([]);
const loadingClientes = ref(false);
const error = ref('');
const showDetail = ref(false);
const selectedCliente = ref(null);
const detailData = ref(null);
const loadingDetail = ref(false);
const detailError = ref('');

const filters = ref({
  search: '',
  registro_desde: '',
  registro_hasta: '',
  ultima_visita_desde: '',
  segmento: '',
  nuevos_en: '',
  min_pedidos: null,
  gasto_min: null,
  gasto_max: null,
  sort: 'nombre_asc',
});

let timer = null;

const buildParams = () => ({
  search: filters.value.search || undefined,
  registro_desde: filters.value.registro_desde || undefined,
  registro_hasta: filters.value.registro_hasta || undefined,
  ultima_visita_desde: filters.value.ultima_visita_desde || undefined,
  segmento: filters.value.segmento || undefined,
  nuevos_en: filters.value.nuevos_en || undefined,
  min_pedidos: filters.value.min_pedidos ?? undefined,
  gasto_min: filters.value.gasto_min ?? undefined,
  gasto_max: filters.value.gasto_max ?? undefined,
  sort: filters.value.sort || undefined,
});

const loadClientes = async () => {
  loadingClientes.value = true;
  error.value = '';

  try {
  const response = await axios.get('/api/admin/clientes', {
  params: buildParams(),
  withCredentials: true
});
    clientes.value = response.data?.data ?? [];

    if (!selectedCliente.value && clientes.value.length > 0) {
      selectedCliente.value = clientes.value[0];
    }
  } catch (e) {
    error.value = e?.response?.data?.error?.message ?? 'No fue posible cargar los clientes.';
  } finally {
    loadingClientes.value = false;
  }
};

const onFilterChange = () => {
  clearTimeout(timer);
  timer = setTimeout(loadClientes, 260);
};

const resetFilters = () => {
  filters.value = {
    search: '',
    registro_desde: '',
    registro_hasta: '',
    ultima_visita_desde: '',
    segmento: '',
    nuevos_en: '',
    min_pedidos: null,
    gasto_min: null,
    gasto_max: null,
    sort: 'nombre_asc',
  };
  loadClientes();
};

const openDetail = async (cliente) => {
  selectedCliente.value = cliente;
  showDetail.value = true;
  detailData.value = null;
  detailError.value = '';
  loadingDetail.value = true;

  try {
    const response = await axios.get(`/api/admin/clientes/${cliente.id}/historial`, {
  withCredentials: true
});
    detailData.value = response.data?.data ?? null;
  } catch (e) {
    detailError.value = e?.response?.data?.error?.message ?? 'No se pudo cargar el detalle del cliente.';
  } finally {
    loadingDetail.value = false;
  }
};

const closeDetail = () => {
  showDetail.value = false;
};

const fmtMoney = (v) => Number(v || 0).toFixed(2);
const formatDate = (value) => {
  if (!value) return 'Sin datos';
  return new Date(value).toLocaleDateString('es-CO', { year: 'numeric', month: 'short', day: '2-digit' });
};
const labelTipo = (tipo) => ({ vip: 'VIP', frecuente: 'Frecuente', nuevo: 'Nuevo', inactivo: 'Inactivo' }[tipo] || 'Ocasional');

onMounted(loadClientes);
</script>

<style scoped>
.historial-page { display:grid; gap:16px; }
.panel { padding: 22px; }
.panel-head { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
.eyebrow { color:#ffd7aa; text-transform:uppercase; letter-spacing:.08em; font-size:12px; margin:0; }
.filters-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-top:16px; }
.field { display:grid; gap:5px; }
.field-actions { align-items:end; }
input, select { border:1px solid rgba(148,163,184,.3); border-radius:10px; background:#0f172a; color:#fff; padding:10px; }
.cards-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap:12px; }
.cliente-card { cursor:pointer; transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
.cliente-card:hover { transform: translateY(-2px); box-shadow: 0 14px 28px rgba(2,6,23,.42); }
.cliente-top { display:flex; justify-content:space-between; gap:8px; align-items:start; }
.cliente-top h3 { margin:0; }
.truncate { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cliente-metrics { display:grid; grid-template-columns: 1fr 1fr; gap:8px; margin-top:10px; }
.cliente-metrics dt { font-size:12px; color:#94a3b8; }
.cliente-metrics dd { margin:0; font-weight:600; color:#e2e8f0; }
.badge { text-transform:uppercase; border-radius:999px; padding:4px 10px; font-size:11px; font-weight:700; }
.badge-vip { background: rgba(245,158,11,.22); color:#fcd34d; }
.badge-frecuente { background: rgba(59,130,246,.2); color:#93c5fd; }
.badge-nuevo { background: rgba(34,197,94,.2); color:#86efac; }
.badge-inactivo { background: rgba(148,163,184,.24); color:#cbd5e1; }
.cliente-vip { border-color: rgba(245,158,11,.45); }
.cliente-frecuente { border-color: rgba(59,130,246,.4); }
.cliente-nuevo { border-color: rgba(34,197,94,.4); }
.overlay { position:fixed; inset:0; background: rgba(2,6,23,.7); backdrop-filter: blur(2px); display:flex; justify-content:flex-end; z-index:80; }
.detail-panel { width: min(860px, 96vw); height:100vh; overflow:auto; border-radius:0; border-left:1px solid rgba(148,163,184,.28); }
.detail-head { display:flex; justify-content:space-between; align-items:start; gap:12px; margin-bottom:10px; }
.detail-metrics { display:grid; grid-template-columns: repeat(auto-fit,minmax(150px,1fr)); gap:10px; margin:12px 0; }
.metric { padding:12px; }
.metric strong { font-size:20px; }
.detail-section { margin-top:14px; }
.simple-list, .products-list { list-style:none; padding:0; margin:0; display:grid; gap:6px; }
.simple-list li, .products-list li { display:flex; justify-content:space-between; gap:8px; }
.order-item { margin-top:10px; border:1px solid rgba(148,163,184,.2); border-radius:12px; overflow:hidden; }
summary { display:flex; justify-content:space-between; gap:8px; padding:10px; cursor:pointer; background: rgba(15,23,42,.9); }
.alert { padding:10px 12px; border-radius:10px; }
.alert-error { border:1px solid rgba(248,113,113,.4); color:#fecaca; background: rgba(127,29,29,.35); }
.muted { color:#94a3b8; }
.empty-state { grid-column: 1 / -1; text-align:center; }
.fade-enter-active, .fade-leave-active { transition: opacity .22s ease; }
.fade-enter-from, .fade-leave-to { opacity:0; }
</style>
