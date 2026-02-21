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
  --text: #e8ecf3;
  --muted: #aab3c2;
  --line: rgba(180, 192, 214, .20);
  --glass: rgba(15, 20, 31, .62);
  --glass-strong: rgba(13, 18, 28, .78);
  --wine: #5b2a35;
  --wine-soft: #6e3642;
  --wine-glow: rgba(110, 54, 66, .16);
  --ok: #4d7f67;
  --warn: #8f7448;
  --danger: #8f4f5d;
}
* { box-sizing: border-box; }
body {
  margin: 0;
  min-height: 100vh;
  font-family: Inter, "Segoe UI", system-ui, sans-serif;
  color: var(--text);
  background-image:
    linear-gradient(140deg, rgba(8,11,17,.82), rgba(11,15,23,.78)),
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
  box-shadow: 0 6px 18px rgba(2,6,13,.26);
}
.topbar h1 { margin: 0; font-size: 1.42rem; letter-spacing: .01em; }
.muted { margin: 2px 0 0; color: var(--muted); }
.stats { display: grid; grid-template-columns: repeat(3, minmax(86px, 1fr)); gap: 8px; }
.stats article {
  background: rgba(148, 163, 184, .06);
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
.ghost:hover { border-color: rgba(110, 54, 66, .42); box-shadow: 0 0 0 1px rgba(110, 54, 66, .20); }
.error {
  margin: 0;
  padding: 10px;
  border-radius: 12px;
  color: #ffd9df;
  border: 1px solid rgba(240,142,160,.5);
  background: rgba(91,42,53,.30);
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
  box-shadow: 0 6px 16px rgba(2,6,13,.22);
}
.col-head { position: sticky; top: 0; z-index: 3; padding: 11px 12px; border-bottom: 1px solid rgba(255,255,255,.14); background: rgba(12, 17, 27, .76); backdrop-filter: blur(6px); display: flex; justify-content: space-between; align-items: center; }
.col-list { overflow-y: auto; min-height: 0; padding: 10px; display: flex; flex-direction: column; gap: 10px; scrollbar-width: thin; scrollbar-color: rgba(165,58,74,.55) transparent; }
.card {
  background: linear-gradient(180deg, rgba(16,22,34,.76), rgba(13,19,30,.82));
  border: 1px solid rgba(167, 179, 201, .20);
  border-radius: 14px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  cursor: pointer;
  transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.card:hover { transform: translateY(-1px); border-color: rgba(161, 173, 194, .32); box-shadow: 0 8px 16px rgba(2,6,13,.24); }
.card-selected { border-color: rgba(124, 137, 160, .55); box-shadow: 0 0 0 1px rgba(124, 137, 160, .22); }
.card-new { animation: glowPremium 1.6s ease; }
.card-critical { border-color: rgba(143, 79, 93, .55); }
.card-head { display: flex; justify-content: space-between; align-items: center; }
.num { font-weight: 800; font-size: 1.12rem; }
.timer { border-radius: 999px; padding: 4px 10px; font-weight: 800; letter-spacing: .02em; }
.t-ok { color: #b8d5c8; background: rgba(77,127,103,.22); }
.t-warn { color: #d7c39b; background: rgba(143,116,72,.24); }
.t-critical { color: #d9a9b3; background: rgba(143,79,93,.22); animation: pulseRed 2.4s ease-in-out infinite; }
.items { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 6px; }
.items li { display: flex; gap: 8px; align-items: baseline; }
.qty { min-width: 42px; color: #f3d6a2; font-weight: 900; font-size: 1.15rem; }
.name { font-weight: 620; }

.card-head-meta { display:flex; align-items:center; gap:8px; }
.note-indicator { display:inline-flex; align-items:center; gap:6px; padding: 3px 8px; border-radius: 999px; border: 1px solid rgba(180, 192, 214, .28); background: rgba(180, 192, 214, .10); color: #d3dbea; font-size: .74rem; font-weight: 600; line-height: 1; }
.note-indicator-icon { font-size: .72rem; opacity: .86; }

.card-note-preview {
  margin: 0;
  color: #c5cedd;
  font-size: .8rem;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}

.action { width: 100%; border: none; border-radius: 12px; padding: 13px 12px; font-size: 0.98rem; font-weight: 800; letter-spacing: .01em; cursor: pointer; transition: transform .15s ease, filter .15s ease; }
.action:hover { filter: brightness(1.03); transform: translateY(-1px); }
.action:active { transform: translateY(0); }
.action[disabled] { opacity: .55; cursor: not-allowed; transform: none; }
.action-start { background: linear-gradient(180deg, #7f5037, #6d442f); color: #f3ebe7; }
.action-ready { background: linear-gradient(180deg, #456b58, #3c5d4d); color: #e5efe9; }
.action-deliver { background: linear-gradient(180deg, #4c6279, #41566c); color: #e4edf6; }
.fade-enter-active, .fade-leave-active, .fade-move { transition: all .28s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(8px); }

.drawer-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, .52); display: flex; justify-content: flex-end; z-index: 90; }
.drawer {
  width: min(560px, 100vw);
  height: 100%;
  background: rgba(12, 17, 27, .88);
  border-left: 1px solid var(--line);
  backdrop-filter: blur(10px);
  padding: 14px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
  box-shadow: -6px 0 18px rgba(2,6,13,.30);
}
.drawer-head { display: flex; justify-content: space-between; align-items: center; }
.drawer-title { margin: 0; font-size: 1.5rem; }
.badge { border: 1px solid transparent; border-radius: 999px; font-size: .82rem; font-weight: 700; padding: 4px 10px; }
.b-pendiente { background: rgba(143,116,72,.20); color: #d8c29b; border-color: rgba(143,116,72,.42); }
.b-preparando { background: rgba(127,80,55,.20); color: #d7b6a6; border-color: rgba(127,80,55,.42); }
.b-listo { background: rgba(77,127,103,.20); color: #bbd1c7; border-color: rgba(77,127,103,.42); }
.b-entregado { background: rgba(76,98,121,.20); color: #b9c7d7; border-color: rgba(76,98,121,.42); }
.ticket { background: rgba(15, 20, 31, .64); border: 1px solid var(--line); border-radius: 12px; padding: 12px; }
.ticket-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.ticket-grid p { margin: 0; color: #eadddf; }
.priority-pill {
  margin-top: 6px;
  display: inline-block;
  border-radius: 999px;
  background: rgba(91,42,53,.32);
  color: #cfadb5;
  border: 1px solid rgba(143,79,93,.46);
  font-size: .8rem;
  padding: 4px 9px;
  animation: pulseRed 2.8s ease-in-out infinite;
}
.item-row { border-bottom: 1px dashed rgba(255,255,255,.18); padding: 8px 0; }
.item-row:last-child { border-bottom: 0; }
.item-main { display:flex; justify-content:space-between; gap:10px; align-items:flex-start; }
.item-left { display:flex; gap:8px; align-items:baseline; min-width:0; }
.item-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.item-extra, .item-note { margin: 4px 0 0 0; color: #b8c0cf; font-size: .88rem; }
.notes-section { display: grid; gap: 10px; }
.notes-title { margin: 0; display: inline-flex; align-items: center; gap: 8px; color: #e6dde4; font-size: .95rem; letter-spacing: .01em; }
.notes-block {
  margin: 0;
  border: 1px solid rgba(180, 192, 214, .26);
  background: linear-gradient(180deg, rgba(180, 192, 214, .12), rgba(180, 192, 214, .06));
  color: #e2e9f4;
  border-radius: 12px;
  padding: 12px;
  font-size: .93rem;
  line-height: 1.45;
  white-space: pre-wrap;
  word-break: break-word;
}
.item-note-chip {
  max-width: 52%;
  display: inline-flex;
  align-items: flex-start;
  gap: 6px;
  border: 1px solid rgba(180, 192, 214, .30);
  background: rgba(180, 192, 214, .12);
  color: #dce4f0;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: .79rem;
  line-height: 1.2;
  flex-shrink: 0;
}
.item-note-chip--button {
  text-align: left;
  cursor: pointer;
  transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
}
.item-note-chip--button:hover,
.item-note-chip--button:focus-visible {
  border-color: rgba(212, 222, 240, .65);
  background: rgba(180, 192, 214, .18);
  box-shadow: 0 0 0 2px rgba(180, 192, 214, .16);
  outline: none;
}
.item-note-text {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;

  word-break: break-word;
}
.drawer-items { max-height: 46vh; overflow-y: auto; padding-right: 2px; }
.items-summary { margin: 0 0 8px 0; color: #d3c3cb; font-size: .87rem; }
.category-title { margin: 10px 0 4px 0; color: #f4d6de; font-size: .82rem; text-transform: uppercase; letter-spacing: .06em; }
.empty-items {
  margin: 8px 0 0;
  padding: 10px;
  border-radius: 10px;
  border: 1px dashed rgba(143,79,93,.42);
  background: rgba(91,42,53,.22);
  color: #d7b9c1;
}
.drawer-actions { display: grid; gap: 8px; }
.drawer-slide-enter-active, .drawer-slide-leave-active { transition: all .25s ease; }
.drawer-slide-enter-from, .drawer-slide-leave-to { opacity: 0; }
.drawer-slide-enter-from .drawer, .drawer-slide-leave-to .drawer { transform: translateX(28px); }


.note-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(3, 6, 14, .72);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  z-index: 120;
}
.note-modal {
  width: min(540px, 100%);
  background: linear-gradient(180deg, rgba(15, 20, 31, .98), rgba(12, 17, 27, .98));
  border: 1px solid rgba(180, 192, 214, .24);
  border-radius: 16px;
  box-shadow: 0 20px 48px rgba(2,6,13,.46);
  display: grid;
  gap: 10px;
  padding: 14px;
  max-height: min(78vh, 640px);
}
.note-modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}
.note-modal-title {
  margin: 0;
  font-size: 1rem;
  color: #e5edf8;
}
.note-modal-context {
  margin: 0;
  color: #b8c4d9;
  font-size: .88rem;
}
.note-modal-content {
  overflow-y: auto;
  border-radius: 12px;
  border: 1px solid rgba(180, 192, 214, .18);
  background: rgba(18, 24, 37, .8);
  padding: 12px;
}
.note-modal-content p {
  margin: 0;
  color: #eaf1fb;
  line-height: 1.58;
  white-space: pre-wrap;
  word-break: break-word;
}
.note-modal-footer {
  display: flex;
  justify-content: flex-end;
}
.note-modal-close {
  border: 1px solid rgba(180, 192, 214, .28);
  background: rgba(180, 192, 214, .1);
  color: #e5edf8;
  border-radius: 10px;
  padding: 8px 14px;
  cursor: pointer;
}
.note-modal-close-ghost {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.note-modal-enter-active, .note-modal-leave-active {
  transition: opacity .18s ease;
}
.note-modal-enter-active .note-modal,
.note-modal-leave-active .note-modal {
  transition: transform .22s ease, opacity .22s ease;
}
.note-modal-enter-from, .note-modal-leave-to {
  opacity: 0;
}
.note-modal-enter-from .note-modal,
.note-modal-leave-to .note-modal {
  transform: translateY(10px) scale(.98);
  opacity: 0;
}
body.note-modal-open { overflow: hidden; }

@keyframes glowPremium {
  0% { box-shadow: 0 0 0 rgba(165,58,74,0); }
  45% { box-shadow: 0 0 12px rgba(110,54,66,.18); }
  100% { box-shadow: 0 0 0 rgba(165,58,74,0); }
}
@keyframes pulseRed {
  0%,100% { box-shadow: 0 0 0 rgba(240,142,160,0); }
  50% { box-shadow: 0 0 8px rgba(143,79,93,.18); }
}
@media (max-width: 1280px) {
  .topbar { grid-template-columns: 1fr; }
  .board { grid-template-columns: repeat(2, minmax(250px, 1fr)); }
}
@media (max-width: 840px) {
  .board { grid-template-columns: 1fr; }
  .drawer { width: 100vw; }
  .notes-block { padding: 10px; font-size: .9rem; }
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
  border: 1px solid rgba(180, 192, 214, .24);
  background: rgba(15, 20, 31, .42);
  backdrop-filter: blur(6px);
  color: #d6c8ce;
  text-decoration: none;
  font-size: .82rem;
  font-weight: 600;
  letter-spacing: .01em;
  box-shadow: 0 2px 8px rgba(2,6,13,.18);
  transition: all .2s ease;
}
.back-admin-btn:hover {
  border-color: rgba(110, 54, 66, .38);
  color: #f0e8eb;
  transform: translateY(-1px);
}
.back-admin-btn .back-admin-icon { font-size: .95rem; line-height: 1; opacity: .9; }
@media (max-width: 840px) {
  body.has-admin-back .kds { padding-top: 72px; }
  .back-admin-btn { left: 12px; top: 10px; }
}
/* =========================
   📝 NOTAS DEL CLIENTE (PRO)
========================= */
.notes-section { padding: 12px; }
.notes-head {
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:10px;
  margin-bottom: 10px;
}
.notes-title { font-weight: 800; letter-spacing: .01em; }
.notes-count{
  font-size: .78rem;
  color: #d3dbea;
  border: 1px solid rgba(180,192,214,.26);
  background: rgba(180,192,214,.10);
  border-radius: 999px;
  padding: 3px 9px;
}

.notes-list{ display:flex; flex-direction:column; gap:10px; margin-top:10px; }
.notes-row{
  border: 1px solid rgba(180,192,214,.18);
  background: rgba(180,192,214,.08);
  border-radius: 12px;
  padding: 10px;
}
.notes-row-left{
  display:flex;
  align-items:center;
  gap:10px;
  margin-bottom: 6px;
}
.notes-chip{
  font-size: .78rem;
  font-weight: 800;
  color: #f3d6a2;
  background: rgba(143,116,72,.18);
  border: 1px solid rgba(143,116,72,.30);
  border-radius: 999px;
  padding: 3px 8px;
}
.notes-item-name{ font-weight: 800; }
.notes-row-note{
  color: #dce3ef;
  font-size: .92rem;
  line-height: 1.35;
  white-space: pre-wrap;
  word-break: break-word;
}

/* Nota por item más pro */
.item-client-note{
  margin: 8px 0 0;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  max-width: 100%;
  border: 1px solid rgba(180,192,214,.22);
  background: rgba(180,192,214,.10);
  color: #d8dfeb;
  border-radius: 12px;
  padding: 7px 10px;
  font-size: .86rem;
  line-height: 1.35;
  white-space: pre-wrap;
  word-break: break-word;
}
.item-note-label{
  font-size: .75rem;
  font-weight: 800;
  opacity: .85;
  border: 1px solid rgba(255,255,255,.18);
  background: rgba(255,255,255,.06);
  padding: 2px 8px;
  border-radius: 999px;
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
            <div class="card-head-meta">
              <span
                v-if="orderNoteCount(order) > 0"
                class="note-indicator"
                role="status"
                :aria-label="`Pedido con ${orderNoteCount(order)} nota(s)`"
              >
                <span class="note-indicator-icon" aria-hidden="true">✎</span>
                <span>@{{ orderNoteCount(order) }}</span>
              </span>
              <span class="timer" :class="timerClass(order)">@{{ formatElapsed(order._elapsedMs) }}</span>
            </div>
          </header>

          <ul class="items">
            <li v-for="(item, idx) in getOrderItems(order).slice(0,4)" :key="item._k || item.id || `${order.id}-${idx}`">
              <span class="qty">@{{ item._qty }}x</span>
              <span class="name">@{{ item._name }}</span>
            </li>
          </ul>

          <p v-if="orderPreviewNote(order)" class="card-note-preview" :title="orderPreviewNote(order)">@{{ orderPreviewNote(order) }}</p>

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
    @toast="showToast"
  />

  <div v-if="toastMessage" class="toast">@{{ toastMessage }}</div>
</div>

<script src="https://unpkg.com/vue@3"></script>
<script>
const POLLING_MS = 4000;
const DELIVERED_HIDE_MS = 15 * 60 * 1000;

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

    return {
      ...raw,
      _k: raw?.id || `${order?.id || 'o'}-${idx}`,
      _qty: qty,
      _name: name,
      _category: category,
      _note: note,
      _extras: extras,
    };
  });
}


const OrderDetailsDrawer = {
  props: {
    order: { type: Object, default: null },
    open: { type: Boolean, default: false },
    priorityOverrides: { type: Object, default: () => ({}) }, // lo dejo para no dañar tu lógica global
  },
  emits: ['close', 'actionDone', 'toast'],

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
      if (!this.order) return null;
      if (this.order.estado === 'pendiente')
        return { label: 'Comenzar', next: 'preparando', className: 'action action-start' };
      if (this.order.estado === 'preparando')
        return { label: 'Marcar listo', next: 'listo', className: 'action action-ready' };
      if (this.order.estado === 'listo')
        return { label: 'Entregar', next: 'entregado', className: 'action action-deliver' };
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
          body: endpoint.startsWith('/api/kitchen')
            ? undefined
            : JSON.stringify({ estado: this.primaryAction.next }),
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
            <button v-if="primaryAction" :class="primaryAction.className" :disabled="loadingAction" @click="executePrimaryAction" v-text="loadingAction ? 'Procesando...' : primaryAction.label"></button>

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
          // ✅ items normalizados para TODO el app (tablero + notas)
          _itemsNorm: normalizeOrderItems(order),

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
      return this.normalized.find((o) => o.id === this.selectedOrderId) || null;
    },
    activeCount() { return this.grouped.pendiente.length + this.grouped.preparando.length + this.grouped.listo.length; },
    delayedCount() { return this.normalized.filter((o) => o.estado !== 'entregado' && o._elapsedMin > 6).length; },
    averageMinutes() {
      const active = this.normalized.filter((o) => o.estado !== 'entregado');
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

        this.orders = this.orders.map((o) => o.id === orderId ? { ...o, estado: nextStatus } : o);
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
      this.orders = this.orders.map((o) => o.id === payload.orderId ? { ...o, estado: payload.nextStatus } : o);
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