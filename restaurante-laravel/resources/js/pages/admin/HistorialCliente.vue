<template>
  <section class="historial-page">
    <header class="card panel">
      <div class="panel-head">
        <div>
          <p class="eyebrow">Analítica de fidelización</p>
          <h1>Historial y análisis de clientes</h1>
          <p class="muted">Panel inteligente para segmentar, entender y fidelizar clientes sin saturación visual.</p>
        </div>
        <button class="btn btn-secondary" :disabled="loadingClientes" @click="loadClientes">
          {{ loadingClientes ? 'Actualizando...' : 'Actualizar' }}
        </button>
      </div>

      <section class="kpi-grid" aria-label="Resumen de indicadores">
        <article class="card kpi-card">
          <p>Total clientes</p>
          <strong>{{ dashboardKPIs.totalClientes }}</strong>
        </article>
        <article class="card kpi-card">
          <p>Clientes VIP</p>
          <strong>{{ dashboardKPIs.totalVip }}</strong>
        </article>
        <article class="card kpi-card">
          <p>Ingreso total</p>
          <strong>${{ fmtMoney(dashboardKPIs.ingresoTotal) }}</strong>
        </article>
        <article class="card kpi-card">
          <p>Promedio por cliente</p>
          <strong>${{ fmtMoney(dashboardKPIs.promedioPorCliente) }}</strong>
        </article>
      </section>

      <div class="filters-grid main-filters">
        <div class="field field-search">
          <label>Búsqueda</label>
          <input v-model.trim="filters.search" type="text" placeholder="Nombre o correo" @input="onFilterChange" />
        </div>

        <div class="field">
          <label>Ordenar por</label>
          <select v-model="filters.sort" @change="onFilterChange">
            <option value="nombre_asc">Nombre A-Z</option>
            <option value="gasto_desc">Total gastado</option>
            <option value="pedidos_desc">Cantidad de pedidos</option>
            <option value="ultima_visita_desc">Última visita</option>
          </select>
        </div>

        <div class="field">
          <label>Tipo de cliente</label>
          <select v-model="filters.segmento" @change="onFilterChange">
            <option value="">Todos</option>
            <option value="vip">VIP</option>
            <option value="frecuente">Frecuentes</option>
            <option value="nuevo">Nuevos</option>
          </select>
        </div>

        <div class="field field-actions">
          <button class="btn btn-secondary" @click="toggleAdvancedFilters">
            {{ showAdvancedFilters ? 'Ocultar filtros' : 'Ver más filtros' }}
          </button>
        </div>
      </div>

      <transition name="fade">
        <div v-if="showAdvancedFilters" class="filters-grid advanced-filters">
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
            <label>Última visita hasta</label>
            <input v-model="filters.ultima_visita_hasta" type="date" @change="onFilterChange" />
          </div>

          <div class="field">
            <label>Mínimo de pedidos</label>
            <input v-model.number="filters.min_pedidos" type="number" min="0" placeholder="Ej: 5" @change="onFilterChange" />
          </div>

          <div class="field">
            <label>Gasto mínimo</label>
            <input v-model.number="filters.gasto_min" type="number" min="0" step="0.01" placeholder="0" @change="onFilterChange" />
          </div>

          <div class="field">
            <label>Gasto máximo</label>
            <input v-model.number="filters.gasto_max" type="number" min="0" step="0.01" placeholder="Sin límite" @change="onFilterChange" />
          </div>

          <div class="field field-actions">
            <button class="btn btn-secondary" @click="resetFilters">Limpiar filtros</button>
          </div>
        </div>
      </transition>
    </header>

    <p v-if="error" class="alert alert-error">{{ error }}</p>

    <section class="segment-section" v-if="sectionedClientes.vip.length">
      <div class="segment-header">
        <h2>Clientes VIP</h2>
        <span class="badge badge-vip">TOP {{ sectionedClientes.vip.length }}</span>
      </div>
      <div class="cards-grid vip-grid">
        <article
          v-for="cliente in sectionedClientes.vip"
          :key="cliente.id"
          class="card cliente-card cliente-card-vip"
          @click="openDetail(cliente)"
        >
          <div class="cliente-top">
            <h3>{{ cliente.nombre }}</h3>
            <span class="badge badge-vip">VIP</span>
          </div>
          <dl class="cliente-metrics">
            <div><dt>Total gastado</dt><dd>${{ fmtMoney(cliente.total_gastado) }}</dd></div>
            <div><dt>Pedidos</dt><dd>{{ cliente.cantidad_pedidos }}</dd></div>
            <div><dt>Última visita</dt><dd>{{ formatDate(cliente.ultima_visita) }}</dd></div>
          </dl>
        </article>
      </div>
    </section>

    <section class="segment-section" v-if="sectionedClientes.frecuentes.length">
      <div class="segment-header">
        <h2>Clientes frecuentes</h2>
        <span class="muted">{{ sectionedClientes.frecuentes.length }} en esta página</span>
      </div>
      <div class="cards-grid">
        <article
          v-for="cliente in sectionedClientes.frecuentes"
          :key="cliente.id"
          class="card cliente-card cliente-frecuente"
          @click="openDetail(cliente)"
        >
          <div class="cliente-top">
            <h3>{{ cliente.nombre }}</h3>
            <span class="badge badge-frecuente">Frecuente</span>
          </div>
          <dl class="cliente-metrics">
            <div><dt>Total gastado</dt><dd>${{ fmtMoney(cliente.total_gastado) }}</dd></div>
            <div><dt>Pedidos</dt><dd>{{ cliente.cantidad_pedidos }}</dd></div>
            <div><dt>Última visita</dt><dd>{{ formatDate(cliente.ultima_visita) }}</dd></div>
          </dl>
        </article>
      </div>
    </section>

    <section class="segment-section" v-if="sectionedClientes.nuevos.length">
      <div class="segment-header">
        <h2>Clientes nuevos</h2>
        <span class="badge badge-nuevo">NUEVO</span>
      </div>
      <div class="cards-grid">
        <article
          v-for="cliente in sectionedClientes.nuevos"
          :key="cliente.id"
          class="card cliente-card cliente-nuevo"
          @click="openDetail(cliente)"
        >
          <div class="cliente-top">
            <h3>{{ cliente.nombre }}</h3>
            <span class="badge badge-nuevo">NUEVO</span>
          </div>
          <dl class="cliente-metrics">
            <div><dt>Total gastado</dt><dd>${{ fmtMoney(cliente.total_gastado) }}</dd></div>
            <div><dt>Pedidos</dt><dd>{{ cliente.cantidad_pedidos }}</dd></div>
            <div><dt>Última visita</dt><dd>{{ formatDate(cliente.ultima_visita) }}</dd></div>
          </dl>
        </article>
      </div>
    </section>

    <section class="segment-section" v-if="guestSummary.totalPedidos">
      <div class="segment-header">
        <h2>Clientes invitados</h2>
        <span class="muted">Consolidado</span>
      </div>
      <article class="card guest-card">
        <div class="guest-metrics">
          <div><dt>Total pedidos</dt><dd>{{ guestSummary.totalPedidos }}</dd></div>
          <div><dt>Total gastado</dt><dd>${{ fmtMoney(guestSummary.totalGastado) }}</dd></div>
          <div><dt>Promedio</dt><dd>${{ fmtMoney(guestSummary.promedio) }}</dd></div>
        </div>
        <button class="btn btn-secondary" @click="openGuestDetail">Ver detalle</button>
      </article>
    </section>

    <article v-if="!loadingClientes && !hasVisibleData" class="card empty-state">
      <h3>Sin resultados con los filtros actuales</h3>
      <p class="muted">Prueba ajustando los filtros para ver más clientes.</p>
    </article>

    <footer v-if="totalPages > 1" class="card pagination-wrap">
      <button class="btn btn-secondary" :disabled="currentPage === 1" @click="setPage(currentPage - 1)">Anterior</button>
      <p>Página {{ currentPage }} de {{ totalPages }}</p>
      <button class="btn btn-secondary" :disabled="currentPage === totalPages" @click="setPage(currentPage + 1)">Siguiente</button>
    </footer>

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
              <h4>Información completa</h4>
              <p class="muted">Fecha de registro: {{ formatDate(selectedCliente?.fecha_registro || detailData.cliente?.created_at) }}</p>
              <p class="muted">Última visita: {{ formatDate(detailData.resumen.ultima_visita) }}</p>
            </section>

            <section class="detail-metrics">
              <article class="card metric"><p>Total gastado</p><strong>${{ fmtMoney(detailData.resumen.total_gastado) }}</strong></article>
              <article class="card metric"><p>Cantidad de pedidos</p><strong>{{ detailData.resumen.cantidad_pedidos }}</strong></article>
              <article class="card metric"><p>Promedio por pedido</p><strong>${{ fmtMoney(detailData.resumen.ticket_promedio) }}</strong></article>
              <article class="card metric"><p>Frecuencia visitas</p><strong>{{ detailData.analisis.frecuencia_visitas_dias ?? '-' }} días</strong></article>
            </section>

            <section class="detail-section">
              <h4>Productos más pedidos</h4>
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
import { computed, onMounted, ref } from 'vue';

