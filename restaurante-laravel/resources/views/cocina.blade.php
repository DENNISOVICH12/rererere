<!DOCTYPE html>
@php
  $user = Auth::user();
  $isAdmin = $user && ($user->rol ?? null) === 'admin';
  $serviceArea = strtolower($serviceArea ?? 'plato');
  $serviceAreaLabel = $serviceAreaLabel ?? ($serviceArea === 'bebida' ? 'Bar' : 'Cocina');
@endphp
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KDS {{ $serviceAreaLabel }}</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
<style>
:root {
  --bg: #0a0c0f;
  --surface: #111418;
  --surface2: #181c22;
  --border: #252b36;
  --border-bright: #3a4455;
  --text: #f0f4ff;
  --muted: #8892a4;
  --pending-color: #f5c542;
  --cooking-color: #ff7c2a;
  --ready-color: #2ecc71;
  --danger-color: #ff3b3b;
  --font-display: 'Barlow Condensed', sans-serif;
  --font-body: 'Barlow', sans-serif;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  background: var(--bg);
  color: var(--text);
  font-family: var(--font-body);
  min-height: 100vh;
}

/* ─── TOPBAR ─── */
.kds { min-height: 100vh; display: flex; flex-direction: column; }
.topbar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px;
  background: var(--surface);
  border-bottom: 2px solid var(--border);
  flex-wrap: wrap;
  position: sticky;
  top: 0;
  z-index: 100;
}
.topbar-title {
  font-family: var(--font-display);
  font-size: 1.6rem;
  font-weight: 900;
  letter-spacing: .04em;
  text-transform: uppercase;
  color: var(--text);
  white-space: nowrap;
}
.chips {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  flex: 1;
}
.chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 7px 14px;
  border-radius: 8px;
  border: 2px solid transparent;
  background: var(--surface2);
  font-family: var(--font-display);
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: .04em;
  text-transform: uppercase;
  cursor: pointer;
  transition: all .18s ease;
  color: var(--muted);
}
.chip:hover { color: var(--text); border-color: var(--border-bright); }
.chip.active.chip--pending { background: rgba(245,197,66,.14); border-color: var(--pending-color); color: var(--pending-color); }
.chip.active.chip--cooking { background: rgba(255,124,42,.14); border-color: var(--cooking-color); color: var(--cooking-color); }
.chip.active.chip--ready { background: rgba(46,204,113,.14); border-color: var(--ready-color); color: var(--ready-color); }
.chip.active.chip--delayed { background: rgba(255,59,59,.14); border-color: var(--danger-color); color: var(--danger-color); }
.chip-count {
  background: rgba(255,255,255,.12);
  border-radius: 6px;
  padding: 2px 8px;
  font-size: .9rem;
  font-weight: 900;
}
.topbar-actions { display: flex; gap: 8px; align-items: center; margin-left: auto; }
.btn-ghost {
  border: 1.5px solid var(--border-bright);
  background: var(--surface2);
  color: var(--muted);
  padding: 7px 13px;
  border-radius: 8px;
  font-family: var(--font-body);
  font-size: .85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all .18s ease;
  white-space: nowrap;
}
.btn-ghost:hover { color: var(--text); border-color: #6b7d96; }
.btn-logout {
  border: 1.5px solid rgba(255,59,59,.4);
  background: rgba(255,59,59,.1);
  color: #ff8080;
  padding: 7px 13px;
  border-radius: 8px;
  font-family: var(--font-body);
  font-size: .85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all .18s ease;
}
.btn-logout:hover { background: rgba(255,59,59,.22); color: #ffaaaa; }

/* ─── GRID ─── */
.kds-body { flex: 1; padding: 14px 16px; }
.kds-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 12px;
}
.empty-state {
  grid-column: 1/-1;
  text-align: center;
  padding: 60px 20px;
  font-family: var(--font-display);
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: .08em;
}

/* ─── CARD ─── */
.kds-card {
  background: var(--surface);
  border: 2px solid var(--border);
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  gap: 0;
  overflow: hidden;
  transition: transform .18s ease, box-shadow .18s ease;
}
.kds-card:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(0,0,0,.45); }
.kds-card.is-critical { border-color: var(--danger-color); box-shadow: 0 0 0 2px rgba(255,59,59,.18); }
.kds-card.is-new { animation: cardPop .4s ease; }

