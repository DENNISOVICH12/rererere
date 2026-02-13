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
.kds {
    min-height: 100vh;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.topbar {
    display: grid;
    grid-template-columns: 1.2fr auto auto;
    gap: 10px;
    align-items: center;
}
.topbar h1 { margin: 0; font-size: 1.65rem; }
.muted { margin: 2px 0 0; color: var(--muted); }
.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(86px, 1fr));
    gap: 8px;
}
.stats article {
    background: rgba(255,255,255,.05);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 8px;
    text-align: center;
}
.stats span { color: var(--muted); font-size: .75rem; display: block; }
.stats strong { font-size: 1.1rem; }
.controls { display: flex; gap: 8px; }
.ghost {
    border: 1px solid rgba(255,255,255,.24);
    background: transparent;
    color: var(--text);
    border-radius: 10px;
    padding: 10px 12px;
    cursor: pointer;
}
.error {
    margin: 0;
    padding: 10px;
    border-radius: 10px;
    color: #ffe4e6;
    border: 1px solid rgba(251, 113, 133, .45);
    background: rgba(159, 18, 57, .22);
}
.board {
    flex: 1;
    min-height: 0;
    display: grid;
    grid-template-columns: repeat(4, minmax(250px, 1fr));
    gap: 10px;
}
.col {
    background: var(--panel-2);
    border: 1px solid var(--line);
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.col-head {
    padding: 12px;
    border-bottom: 1px solid var(--line);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.col-list {
    overflow-y: auto;
    min-height: 0;
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.card {
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.card-new { animation: glow 2s ease; }
.card-critical { border-color: rgba(251, 113, 133, .7); }
.card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.num { font-weight: 800; font-size: 1.15rem; }
.timer {
    border-radius: 999px;
    padding: 4px 10px;
    font-weight: 800;
}
.t-ok { color: #4ade80; background: rgba(34,197,94,.18); }
.t-warn { color: #fde047; background: rgba(250,204,21,.18); }
.t-critical { color: var(--red); background: rgba(251,113,133,.17); animation: pulseRed 1.2s infinite; }
.items { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 6px; }
.items li { display: flex; gap: 8px; align-items: baseline; }
.qty { min-width: 42px; color: var(--yellow); font-weight: 900; font-size: 1.18rem; }
.name { font-weight: 600; }
.note {
    margin: 0;
    padding: 8px;
    border-radius: 8px;
    background: rgba(250, 204, 21, .14);
    color: #fde68a;
    font-size: .9rem;
}
.action {
    width: 100%;
    border: none;
    border-radius: 12px;
    padding: 16px 12px;
    font-size: 1.05rem;
    font-weight: 900;
    cursor: pointer;
}
.action[disabled] { opacity: .55; cursor: not-allowed; }
.action-start { background: var(--orange); color: #fff; }
.action-ready { background: var(--green); color: #082610; }
.action-deliver { background: var(--blue); color: #07293b; }
.fade-enter-active, .fade-leave-active, .fade-move { transition: all .32s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(8px); }
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
                    :class="{ 'card-new': highlightedIds.has(order.id), 'card-critical': order._elapsedMin > 6 }"
                >
                    <header class="card-head">
                        <span class="num">#@{{ order.id }}</span>
                        <span class="timer" :class="timerClass(order)">@{{ formatElapsed(order._elapsedMs) }}</span>
                    </header>

                    <ul class="items">
                        <li v-for="(item, idx) in order.items" :key="item.id || `${order.id}-${idx}`">
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
                        @click="actionFor(order).run()"
                    >
                        @{{ processingIds.has(order.id) ? 'Procesando...' : actionFor(order).label }}
                    </button>
                </article>
            </transition-group>
        </section>
    </div>
</div>

<script src="https://unpkg.com/vue@3"></script>
<script>
const POLLING_MS = 4000;
const DELIVERED_HIDE_MS = 15 * 60 * 1000;

Vue.createApp({
    data() {
        return {
            orders: [],
            nowTs: Date.now(),
            error: '',
            soundEnabled: true,
            highlightedIds: new Set(),
            processingIds: new Set(),
            columns: [
                { key: 'pendiente', title: '🟡 Pendientes' },
                { key: 'preparando', title: '🔥 En preparación' },
                { key: 'listo', title: '✅ Listos' },
                { key: 'entregado', title: '📦 Entregados' },
            ],
            pollHandle: null,
            tickHandle: null,
        };
    },
    computed: {
        normalized() {
            return this.orders.map((order) => {
                const ts = new Date(order.created_at).getTime();
                const elapsedMs = Math.max(this.nowTs - ts, 0);
                return {
                    ...order,
                    estado: String(order.estado || '').toLowerCase(),
                    notas: order.notas || order.note || '',
                    items: order.items || order.detalles || [],
                    _createdTs: ts,
                    _elapsedMs: elapsedMs,
                    _elapsedMin: elapsedMs / 60000,
                    _urgency: (elapsedMs / 60000) + (String(order.estado).toLowerCase() === 'pendiente' ? 2 : 0),
                };
            }).filter((order) => {
                if (order.estado !== 'entregado') return true;
                return (this.nowTs - order._createdTs) < DELIVERED_HIDE_MS;
            });
        },
        grouped() {
            const groups = { pendiente: [], preparando: [], listo: [], entregado: [] };
            this.normalized.forEach((order) => {
                if (groups[order.estado]) groups[order.estado].push(order);
            });

            groups.pendiente.sort((a, b) => b._urgency - a._urgency || b._createdTs - a._createdTs);
            groups.preparando.sort((a, b) => b._urgency - a._urgency || b._createdTs - a._createdTs);
            groups.listo.sort((a, b) => b._createdTs - a._createdTs);
            groups.entregado.sort((a, b) => b._createdTs - a._createdTs);
            return groups;
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
            if (order.estado === 'pendiente') {
                return { label: '🔥 COMENZAR', className: 'action-start', run: () => this.changeStatus(order.id, 'preparando') };
            }
            if (order.estado === 'preparando') {
                return { label: '✅ MARCAR LISTO', className: 'action-ready', run: () => this.changeStatus(order.id, 'listo') };
            }
            if (order.estado === 'listo') {
                return { label: '📦 ENTREGAR', className: 'action-deliver', run: () => this.changeStatus(order.id, 'entregado') };
            }
            return null;
        },
        endpointFor(nextStatus, orderId) {
            if (nextStatus === 'preparando') return `/api/kitchen/orders/${orderId}/start`;
            if (nextStatus === 'listo') return `/api/kitchen/orders/${orderId}/ready`;
            if (nextStatus === 'entregado') return `/api/kitchen/orders/${orderId}/deliver`;
            return '';
        },
        async fetchOrders(isInitial = false) {
            try {
                const beforeIds = new Set(this.orders.map((o) => o.id));
                const response = await fetch('/api/kitchen/orders', {
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' },
                });

                if (!response.ok) throw new Error('status ' + response.status);

                const payload = await response.json();
                const incoming = payload?.data ?? payload ?? [];
                this.orders = Array.isArray(incoming) ? incoming : [];
                this.error = '';

                if (!isInitial) {
                    const newOrders = this.orders.filter((o) => !beforeIds.has(o.id) && String(o.estado).toLowerCase() === 'pendiente');
                    this.handleNewOrders(newOrders);
                }
            } catch (e) {
                this.error = 'No se pudo sincronizar la cocina con /api/kitchen/orders';
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
            setTimeout(() => {
                osc.stop();
                ctx.close();
            }, 120);
        },
        async changeStatus(orderId, nextStatus) {
            if (this.processingIds.has(orderId)) return;
            const updating = new Set(this.processingIds);
            updating.add(orderId);
            this.processingIds = updating;

            const token = document.querySelector('meta[name="csrf-token"]').content;
            const endpoint = this.endpointFor(nextStatus, orderId);

            try {
                const res = await fetch(endpoint, {
                    method: 'PATCH',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                });

                if (!res.ok) throw new Error('status ' + res.status);

                this.orders = this.orders.map((order) => {
                    if (order.id !== orderId) return order;
                    return { ...order, estado: nextStatus };
                });
            } catch (e) {
                this.error = 'No se pudo actualizar el estado del pedido';
            } finally {
                const done = new Set(this.processingIds);
                done.delete(orderId);
                this.processingIds = done;
            }
        },
        toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen?.();
            } else {
                document.exitFullscreen?.();
            }
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
    },
}).mount('#app');
</script>
</body>
</html>