const clientes = ref([]);
const loadingClientes = ref(false);
const error = ref('');
const showDetail = ref(false);
const selectedCliente = ref(null);
const detailData = ref(null);
const loadingDetail = ref(false);
const detailError = ref('');
const showAdvancedFilters = ref(false);
const currentPage = ref(1);
const pageSize = 12;

const filters = ref({
  search: '',
  registro_desde: '',
  registro_hasta: '',
  ultima_visita_desde: '',
  ultima_visita_hasta: '',
  segmento: '',
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
  min_pedidos: filters.value.min_pedidos ?? undefined,
  gasto_min: filters.value.gasto_min ?? undefined,
  gasto_max: filters.value.gasto_max ?? undefined,
  sort: normalizeServerSort(filters.value.sort),
});

const normalizeServerSort = (sortValue) => {
  if (sortValue === 'ultima_visita_desc') return 'registro_desc';
  return sortValue || 'nombre_asc';
};

const isGuest = (cliente) => {
  const tipo = String(cliente?.tipo_cliente || '').toLowerCase();
  if (tipo.includes('invit')) return true;
  const nombre = String(cliente?.nombre || '').toLowerCase();
  return nombre === 'invitado' || nombre === 'cliente invitado';
};

const normalizedClientes = computed(() =>
  (clientes.value || []).map((cliente) => ({
    ...cliente,
    tipo_cliente: isGuest(cliente) ? 'invitado' : (cliente.tipo_cliente || 'frecuente'),
  })),
);