@keyframes cardPop {
  0% { transform: scale(.97); opacity: .7; }
  60% { transform: scale(1.015); }
  100% { transform: scale(1); opacity: 1; }
}

/* Card header */
.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 12px 14px 10px;
  border-bottom: 1px solid var(--border);
  background: var(--surface2);
}
.card-head-left { display: flex; flex-direction: column; gap: 2px; }
.card-order-num {
  font-family: var(--font-display);
  font-size: 1.55rem;
  font-weight: 900;
  letter-spacing: .03em;
  line-height: 1;
  color: var(--text);
}
.card-meta {
  font-size: .78rem;
  font-weight: 500;
  color: var(--muted);
}
.card-meta strong { color: #c8d8f0; }
.card-head-right { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }
.timer {
  font-family: var(--font-display);
  font-size: 1.3rem;
  font-weight: 800;
  letter-spacing: .04em;
  padding: 4px 10px;
  border-radius: 8px;
  border: 2px solid var(--border);
  background: var(--surface);
  color: var(--muted);
}
.timer.t-warn { color: var(--pending-color); border-color: rgba(245,197,66,.5); background: rgba(245,197,66,.08); }
.timer.t-critical {
  color: var(--danger-color);
  border-color: rgba(255,59,59,.6);
  background: rgba(255,59,59,.1);
  animation: timerPulse 1.2s ease infinite;
}
@keyframes timerPulse {
  0%, 100% { opacity: 1; }
  50% { opacity: .6; }
}
.status-badge {
  font-family: var(--font-display);
  font-size: .78rem;
  font-weight: 700;
  letter-spacing: .06em;
  text-transform: uppercase;
  padding: 3px 9px;
  border-radius: 6px;
  border: 1.5px solid transparent;
}
.badge-pendiente { background: rgba(245,197,66,.14); border-color: rgba(245,197,66,.5); color: var(--pending-color); }
.badge-preparando { background: rgba(255,124,42,.14); border-color: rgba(255,124,42,.5); color: var(--cooking-color); }
.badge-listo { background: rgba(46,204,113,.14); border-color: rgba(46,204,113,.5); color: var(--ready-color); }

/* Items block */
.items-block {
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  border-bottom: 1px solid var(--border);
  flex: 1;
}
.items-block-title {
  font-family: var(--font-display);
  font-size: .78rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 2px;
}
.item-row {
  display: flex;
  align-items: baseline;
  gap: 10px;
}
.item-qty {
  font-family: var(--font-display);
  font-size: 2rem;
  font-weight: 900;
  line-height: 1;
  color: var(--text);
  min-width: 36px;
  text-align: right;
  flex-shrink: 0;
}
.item-qty-unit {
  font-family: var(--font-display);
  font-size: .9rem;
  font-weight: 700;
  color: var(--muted);
  margin-left: -4px;
  align-self: flex-end;
  padding-bottom: 3px;
}
.item-name {
  font-family: var(--font-display);
  font-size: 1.45rem;
  font-weight: 800;
  letter-spacing: .02em;
  text-transform: uppercase;
  color: var(--text);
  line-height: 1.1;
}
.item-note {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  padding: 6px 10px;
  background: rgba(245,197,66,.07);
  border: 1px solid rgba(245,197,66,.25);
  border-radius: 8px;
  margin-top: 2px;
  margin-left: 46px;
}
.item-note-icon { font-size: .85rem; flex-shrink: 0; margin-top: 1px; }
.item-note-text {
  font-size: .82rem;
  font-weight: 500;
  color: #e8d8a0;
  line-height: 1.35;
  word-break: break-word;
}

/* Card action */
.card-action {
  padding: 10px 14px;
}
.btn-action {
  width: 100%;
  padding: 14px 16px;
  border: none;
  border-radius: 10px;
  font-family: var(--font-display);
  font-size: 1.25rem;
  font-weight: 900;
  letter-spacing: .05em;
  text-transform: uppercase;
  cursor: pointer;
  transition: all .18s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}
.btn-action:hover { transform: translateY(-1px); filter: brightness(1.08); }
.btn-action:active { transform: translateY(1px); filter: brightness(.94); }
.btn-action:disabled { opacity: .55; cursor: not-allowed; transform: none; filter: none; }
.btn-start {
  background: linear-gradient(135deg, #f5c542, #e8a800);
  color: #1a1200;
  box-shadow: 0 6px 20px rgba(245,197,66,.3);
}
.btn-ready {
  background: linear-gradient(135deg, #ff7c2a, #e85a00);
  color: #fff;
  box-shadow: 0 6px 20px rgba(255,124,42,.3);
}
.btn-done {
  background: linear-gradient(135deg, #2ecc71, #1a8a47);
  color: #fff;
  box-shadow: 0 6px 20px rgba(46,204,113,.3);
}

/* Details drawer */
.drawer-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.65);
  z-index: 200;
  display: flex;
  justify-content: flex-end;
}
.drawer {
  width: min(520px, 100vw);
  height: 100%;
  background: #0f1318;
  border-left: 2px solid var(--border);
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}
.drawer-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 18px;
  border-bottom: 2px solid var(--border);
  background: var(--surface);
  position: sticky;
  top: 0;
  z-index: 10;
}
.drawer-title {
  font-family: var(--font-display);
  font-size: 1.8rem;
  font-weight: 900;
  letter-spacing: .04em;
}
.drawer-body { padding: 16px 18px; display: flex; flex-direction: column; gap: 16px; }
.drawer-section {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
}
.drawer-section-title {
  font-family: var(--font-display);
  font-size: .78rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--muted);
  padding: 10px 14px 8px;
  border-bottom: 1px solid var(--border);
  background: var(--surface2);
}
.drawer-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0;
}
.drawer-info-cell {
  padding: 12px 14px;
  border-bottom: 1px solid var(--border);
  border-right: 1px solid var(--border);
}
.drawer-info-cell:nth-child(even) { border-right: none; }
.drawer-info-cell:nth-last-child(-n+2) { border-bottom: none; }
.drawer-info-label {
  font-size: .72rem;
  font-weight: 600;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 4px;
}
.drawer-info-value {
  font-family: var(--font-display);
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text);
}
.drawer-items-list {
  padding: 10px 14px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.drawer-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.drawer-item-main {
  display: flex;
  align-items: baseline;
  gap: 10px;
}
.drawer-item-qty {
  font-family: var(--font-display);
  font-size: 2.2rem;
  font-weight: 900;
  color: var(--text);
  line-height: 1;
  min-width: 40px;
  text-align: right;
}
.drawer-item-qunit {
  font-family: var(--font-display);
  font-size: .9rem;
  font-weight: 700;
  color: var(--muted);
  align-self: flex-end;
  padding-bottom: 3px;
  margin-left: -4px;
}
.drawer-item-name {
  font-family: var(--font-display);
  font-size: 1.6rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .02em;
  color: var(--text);
}
.drawer-item-note {
  display: flex;
  gap: 8px;
  margin-left: 50px;
  padding: 8px 12px;
  background: rgba(245,197,66,.08);
  border: 1px solid rgba(245,197,66,.28);
  border-radius: 8px;
  font-size: .88rem;
  color: #e8d8a0;
  line-height: 1.4;
}
.drawer-order-note {
  margin: 0;
  padding: 12px 14px;
  font-size: .92rem;
  color: #e8d8a0;
  line-height: 1.5;
  border-top: 1px solid var(--border);
}
.critical-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border-radius: 8px;
  background: rgba(255,59,59,.14);
  border: 1.5px solid rgba(255,59,59,.5);
  color: #ff8080;
  font-family: var(--font-display);
  font-size: .85rem;
  font-weight: 700;
  letter-spacing: .04em;
  text-transform: uppercase;
}

