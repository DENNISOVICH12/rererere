<!DOCTYPE html>

<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Modo Cocina PRO</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
:root {
  --bg: #090f1d;
  --panel: #121b2d;
  --panel-2: #0f1728;
  --line: rgba(255,255,255,.11);
  --text: #ecf1ff;
  --muted: #9aa6c4;
  --green: #22c55e;
  --yellow: #facc15;
  --red: #fb7185;
  --orange: #f97316;
  --blue: #38bdf8;
}
* { box-sizing: border-box; }
body {
  margin: 0;
  min-height: 100vh;
  background: radial-gradient(circle at top, #16223d, var(--bg) 60%);
  color: var(--text);
  font-family: Inter, "Segoe UI", system-ui, sans-serif;
}
.kds { min-height: 100vh; padding: 16px; display: flex; flex-direction: column; gap: 14px; }
.topbar { display: grid; grid-template-columns: 1.2fr auto auto; gap: 10px; align-items: center; }
.topbar h1 { margin: 0; font-size: 1.65rem; }
.muted { margin: 2px 0 0; color: var(--muted); }
.stats { display: grid; grid-template-columns: repeat(3, minmax(86px, 1fr)); gap: 8px; }
.stats article { background: rgba(255,255,255,.05); border: 1px solid var(--line); border-radius: 12px; padding: 8px; text-align: center; }
.stats span { color: var(--muted); font-size: .75rem; display: block; }
.stats strong { font-size: 1.1rem; }
.controls { display: flex; gap: 8px; }
.ghost { border: 1px solid rgba(255,255,255,.24); background: transparent; color: var(--text); border-radius: 10px; padding: 10px 12px; cursor: pointer; }
.error { margin: 0; padding: 10px; border-radius: 10px; color: #ffe4e6; border: 1px solid rgba(251,113,133,.45); background: rgba(159,18,57,.22); }
.toast {
  position: fixed;
  right: 16px;
  bottom: 16px;
  background: rgba(15, 23, 40, .95);
  border: 1px solid rgba(56, 189, 248, .45);
  color: #dbeafe;
  padding: 10px 12px;
  border-radius: 10px;
  z-index: 80;
}
.board { flex: 1; min-height: 0; display: grid; grid-template-columns: repeat(4, minmax(250px, 1fr)); gap: 10px; }
.col { background: var(--panel-2); border: 1px solid var(--line); border-radius: 14px; display: flex; flex-direction: column; min-height: 0; }
.col-head { padding: 12px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; }
.col-list { overflow-y: auto; min-height: 0; padding: 10px; display: flex; flex-direction: column; gap: 10px; }
.card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 12px; display: flex; flex-direction: column; gap: 10px; cursor: pointer; }
.card-selected { border-color: rgba(56,189,248,.88); box-shadow: 0 0 0 1px rgba(56,189,248,.32); }
.card-new { animation: glow 2s ease; }
.card-critical { border-color: rgba(251,113,133,.7); }
.card-head { display: flex; justify-content: space-between; align-items: center; }
.num { font-weight: 800; font-size: 1.15rem; }
.timer { border-radius: 999px; padding: 4px 10px; font-weight: 800; }

.t-ok { color: #4ade80; background: rgba(34,197,94,.18); }
.t-warn { color: #fde047; background: rgba(250,204,21,.18); }
.t-critical { color: var(--red); background: rgba(251,113,133,.17); animation: pulseRed 1.2s infinite; }
.items { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 6px; }
.items li { display: flex; gap: 8px; align-items: baseline; }
.qty { min-width: 42px; color: var(--yellow); font-weight: 900; font-size: 1.18rem; }
.name { font-weight: 600; }
.note { margin: 0; padding: 8px; border-radius: 8px; background: rgba(250,204,21,.14); color: #fde68a; font-size: .9rem; }
.action { width: 100%; border: none; border-radius: 12px; padding: 16px 12px; font-size: 1.05rem; font-weight: 900; cursor: pointer; }

.action[disabled] { opacity: .55; cursor: not-allowed; }
.action-start { background: var(--orange); color: #fff; }
.action-ready { background: var(--green); color: #082610; }
.action-deliver { background: var(--blue); color: #07293b; }
.fade-enter-active, .fade-leave-active, .fade-move { transition: all .32s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(8px); }

/* Drawer */
.drawer-overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, .52);
  backdrop-filter: blur(2px);
  display: flex;
  justify-content: flex-end;
  z-index: 90;
}
.drawer {
  width: min(520px, 100vw);
  height: 100%;
  background: linear-gradient(180deg, #121b2d, #0f1728);
  border-left: 1px solid var(--line);
  padding: 14px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.drawer-head { display: flex; justify-content: space-between; align-items: center; }
.drawer-title { margin: 0; font-size: 1.5rem; }
.badge {
  border: 1px solid transparent;
  border-radius: 999px;
  font-size: .82rem;
  font-weight: 700;
  padding: 4px 10px;
}
.b-pendiente { background: rgba(250,204,21,.15); color: #fde047; border-color: rgba(250,204,21,.5); }
.b-preparando { background: rgba(249,115,22,.15); color: #fdba74; border-color: rgba(249,115,22,.5); }
.b-listo { background: rgba(34,197,94,.15); color: #86efac; border-color: rgba(34,197,94,.45); }
.b-entregado { background: rgba(56,189,248,.15); color: #7dd3fc; border-color: rgba(56,189,248,.45); }
.ticket { background: rgba(2, 6, 23, .33); border: 1px solid var(--line); border-radius: 12px; padding: 12px; }
.ticket-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.ticket-grid p { margin: 0; color: #dbe6ff; }
.priority-pill {
  margin-top: 6px;
  display: inline-block;
  border-radius: 999px;
  background: rgba(251,113,133,.15);
  color: var(--red);
  border: 1px solid rgba(251,113,133,.5);
  font-size: .8rem;
  padding: 4px 9px;
  animation: pulseRed 1.2s infinite;
}
.item-row { border-bottom: 1px dashed rgba(255,255,255,.14); padding: 8px 0; }
.item-row:last-child { border-bottom: 0; }
.item-main { display: flex; gap: 8px; align-items: baseline; }
.item-extra, .item-note { margin: 4px 0 0 0; color: var(--muted); font-size: .9rem; }
.drawer-items {
  max-height: 46vh;
  overflow-y: auto;
  padding-right: 2px;
}
.items-summary {
  margin: 0 0 8px 0;
  color: var(--muted);
  font-size: .87rem;
}
.category-title {
  margin: 10px 0 4px 0;
  color: #bfdbfe;
  font-size: .82rem;
  text-transform: uppercase;
  letter-spacing: .06em;
}

.drawer-actions { display: grid; gap: 8px; }
.secondary-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.sec-btn { border: 1px solid rgba(255,255,255,.2); background: transparent; color: var(--text); border-radius: 10px; padding: 9px 10px; cursor: pointer; }
.drawer-slide-enter-active, .drawer-slide-leave-active { transition: all .25s ease; }
.drawer-slide-enter-from, .drawer-slide-leave-to { opacity: 0; }
.drawer-slide-enter-from .drawer, .drawer-slide-leave-to .drawer { transform: translateX(28px); }

@keyframes glow {
  0% { box-shadow: 0 0 0 rgba(56,189,248,0); }
  50% { box-shadow: 0 0 26px rgba(56,189,248,.46); }
  100% { box-shadow: 0 0 0 rgba(56,189,248,0); }
}
@keyframes pulseRed {
  0%,100% { box-shadow: 0 0 0 rgba(251,113,133,0); }
  50% { box-shadow: 0 0 18px rgba(251,113,133,.45); }
}
@media (max-width: 1280px) {
  .topbar { grid-template-columns: 1fr; }
  .board { grid-template-columns: repeat(2, minmax(250px, 1fr)); }
}
@media (max-width: 840px) {
  .board { grid-template-columns: 1fr; }
  .drawer { width: 100vw; }

}
</style>
</head>
<body>
<div id="app" class="kds">
  <header class="topbar">
    <div>
      <h1>Modo Cocina PRO</h1>
      <p class="muted">Velocidad > lectura · KDS profesional</p>
    </div>

    <div class="stats">
      <article><span>Activos</span><strong>@{{ activeCount }}</strong></article>
      <article><span>Promedio</span><strong>@{{ averageMinutes.toFixed(1) }}m</strong></article>
      <article><span>Atrasados</span><strong style="color: var(--red)">@{{ delayedCount }}</strong></article>
    </div>

    <div class="controls">
      <button class="ghost" @click="soundEnabled = !soundEnabled">@{{ soundEnabled ? '🔊 Sonido ON' : '🔈 Sonido OFF' }}</button>
      <button class="ghost" @click="toggleFullscreen">⛶ Pantalla completa</button>
    </div>
  </header>

  <p v-if="error" class="error">@{{ error }}</p>

  <div class="board">
    <section v-for="column in columns" :key="column.key" class="col">
      <header class="col-head">
        <strong>@{{ column.title }}</strong>
        <span>@{{ grouped[column.key].length }}</span>
      </header>

      <transition-group name="fade" tag="div" class="col-list">
        <article
          v-for="order in grouped[column.key]"
          :key="order.id"
          :id="`order-${order.id}`"
          class="card"
          :class="{
            'card-new': highlightedIds.has(order.id),
            'card-critical': order._elapsedMin > 6,
            'card-selected': selectedOrderId === order.id,
          }"
          @click="openOrderDetails(order)"
        >
          <header class="card-head">
            <span class="num">#@{{ order.id }}</span>
            <span class="timer" :class="timerClass(order)">@{{ formatElapsed(order._elapsedMs) }}</span>
          </header>

          <ul class="items">
            <li v-for="(item, idx) in (order.items || []).slice(0, 4)" :key="item.id || `${order.id}-${idx}`">
              <span class="qty">@{{ item.cantidad ?? item.quantity ?? 1 }}x</span>
              <span class="name">@{{ item.nombre ?? item.menu_item?.nombre ?? item.menuItem?.nombre ?? 'Item' }}</span>
            </li>
          </ul>

          <p v-if="order.notas" class="note">📝 @{{ order.notas }}</p>

          <button
            v-if="actionFor(order)"
            class="action"
            :class="actionFor(order).className"
            :disabled="processingIds.has(order.id)"
            @click.stop="actionFor(order).run()"
          >
            @{{ processingIds.has(order.id) ? 'Procesando...' : actionFor(order).label }}
          </button>
        </article>
      </transition-group>
    </section>
  </div>


  <order-details-drawer
    :open="drawerOpen"
    :order="selectedOrder"
    :priority-overrides="priorityOverrides"
    @close="closeOrderDetails"
    @action-done="handleActionDone"
    @priority-toggle="togglePriority"
    @toast="showToast"
  />

  <div v-if="toastMessage" class="toast">@{{ toastMessage }}</div>
</div>

<script src="https://unpkg.com/vue@3"></script>
<script>
const POLLING_MS = 4000;
const DELIVERED_HIDE_MS = 15 * 60 * 1000;

const OrderDetailsDrawer = {
  props: {
    order: { type: Object, default: null },
    open: { type: Boolean, default: false },
    priorityOverrides: { type: Object, default: () => ({}) },
  },
  emits: ['close', 'actionDone', 'priorityToggle', 'toast'],
  data() {
    return {
      loadingAction: false,
    };
  },
  computed: {
    hasOrder() {
      return !!this.order;
    },
    statusClass() {
      const status = this.order?.estado;
      if (!status) return '';
      return `b-${status}`;
    },
    isOverdue() {
      return this.order ? this.order._elapsedMin > 6 : false;
    },
    isPriority() {
      if (!this.order) return false;
      return this.isOverdue || !!this.priorityOverrides[this.order.id];
    },
    normalizedItems() {
      const source = this.order?.items || this.order?.detalles || [];
      if (!Array.isArray(source)) return [];

      return source.map((item, idx) => {
        const qty = Number(item.cantidad ?? item.quantity ?? 1) || 1;
        const name = item.nombre ?? item.menu_item?.nombre ?? item.menuItem?.nombre ?? item.producto?.nombre ?? 'Item';
        const extrasRaw = item.extras ?? item.opciones ?? item.options ?? item.adiciones ?? null;
        const notesRaw = item.notas ?? item.nota ?? item.note ?? null;
        const categoryRaw = item.categoria ?? item.category ?? item.tipo ?? item.menu_item?.categoria ?? item.menuItem?.categoria ?? null;

        const extras = Array.isArray(extrasRaw) ? extrasRaw.join(', ') : extrasRaw;
        const note = Array.isArray(notesRaw) ? notesRaw.join(' · ') : notesRaw;
        const category = String(categoryRaw || '').trim();

        return {
          id: item.id || `${this.order?.id || 'o'}-${idx}`,
          qty,
          name,
          extras: extras || '',
          note: note || '',
          category: category || 'General',
        };
      });
    },
    groupedItems() {
      const groups = {};
      this.normalizedItems.forEach((item) => {
        if (!groups[item.category]) groups[item.category] = [];
        groups[item.category].push(item);
      });
      return groups;
    },
    totalItemsCount() {
      return this.normalizedItems.reduce((acc, item) => acc + item.qty, 0);
    },
    delayLabel() {
      if (!this.order) return '';
      if (this.order._elapsedMin <= 6) return '';
      return `Atrasado +${Math.floor(this.order._elapsedMin - 6)} min`;
    },

    primaryAction() {
      if (!this.order) return null;
      if (this.order.estado === 'pendiente') return { label: '🔥 COMENZAR', next: 'preparando', className: 'action action-start' };
      if (this.order.estado === 'preparando') return { label: '✅ MARCAR LISTO', next: 'listo', className: 'action action-ready' };
      if (this.order.estado === 'listo') return { label: '📦 ENTREGAR', next: 'entregado', className: 'action action-deliver' };
      return null;
    },
  },
  methods: {
    statusLabel(status) {
      return {
        pendiente: 'Pendiente',
        preparando: 'En preparación',
        listo: 'Listo',
        entregado: 'Entregado',
      }[status] || status;
    },
    fmtTime(dateRaw) {
      if (!dateRaw) return '-';
      return new Date(dateRaw).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
    },
    fmtDate(dateRaw) {
      if (!dateRaw) return '-';
      return new Date(dateRaw).toLocaleString('es-CO', {
        year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit'
      });
    },
    formatElapsed(ms) {
      const sec = Math.floor(ms / 1000);
      const m = String(Math.floor(sec / 60)).padStart(2, '0');
      const s = String(sec % 60).padStart(2, '0');
      return `${m}:${s}`;
    },
    endpointFor(nextStatus, orderId) {
      if (nextStatus === 'preparando') return `/api/kitchen/orders/${orderId}/start`;
      if (nextStatus === 'listo') return `/api/kitchen/orders/${orderId}/ready`;
      if (nextStatus === 'entregado') return `/api/kitchen/orders/${orderId}/deliver`;
      return `/pedidos/${orderId}/estado`;
    },
    async executePrimaryAction() {
      if (!this.primaryAction || !this.order || this.loadingAction) return;
      const token = document.querySelector('meta[name="csrf-token"]')?.content;
      this.loadingAction = true;

      try {
        const endpoint = this.endpointFor(this.primaryAction.next, this.order.id);
        let res = await fetch(endpoint, {
          method: endpoint.startsWith('/api/kitchen') ? 'PATCH' : 'PUT',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token,
          },
          body: endpoint.startsWith('/api/kitchen') ? undefined : JSON.stringify({ estado: this.primaryAction.next }),
        });

        if (!res.ok && endpoint.startsWith('/api/kitchen')) {
          const fallback = `/pedidos/${this.order.id}/estado`;
          res = await fetch(fallback, {
            method: 'PUT',
            credentials: 'include',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({ estado: this.primaryAction.next }),
          });
        }

        if (!res.ok) throw new Error('status ' + res.status);

        this.$emit('actionDone', { orderId: this.order.id, nextStatus: this.primaryAction.next });
        this.$emit('toast', '✅ Pedido actualizado');
      } catch (e) {
        this.$emit('toast', '⚠️ No se pudo actualizar el pedido');
      } finally {
        this.loadingAction = false;
      }
    },
    copySummary() {
      if (!this.order) return;
      const lines = this.normalizedItems.map((item) => `- ${item.qty}x ${item.name}`);

      const summary = `Pedido #${this.order.id}\nEstado: ${this.statusLabel(this.order.estado)}\n${lines.join('\n')}\nNotas: ${this.order.notas || 'Sin notas'}`;
      navigator.clipboard?.writeText(summary);
      this.$emit('toast', '📋 Resumen copiado');
    },
    printTicket() {
      this.$emit('toast', '🖨️ Impresión pendiente de integrar');
    },
    onEsc(evt) {
      if (evt.key === 'Escape' && this.open) this.$emit('close');
      if (evt.key === 'Enter' && this.open) this.executePrimaryAction();
    },
  },
  mounted() {
    window.addEventListener('keydown', this.onEsc);
  },
  beforeUnmount() {
    window.removeEventListener('keydown', this.onEsc);
  },
  template: `
    <transition name="drawer-slide">
      <div v-if="open" class="drawer-overlay" @click.self="$emit('close')">
        <aside class="drawer">
          <header class="drawer-head">
            <h2 class="drawer-title">Pedido #{{ order?.id || '-' }}</h2>
            <button class="ghost" @click="$emit('close')">✕</button>
          </header>

          <section v-if="hasOrder" class="ticket">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
              <span class="badge" :class="statusClass">{{ statusLabel(order.estado) }}</span>
              <span class="timer" :class="order._elapsedMin > 6 ? 't-critical' : (order._elapsedMin >= 3 ? 't-warn' : 't-ok')">{{ formatElapsed(order._elapsedMs) }}</span>
            </div>

            <div class="ticket-grid" style="margin-top:10px;">
              <p><strong>Creado:</strong> {{ fmtDate(order.created_at) }}</p>
              <p><strong>Hora:</strong> {{ fmtTime(order.created_at) }}</p>
              <p><strong>Mesa:</strong> {{ order.mesa || '-' }}</p>
              <p><strong>Cliente:</strong> {{ order.cliente?.nombre || order.cliente_nombre || '-' }}</p>
            </div>

            <span v-if="isPriority" class="priority-pill">⚠ Prioridad alta · {{ delayLabel || "Pedido priorizado" }}</span>
          </section>

          <section v-if="hasOrder" class="ticket">
            <h3 style="margin:0 0 8px 0;">Items</h3>
            <p class="items-summary">{{ normalizedItems.length }} líneas · {{ totalItemsCount }} unidades</p>

            <div class="drawer-items">
              <template v-for="(categoryItems, categoryName) in groupedItems" :key="categoryName">
                <h4 class="category-title" v-if="categoryName && categoryName !== 'General'">{{ categoryName }}</h4>
                <article class="item-row" v-for="item in categoryItems" :key="item.id">
                  <div class="item-main">
                    <span class="qty">{{ item.qty }}x</span>
                    <strong>{{ item.name }}</strong>
                  </div>
                  <p v-if="item.extras" class="item-extra">➕ {{ item.extras }}</p>
                  <p v-if="item.note" class="item-note">📝 {{ item.note }}</p>
                </article>
              </template>
            </div>

          </section>

          <section v-if="hasOrder && order.notas" class="ticket">
            <h3 style="margin:0 0 8px 0;">Notas</h3>
            <p class="note">{{ order.notas }}</p>

          </section>

          <section class="ticket drawer-actions" v-if="hasOrder">
            <button v-if="primaryAction" :class="primaryAction.className" :disabled="loadingAction" @click="executePrimaryAction">
              {{ loadingAction ? 'Procesando...' : primaryAction.label }}

            </button>
            <p v-else class="muted" style="margin:0;">✅ Finalizado</p>

            <div class="secondary-actions">
              <button class="sec-btn" @click="printTicket">Imprimir</button>
              <button class="sec-btn" @click="copySummary">Copiar resumen</button>
              <button class="sec-btn" @click="$emit('priorityToggle', order.id)">Marcar prioridad</button>
              <button class="sec-btn" @click="$emit('close')">Volver al tablero</button>
            </div>
          </section>
        </aside>
      </div>
    </transition>
  `,
};

Vue.createApp({
  components: { OrderDetailsDrawer },
  data() {
    return {
      orders: [],
      nowTs: Date.now(),
      error: '',
      soundEnabled: true,
      highlightedIds: new Set(),
      processingIds: new Set(),
      selectedOrderId: null,
      drawerOpen: false,
      priorityOverrides: {},
      toastMessage: '',
      columns: [
        { key: 'pendiente', title: '🟡 Pendientes' },
        { key: 'preparando', title: '🔥 En preparación' },
        { key: 'listo', title: '✅ Listos' },
        { key: 'entregado', title: '📦 Entregados' },
      ],
      pollHandle: null,
      tickHandle: null,
      toastHandle: null,
    };
  },
  computed: {
    normalized() {
      return this.orders.map((order) => {
        const ts = new Date(order.created_at).getTime();
        const elapsedMs = Math.max(this.nowTs - ts, 0);
        const status = String(order.estado || '').toLowerCase();
        return {
          ...order,
          estado: status,
          notas: order.notas || order.note || '',
          items: order.items || order.detalles || [],
          _createdTs: ts,
          _elapsedMs: elapsedMs,
          _elapsedMin: elapsedMs / 60000,
          _urgency: (elapsedMs / 60000) + (status === 'pendiente' ? 2 : 0) + (this.priorityOverrides[order.id] ? 2 : 0),
        };
      }).filter((order) => {
        if (order.estado !== 'entregado') return true;
        return (this.nowTs - order._createdTs) < DELIVERED_HIDE_MS;
      });
    },
    grouped() {
      const groups = { pendiente: [], preparando: [], listo: [], entregado: [] };
      this.normalized.forEach((order) => { if (groups[order.estado]) groups[order.estado].push(order); });
      groups.pendiente.sort((a, b) => b._urgency - a._urgency || b._createdTs - a._createdTs);
      groups.preparando.sort((a, b) => b._urgency - a._urgency || b._createdTs - a._createdTs);
      groups.listo.sort((a, b) => b._createdTs - a._createdTs);
      groups.entregado.sort((a, b) => b._createdTs - a._createdTs);
      return groups;
    },
    selectedOrder() {
      if (!this.selectedOrderId) return null;
      return this.normalized.find((order) => order.id === this.selectedOrderId) || null;
    },
    activeCount() {
      return this.grouped.pendiente.length + this.grouped.preparando.length + this.grouped.listo.length;
    },
    delayedCount() {
      return this.normalized.filter((order) => order.estado !== 'entregado' && order._elapsedMin > 6).length;
    },
    averageMinutes() {
      const active = this.normalized.filter((order) => order.estado !== 'entregado');
      if (!active.length) return 0;
      return active.reduce((acc, order) => acc + order._elapsedMin, 0) / active.length;
    },
  },
  methods: {
    showToast(message) {
      this.toastMessage = message;
      clearTimeout(this.toastHandle);
      this.toastHandle = setTimeout(() => { this.toastMessage = ''; }, 2200);
    },
    togglePriority(orderId) {
      this.priorityOverrides = {
        ...this.priorityOverrides,
        [orderId]: !this.priorityOverrides[orderId],
      };
      this.showToast('🚩 Prioridad actualizada');
    },
    formatElapsed(ms) {
      const sec = Math.floor(ms / 1000);
      const m = String(Math.floor(sec / 60)).padStart(2, '0');
      const s = String(sec % 60).padStart(2, '0');
      return `${m}:${s}`;
    },
    timerClass(order) {
      if (order._elapsedMin > 6) return 't-critical';
      if (order._elapsedMin >= 3) return 't-warn';
      return 't-ok';
    },
    actionFor(order) {
      if (order.estado === 'pendiente') return { label: '🔥 COMENZAR', className: 'action-start', run: () => this.quickAction(order.id, 'preparando') };
      if (order.estado === 'preparando') return { label: '✅ MARCAR LISTO', className: 'action-ready', run: () => this.quickAction(order.id, 'listo') };
      if (order.estado === 'listo') return { label: '📦 ENTREGAR', className: 'action-deliver', run: () => this.quickAction(order.id, 'entregado') };
      return null;
    },
    async quickAction(orderId, nextStatus) {
      if (this.processingIds.has(orderId)) return;
      const set = new Set(this.processingIds); set.add(orderId); this.processingIds = set;

      const token = document.querySelector('meta[name="csrf-token"]')?.content;
      let endpoint = '/pedidos/' + orderId + '/estado';
      let method = 'PUT';
      let body = JSON.stringify({ estado: nextStatus });

      if (nextStatus === 'preparando') { endpoint = `/api/kitchen/orders/${orderId}/start`; method = 'PATCH'; body = undefined; }
      if (nextStatus === 'listo') { endpoint = `/api/kitchen/orders/${orderId}/ready`; method = 'PATCH'; body = undefined; }
      if (nextStatus === 'entregado') { endpoint = `/api/kitchen/orders/${orderId}/deliver`; method = 'PATCH'; body = undefined; }

      try {
        let res = await fetch(endpoint, {
          method,
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token,
          },
          body,
        });

        if (!res.ok && endpoint.startsWith('/api/kitchen')) {
          res = await fetch(`/pedidos/${orderId}/estado`, {
            method: 'PUT',
            credentials: 'include',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({ estado: nextStatus }),
          });
        }

        if (!res.ok) throw new Error('status ' + res.status);

        this.orders = this.orders.map((order) => order.id === orderId ? { ...order, estado: nextStatus } : order);
        this.showToast('✅ Pedido actualizado');
      } catch (e) {
        this.error = 'No se pudo actualizar el estado del pedido';
      } finally {
        const done = new Set(this.processingIds); done.delete(orderId); this.processingIds = done;
      }
    },
    openOrderDetails(order) {
      this.selectedOrderId = order.id;
      this.drawerOpen = true;
    },
    closeOrderDetails() {
      this.drawerOpen = false;
      this.selectedOrderId = null;
    },
    handleActionDone(payload) {
      this.orders = this.orders.map((order) => order.id === payload.orderId ? { ...order, estado: payload.nextStatus } : order);
      this.fetchOrders(false);
    },
    async fetchOrders(isInitial = false) {
      try {
        const beforeIds = new Set(this.orders.map((o) => o.id));
        let response = await fetch('/api/kitchen/orders', {
          credentials: 'include',
          headers: { 'Accept': 'application/json' },
        });

        if (!response.ok) {
          response = await fetch('/pedidos', {
            credentials: 'include',
            headers: { 'Accept': 'application/json' },
          });
        }

        if (!response.ok) throw new Error('status ' + response.status);

        const payload = await response.json();
        const incoming = payload?.data ?? payload ?? [];
        this.orders = Array.isArray(incoming) ? incoming : [];
        this.error = '';


        if (!isInitial) {
          const newOrders = this.orders.filter((o) => !beforeIds.has(o.id) && String(o.estado || '').toLowerCase() === 'pendiente');
          this.handleNewOrders(newOrders);
        }
      } catch (e) {
        this.error = 'No se pudo sincronizar la cocina';
      }
    },
    handleNewOrders(newOrders) {
      if (!newOrders.length) return;
      if (this.soundEnabled) this.beep();
      const next = new Set(this.highlightedIds);
      newOrders.forEach((order) => {
        next.add(order.id);
        this.$nextTick(() => {
          const el = document.getElementById(`order-${order.id}`);
          el?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
      });
      this.highlightedIds = next;
      setTimeout(() => {
        const clean = new Set(this.highlightedIds);
        newOrders.forEach((order) => clean.delete(order.id));
        this.highlightedIds = clean;
      }, 3600);
    },
    beep() {
      const Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return;
      const ctx = new Ctx();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.frequency.value = 880;
      gain.gain.value = 0.05;
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      setTimeout(() => { osc.stop(); ctx.close(); }, 120);
    },
    toggleFullscreen() {
      if (!document.fullscreenElement) document.documentElement.requestFullscreen?.();
      else document.exitFullscreen?.();
    },
  },
  mounted() {
    this.fetchOrders(true);
    this.pollHandle = setInterval(() => this.fetchOrders(false), POLLING_MS);
    this.tickHandle = setInterval(() => { this.nowTs = Date.now(); }, 1000);
  },
  beforeUnmount() {
    clearInterval(this.pollHandle);
    clearInterval(this.tickHandle);
    clearTimeout(this.toastHandle);
  },

}).mount('#app');
</script>
</body>
</html>