const filteredClientes = computed(() => {
  const minDate = filters.value.ultima_visita_desde ? new Date(`${filters.value.ultima_visita_desde}T00:00:00`) : null;
  const maxDate = filters.value.ultima_visita_hasta ? new Date(`${filters.value.ultima_visita_hasta}T23:59:59`) : null;

  let data = normalizedClientes.value.filter((cliente) => {
    if (cliente.tipo_cliente === 'invitado') return true;
    const pedidos = Number(cliente.cantidad_pedidos || 0);
    const gasto = Number(cliente.total_gastado || 0);
    const visita = cliente.ultima_visita ? new Date(cliente.ultima_visita) : null;

    if (filters.value.min_pedidos !== null && filters.value.min_pedidos !== '' && pedidos < Number(filters.value.min_pedidos)) return false;
    if (filters.value.gasto_min !== null && filters.value.gasto_min !== '' && gasto < Number(filters.value.gasto_min)) return false;
    if (filters.value.gasto_max !== null && filters.value.gasto_max !== '' && gasto > Number(filters.value.gasto_max)) return false;
    if (minDate && (!visita || visita < minDate)) return false;
    if (maxDate && (!visita || visita > maxDate)) return false;
    return true;
  });

  data = [...data].sort((a, b) => {
    const aName = String(a.nombre || '').toLowerCase();
    const bName = String(b.nombre || '').toLowerCase();
    const sorters = {
      nombre_asc: () => aName.localeCompare(bName),
      gasto_desc: () => Number(b.total_gastado || 0) - Number(a.total_gastado || 0),
      pedidos_desc: () => Number(b.cantidad_pedidos || 0) - Number(a.cantidad_pedidos || 0),
      ultima_visita_desc: () => new Date(b.ultima_visita || 0).getTime() - new Date(a.ultima_visita || 0).getTime(),
    };
    const fn = sorters[filters.value.sort] || sorters.nombre_asc;
    return fn();
  });

  return data;
});