/* Toast */
.toast {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background: var(--surface2);
  border: 1.5px solid var(--border-bright);
  color: var(--text);
  padding: 12px 18px;
  border-radius: 10px;
  font-weight: 600;
  font-size: .9rem;
  z-index: 300;
  box-shadow: 0 10px 30px rgba(0,0,0,.5);
}

/* Error */
.error-bar {
  margin: 10px 16px 0;
  padding: 10px 14px;
  border-radius: 10px;
  background: rgba(255,59,59,.12);
  border: 1.5px solid rgba(255,59,59,.4);
  color: #ffaaaa;
  font-size: .88rem;
  font-weight: 500;
}

/* Transitions */
.fade-enter-active, .fade-leave-active, .fade-move { transition: all .25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(6px); }
.drawer-slide-enter-active, .drawer-slide-leave-active { transition: all .22s ease; }
.drawer-slide-enter-from, .drawer-slide-leave-to { opacity: 0; }
.drawer-slide-enter-from .drawer, .drawer-slide-leave-to .drawer { transform: translateX(24px); }

/* Admin sidebar compat */
.global-sidebar { /* keep existing if admin */ }
body.has-admin-sidebar .kds { margin-left: 102px; }

@media (max-width: 700px) {
  .kds-grid { grid-template-columns: 1fr; }
  .topbar { gap: 8px; }
  .topbar-title { font-size: 1.3rem; }
  body.has-admin-sidebar .kds { margin-left: 0; padding-top: 80px; }
}
@media (max-width: 500px) {
  .item-qty { font-size: 1.6rem; }
  .item-name { font-size: 1.2rem; }
}
</style>
</head>
<body class="{{ $isAdmin ? 'has-admin-sidebar' : '' }}">
@if($isAdmin)
  @include('layouts.partials.admin-sidebar')
