<!DOCTYPE html>
@php
  $user = Auth::user();
  $isAdmin = $user && ($user->rol ?? null) === 'admin';
  $adminBackUrl = Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin');
  $serviceArea = strtolower($serviceArea ?? 'plato');
  $serviceAreaLabel = $serviceAreaLabel ?? ($serviceArea === 'bebida' ? 'Bar' : 'Cocina');
@endphp

<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KDS {{ $serviceAreaLabel }}</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>

:root {
  --bg: #111418;
  --panel: #1a2028;
  --panel-soft: #202734;
  --text: #f1f5fb;
  --muted: #a9b3c3;
  --line: #313b4d;
  --pending: #6b7280;
  --cooking: #f5c451;
  --ready: #5ac887;
  --danger: #ff4d4d;
}
* { box-sizing: border-box; }
body {
  margin: 0;
  min-height: 100vh;
  font-family: Inter, "Segoe UI", system-ui, sans-serif;
  color: var(--text);
  background: var(--bg);
}
.kds { min-height: 100vh; padding: 14px 16px; max-width: 1700px; margin: 0 auto; display: flex; flex-direction: column; gap: 10px; }
.topbar {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 10px;
  align-items: center;
  background: var(--panel);
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 8px 12px;
}
.topbar-title { margin: 0; font-size: 1.12rem; font-weight: 700; }
.status-chips { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.status-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid var(--line);
  border-radius: 999px;
  padding: 4px 9px;
  background: #242d3a;
  font-size: .82rem;
  color: #e5edf8;
  min-height: 30px;
  cursor: pointer;
  transition: all .2s ease;
}
.status-chip:hover { border-color: #6f819f; transform: translateY(-1px); }
.status-chip.active { background: #3a465c; border-color: #8aa2ca; color: #ffffff; box-shadow: 0 6px 16px rgba(0,0,0,.22); }
.status-chip:not(.active) { background: transparent; }
.status-chip[type="button"] { appearance: none; -webkit-appearance: none; font: inherit; }
.status-chip-label { color: #bec8da; font-weight: 600; }
.status-chip-value { font-weight: 800; }
.status-chip--pending { border-color: rgba(107,114,128,.85); }
.status-chip--cooking { border-color: rgba(245,196,81,.85); }
.status-chip--ready { border-color: rgba(90,200,135,.85); }
.topbar-right { display: flex; align-items: center; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }
.controls { display: flex; gap: 6px; justify-content: flex-end; }
.user-panel { display: inline-flex; align-items: center; gap: 6px; }
.user-label {
  font-size: .78rem;
  color: #d6dfef;
  border: 1px solid #3b475c;
  border-radius: 999px;
  padding: 6px 10px;
  background: #242d3a;
  white-space: nowrap;
}
.btn-icon { opacity: .85; margin-right: 4px; }
.ghost {
  border: 1px solid #42506a;
  background: #242d3a;
  color: var(--text);
  border-radius: 999px;
  padding: 7px 11px;
  min-height: 34px;
  cursor: pointer;
  transition: all .2s ease;
  font-size: .82rem;
  line-height: 1;
  white-space: nowrap;
}
.ghost:hover { border-color: #6f819f; }
.error { margin: 0; padding: 10px; border-radius: 12px; color: #ffd9df; border: 1px solid rgba(240,142,160,.5); background: rgba(91,42,53,.30); }
.toast { position: fixed; right: 16px; bottom: 16px; background: #19202a; border: 1px solid #3c4860; color: #f9e9ee; padding: 10px 12px; border-radius: 10px; z-index: 80; }
.kds-grid { flex: 1; min-height: 0; }
.kds-grid-inner { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 10px; }
.kds-card {
  background: var(--panel);
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 10px;
  display: grid;
  gap: 8px;
  cursor: pointer;
  transition: all .2s ease;
}
.kds-card:hover { transform: scale(1.02); box-shadow: 0 8px 25px rgba(0,0,0,0.3); }
.card-selected { border-color: #8194b3; }
.card-new { animation: glowPremium 1.6s ease; }
.card-critical { border: 2px solid var(--danger); box-shadow: 0 0 0 2px rgba(255,77,77,.12); }
.kds-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.kds-card-head-main { display: grid; gap: 4px; }
.card-action-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 999px;
  border: 1px solid #445169;
  color: #cdd9ed;
  font-size: 1.1rem;
  background: rgba(116, 138, 170, .12);
}
.card-footer-hint {
  margin: 0;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  justify-self: end;
  color: #b9c6dc;
  font-size: .78rem;
  border: 1px dashed rgba(143, 162, 189, .45);
  border-radius: 999px;
  padding: 4px 10px;
}
.num { font-weight: 900; font-size: 1.42rem; margin: 0; line-height: 1.1; }
.kds-meta { margin: 2px 0 0; color: var(--muted); font-size: .78rem; }
.timer { border-radius: 999px; padding: 3px 8px; font-size: .72rem; font-weight: 800; border: 1px solid #3a4558; background: #242d3a; color: #dbe5f6; }
.t-ok, .t-warn, .t-critical { color: #dbe5f6; background: #242d3a; animation: none; }
.service-block { border-radius: 10px; border: 1px solid var(--line); background: var(--panel-soft); padding: 8px; display: grid; gap: 8px; }
.service-block-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.service-block--pendiente { border-color: rgba(107,114,128,.9); }
.service-block--preparando { border-color: rgba(245,196,81,.9); }
.service-block--listo { border-color: rgba(90,200,135,.9); }
.service-block--entregado { border-color: rgba(89,165,255,.8); }
.badge { border: 1px solid transparent; border-radius: 999px; font-size: .75rem; font-weight: 700; padding: 3px 8px; }
.b-pendiente { background: rgba(107,114,128,.2); color: #e2e8f0; border-color: rgba(107,114,128,.45); }
.b-preparando { background: rgba(245,196,81,.18); color: #ffe8ae; border-color: rgba(245,196,81,.45); }
.b-listo { background: rgba(90,200,135,.18); color: #d5ffe7; border-color: rgba(90,200,135,.45); }
.b-entregado { background: rgba(89,165,255,.18); color: #d6e7ff; border-color: rgba(89,165,255,.45); }
.items { margin: 0; padding: 0; list-style: none; display: grid; gap: 6px; }
.items li { display: flex; gap: 8px; align-items: baseline; }
.qty { min-width: 40px; color: #ffffff; font-weight: 900; font-size: 1.2rem; }
.name { font-weight: 700; font-size: 1.04rem; color: #eff4ff; text-transform: uppercase; letter-spacing: .01em; }
.card-note-preview { margin: 0; color: #b9c3d5; font-size: .8rem; line-height: 1.3; }
.action {
  width: auto;
  border: 1px solid #404c62;
  border-radius: 8px;
  padding: 7px 12px;
  font-size: .82rem;
  font-weight: 700;
  cursor: pointer;
  background: #2a3342;
  color: #eef4ff;
  justify-self: end;
}
.action:hover { filter: brightness(1.08); }
.action[disabled] { opacity: .55; cursor: not-allowed; }
.action-next-pendiente { background: #374151; border-color: #6b7280; color: #f5f7fb; }
.action-next-preparando { background: #4b3b16; border-color: #f5c451; color: #ffe6a7; }
.action-next-listo { display: none; }
.fade-enter-active, .fade-leave-active, .fade-move { transition: all .28s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(8px); }
.drawer-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, .52); display: flex; justify-content: flex-end; z-index: 90; }
.drawer { width: min(560px, 100vw); height: 100%; background: #151b23; border-left: 1px solid var(--line); padding: 18px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; }
.drawer-head { display: flex; justify-content: space-between; align-items: center; }
.drawer-title { margin: 0; font-size: 1.45rem; }
.ticket { background: #1d2530; border: 1px solid var(--line); border-radius: 12px; padding: 14px; }
.ticket-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.ticket-grid p { margin: 0; color: #eadddf; }
.priority-pill { margin-top: 6px; display: inline-block; border-radius: 999px; background: rgba(255,77,77,.12); color: #ffb4b4; border: 1px solid rgba(255,77,77,.55); font-size: .8rem; padding: 4px 9px; }
.item-row { border-bottom: 1px dashed rgba(255,255,255,.18); padding: 8px 0; }
.item-row:last-child { border-bottom: 0; }
.item-main { display:flex; justify-content:space-between; gap:10px; align-items:flex-start; }
.item-left { display:flex; gap:8px; align-items:baseline; min-width:0; }
.item-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.item-extra, .item-note { margin: 4px 0 0 0; color: #b8c0cf; font-size: .88rem; }
.notes-section { display: grid; gap: 10px; padding: 12px; }
.notes-title { margin: 0; display: inline-flex; align-items: center; gap: 8px; color: #e6dde4; font-size: .95rem; letter-spacing: .01em; }
.notes-block { margin: 0; border: 1px solid rgba(180, 192, 214, .26); background: #222b38; color: #e2e9f4; border-radius: 12px; padding: 12px; font-size: .93rem; line-height: 1.45; white-space: pre-wrap; word-break: break-word; }
.item-note-chip { max-width: 52%; display: inline-flex; align-items: flex-start; gap: 6px; border: 1px solid rgba(180, 192, 214, .30); background: rgba(180, 192, 214, .12); color: #dce4f0; border-radius: 999px; padding: 4px 10px; font-size: .79rem; line-height: 1.2; flex-shrink: 0; }
.item-note-chip--button { text-align: left; cursor: pointer; }
.item-note-chip--button:hover,.item-note-chip--button:focus-visible { border-color: rgba(212, 222, 240, .65); background: rgba(180, 192, 214, .18); outline: none; }
.item-note-text { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; word-break: break-word; }
.drawer-items { max-height: 46vh; overflow-y: auto; padding-right: 2px; }
.items-summary { margin: 0 0 8px 0; color: #d3c3cb; font-size: .87rem; }
.category-title { margin: 10px 0 4px 0; color: #f4d6de; font-size: .82rem; text-transform: uppercase; letter-spacing: .06em; }
.empty-items { margin: 8px 0 0; padding: 10px; border-radius: 10px; border: 1px dashed rgba(143,79,93,.42); background: rgba(91,42,53,.22); color: #d7b9c1; }
.drawer-actions { display: grid; gap: 8px; }
.drawer-slide-enter-active, .drawer-slide-leave-active { transition: all .25s ease; }
.drawer-slide-enter-from, .drawer-slide-leave-to { opacity: 0; }
.drawer-slide-enter-from .drawer, .drawer-slide-leave-to .drawer { transform: translateX(28px); }
.note-modal-overlay { position: fixed; inset: 0; background: rgba(3, 6, 14, .72); display: flex; align-items: center; justify-content: center; padding: 16px; z-index: 120; }
.note-modal { width: min(540px, 100%); background: #161d27; border: 1px solid rgba(180, 192, 214, .24); border-radius: 16px; display: grid; gap: 10px; padding: 14px; max-height: min(78vh, 640px); }
.note-modal-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.note-modal-title { margin: 0; font-size: 1rem; color: #e5edf8; }
.note-modal-context { margin: 0; color: #b8c4d9; font-size: .88rem; }
.note-modal-content { overflow-y: auto; border-radius: 12px; border: 1px solid rgba(180, 192, 214, .18); background: rgba(18, 24, 37, .8); padding: 12px; }
.note-modal-content p { margin: 0; color: #eaf1fb; line-height: 1.58; white-space: pre-wrap; word-break: break-word; }
.note-modal-footer { display: flex; justify-content: flex-end; }
.note-modal-close { border: 1px solid rgba(180, 192, 214, .28); background: rgba(180, 192, 214, .1); color: #e5edf8; border-radius: 10px; padding: 8px 14px; cursor: pointer; }
.note-modal-close-ghost { width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; }
.note-modal-enter-active, .note-modal-leave-active { transition: opacity .18s ease; }
.note-modal-enter-active .note-modal,.note-modal-leave-active .note-modal { transition: transform .22s ease, opacity .22s ease; }
.note-modal-enter-from, .note-modal-leave-to { opacity: 0; }
.note-modal-enter-from .note-modal,.note-modal-leave-to .note-modal { transform: translateY(10px) scale(.98); opacity: 0; }
body.note-modal-open { overflow: hidden; }
@keyframes glowPremium {
  0% { box-shadow: 0 0 0 rgba(90,200,135,0); }
  45% { box-shadow: 0 0 16px rgba(90,200,135,.24); }
  100% { box-shadow: 0 0 0 rgba(90,200,135,0); }
}
@media (max-width: 1280px) {
  .topbar { grid-template-columns: 1fr; gap: 8px; }
  .topbar-right { justify-content: space-between; }
}
@media (max-width: 900px) {
  .kds-grid-inner { grid-template-columns: 1fr; }
}
@media (max-width: 840px) {
  .topbar { padding: 8px 10px; }
  .topbar-title { font-size: 1.06rem; }
  .status-chip { font-size: .8rem; min-height: 32px; }
  .topbar-right { justify-content: flex-start; }
  .controls { width: 100%; }
  .controls .ghost { flex: 1; justify-content: center; }
  .user-panel { width: 100%; justify-content: space-between; }
  .drawer { width: 100vw; }
  .item-note-chip { font-size: .76rem; }
}
@media (max-width: 520px) {
  .item-main { flex-direction: column; align-items: stretch; }
  .item-note-chip { max-width: 100%; width: fit-content; }
}
.col-list::-webkit-scrollbar,.drawer-items::-webkit-scrollbar{width:8px;height:8px;}
.col-list::-webkit-scrollbar-thumb,.drawer-items::-webkit-scrollbar-thumb{background:rgba(111,123,145,.45);border-radius:999px;}
.col-list::-webkit-scrollbar-track,.drawer-items::-webkit-scrollbar-track{background:transparent;}
body.has-admin-back .kds { padding-top: 64px; }
.back-admin-btn {
  position: fixed;
  top: 14px;
  left: 18px;
  z-index: 120;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 7px 12px;
  border-radius: 999px;
  border: 1px solid #3b475c;
  background: #1b222d;
  color: #d6c8ce;
  text-decoration: none;
  font-size: .82rem;
  font-weight: 600;
}
.back-admin-btn:hover { border-color: #7084a6; color: #f0e8eb; transform: translateY(-1px); }
.back-admin-btn .back-admin-icon { font-size: .95rem; line-height: 1; opacity: .9; }
@media (max-width: 840px) {
  body.has-admin-back .kds { padding-top: 72px; }
  .back-admin-btn { left: 12px; top: 10px; }
}

</style>
</head>

<body class="{{ $isAdmin ? 'has-admin-back' : '' }}">
<div id="app" class="kds">
  @if($isAdmin)
    <a href="{{ $adminBackUrl }}" class="back-admin-btn" aria-label="Volver al panel de administración">
      <span class="back-admin-icon" aria-hidden="true">←</span>
      <span>Volver al Admin</span>
    </a>
  @endif

  <header class="topbar">
    <h1 class="topbar-title">{{ $serviceAreaLabel }}</h1>

    <div class="status-chips" role="tablist" aria-live="polite" aria-label="Filtro por estado">
      <button type="button" class="status-chip status-chip--pending" role="tab" :aria-selected="activeFilter === 'pendiente'" :class="{ active: activeFilter === 'pendiente' }" @click="activeFilter = 'pendiente'"><span class="status-chip-label">🧾 Pendientes</span><span class="status-chip-value">@{{ activeServiceSummary.pendiente }}</span></button>
      <button type="button" class="status-chip status-chip--cooking" role="tab" :aria-selected="activeFilter === 'preparando'" :class="{ active: activeFilter === 'preparando' }" @click="activeFilter = 'preparando'"><span class="status-chip-label">👨‍🍳 Preparando</span><span class="status-chip-value">@{{ activeServiceSummary.preparando }}</span></button>
      <button type="button" class="status-chip status-chip--ready" role="tab" :aria-selected="activeFilter === 'listo'" :class="{ active: activeFilter === 'listo' }" @click="activeFilter = 'listo'"><span class="status-chip-label">✅ Listos</span><span class="status-chip-value">@{{ activeServiceSummary.listo }}</span></button>
      <button type="button" class="status-chip" role="tab" :aria-selected="activeFilter === 'atrasados'" :class="{ active: activeFilter === 'atrasados' }" @click="activeFilter = 'atrasados'"><span class="status-chip-label">⏱ Atrasados</span><span class="status-chip-value">@{{ delayedCount }}</span></button>
    </div>

    <div class="topbar-right">
      <div class="controls" aria-label="Acciones rápidas">
        <button type="button" class="ghost" @click="fetchOrders(false)"><span class="btn-icon">↻</span> Actualizar</button>
        <button type="button" class="ghost" @click="toggleFullscreen"><span class="btn-icon">⤢</span> Pantalla completa</button>
      </div>

      <div class="user-panel">
        @if($user)
          <span class="user-label">{{ ($user->rol ?? 'usuario') . ': ' . ($user->name ?? $user->usuario ?? 'sin-nombre') }}</span>
        @endif
        @if(Route::has('logout'))
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="ghost">Cerrar sesión</button>
          </form>
        @endif
      </div>
    </div>
  </header>

  <p v-if="error" class="error">@{{ error }}</p>

  <div class="kds-grid">
    <transition-group name="fade" tag="div" class="kds-grid-inner">
      <article
        v-for="order in filteredOrders"
        :key="order.id"
        :id="`order-${order.id}`"
        class="kds-card"
        :class="{
          'card-new': highlightedIds.has(order.id),
          'card-critical': order._elapsedMin > 6,
          'card-selected': selectedOrderId === order.id,
        }"
        @click="openOrderDetails(order)"
      >
        <header class="kds-card-head">
          <div class="kds-card-head-main">
            <h2 class="num">Pedido #@{{ order.id }}</h2>
            <p class="kds-meta">Mesa @{{ order.mesa || '-' }} · @{{ fmtTime(order.created_at) }}</p>
          </div>
          <span class="card-action-icon" aria-hidden="true">›</span>
          <span class="timer" :class="timerClass(order)">@{{ formatElapsed(order._elapsedMs) }}</span>
        </header>


        <section
          v-for="group in serviceGroupsFor(order)"
          :key="`${order.id}-${group.key}`"
          class="service-block"
          :class="`service-block--${group.status}`"
          @click.stop
        >
          <header class="service-block-head">
            <strong>@{{ group.emoji }} @{{ group.label }}</strong>
            <span class="badge" :class="serviceBadgeClass(group.status)">@{{ statusLabel(group.status) }}</span>
          </header>

          <ul class="items">
            <li v-for="item in group.items" :key="item._k">
              <span class="qty">@{{ item._qty }}x</span>
              <span class="name">@{{ item._name }}</span>
            </li>
          </ul>

          <button
            v-if="canStartService(group.status)"
            class="action"
            :class="serviceActionClass(group.status)"
            :disabled="isGroupProcessing(order.id, group.key)"
            @click.stop="updateGroupStatus(order.id, group.key)"
          >
            @{{ isGroupProcessing(order.id, group.key) ? 'Guardando…' : (group.status === 'pendiente' ? 'Iniciar' : group.status === 'preparando' ? 'Listo' : serviceActionLabel(group.status, group.key)) }}
          </button>
        </section>

        <p v-if="orderPreviewNote(order)" class="card-note-preview" :title="orderPreviewNote(order)">@{{ orderPreviewNote(order) }}</p>
        <p class="card-footer-hint">👁 Ver detalles</p>
      </article>
    </transition-group>
  </div>

  <order-details-drawer
    :open="drawerOpen"
    :order="selectedOrder"
    :priority-overrides="priorityOverrides"
    @close="closeOrderDetails"
    @toast="showToast"
  />

  <div v-if="toastMessage" class="toast">@{{ toastMessage }}</div>
</div>

<script src="https://unpkg.com/vue@3"></script>
<script>
const POLLING_MS = 4000;
const DELIVERED_HIDE_MS = 15 * 60 * 1000;
const ACTIVE_SERVICE_AREA = @json($serviceArea);
const ACTIVE_SERVICE_LABEL = @json($serviceAreaLabel);
const STATUS_LABELS = {
  pendiente: 'Pendiente',
  preparando: 'En preparación',
  listo: 'Listo',
  entregado: 'Entregado',
};
const REQUESTED_WITH = 'XMLHttpRequest';

function normalizeStatus(status) {
  return String(status || '').trim().toLowerCase();
}

function statusLabelFor(status) {
  const normalized = normalizeStatus(status);
  return STATUS_LABELS[normalized] || (normalized ? normalized.charAt(0).toUpperCase() + normalized.slice(1) : '-');
}

function buildJsonHeaders(csrfToken = '', includeBody = false) {
  const headers = {
    'Accept': 'application/json',
    'X-Requested-With': REQUESTED_WITH,
  };

  if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
  if (includeBody) headers['Content-Type'] = 'application/json';

  return headers;
}

/* =========================================================
   ✅ HELPERS "DEEP" PARA NOTAS / CANTIDAD / NOMBRE / CATEGORÍA
   (tu API puede mandar item.pivot.nota, item.detalle.nota, etc.)
========================================================= */
function asText(v) {
  if (Array.isArray(v)) return v.join(' · ');
  if (v === null || v === undefined) return '';
  return String(v);
}
function deepGet(obj, path) {
  try {
    return path.split('.').reduce((acc, k) => (acc && acc[k] !== undefined ? acc[k] : undefined), obj);
  } catch { return undefined; }
}
function pickFirst(obj, paths) {
  for (const p of paths) {
    const val = deepGet(obj, p);
    const txt = asText(val).trim();
    if (txt) return txt;
  }
  return '';
}
function pickFirstNum(obj, paths, fallback = 1) {
  for (const p of paths) {
    const val = deepGet(obj, p);
    const n = Number(val);
    if (Number.isFinite(n) && n > 0) return n;
  }
  return fallback;
}
function normalizeAndDedupeOrders(list) {
  const map = new Map();

  for (const raw of (Array.isArray(list) ? list : [])) {
    const idNum = Number(raw?.id);
    if (!Number.isFinite(idNum)) continue;

    // normaliza ID siempre a number
    const normalized = { ...raw, id: idNum };
    map.set(idNum, normalized);
  }

  return [...map.values()];
}
function normalizeOrderItems(order) {
  const source =
    order?.items ||
    order?.detalles ||
    order?.detalle ||
    order?.pedido_detalles ||
    order?.order_items ||
    [];

  const arr = Array.isArray(source) ? source : [];

  return arr.map((raw, idx) => {
    // merge "pivot" o "detalle" si vienen así
    const merged = {
      ...raw,
      ...(raw?.pivot || {}),
      ...(raw?.detalle || {}),
      ...(raw?.pedido_detalle || {}),
      ...(raw?.order_item || {}),
    };

    const qty = pickFirstNum(merged, [
      'cantidad','quantity','qty',
      'pivot.cantidad','pivot.quantity','pivot.qty',
      'detalle.cantidad','pedido_detalle.cantidad','order_item.cantidad',
    ], 1);

    const name = pickFirst(merged, [
      'nombre',
      'menu_item.nombre',
      'menuItem.nombre',
      'producto.nombre',
      'product.nombre',
      'item.nombre',
    ]) || 'Item';

    const category = pickFirst(merged, [
      'categoria','category','tipo',
      'menu_item.categoria','menuItem.categoria'
    ]) || 'General';

    const note = pickFirst(merged, [
      // comunes
      'nota','observacion','comentario','note','notas',
      // nombres típicos en apps
      'instrucciones','instrucciones_especiales','instruccionesEspeciales',
      'special_instructions','specialInstructions',
      'comentarios_cliente','comentario_cliente','customer_note','customerNote',
      // anidados
      'pivot.nota','pivot.observacion','pivot.comentario','pivot.note','pivot.notas',
      'detalle.nota','pedido_detalle.nota','order_item.nota',
      'detalle.observacion','pedido_detalle.observacion','order_item.observacion',
      'detalle.comentario','pedido_detalle.comentario','order_item.comentario',
    ]);

    const extras = pickFirst(merged, [
      'extras','opciones','options','adiciones',
      'pivot.extras','pivot.opciones','detalle.extras'
    ]);

    const serviceGroupRaw = pickFirst(merged, ['grupo_servicio','grupoServicio','service_group','serviceGroup']) || 'plato';
    const serviceStatusRaw = pickFirst(merged, ['estado_servicio','estadoServicio','service_status','serviceStatus']) || order?.estado || 'pendiente';

    return {
      ...raw,
      _k: raw?.id || `${order?.id || 'o'}-${idx}`,
      _qty: qty,
      _name: name,
      _category: category,
      _note: note,
      _extras: extras,
      _serviceGroup: String(serviceGroupRaw).toLowerCase(),
      _serviceStatus: String(serviceStatusRaw).toLowerCase(),
    };
  });
}


const OrderDetailsDrawer = {
  props: {
    order: { type: Object, default: null },
    open: { type: Boolean, default: false },
    priorityOverrides: { type: Object, default: () => ({}) }, // lo dejo para no dañar tu lógica global
  },
  emits: ['close', 'actionDone', 'actionRequested', 'toast'],

  data() {
    return {
      loadingAction: false,
      noteModalOpen: false,
      noteModalText: '',
      noteModalContext: '',
      noteModalTitleId: 'note-modal-title',
    };
  },
  computed: {
    hasOrder() { return !!this.order; },

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
      // ✅ prioridad automática por atraso + override si existe (aunque ya no lo togglearás desde el drawer)
      return this.isOverdue || !!this.priorityOverrides[this.order.id];
    },

    // ✅ NORMALIZA ITEMS + NOTAS POR ITEM
    normalizedItems() {
      const source =
        this.order?.items ||
        this.order?.detalles ||
        this.order?.detalle ||
        this.order?.pedido_detalles ||
        [];

      if (!Array.isArray(source)) return [];

      return source.map((item, idx) => {
        const qty = Number(item.cantidad ?? item.quantity ?? 1) || 1;
        const name = item.nombre ?? item.menu_item?.nombre ?? item.menuItem?.nombre ?? item.producto?.nombre ?? 'Item';
        const extrasRaw = item.extras ?? item.opciones ?? item.options ?? item.adiciones ?? null;
        const raw = item.nota
          ?? item.note
          ?? item.notas
          ?? item.observacion
          ?? item.observaciones
          ?? item.comentario
          ?? item.comentarios
          ?? item.instrucciones
          ?? item.special_instructions
          ?? item.pivot?.nota
          ?? item.detalle?.nota
          ?? item.pedido_detalle?.nota
          ?? item.order_item?.nota
          ?? '';
        const categoryRaw = item.categoria ?? item.category ?? item.tipo ?? item.menu_item?.categoria ?? item.menuItem?.categoria ?? null;


        const extras = Array.isArray(extrasRaw) ? extrasRaw.join(', ') : extrasRaw;
        const note = this.normalizeNoteValue(raw);
        const category = String(categoryRaw || '').trim();

        return {
          id: item.id || `${this.order?.id || 'o'}-${idx}`,
          qty,
          name,
          extras: String(extras || '').trim(),
          note: String(note || '').trim(),
          category: category || 'General',
        };
      });
    },

    // ✅ NOTA GENERAL DEL PEDIDO (IMPORTANTE: incluye order.notas)
    orderComments() {
      return this.extractNote(this.order);

    },

    groupedItems() {
      const groups = {};
      this.normalizedItems.forEach((item) => {
        const key = item.category || 'General';
        if (!groups[key]) groups[key] = [];
        groups[key].push(item);
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
      return null;
    },
  },

  methods: {
    openNoteModal(noteText, contextText = '') {
      const normalized = this.normalizeNoteValue(noteText);
      if (!normalized) return;

      this.noteModalText = normalized;
      this.noteModalContext = String(contextText || '').trim();
      this.noteModalOpen = true;
      document.body.classList.add('note-modal-open');
    },

    closeNoteModal() {
      this.noteModalOpen = false;
      this.noteModalText = '';
      this.noteModalContext = '';
      document.body.classList.remove('note-modal-open');
    },

    onNoteChipKeydown(event, noteText, contextText = '') {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      event.preventDefault();
      this.openNoteModal(noteText, contextText);
    },

    normalizeNoteValue(raw) {
      if (Array.isArray(raw)) {
        return raw
          .map((entry) => String(entry ?? '').trim())
          .filter(Boolean)
          .join(' · ')
          .trim();
      }

      if (typeof raw === 'object' && raw !== null) {
        return Object.values(raw)
          .map((entry) => String(entry ?? '').trim())
          .filter(Boolean)
          .join(' · ')
          .trim();
      }

      return String(raw ?? '').trim();
    },
    extractNote(source) {
      if (!source || typeof source !== 'object') return '';

      const directFields = ['nota', 'nota_cliente', 'notas', 'observacion', 'observaciones', 'comentario', 'comentarios', 'note', 'notes', 'special_instructions', 'instrucciones'];
      const nestedFields = ['pivot', 'detalle', 'pedido_detalle', 'pedidoDetalle', 'order_item', 'orderItem'];

      const candidates = [];

      directFields.forEach((field) => {
        candidates.push(source[field]);
      });

      nestedFields.forEach((container) => {
        const nested = source[container];
        if (!nested || typeof nested !== 'object') return;
        directFields.forEach((field) => {
          candidates.push(nested[field]);
        });
      });

      for (const candidate of candidates) {
        const normalized = this.normalizeNoteValue(candidate);
        if (normalized) return normalized;
      }

      return '';
    },
    statusLabel(status) {
      return statusLabelFor(status);
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

    async executePrimaryAction() {
      if (!this.primaryAction || !this.order || this.loadingAction) return;
      this.loadingAction = true;
      this.$emit('actionRequested', this.order.id, this.primaryAction.next);
      this.$emit('actionDone', { orderId: this.order.id, nextStatus: this.primaryAction.next });
      setTimeout(() => { this.loadingAction = false; }, 150);
    },

    onEsc(evt) {
      if (evt.key === 'Escape' && this.noteModalOpen) {
        this.closeNoteModal();
        return;
      }
      if (evt.key === 'Escape' && this.open) this.$emit('close');
      if (evt.key === 'Enter' && this.open && !this.noteModalOpen) this.executePrimaryAction();
    },
  },

  mounted() {
    window.addEventListener('keydown', this.onEsc);
  },
  beforeUnmount() {
    window.removeEventListener('keydown', this.onEsc);
    document.body.classList.remove('note-modal-open');
  },

  watch: {
    open(nextOpen) {
      if (!nextOpen && this.noteModalOpen) this.closeNoteModal();
    },
  },

  // ✅ TEMPLATE: sin secondary-actions + con notas visibles
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
              <span class="timer"
                :class="order._elapsedMin > 6 ? 't-critical' : (order._elapsedMin >= 3 ? 't-warn' : 't-ok')"
                v-text="formatElapsed(order._elapsedMs)">
              </span>
            </div>

            <div class="ticket-grid" style="margin-top:10px;">
              <p><strong>Creado:</strong> <span v-text="fmtDate(order.created_at)"></span></p>
              <p><strong>Hora:</strong> <span v-text="fmtTime(order.created_at)"></span></p>
              <p><strong>Mesa:</strong> <span v-text="order.mesa || '-'"></span></p>
              <p><strong>Cliente:</strong> <span v-text="order.cliente?.nombre || order.cliente_nombre || '-'"></span></p>
            </div>

            <span v-if="isPriority" class="priority-pill"
              v-text="'Prioridad alta · ' + (delayLabel || 'Pedido priorizado')">
            </span>
          </section>

          <section v-if="hasOrder && orderComments" class="ticket notes-section">
            <h3 class="notes-title"><span aria-hidden="true">📝</span> Notas del cliente</h3>
            <p class="notes-block" v-text="orderComments"></p>

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
                    <div class="item-left">
                      <span class="qty" v-text="item.qty + 'x'"></span>
                      <strong class="item-name" v-text="item.name"></strong>
                    </div>
                    <button
                      v-if="item.note"
                      type="button"
                      class="item-note-chip item-note-chip--button"
                      :title="item.note"
                      :data-note="item.note"
                      @click.stop="openNoteModal(item.note, item.qty + 'x ' + item.name)"
                      @keydown="onNoteChipKeydown($event, item.note, item.qty + 'x ' + item.name)"
                    >
                      ✎ <span class="item-note-text" v-text="item.note"></span>
                    </button>
                  </div>

                  <p v-if="item.extras" class="item-extra" v-text="'Extras: ' + item.extras"></p>

                </article>
              </template>
            </div>
          </section>

          <!-- ✅ SOLO ACCIÓN PRINCIPAL (sin botones secundarios) -->
          <section class="ticket drawer-actions" v-if="hasOrder">
            <button v-if="primaryAction" :class="primaryAction.className" :disabled="loadingAction" @click="executePrimaryAction" v-text="loadingAction ? 'Guardando…' : primaryAction.label"></button>

            <p v-else class="muted" style="margin:0;">✅ Finalizado</p>
          </section>

          <transition name="note-modal">
            <div v-if="noteModalOpen" class="note-modal-overlay" @click.self="closeNoteModal">
              <section class="note-modal" role="dialog" aria-modal="true" :aria-labelledby="noteModalTitleId">
                <header class="note-modal-head">
                  <h3 class="note-modal-title" :id="noteModalTitleId">Nota del cliente</h3>
                  <button type="button" class="ghost note-modal-close note-modal-close-ghost" aria-label="Cerrar" @click="closeNoteModal">✕</button>
                </header>

                <p v-if="noteModalContext" class="note-modal-context" v-text="noteModalContext"></p>

                <div class="note-modal-content">
                  <p v-text="noteModalText"></p>
                </div>

                <footer class="note-modal-footer">
                  <button type="button" class="note-modal-close" @click="closeNoteModal">Cerrar</button>
                </footer>
              </section>
            </div>
          </transition>

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
      activeServiceArea: String(ACTIVE_SERVICE_AREA || 'plato').toLowerCase(),
      activeServiceLabel: ACTIVE_SERVICE_LABEL || 'Cocina',
      nowTs: Date.now(),
      error: '',
      soundEnabled: true,
      highlightedIds: new Set(),
      processingIds: new Set(),
      processingGroupIds: new Set(),
      optimisticSnapshots: {},
      lastSyncAt: null,
      syncInFlight: false,
      selectedOrderId: null,
      activeFilter: 'pendiente',
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
        const normalizedOrder = {
          ...order,
          _itemsNorm: normalizeOrderItems(order),
        };
        const activeGroup = this.serviceGroupsFor(normalizedOrder).find((group) => group.key === this.activeServiceArea);
        if (!activeGroup) {
          return null;
        }

        const status = String(activeGroup.status || 'pendiente').toLowerCase();

        return {
          ...normalizedOrder,
          estado: status,
          _createdTs: ts,
          _elapsedMs: elapsedMs,
          _elapsedMin: elapsedMs / 60000,
          _urgency: (elapsedMs / 60000) + (status === 'pendiente' ? 2 : 0) + (this.priorityOverrides[order.id] ? 2 : 0),
        };
      }).filter((order) => order);
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
    boardOrders() {
      return [...this.normalized].sort((a, b) => b._urgency - a._urgency || b._createdTs - a._createdTs);
    },
    filteredOrders() {
      if (this.activeFilter === 'atrasados') {
        return this.boardOrders.filter((order) => order.estado !== 'entregado' && order._elapsedMin > 6);
      }
      return this.boardOrders.filter((order) => order.estado === this.activeFilter);
    },
    serviceSummary() {
      const summary = {
        bebida: { pendiente: 0, preparando: 0, listo: 0, entregado: 0 },
        plato: { pendiente: 0, preparando: 0, listo: 0, entregado: 0 },
      };
      this.normalized.forEach((order) => {
        this.serviceGroupsFor(order, { includeAll: true }).forEach((group) => {
          if (summary[group.key] && summary[group.key][group.status] !== undefined) {
            summary[group.key][group.status] += 1;
          }
        });
      });
      return summary;
    },
    activeServiceSummary() {
      return this.serviceSummary[this.activeServiceArea] || { pendiente: 0, preparando: 0, listo: 0, entregado: 0 };
    },
    selectedOrder() {
      if (!this.selectedOrderId) return null;
      return this.normalized.find((o) => o.id === this.selectedOrderId) || null;
    },
    activeCount() { return this.grouped.pendiente.length + this.grouped.preparando.length + this.grouped.listo.length; },
    delayedCount() { return this.normalized.filter((o) => o.estado !== 'entregado' && o._elapsedMin > 6).length; },
    averageMinutes() {
      const active = this.normalized.filter((o) => o.estado !== 'listo');
      if (!active.length) return 0;
      return active.reduce((acc, o) => acc + o._elapsedMin, 0) / active.length;
    },
  },
  methods: {
    getOrderItems(order) {
      // ✅ usa lo ya normalizado cuando exista
      if (!order) return [];
      if (Array.isArray(order._itemsNorm)) return order._itemsNorm;
      return normalizeOrderItems(order);
    },
    fmtTime(dateRaw) {
      if (!dateRaw) return '-';
      const ts = Date.parse(dateRaw);
      if (!Number.isFinite(ts)) return '-';
      return new Date(ts).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
    },
    statusLabel(status) {
      return statusLabelFor(status);
    },
    apiRequestOptions(method = 'GET', body = null) {
      const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const hasBody = body !== null && body !== undefined;
      return {
        method,
        credentials: 'include',
        headers: buildJsonHeaders(token, hasBody),
        ...(hasBody ? { body: JSON.stringify(body) } : {}),
      };
    },
    async requestFirstOk(attempts) {
      const failures = [];

      for (const attempt of attempts) {
        try {
          const response = await fetch(attempt.url, this.apiRequestOptions(attempt.method || 'GET', attempt.body));
          if (response.ok) {
            return { ok: true, response, source: attempt.source || attempt.url };
          }
          failures.push({ status: response.status, url: attempt.url, response });
        } catch (error) {
          failures.push({ status: 0, url: attempt.url, error });
        }
      }

      const unauthorized = failures.some((failure) => failure.status === 401 || failure.status === 419);
      return { ok: false, failures, unauthorized };
    },
    handleUnauthorized(message = 'Sesión expirada en cocina') {
      this.error = message;
      this.showToast('🔐 Inicia sesión nuevamente o usa una ruta web con sesión/cookies en desarrollo.');
    },
    canStartService(status) {
      return status === 'pendiente' || status === 'preparando';
    },
    nextServiceStatus(status) {
      if (status === 'pendiente') return 'preparando';
      if (status === 'preparando') return 'listo';
      return status;
    },
    serviceActionLabel(status, groupKey = null) {
      if (status === 'pendiente') {
        if ((groupKey || this.activeServiceArea) === 'bebida') return 'Iniciar bar';
        return 'Iniciar cocina';
      }
      if (status === 'preparando') return 'Marcar listo';
      if (status === 'listo') return 'Listo';
      return 'Finalizado';
    },

    serviceActionClass(status) {
      if (status === 'pendiente') return 'action-next-pendiente';
      if (status === 'preparando') return 'action-next-preparando';
      if (status === 'listo') return 'action-next-listo';
      return '';
    },
    serviceBadgeClass(status) {
      if (!status) return '';
      return `b-${status}`;
    },
    isGroupProcessing(orderId, groupKey) {
      return this.processingGroupIds.has(`${orderId}:${groupKey}`);
    },
    serviceGroupsFor(order, options = {}) {
      const labels = {
        bebida: { label: 'Bebidas', emoji: '🍹' },
        plato: { label: 'Platos', emoji: '🍽' },
      };
      const groups = { bebida: [], plato: [] };
      this.getOrderItems(order).forEach((item) => {
        const key = (item._serviceGroup || 'plato').toLowerCase();
        if (groups[key]) groups[key].push(item);
      });
      const allowedGroups = options.includeAll ? ['bebida', 'plato'] : [this.activeServiceArea];
      return allowedGroups
        .filter((key) => groups[key]?.length)
        .map((key) => {
          const statuses = groups[key].map((item) => item._serviceStatus || order.estado || 'pendiente');
          const status = this.resolveGroupStatus(statuses);
          return { key, ...labels[key], status, items: groups[key] };
        });
    },
    resolveGroupStatus(statuses) {
      if (statuses.includes('pendiente')) return 'pendiente';
      if (statuses.includes('preparando')) return 'preparando';
      if (statuses.includes('listo')) return 'listo';
      return 'entregado';
    },
    patchOrderGroupStatus(order, groupKey, nextStatus) {
      const source = this.getOrderItems(order);
      const patchedItems = source.map((item) => {
        if ((item._serviceGroup || 'plato') !== groupKey) return item;
        return { ...item, _serviceStatus: nextStatus, estado_servicio: nextStatus };
      });
      return { ...order, _itemsNorm: patchedItems };
    },
    async updateGroupStatus(orderId, groupKey) {
      const processingKey = `${orderId}:${groupKey}`;
      if (this.processingGroupIds.has(processingKey)) return;

      const idx = this.orders.findIndex((o) => Number(o.id) === Number(orderId));
      if (idx < 0) return;

      const prevOrder = this.orders[idx];
      const currentGroup = this.serviceGroupsFor(prevOrder, { includeAll: true }).find((group) => group.key === groupKey);
      const currentStatus = currentGroup?.status || 'pendiente';
      const nextStatus = this.nextServiceStatus(currentStatus);

      if (nextStatus === currentStatus) return;


      const optimisticOrder = this.patchOrderGroupStatus(prevOrder, groupKey, nextStatus);
      this.orders = this.orders.map((o) => (Number(o.id) === Number(orderId) ? optimisticOrder : o));

      const nextSet = new Set(this.processingGroupIds);
      nextSet.add(processingKey);
      this.processingGroupIds = nextSet;

      const attempts = [
        { url: `/pedidos/${orderId}/servicio/${groupKey}`, method: 'PUT', source: 'api-service-group' },
      ];


      try {
        const result = await this.requestFirstOk(attempts);
        if (!result.ok) {
          if (result.unauthorized) {
            this.handleUnauthorized('Sesión no autorizada para actualizar grupos de servicio');
          }
          throw new Error('No endpoint accepted service-group update');
        }
        this.error = '';
      } catch (err) {
        this.orders = this.orders.map((o) => (Number(o.id) === Number(orderId) ? prevOrder : o));
        if (!this.error) {
          this.error = 'No se pudo actualizar el estado por grupo de servicio';
        }
        this.showToast('⚠️ No se pudo guardar el grupo. Revertido.');
      } finally {
        const doneSet = new Set(this.processingGroupIds);
        doneSet.delete(processingKey);
        this.processingGroupIds = doneSet;
      }
    },

    orderNoteCount(order) {
      const items = this.getOrderItems(order);
      const itemNotes = items.reduce((acc, it) => acc + (String(it._note || '').trim() ? 1 : 0), 0);

      const orderComment = pickFirst(order || {}, [
        'comentarios','comentario','observacion','nota','notas','note',
        'special_instructions','specialInstructions','instrucciones'
      ]);

      return itemNotes + (String(orderComment || '').trim() ? 1 : 0);
    },

    orderPreviewNote(order) {
      const items = this.getOrderItems(order);
      const firstItemNote = items.map((it) => String(it._note || '').trim()).find(Boolean);
      if (firstItemNote) return `Nota cliente: ${firstItemNote}`;

      const orderComment = pickFirst(order || {}, [
        'comentarios','comentario','observacion','nota','notas','note',
        'special_instructions','specialInstructions','instrucciones'
      ]);
      return String(orderComment || '').trim() ? `Comentario: ${String(orderComment).trim()}` : '';
    },

    showToast(message) {
      this.toastMessage = message;
      clearTimeout(this.toastHandle);
      this.toastHandle = setTimeout(() => { this.toastMessage = ''; }, 2200);
    },
    togglePriority(orderId) {
      this.priorityOverrides = { ...this.priorityOverrides, [orderId]: !this.priorityOverrides[orderId] };
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

    openOrderDetails(order) {
      this.selectedOrderId = order.id;
      this.drawerOpen = true;
    },
    closeOrderDetails() {
      this.drawerOpen = false;
      this.selectedOrderId = null;
    },
    mergeIncomingOrders(incomingOrders, isInitial = false) {
      // ✅ Asegura que lo que ya está en memoria también tenga id numérico
      this.orders = this.orders
      .map(o => ({ ...o, id: Number(o.id) }))
      .filter(o => Number.isFinite(o.id));
      const beforeIds = new Set(this.orders.map((o) => o.id));
      if (isInitial) {
        this.orders = incomingOrders;
      } else {
        const incomingById = new Map(incomingOrders.map((o) => [o.id, o]));
        const merged = this.orders.map((order) => {
          const candidate = incomingById.get(order.id);
          if (!candidate) return order;
          if (this.processingIds.has(order.id)) return order;
          incomingById.delete(order.id);
          return { ...order, ...candidate };
        });
        for (const pending of incomingById.values()) {
          merged.push(pending);
        }
        this.orders = merged;
      }

      const newOrders = this.orders.filter((o) => !beforeIds.has(o.id) && String(o.estado || '').toLowerCase() === 'pendiente');
      if (!isInitial) this.handleNewOrders(newOrders);
    },
    async fetchOrders(isInitial = false) {
      if (this.syncInFlight) return;
      this.syncInFlight = true;

      try {
        const qs = new URLSearchParams();
        if (!isInitial && this.lastSyncAt) qs.set('since', this.lastSyncAt);

        const result = await this.requestFirstOk([
  { url: '/pedidos', method: 'GET', source: 'api-orders' },
]);

        if (!result.ok) {
          if (result.unauthorized) {
            this.handleUnauthorized('Sesión no autorizada para consultar la cocina');
            return;
          }
          throw new Error('status sync failed');
        }

        const payload = await result.response.json().catch(() => []);
        const incoming = payload?.data ?? payload ?? [];
        const items = Array.isArray(incoming) ? incoming : [];

        const cleanItems = normalizeAndDedupeOrders(items);

        this.mergeIncomingOrders(cleanItems, isInitial || !this.lastSyncAt);
        this.lastSyncAt = payload?.meta?.server_time || new Date().toISOString();
        this.error = '';
      } catch (e) {
        this.error = 'No se pudo sincronizar la cocina';
      } finally {
        this.syncInFlight = false;
      }
    },
    handleNewOrders(newOrders) {
      if (!newOrders.length) return;
      if (this.soundEnabled) this.beep();
      const next = new Set(this.highlightedIds);
      newOrders.forEach((o) => {
        next.add(o.id);
        this.$nextTick(() => {
          const el = document.getElementById(`order-${o.id}`);
          el?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
      });
      this.highlightedIds = next;
      setTimeout(() => {
        const clean = new Set(this.highlightedIds);
        newOrders.forEach((o) => clean.delete(o.id));
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