const nonGuestClientes = computed(() => filteredClientes.value.filter((cliente) => cliente.tipo_cliente !== 'invitado'));
const guestClientes = computed(() => filteredClientes.value.filter((cliente) => cliente.tipo_cliente === 'invitado'));

const totalPages = computed(() => Math.max(1, Math.ceil(nonGuestClientes.value.length / pageSize)));

const paginatedClientes = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  return nonGuestClientes.value.slice(start, start + pageSize);
});

const sectionedClientes = computed(() => {
  const vipSorted = [...paginatedClientes.value]
    .filter((cliente) => cliente.tipo_cliente === 'vip')
    .sort((a, b) => Number(b.total_gastado || 0) - Number(a.total_gastado || 0));

  const vip = vipSorted.slice(0, 4);
  const vipIds = new Set(vip.map((c) => c.id));

  const frecuentes = paginatedClientes.value.filter(
    (cliente) => (cliente.tipo_cliente === 'frecuente' || (Number(cliente.cantidad_pedidos || 0) > 1 && cliente.tipo_cliente !== 'nuevo')) && !vipIds.has(cliente.id),
  );

  const nuevos = paginatedClientes.value.filter((cliente) => cliente.tipo_cliente === 'nuevo' && !vipIds.has(cliente.id));

  return { vip, frecuentes, nuevos };
});

const guestSummary = computed(() => {
  const totalPedidos = guestClientes.value.reduce((acc, c) => acc + Number(c.cantidad_pedidos || 0), 0);
  const totalGastado = guestClientes.value.reduce((acc, c) => acc + Number(c.total_gastado || 0), 0);
  return {
    totalPedidos,
    totalGastado,
    promedio: totalPedidos ? totalGastado / totalPedidos : 0,
  };
});

const dashboardKPIs = computed(() => {
  const visibles = nonGuestClientes.value;
  const ingresoTotal = visibles.reduce((acc, c) => acc + Number(c.total_gastado || 0), 0);
  const totalVip = visibles.filter((c) => c.tipo_cliente === 'vip').length;
  return {
    totalClientes: visibles.length,
    totalVip,
    ingresoTotal,
    promedioPorCliente: visibles.length ? ingresoTotal / visibles.length : 0,
  };
});

const hasVisibleData = computed(
  () => sectionedClientes.value.vip.length || sectionedClientes.value.frecuentes.length || sectionedClientes.value.nuevos.length || guestSummary.value.totalPedidos,
);