@endif

<div id="app" class="kds">

  <!-- TOPBAR -->
  <header class="topbar">
    <h1 class="topbar-title">{{ $serviceAreaLabel }}</h1>

    <div class="chips">
      <button type="button" class="chip chip--pending" :class="{ active: activeFilter === 'pendiente' }" @click="activeFilter = 'pendiente'">
        🧾 Pendientes <span class="chip-count">@{{ activeServiceSummary.pendiente }}</span>
      </button>
      <button type="button" class="chip chip--cooking" :class="{ active: activeFilter === 'preparando' }" @click="activeFilter = 'preparando'">
        🔥 Preparando <span class="chip-count">@{{ activeServiceSummary.preparando }}</span>
      </button>
      <button type="button" class="chip chip--ready" :class="{ active: activeFilter === 'listo' }" @click="activeFilter = 'listo'">
        ✅ Listos <span class="chip-count">@{{ activeServiceSummary.listo }}</span>
      </button>
      <button type="button" class="chip chip--delayed" :class="{ active: activeFilter === 'atrasados' }" @click="activeFilter = 'atrasados'">
        ⏱ Atrasados <span class="chip-count">@{{ delayedCount }}</span>
      </button>
    </div>

    <div class="topbar-actions">
      <button type="button" class="btn-ghost" @click="fetchOrders(false)">↻ Actualizar</button>
      <button type="button" class="btn-ghost" @click="toggleFullscreen">⤢ Pantalla completa</button>
      @if($user)
        <span style="font-size:.8rem;color:var(--muted);white-space:nowrap;">{{ $user->usuario ?? $user->name ?? '' }}</span>
      @endif
      <button type="button" class="btn-logout" @click="showLogoutModal = true">🔒 Salir</button>

<!-- Modal confirmación -->
<div v-if="showLogoutModal" style="position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:500;display:flex;align-items:center;justify-content:center;">
  <div style="background:#111418;border:2px solid #252b36;border-radius:16px;padding:28px 24px;width:min(360px,90vw);display:flex;flex-direction:column;gap:16px;">
    <h3 style="font-family:'Barlow Condensed',sans-serif;font-size:1.6rem;font-weight:900;text-transform:uppercase;letter-spacing:.04em;margin:0;">¿Cerrar sesión?</h3>
    <p style="color:#8892a4;font-size:.95rem;margin:0;">Tu sesión se cerrará y serás redirigido al login.</p>
    <div style="display:flex;gap:10px;margin-top:4px;">
      <button type="button" class="btn-ghost" style="flex:1;" @click="showLogoutModal = false">Cancelar</button>
      <button type="button" class="btn-logout" style="flex:2;padding:10px 16px;font-size:1rem;" @click="confirmLogout">
        🔒 Confirmar salida
      </button>
    </div>
  </div>
