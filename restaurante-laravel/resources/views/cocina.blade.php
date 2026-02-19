<!DOCTYPE html>
@php
  $user = Auth::user();
  $isAdmin = $user && ($user->rol ?? null) === 'admin';
  $adminBackUrl = Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin');
@endphp

<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Modo Cocina PRO</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
:root {
  --text: #f5edf0;
  --muted: #ccbfc4;
  --line: rgba(255,255,255,.18);
  --glass: rgba(14, 10, 16, .52);
  --glass-strong: rgba(9, 7, 13, .72);
  --wine: #8a1c2b;
  --wine-soft: #a53a4a;
  --wine-glow: rgba(165, 58, 74, .35);
  --ok: #68b98f;
  --warn: #dfc36f;
  --danger: #f08ea0;

}
* { box-sizing: border-box; }
body {
  margin: 0;
  min-height: 100vh;
  font-family: Inter, "Segoe UI", system-ui, sans-serif;
  color: var(--text);
  background-image:
    linear-gradient(120deg, rgba(0,0,0,.62), rgba(0,0,0,.62)),
    url("https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1800&q=80");
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
}
.kds { min-height: 100vh; padding: 18px 20px; max-width: 1640px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }
.topbar {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 12px;

  align-items: center;
  background: var(--glass);
  backdrop-filter: blur(8px);
  border: 1px solid var(--line);
  border-radius: 16px;
  padding: 10px 14px;
  box-shadow: 0 10px 28px rgba(0,0,0,.25);
}
.topbar h1 { margin: 0; font-size: 1.42rem; letter-spacing: .01em; }
.muted { margin: 2px 0 0; color: var(--muted); }
.stats { display: grid; grid-template-columns: repeat(3, minmax(86px, 1fr)); gap: 8px; }
.stats article {
  background: rgba(255,255,255,.05);
  border: 1px solid var(--line);
  border-radius: 999px;
  padding: 5px 10px;
  text-align: center;
  min-width: 86px;
}
.stats span { color: var(--muted); font-size: .75rem; display: block; }
.stats strong { font-size: 1rem; }
.controls { display: flex; gap: 8px; justify-content: flex-end; }
.col-title { font-weight: 700; letter-spacing: .01em; }
.count-chip { padding: 2px 8px; border-radius: 999px; border: 1px solid rgba(255,255,255,.2); background: rgba(255,255,255,.07); font-size: .82rem; color: #f3e9ed; }
.btn-icon { opacity: .85; margin-right: 4px; }


.ghost {
  border: 1px solid rgba(255,255,255,.25);
  background: rgba(255,255,255,.06);
  color: var(--text);
  border-radius: 999px;
  padding: 9px 13px;
  cursor: pointer;
  transition: all .2s ease;
}
.ghost:hover { border-color: rgba(165,58,74,.55); box-shadow: 0 0 0 1px rgba(165,58,74,.35); }
.error {
  margin: 0;
  padding: 10px;
  border-radius: 12px;
  color: #ffd9df;
  border: 1px solid rgba(240,142,160,.5);
  background: rgba(138,28,43,.35);
}

.toast {
  position: fixed;
  right: 16px;
  bottom: 16px;
  background: rgba(17, 11, 18, .94);
  border: 1px solid rgba(165,58,74,.6);
  color: #f9e9ee;

  padding: 10px 12px;
  border-radius: 10px;
  z-index: 80;
}
.board { flex: 1; min-height: 0; display: grid; grid-template-columns: repeat(4, minmax(260px, 1fr)); gap: 8px; }

.col {
  background: var(--glass);
  border: 1px solid var(--line);
  backdrop-filter: blur(8px);
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  min-height: 0;
  box-shadow: 0 8px 20px rgba(0,0,0,.22);
}
.col-head { position: sticky; top: 0; z-index: 3; padding: 11px 12px; border-bottom: 1px solid rgba(255,255,255,.14); background: rgba(11,8,14,.72); backdrop-filter: blur(6px); display: flex; justify-content: space-between; align-items: center; }
.col-list { overflow-y: auto; min-height: 0; padding: 10px; display: flex; flex-direction: column; gap: 10px; scrollbar-width: thin; scrollbar-color: rgba(165,58,74,.55) transparent; }
.card {
  background: linear-gradient(180deg, rgba(12,8,14,.74), rgba(9,7,13,.78));
  border: 1px solid rgba(255,255,255,.10);

  border-radius: 14px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  cursor: pointer;
  transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.card:hover { transform: translateY(-2px); border-color: rgba(165,58,74,.55); box-shadow: 0 10px 20px rgba(0,0,0,.24), 0 0 0 1px rgba(165,58,74,.25); }

.card-selected { border-color: rgba(165,58,74,.8); box-shadow: 0 0 0 1px rgba(165,58,74,.4); }
.card-new { animation: glowPremium 2s ease; }
.card-critical { border-color: rgba(240,142,160,.7); }
.card-head { display: flex; justify-content: space-between; align-items: center; }
.num { font-weight: 800; font-size: 1.12rem; }
.timer { border-radius: 999px; padding: 4px 10px; font-weight: 800; letter-spacing: .02em; }

.t-ok { color: #8de7b8; background: rgba(104,185,143,.2); }
.t-warn { color: #f6dea0; background: rgba(223,195,111,.2); }
.t-critical { color: #ffc2cf; background: rgba(240,142,160,.19); animation: pulseRed 1.25s infinite; }
.items { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 6px; }
.items li { display: flex; gap: 8px; align-items: baseline; }
.qty { min-width: 42px; color: #f3d6a2; font-weight: 900; font-size: 1.15rem; }
.name { font-weight: 620; }
.note { margin: 0; padding: 8px; border-radius: 8px; background: rgba(138,28,43,.28); color: #ffd6dd; font-size: .9rem; }
.action { width: 100%; border: none; border-radius: 12px; padding: 13px 12px; font-size: 0.98rem; font-weight: 800; letter-spacing: .01em; cursor: pointer; transition: transform .15s ease, filter .15s ease; }
.action:hover { filter: brightness(1.05); transform: translateY(-1px); }
.action:active { transform: translateY(0); }
.action[disabled] { opacity: .55; cursor: not-allowed; transform: none; }


.action-start { background: linear-gradient(180deg, #bc4d2d, #a73f22); color: #fff8f5; }
.action-ready { background: linear-gradient(180deg, #3f8a66, #346f53); color: #e8fff2; }
.action-deliver { background: linear-gradient(180deg, #4f7295, #3e5f80); color: #ebf5ff; }

.fade-enter-active, .fade-leave-active, .fade-move { transition: all .28s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(8px); }

.drawer-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, .52); display: flex; justify-content: flex-end; z-index: 90; }
.drawer {
  width: min(560px, 100vw);

  height: 100%;
  background: rgba(12, 8, 13, .88);
  border-left: 1px solid var(--line);
  backdrop-filter: blur(10px);
  padding: 14px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
  box-shadow: -8px 0 30px rgba(0,0,0,.3);
.drawer-head { display: flex; justify-content: space-between; align-items: center; }
.drawer-title { margin: 0; font-size: 1.5rem; }
.badge { border: 1px solid transparent; border-radius: 999px; font-size: .82rem; font-weight: 700; padding: 4px 10px; }
.b-pendiente { background: rgba(223,195,111,.17); color: #ffe59f; border-color: rgba(223,195,111,.5); }
.b-preparando { background: rgba(188,77,45,.17); color: #ffcfbb; border-color: rgba(188,77,45,.52); }
.b-listo { background: rgba(104,185,143,.17); color: #c2f3da; border-color: rgba(104,185,143,.52); }
.b-entregado { background: rgba(130,151,177,.17); color: #d9e6f5; border-color: rgba(130,151,177,.52); }
.ticket { background: rgba(18, 11, 18, .62); border: 1px solid var(--line); border-radius: 12px; padding: 12px; }
.ticket-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.ticket-grid p { margin: 0; color: #eadddf; }
.priority-pill {
  margin-top: 6px;
  display: inline-block;
  border-radius: 999px;
  background: rgba(138,28,43,.35);
  color: #ffc2cf;
  border: 1px solid rgba(240,142,160,.6);

  font-size: .8rem;
  padding: 4px 9px;
  animation: pulseRed 1.2s infinite;

}
.item-row { border-bottom: 1px dashed rgba(255,255,255,.18); padding: 8px 0; }
.item-row:last-child { border-bottom: 0; }
.item-main { display: flex; gap: 8px; align-items: baseline; }
.item-extra, .item-note { margin: 4px 0 0 0; color: #cbbec5; font-size: .9rem; }

.drawer-items { max-height: 46vh; overflow-y: auto; padding-right: 2px; }
.items-summary { margin: 0 0 8px 0; color: #d3c3cb; font-size: .87rem; }
.category-title { margin: 10px 0 4px 0; color: #f4d6de; font-size: .82rem; text-transform: uppercase; letter-spacing: .06em; }
.empty-items {
  margin: 8px 0 0;
  padding: 10px;
  border-radius: 10px;
  border: 1px dashed rgba(240,142,160,.55);
  background: rgba(138,28,43,.22);
  color: #ffd6dd;

}
.drawer-actions { display: grid; gap: 8px; }
.secondary-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.sec-btn {
  border: 1px solid rgba(255,255,255,.24);
  background: rgba(255,255,255,.04);
  color: var(--text);
  border-radius: 10px;
  padding: 9px 10px;
  cursor: pointer;
  transition: all .2s ease;
}
.sec-btn:hover { border-color: rgba(165,58,74,.5); }


.drawer-slide-enter-active, .drawer-slide-leave-active { transition: all .25s ease; }
.drawer-slide-enter-from, .drawer-slide-leave-to { opacity: 0; }
.drawer-slide-enter-from .drawer, .drawer-slide-leave-to .drawer { transform: translateX(28px); }

@keyframes glowPremium {
  0% { box-shadow: 0 0 0 rgba(165,58,74,0); }
  45% { box-shadow: 0 0 20px rgba(165,58,74,.36); }

  100% { box-shadow: 0 0 0 rgba(165,58,74,0); }
}
@keyframes pulseRed {
  0%,100% { box-shadow: 0 0 0 rgba(240,142,160,0); }
  50% { box-shadow: 0 0 16px rgba(240,142,160,.35); }


}
@media (max-width: 1280px) {
  .topbar { grid-template-columns: 1fr; }
  .board { grid-template-columns: repeat(2, minmax(250px, 1fr)); }
}
@media (max-width: 840px) {
  .board { grid-template-columns: 1fr; }
  .drawer { width: 100vw; }
}

.col-list::-webkit-scrollbar,.drawer-items::-webkit-scrollbar{width:8px;height:8px;}
.col-list::-webkit-scrollbar-thumb,.drawer-items::-webkit-scrollbar-thumb{background:rgba(165,58,74,.45);border-radius:999px;}
.col-list::-webkit-scrollbar-track,.drawer-items::-webkit-scrollbar-track{background:transparent;}

body.has-admin-back .kds {
  padding-top: 76px;
}
.back-admin-btn {
  position: fixed;
  top: 16px;
  left: 20px;
  z-index: 120;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 14px;
  border-radius: 999px;
  border: 1px solid rgba(180, 192, 214, .30);
  background: rgba(15, 20, 31, .62);
  backdrop-filter: blur(7px);
  color: var(--text);
  text-decoration: none;
  font-size: .88rem;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(2,6,13,.24);
  transition: all .2s ease;
}
.back-admin-btn:hover {
  border-color: rgba(110, 54, 66, .42);
  box-shadow: 0 6px 16px rgba(2,6,13,.3);
  transform: translateY(-1px);
}
.back-admin-btn svg {
  width: 14px;
  height: 14px;
  opacity: .88;
}
@media (max-width: 840px) {
  body.has-admin-back .kds {
    padding-top: 84px;
  }
  .back-admin-btn {
    left: 14px;
    top: 12px;
  }
}

</style>
</head>
<body class="{{ $isAdmin ? 'has-admin-back' : '' }}">
<div id="app" class="kds">
  @if($isAdmin)
    <a href="{{ $adminBackUrl }}" class="back-admin-btn" aria-label="Volver al panel de administración">
      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M10 6L4 12L10 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M4 12H20" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span>Volver al Admin</span>
    </a>
  @endif
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
      <button class="ghost" @click="soundEnabled = !soundEnabled"><span class="btn-icon">🔉</span> @{{ soundEnabled ? 'Sonido ON' : 'Sonido OFF' }}</button>
      <button class="ghost" @click="toggleFullscreen"><span class="btn-icon">⤢</span> Pantalla completa</button>
    </div>
  </header>

  <p v-if="error" class="error">@{{ error }}</p>

  <div class="board">
    <section v-for="column in columns" :key="column.key" class="col">
      <header class="col-head">
        <strong class="col-title">@{{ column.title }}</strong>
        <span class="count-chip">@{{ grouped[column.key].length }}</span>
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

          <p v-if="order.notas" class="note">Nota: @{{ order.notas }}</p>

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
      const source = this.order?.items || this.order?.detalles || this.order?.detalle || this.order?.pedido_detalles || [];

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
      if (this.order.estado === 'pendiente') return { label: 'Comenzar', next: 'preparando', className: 'action action-start' };
      if (this.order.estado === 'preparando') return { label: 'Marcar listo', next: 'listo', className: 'action action-ready' };
      if (this.order.estado === 'listo') return { label: 'Entregar', next: 'entregado', className: 'action action-deliver' };

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
      const ts = Date.parse(dateRaw);
      if (!Number.isFinite(ts)) return '-';
      return new Date(ts).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
    },
    fmtDate(dateRaw) {
      if (!dateRaw) return '-';
      const ts = Date.parse(dateRaw);
      if (!Number.isFinite(ts)) return '-';
      return new Date(ts).toLocaleString('es-CO', {

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
            <h2 class="drawer-title" v-text="'Pedido #' + (order?.id || '-')"></h2>
            <button class="ghost" @click="$emit('close')">✕</button>
          </header>

          <section v-if="hasOrder" class="ticket">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
              <span class="badge" :class="statusClass" v-text="statusLabel(order.estado)"></span>
              <span class="timer" :class="order._elapsedMin > 6 ? 't-critical' : (order._elapsedMin >= 3 ? 't-warn' : 't-ok')" v-text="formatElapsed(order._elapsedMs)"></span>
            </div>

            <div class="ticket-grid" style="margin-top:10px;">
              <p><strong>Creado:</strong> <span v-text="fmtDate(order.created_at)"></span></p>
              <p><strong>Hora:</strong> <span v-text="fmtTime(order.created_at)"></span></p>
              <p><strong>Mesa:</strong> <span v-text="order.mesa || '-'"></span></p>
              <p><strong>Cliente:</strong> <span v-text="order.cliente?.nombre || order.cliente_nombre || '-'"></span></p>
            </div>

            <span v-if="isPriority" class="priority-pill" v-text="'Prioridad alta · ' + (delayLabel || 'Pedido priorizado')"></span>
          </section>

          <section v-if="hasOrder" class="ticket">
            <h3 style="margin:0 0 8px 0;">Items</h3>
            <p class="items-summary" v-text="normalizedItems.length + ' líneas · ' + totalItemsCount + ' unidades'"></p>

            <div v-if="!normalizedItems.length" class="empty-items">
              Este pedido no tiene items asociados. Revisar backend/relación.
            </div>

            <div v-else class="drawer-items">
              <template v-for="(categoryItems, categoryName) in groupedItems" :key="categoryName">
                <h4 class="category-title" v-if="categoryName && categoryName !== 'General'" v-text="categoryName"></h4>
                <article class="item-row" v-for="item in categoryItems" :key="item.id">
                  <div class="item-main">
                    <span class="qty" v-text="item.qty + 'x'"></span>
                    <strong v-text="item.name"></strong>
                  </div>
                  <p v-if="item.extras" class="item-extra" v-text="'Extras: ' + item.extras"></p>
                  <p v-if="item.note" class="item-note" v-text="'Nota: ' + item.note"></p>

                </article>
              </template>
            </div>
          </section>

          <section v-if="hasOrder && order.notas" class="ticket">
            <h3 style="margin:0 0 8px 0;">Notas</h3>
            <p class="note" v-text="order.notas"></p>
          </section>


          <section class="ticket drawer-actions" v-if="hasOrder">
            <button v-if="primaryAction" :class="primaryAction.className" :disabled="loadingAction" @click="executePrimaryAction" v-text="loadingAction ? 'Procesando...' : primaryAction.label"></button>
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
        { key: 'pendiente', title: 'Pendientes' },
        { key: 'preparando', title: 'En preparación' },
        { key: 'listo', title: 'Listos' },
        { key: 'entregado', title: 'Entregados' },

      ],
      pollHandle: null,
      tickHandle: null,
      toastHandle: null,
    };
  },
  computed: {
    normalized() {
      return this.orders.map((order) => {
        const parsedTs = Date.parse(order.created_at);
        const ts = Number.isFinite(parsedTs) ? parsedTs : this.nowTs;

        const elapsedMs = Math.max(this.nowTs - ts, 0);
        const status = String(order.estado || '').toLowerCase();
        return {
          ...order,
          estado: status,
          notas: order.notas || order.note || '',
          items: order.items || order.detalles || order.detalle || order.pedido_detalles || [],

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
      if (order.estado === 'pendiente') return { label: 'Comenzar', className: 'action-start', run: () => this.quickAction(order.id, 'preparando') };
      if (order.estado === 'preparando') return { label: 'Marcar listo', className: 'action-ready', run: () => this.quickAction(order.id, 'listo') };
      if (order.estado === 'listo') return { label: 'Entregar', className: 'action-deliver', run: () => this.quickAction(order.id, 'entregado') };

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