const loadClientes = async () => {
  loadingClientes.value = true;
  error.value = '';

  try {
    const response = await axios.get('/api/admin/clientes', { params: buildParams() });
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
  currentPage.value = 1;
  clearTimeout(timer);
  timer = setTimeout(loadClientes, 260);
};

const toggleAdvancedFilters = () => {
  showAdvancedFilters.value = !showAdvancedFilters.value;
};

const setPage = (page) => {
  currentPage.value = Math.min(Math.max(page, 1), totalPages.value);
};

const resetFilters = () => {
  filters.value = {
    search: '',
    registro_desde: '',
    registro_hasta: '',
    ultima_visita_desde: '',
    ultima_visita_hasta: '',
    segmento: '',
    min_pedidos: null,
    gasto_min: null,
    gasto_max: null,
    sort: 'nombre_asc',
  };
  currentPage.value = 1;
  loadClientes();
};

const openDetail = async (cliente) => {
  selectedCliente.value = cliente;
  showDetail.value = true;
  detailData.value = null;
  detailError.value = '';
  loadingDetail.value = true;

  try {
    const response = await axios.get(`/api/admin/clientes/${cliente.id}/historial`);
    detailData.value = response.data?.data ?? null;
  } catch (e) {
    detailError.value = e?.response?.data?.error?.message ?? 'No se pudo cargar el detalle del cliente.';
  } finally {
    loadingDetail.value = false;
  }
};

const openGuestDetail = () => {
  if (!guestClientes.value.length) return;
  openDetail(guestClientes.value[0]);
};

const closeDetail = () => {
  showDetail.value = false;
};

const fmtMoney = (v) => Number(v || 0).toFixed(2);
const formatDate = (value) => {
  if (!value) return 'Sin datos';
  return new Date(value).toLocaleDateString('es-CO', { year: 'numeric', month: 'short', day: '2-digit' });
};

onMounted(loadClientes);
</script>

<style scoped>
.historial-page { display:grid; gap:16px; }
.panel { padding:22px; display:grid; gap:14px; }
.panel-head { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
.eyebrow { color:#ffd7aa; text-transform:uppercase; letter-spacing:.08em; font-size:12px; margin:0; }
.kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px; }
.kpi-card { padding:14px; display:grid; gap:6px; background:rgba(15,23,42,.7); }
.kpi-card p { margin:0; color:#94a3b8; font-size:13px; }
.kpi-card strong { font-size:22px; color:#f8fafc; }
.filters-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px; }
.advanced-filters { padding-top:6px; border-top:1px solid rgba(148,163,184,.15); }
.field { display:grid; gap:5px; }
.field-actions { align-items:end; }
input, select { border:1px solid rgba(148,163,184,.3); border-radius:10px; background:#0f172a; color:#fff; padding:10px; }
.segment-section { display:grid; gap:10px; }
.segment-header { display:flex; justify-content:space-between; align-items:center; gap:8px; }
.segment-header h2 { margin:0; font-size:18px; }
.cards-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:12px; }
.vip-grid { grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); }
.cliente-card { cursor:pointer; transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
.cliente-card:hover { transform:translateY(-2px); box-shadow:0 14px 28px rgba(2,6,23,.42); }
.cliente-card-vip { border-color:rgba(245,158,11,.45); background:linear-gradient(160deg, rgba(245,158,11,.12), rgba(15,23,42,.88)); }
.cliente-top { display:flex; justify-content:space-between; gap:8px; align-items:start; }
.cliente-top h3 { margin:0; font-size:16px; }
.cliente-metrics { display:grid; grid-template-columns:1fr; gap:8px; margin-top:10px; }
.cliente-metrics dt { font-size:12px; color:#94a3b8; }
.cliente-metrics dd { margin:0; font-weight:600; color:#e2e8f0; }
.badge { text-transform:uppercase; border-radius:999px; padding:4px 10px; font-size:11px; font-weight:700; }
.badge-vip { background:rgba(245,158,11,.22); color:#fcd34d; }
.badge-frecuente { background:rgba(59,130,246,.2); color:#93c5fd; }
.badge-nuevo { background:rgba(34,197,94,.2); color:#86efac; }
.cliente-frecuente { border-color:rgba(59,130,246,.4); }
.cliente-nuevo { border-color:rgba(34,197,94,.4); }
.guest-card { display:flex; justify-content:space-between; align-items:end; flex-wrap:wrap; gap:12px; }
.guest-metrics { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; width:min(100%,560px); }
.guest-metrics dt { font-size:12px; color:#94a3b8; }
.guest-metrics dd { margin:0; font-size:20px; font-weight:700; color:#e2e8f0; }
.pagination-wrap { display:flex; justify-content:center; align-items:center; gap:12px; padding:12px; }
.overlay { position:fixed; inset:0; background:rgba(2,6,23,.7); backdrop-filter:blur(2px); display:flex; justify-content:flex-end; z-index:80; }
.detail-panel { width:min(860px,96vw); height:100vh; overflow:auto; border-radius:0; border-left:1px solid rgba(148,163,184,.28); }
.detail-head { display:flex; justify-content:space-between; align-items:start; gap:12px; margin-bottom:10px; }
.detail-metrics { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px; margin:12px 0; }
.metric { padding:12px; }
.metric strong { font-size:20px; }
.detail-section { margin-top:14px; }
.simple-list, .products-list { list-style:none; padding:0; margin:0; display:grid; gap:6px; }
.simple-list li, .products-list li { display:flex; justify-content:space-between; gap:8px; }
.order-item { margin-top:10px; border:1px solid rgba(148,163,184,.2); border-radius:12px; overflow:hidden; }
summary { display:flex; justify-content:space-between; gap:8px; padding:10px; cursor:pointer; background:rgba(15,23,42,.9); }
.alert { padding:10px 12px; border-radius:10px; }
.alert-error { border:1px solid rgba(248,113,113,.4); color:#fecaca; background:rgba(127,29,29,.35); }
.muted { color:#94a3b8; }
.empty-state { text-align:center; }
.fade-enter-active, .fade-leave-active { transition:opacity .22s ease; }
.fade-enter-from, .fade-leave-to { opacity:0; }
</style>