</div>
  </header>

  <p v-if="error" class="error-bar">@{{ error }}</p>

  <!-- GRID -->
  <main class="kds-body">
    <transition-group name="fade" tag="div" class="kds-grid">

      <div v-if="!filteredOrders.length" key="empty" class="empty-state">
        @{{ activeFilter === 'pendiente' ? '✓ Sin pedidos pendientes' : activeFilter === 'preparando' ? 'Nada en preparación' : activeFilter === 'listo' ? 'Nada listo aún' : 'Sin pedidos atrasados' }}
      </div>

      <article
        v-for="order in filteredOrders"
        :key="order.id"
        class="kds-card"
        :class="{
          'is-critical': order._elapsedMin > 6,
          'is-new': highlightedIds.has(order.id),
        }"
      >
        <!-- HEAD -->
        <header class="card-head">
          <div class="card-head-left">
            <span class="card-order-num">Pedido #@{{ order.id }}</span>
            <span class="card-meta">Mesa <strong>@{{ order.mesa_numero || order.mesa_id || '—' }}</strong> · @{{ fmtTime(order.created_at) }}</span>
          </div>
          <div class="card-head-right">
            <span class="timer" :class="timerClass(order)">@{{ formatElapsed(order._elapsedMs) }}</span>
            <span class="status-badge" :class="`badge-${order.estado}`">@{{ statusLabel(order.estado) }}</span>
          </div>
        </header>

        <!-- ITEMS -->
        <section
          v-for="group in serviceGroupsFor(order)"
          :key="`${order.id}-${group.key}`"
          class="items-block"
        >
          <p class="items-block-title">@{{ group.emoji }} @{{ group.label }}</p>
          <div v-for="item in group.items" :key="item._k">
            <div class="item-row">
              <span class="item-qty">@{{ item._qty }}</span>
              <span class="item-qty-unit">x</span>
              <span class="item-name">@{{ item._name }}</span>
            </div>
            <div v-if="item._note" class="item-note">
              <span class="item-note-icon">📝</span>
              <span class="item-note-text">@{{ item._note }}</span>
            </div>
          </div>
        </section>

        <!-- ACTION -->
        <div class="card-action">
          <template v-for="group in serviceGroupsFor(order)" :key="`btn-${order.id}-${group.key}`">
            <button
              v-if="group.status === 'pendiente'"
              class="btn-action btn-start"
              :disabled="isGroupProcessing(order.id, group.key)"
              @click="updateGroupStatus(order.id, group.key)"
            >
              <span>🔥</span>
              @{{ isGroupProcessing(order.id, group.key) ? 'Guardando…' : 'Iniciar' }}
            </button>
            <button
              v-else-if="group.status === 'preparando'"
              class="btn-action btn-ready"
              :disabled="isGroupProcessing(order.id, group.key)"
              @click="updateGroupStatus(order.id, group.key)"
            >
              <span>✅</span>
              @{{ isGroupProcessing(order.id, group.key) ? 'Guardando…' : 'Marcar listo' }}
            </button>
            <button
              v-else-if="group.status === 'listo'"
              class="btn-action btn-done"
              disabled
            >
              ✓ Listo para entregar
            </button>
          </template>
        </div>

      </article>
    </transition-group>
  </main>

  <!-- DRAWER DETALLE -->
  <transition name="drawer-slide">
    <div v-if="drawerOpen && selectedOrder" class="drawer-overlay" @click.self="drawerOpen = false">
      <aside class="drawer">
        <header class="drawer-head">
          <h2 class="drawer-title">Pedido #@{{ selectedOrder.id }}</h2>
          <button class="btn-ghost" @click="drawerOpen = false">✕ Cerrar</button>
        </header>
        <div class="drawer-body">

          <!-- Info -->
          <div class="drawer-section">
            <p class="drawer-section-title">Información</p>
            <div class="drawer-info-grid">
              <div class="drawer-info-cell">
                <p class="drawer-info-label">Mesa</p>
                <p class="drawer-info-value">@{{ selectedOrder.mesa_numero || selectedOrder.mesa_id || '—' }}</p>
              </div>
              <div class="drawer-info-cell">
                <p class="drawer-info-label">Hora</p>
                <p class="drawer-info-value">@{{ fmtTime(selectedOrder.created_at) }}</p>
              </div>
              <div class="drawer-info-cell">
                <p class="drawer-info-label">Estado</p>
                <p class="drawer-info-value">@{{ statusLabel(selectedOrder.estado) }}</p>
              </div>
              <div class="drawer-info-cell">
                <p class="drawer-info-label">Tiempo</p>
                <p class="drawer-info-value" :style="selectedOrder._elapsedMin > 6 ? 'color:var(--danger-color)' : ''">@{{ formatElapsed(selectedOrder._elapsedMs) }}</p>
              </div>
            </div>
            <div v-if="selectedOrder._elapsedMin > 6" style="padding:10px 14px;border-top:1px solid var(--border);">
              <span class="critical-pill">⚠ Atrasado +@{{ Math.floor(selectedOrder._elapsedMin - 6) }} min</span>
            </div>
          </div>

          <!-- Items -->
          <div class="drawer-section">
            <p class="drawer-section-title">Items del pedido</p>
            <div class="drawer-items-list">
              <div v-for="(item, idx) in getOrderItems(selectedOrder)" :key="idx" class="drawer-item">
                <div class="drawer-item-main">
                  <span class="drawer-item-qty">@{{ item._qty }}</span>
                  <span class="drawer-item-qunit">x</span>
                  <span class="drawer-item-name">@{{ item._name }}</span>
                </div>
                <div v-if="item._note" class="drawer-item-note">
                  📝 @{{ item._note }}
                </div>
              </div>
            </div>
          </div>

        </div>
      </aside>
    </div>
  </transition>

  <div v-if="toastMessage" class="toast">@{{ toastMessage }}</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@3.4.21/dist/vue.global.prod.min.js"></script>
<script>
const POLLING_MS = 4000;
const SERVICE_AREA = @json($serviceArea ?? 'plato');
const ACTIVE_SERVICE_LABEL = @json($serviceAreaLabel);
const STATUS_LABELS = { pendiente:'Pendiente', preparando:'En preparación', listo:'Listo', entregado:'Entregado' };

function statusLabelFor(s) {
  const n = String(s||'').trim().toLowerCase();
  return STATUS_LABELS[n] || (n ? n.charAt(0).toUpperCase()+n.slice(1) : '-');
}
function asText(v) { return (v===null||v===undefined)?'':Array.isArray(v)?v.join(' · '):String(v); }
function deepGet(obj,path) { try { return path.split('.').reduce((a,k)=>a&&a[k]!==undefined?a[k]:undefined,obj); } catch{return undefined;} }
function pickFirst(obj,paths) { for(const p of paths){const v=asText(deepGet(obj,p)).trim();if(v)return v;} return ''; }
function pickFirstNum(obj,paths,fb=1) { for(const p of paths){const n=Number(deepGet(obj,p));if(Number.isFinite(n)&&n>0)return n;} return fb; }

function normalizeAndDedupeOrders(list) {
  const map = new Map();
  for(const raw of (Array.isArray(list)?list:[])) {
    const id=Number(raw?.id);
    if(!Number.isFinite(id)) continue;
    map.set(id,{...raw,id});
  }
  return [...map.values()];
}

function normalizeOrderItems(order) {
  const source = order?.items||order?.detalles||order?.detalle||order?.pedido_detalles||order?.order_items||[];
  return (Array.isArray(source)?source:[]).map((raw,idx)=>{
    const merged={...raw,...(raw?.pivot||{}),...(raw?.detalle||{}),...(raw?.pedido_detalle||{})};
    const qty=pickFirstNum(merged,['cantidad','quantity','qty','pivot.cantidad'],1);
    const name=pickFirst(merged,['nombre','menu_item.nombre','menuItem.nombre','producto.nombre'])||'Item';
    const note=pickFirst(merged,['nota','observacion','comentario','note','notas','special_instructions','pivot.nota','detalle.nota','pedido_detalle.nota']);
    const sg=String(merged.grupo_servicio||merged.grupoServicio||merged.service_group||'plato').toLowerCase();
    const ss=String(pickFirst(merged,['estado_servicio','estadoServicio','service_status'])||order?.estado||'pendiente').toLowerCase();
    return {...raw, _k:raw?.id||`${order?.id||'o'}-${idx}`, _qty:qty, _name:name, _note:note, _serviceGroup:sg, _serviceStatus:ss };
  });
}

Vue.createApp({
  data() {
    return {
      orders:[],
      activeServiceArea: String(SERVICE_AREA||'plato').toLowerCase(),
      nowTs: Date.now(),
      error:'',
      highlightedIds: new Set(),
      processingGroupIds: new Set(),
      lastSyncAt: null,
      syncInFlight: false,
      activeFilter:'pendiente',
      drawerOpen: false,
      selectedOrderId: null,
      priorityOverrides:{},
      toastMessage:'',
      pollHandle:null,
      tickHandle:null,
      toastHandle:null,
      showLogoutModal: false,
    };
  },
  computed: {
    normalized() {
      return this.orders.map(order=>{
        const ts=Date.parse(order.created_at)||this.nowTs;
        const elapsedMs=Math.max(this.nowTs-ts,0);
        const norm={...order,_itemsNorm:normalizeOrderItems(order)};
        const activeGroup=this.serviceGroupsFor(norm).find(g=>g.key===this.activeServiceArea);
        if(!activeGroup) return null;
        const status=String(activeGroup.status||'pendiente').toLowerCase();
        return {...norm,estado:status,_createdTs:ts,_elapsedMs:elapsedMs,_elapsedMin:elapsedMs/60000,
          _urgency:(elapsedMs/60000)+(status==='pendiente'?2:0)+(this.priorityOverrides[order.id]?2:0)};
      }).filter(Boolean);
    },
    filteredOrders() {
      const sorted=[...this.normalized].sort((a,b)=>b._urgency-a._urgency||b._createdTs-a._createdTs);
      if(this.activeFilter==='atrasados') return sorted.filter(o=>o.estado!=='entregado'&&o._elapsedMin>6);
      return sorted.filter(o=>o.estado===this.activeFilter);
    },
    serviceSummary() {
      const s={bebida:{pendiente:0,preparando:0,listo:0,entregado:0},plato:{pendiente:0,preparando:0,listo:0,entregado:0}};
      this.normalized.forEach(order=>this.serviceGroupsFor(order,{includeAll:true}).forEach(g=>{
        if(s[g.key]&&s[g.key][g.status]!==undefined) s[g.key][g.status]+=1;
      }));
      return s;
    },
    activeServiceSummary() { return this.serviceSummary[this.activeServiceArea]||{pendiente:0,preparando:0,listo:0,entregado:0}; },
    delayedCount() { return this.normalized.filter(o=>o.estado!=='entregado'&&o._elapsedMin>6).length; },
    selectedOrder() { return this.selectedOrderId?this.normalized.find(o=>o.id===this.selectedOrderId)||null:null; },
  },
  methods: {
    getOrderItems(order) {
      if(!order) return [];
      if(Array.isArray(order._itemsNorm)) return order._itemsNorm;
      return normalizeOrderItems(order);
    },
    fmtTime(raw) {
      const ts=Date.parse(raw);
      return Number.isFinite(ts)?new Date(ts).toLocaleTimeString('es-CO',{hour:'2-digit',minute:'2-digit'}):'-';
    },
    statusLabel(s) { return statusLabelFor(s); },
    formatElapsed(ms) {
      const sec=Math.floor(ms/1000);
      return `${String(Math.floor(sec/60)).padStart(2,'0')}:${String(sec%60).padStart(2,'0')}`;
    },
    timerClass(order) {
      if(order._elapsedMin>6) return 't-critical';
      if(order._elapsedMin>=3) return 't-warn';
      return '';
    },
    async confirmLogout() {
  const token = document.querySelector('meta[name="csrf-token"]')?.content;
  try {
    await fetch('/logout', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'X-CSRF-TOKEN': token,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      }
    });
  } catch(e) {}
  window.location.href = '/login';
},
    serviceGroupsFor(order,options={}) {
      const labels={bebida:{label:'Bebidas',emoji:'🍹'},plato:{label:'Platos',emoji:'🍽'}};
      const groups={bebida:[],plato:[]};
      this.getOrderItems(order).forEach(item=>{
        const k=(item._serviceGroup||'plato').toLowerCase();
        if(groups[k]) groups[k].push(item);
      });
      const allowed=options.includeAll?['bebida','plato']:[this.activeServiceArea];
      return allowed.filter(k=>groups[k]?.length).map(k=>{
        const statuses=groups[k].map(i=>i._serviceStatus||order.estado||'pendiente');
        const status=statuses.includes('pendiente')?'pendiente':statuses.includes('preparando')?'preparando':statuses.includes('listo')?'listo':'entregado';
        return {key:k,...labels[k],status,items:groups[k]};
      });
    },
    isGroupProcessing(orderId,groupKey) { return this.processingGroupIds.has(`${orderId}:${groupKey}`); },
    patchOrderGroupStatus(order,groupKey,nextStatus) {
      const patched=this.getOrderItems(order).map(item=>(item._serviceGroup||'plato')!==groupKey?item:{...item,_serviceStatus:nextStatus,estado_servicio:nextStatus});
      return{...order,_itemsNorm:patched};
    },
    async updateGroupStatus(orderId,groupKey) {
      const pk=`${orderId}:${groupKey}`;
      if(this.processingGroupIds.has(pk)) return;
      const idx=this.orders.findIndex(o=>Number(o.id)===Number(orderId));
      if(idx<0) return;
      const prev=this.orders[idx];
      const grp=this.serviceGroupsFor(prev,{includeAll:true}).find(g=>g.key===groupKey);
      const cur=grp?.status||'pendiente';
      const next=cur==='pendiente'?'preparando':cur==='preparando'?'listo':cur;
      if(next===cur) return;
      const optimistic=this.patchOrderGroupStatus(prev,groupKey,next);
      this.orders=this.orders.map(o=>Number(o.id)===Number(orderId)?optimistic:o);
      const ns=new Set(this.processingGroupIds); ns.add(pk); this.processingGroupIds=ns;
      try {
        const token=document.querySelector('meta[name="csrf-token"]')?.content||'';
        const res=await fetch(`/pedidos/${orderId}/servicio/${groupKey}`,{method:'PUT',credentials:'include',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':token,'X-Requested-With':'XMLHttpRequest'}});
        if(!res.ok) throw new Error('failed');
        this.error='';
      } catch {
        this.orders=this.orders.map(o=>Number(o.id)===Number(orderId)?prev:o);
        this.showToast('⚠️ No se pudo guardar. Revertido.');
      } finally {
        const ds=new Set(this.processingGroupIds); ds.delete(pk); this.processingGroupIds=ds;
      }
    },
    showToast(msg) {
      this.toastMessage=msg;
      clearTimeout(this.toastHandle);
      this.toastHandle=setTimeout(()=>{this.toastMessage='';},2500);
    },
    toggleFullscreen() {
      if(!document.fullscreenElement) document.documentElement.requestFullscreen?.();
      else document.exitFullscreen?.();
    },
    async fetchOrders(isInitial=false) {
      if(this.syncInFlight) return;
      this.syncInFlight=true;
      try {
        const token=document.querySelector('meta[name="csrf-token"]')?.content||'';
        const res=await fetch('/pedidos',{method:'GET',credentials:'include',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':token}});
        if(!res.ok) { if(res.status===401||res.status===419){this.error='Sesión expirada';} throw new Error('sync failed'); }
        const payload=await res.json().catch(()=>[]);
        const incoming=payload?.data??payload??[];
        const items=normalizeAndDedupeOrders(Array.isArray(incoming)?incoming:[]);
        if(isInitial||!this.lastSyncAt) {
          this.orders=items;
        } else {
          const beforeIds=new Set(this.orders.map(o=>o.id));
          const byId=new Map(items.map(o=>[o.id,o]));
          const merged=this.orders.map(o=>{const c=byId.get(o.id);if(!c)return o;byId.delete(o.id);return{...o,...c};});
          for(const p of byId.values()) merged.push(p);
          const newOrders=merged.filter(o=>!beforeIds.has(o.id)&&String(o.estado||'').toLowerCase()==='pendiente');
          if(newOrders.length) { this.beep(); const ns=new Set(this.highlightedIds); newOrders.forEach(o=>ns.add(o.id)); this.highlightedIds=ns; setTimeout(()=>{const cs=new Set(this.highlightedIds);newOrders.forEach(o=>cs.delete(o.id));this.highlightedIds=cs;},3500); }
          this.orders=merged;
        }
        this.lastSyncAt=new Date().toISOString();
        this.error='';
      } catch(e) { if(!this.error) this.error='No se pudo sincronizar'; }
      finally { this.syncInFlight=false; }
    },
    beep() {
      const Ctx=window.AudioContext||window.webkitAudioContext;
      if(!Ctx) return;
      const ctx=new Ctx(); const osc=ctx.createOscillator(); const gain=ctx.createGain();
      osc.frequency.value=880; gain.gain.value=0.05;
      osc.connect(gain); gain.connect(ctx.destination);
      osc.start(); setTimeout(()=>{osc.stop();ctx.close();},120);
    },
  },
  mounted() {
    this.fetchOrders(true);
    this.pollHandle=setInterval(()=>this.fetchOrders(false),POLLING_MS);
    this.tickHandle=setInterval(()=>{this.nowTs=Date.now();},1000);
  },
  beforeUnmount() {
    clearInterval(this.pollHandle);
    clearInterval(this.tickHandle);
    clearTimeout(this.toastHandle);
  },
}).mount('#app');
</script>
<script src="{{ asset('js/confirm-modal.js') }}" defer></script>
<script src="{{ asset('js/logout.js') }}" defer></script>
</body>
</html>