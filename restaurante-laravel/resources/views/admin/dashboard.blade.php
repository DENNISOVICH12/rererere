<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panel Administrativo · Premium</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg: #020617;
            --panel: rgba(15, 23, 42, .85);
            --panel-soft: rgba(15, 23, 42, .72);
            --border: rgba(255,255,255,.08);
            --text: #e2e8f0;
            --muted: #94a3b8;
            --primary: #9c2030;
            --success: #4ade80;
            --warning: #f59e0b;
            --danger: #fb7185;
        }
        * { box-sizing: border-box; }
        body {
            background:
                radial-gradient(circle at 0 0, rgba(156,32,48,.16), transparent 35%),
                radial-gradient(circle at 100% 0, rgba(255,215,170,.08), transparent 24%),
                linear-gradient(160deg, #0f172a, var(--bg));
            color: var(--text);
        }
        .main { padding: 28px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
        .title h1 { margin: 0; font-size: 1.8rem; }
        .title p  { margin: 6px 0 0; color: var(--muted); }
        .filters  { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; background: var(--panel); padding: 10px; border-radius: 14px; border: 1px solid var(--border); }
        .btn, .date-input { border: 1px solid var(--border); background: #0d1320; color: var(--text); padding: 8px 10px; border-radius: 10px; font-size: .85rem; cursor: pointer; }
        .btn.active  { background: rgba(156,32,48,.28); border-color: rgba(156,32,48,.65); }
        .btn.primary { background: linear-gradient(135deg,#b12a3b,#9c2030); border: 0; }
        .btn.danger  { background: #7f1d1d; color: #fecaca; border: 0; font-size: 12px; padding: 5px 12px; border-radius: 8px; }
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 16px; }
        .kpi { background: linear-gradient(140deg, var(--panel), var(--panel-soft)); border: 1px solid var(--border); border-radius: 14px; padding: 14px; }
        .kpi .label { color: var(--muted); font-size: .82rem; }
        .kpi .value { font-size: 1.4rem; font-weight: 700; margin-top: 6px; }
        .layout { display: grid; grid-template-columns: 2fr 1fr; gap: 14px; }
        .panel { background: var(--panel); border: 1px solid var(--border); border-radius: 16px; padding: 14px; }
        .panel h3 { margin: 0 0 12px; font-size: 1rem; }
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .ops-list, .insight-list { display: grid; gap: 10px; }
        .pill { border-radius: 10px; padding: 10px; border: 1px solid var(--border); background: rgba(255,255,255,.02); font-size: .87rem; }
        .insight-danger  { border-color: rgba(251,113,133,.45); }
        .insight-warning { border-color: rgba(250,204,21,.45); }
        .insight-success { border-color: rgba(74,222,128,.45); }
        table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        th, td { padding: 10px 8px; border-bottom: 1px solid rgba(255,255,255,.06); text-align: left; }
        th { color: var(--muted); font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; }
        .status { padding: 4px 8px; border-radius: 999px; font-size: .74rem; font-weight: 600; }
        .s-pendiente                     { background: rgba(251,113,133,.25); color: #fecdd3; }
        .s-preparando, .s-retenido       { background: rgba(250,204,21,.22);  color: #fef08a; }
        .s-listo, .s-entregado           { background: rgba(74,222,128,.22);  color: #bbf7d0; }
        .s-cancelado                     { background: rgba(248,113,113,.22); color: #fecaca; }
        .s-facturado                     { background: rgba(99,102,241,.22);  color: #c7d2fe; }
        .link-btn { color: #ffd7aa; cursor: pointer; text-decoration: underline; }
        /* ── Modal base ── */
        .modal { position: fixed; inset: 0; background: rgba(2,6,16,.8); display: none; place-items: center; z-index: 30; padding: 18px; }
        .modal.open { display: grid; }
        .modal-card { width: min(700px,100%); background: #0f1523; border: 1px solid var(--border); border-radius: 16px; padding: 16px; }
        /* ── Modal ajuste comprobante ── */
        .ajuste-modal { width: min(560px,95vw); background: #0f172a; border: 1px solid #1e293b; border-radius: 16px; padding: 28px; color: #e2e8f0; box-shadow: 0 20px 50px rgba(0,0,0,.5); }
        .ajuste-modal h3 { margin: 0 0 6px; font-size: 1.1rem; color: #f8fafc; }
        .ajuste-modal p.sub { margin: 0 0 20px; color: #64748b; font-size: .88rem; }
        .ajuste-item-list { list-style: none; padding: 0; margin: 0 0 20px; display: grid; gap: 8px; }
        .ajuste-item-list li { display: flex; justify-content: space-between; align-items: center; border: 1px solid #1e293b; border-radius: 10px; padding: 10px 14px; background: rgba(15,23,42,.6); }
        .ajuste-item-list li.anulado { opacity: .4; text-decoration: line-through; }
        .ajuste-item-list li .precio { font-weight: 700; color: #f1f5f9; }
        .ajuste-justificacion { width: 100%; box-sizing: border-box; background: #1e293b; border: 1px solid #334155; border-radius: 10px; color: #e2e8f0; padding: 10px 14px; font-size: 14px; resize: vertical; min-height: 80px; margin-bottom: 16px; }
        .ajuste-justificacion:focus { outline: none; border-color: #3b82f6; }
        .ajuste-error { color: #f87171; font-size: 13px; margin-bottom: 12px; display: none; }
        .ajuste-btns { display: flex; justify-content: flex-end; gap: 10px; }
        .btn-cancelar-ajuste { background: #1e293b; color: #94a3b8; border: 0; border-radius: 10px; padding: 9px 18px; cursor: pointer; }
        .btn-confirmar-ajuste { background: #dc2626; color: #fff; border: 0; border-radius: 10px; padding: 9px 18px; font-weight: 700; cursor: pointer; transition: background 150ms; }
        .btn-confirmar-ajuste:disabled { opacity: .5; cursor: not-allowed; }
        .btn-confirmar-ajuste:hover:not(:disabled) { background: #b91c1c; }
        /* ── Historial ajustes ── */
        .seccion-ajustes { margin-top: 32px; }
        .seccion-ajustes h2 { font-size: 1rem; font-weight: 700; color: #f1f5f9; margin: 0 0 14px; display: flex; align-items: center; gap: 8px; }
        .badge-ajuste { background: #7f1d1d; color: #fca5a5; font-size: 11px; border-radius: 999px; padding: 2px 8px; }
        .tabla-ajustes { width: 100%; border-collapse: collapse; font-size: 13px; }
        .tabla-ajustes th { text-align: left; padding: 8px 12px; background: #1e293b; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: .04em; }
        .tabla-ajustes td { padding: 10px 12px; border-bottom: 1px solid #1e293b; color: #cbd5e1; }
        .tabla-ajustes tr:hover td { background: rgba(30,41,59,.5); }
        .monto-anulado { color: #f87171; font-weight: 700; }
        .justif-text { max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #94a3b8; }
        .form-section { display: none; }
        @media (max-width: 1180px) { .layout, .charts-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
@include('layouts.partials.admin-sidebar')

<div class="main content" id="dashboardApp">

    {{-- ── Topbar ── --}}
    <div class="topbar">
        <div class="title">
            <h1>Dashboard Estratégico</h1>
            <p id="rangeLabel">{{ $initialRange['label'] ?? 'Hoy' }}</p>
        </div>
        <div class="filters">
            <button class="btn js-preset" data-preset="today">Hoy</button>
            <button class="btn js-preset" data-preset="yesterday">Ayer</button>
            <button class="btn js-preset" data-preset="last_7_days">Últimos 7 días</button>
            <button class="btn js-preset" data-preset="last_30_days">Últimos 30 días</button>
            <input class="date-input" type="date" id="startDate" value="{{ $initialRange['start_date'] }}">
            <input class="date-input" type="date" id="endDate"   value="{{ $initialRange['end_date'] }}">
            <button class="btn primary" id="applyCustom">Aplicar</button>
            <button class="btn" id="btnBackup" onclick="abrirModalBackup()"
               style="background:#065f46;border:1px solid rgba(52,211,153,.3);color:#6ee7b7;display:inline-flex;align-items:center;gap:6px;">
               💾 Backup
            </button>
            <button class="btn" type="button" data-logout>Cerrar sesión</button>
        </div>
    </div>

    {{-- ── KPIs ── --}}
    <div class="kpi-grid" id="kpis"></div>

    {{-- ── Layout principal ── --}}
    <div class="layout">
        <div>
            <div class="panel" style="margin-bottom:12px;">
                <h3>Visual analítico</h3>
                <div class="charts-grid">
                    <div><canvas id="revenueChart"  height="120"></canvas></div>
                    <div><canvas id="ordersChart"   height="120"></canvas></div>
                    <div><canvas id="productsChart" height="120"></canvas></div>
                    <div><canvas id="hoursChart"    height="120"></canvas></div>
                </div>
            </div>

            <div class="panel" id="pedidosPanel">
                <span id="urlPedidos" data-url="{{ route('admin.dashboard.pedidos') }}" style="display:none;"></span>
                <!-- Cabecera -->
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
                    <h3 style="margin:0;">Pedidos <span id="pedidosCount" style="font-size:.8rem;color:var(--muted);font-weight:400;"></span></h3>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input type="text" id="pedidosBuscar" placeholder="Buscar cliente, mesa, ID..."
                            style="background:#0d1320;border:1px solid var(--border);color:var(--text);border-radius:9px;padding:6px 10px;font-size:.82rem;width:200px;"
                            oninput="filtrarPedidosTabla()">
                        <button class="btn" id="btnVerTodos" onclick="toggleVerTodos()" style="font-size:.82rem;padding:6px 12px;">
                            Ver todos
                        </button>
                    </div>
                </div>

                <!-- Tabla -->
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th><th>Mesa</th><th>Cliente</th><th>Mesero</th>
                                <th>Total</th><th>Estado</th><th>Tiempo</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="recentOrders"></tbody>
                    </table>
                </div>

                <!-- "Ver menos" al fondo cuando está expandido -->
                <div id="verMenosWrap" style="display:none;text-align:center;margin-top:10px;">
                    <button class="btn" onclick="toggleVerTodos()" style="font-size:.82rem;padding:6px 16px;">↑ Ver menos</button>
                </div>
            </div>

            {{-- ── Historial de modificaciones de comprobantes ── --}}
            <div class="seccion-ajustes panel" style="margin-top:14px;">
                <h2>
                    🔧 Historial de modificaciones
                    <span class="badge-ajuste" id="badgeAjustes">0</span>
                </h2>
                <div style="overflow-x:auto">
                    <table class="tabla-ajustes">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Comprobante</th>
                                <th>Mesa</th>
                                <th>Ítem anulado</th>
                                <th>Monto anulado</th>
                                <th>Total anterior</th>
                                <th>Total nuevo</th>
                                <th>Admin</th>
                                <th>Justificación</th>
                            </tr>
                        </thead>
                        <tbody id="tablaAjustesBody">
                            <tr><td colspan="9" style="text-align:center;color:#475569;padding:24px">Cargando historial...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div>
            <div class="panel" style="margin-bottom:12px;">
                <h3>Insights automáticos</h3>
                <div class="insight-list" id="insights"></div>
            </div>
            <div class="panel">
                <h3>Análisis operativo</h3>
                <div class="ops-list" id="operations"></div>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal detalle pedido ── --}}
<div class="modal" id="orderModal">
    <div class="modal-card">
        <h3 id="modalTitle">Detalle pedido</h3>
        <div id="modalBody"></div>
        <button class="btn" id="closeModal" style="margin-top:10px;">Cerrar</button>
    </div>
</div>

{{-- ── Modal editar pedido (admin) ── --}}
<div class="modal" id="editOrderModal">
    <div class="ajuste-modal" style="width:min(680px,96vw);max-height:90vh;overflow-y:auto;">

        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px;">
            <div>
                <h3 id="editModalTitle" style="margin:0 0 4px;">Editar pedido</h3>
                <p class="sub" id="editModalSub" style="margin:0;"></p>
            </div>
            <button onclick="cerrarEditModal()" style="background:none;border:none;color:#64748b;font-size:1.2rem;cursor:pointer;padding:4px;">✕</button>
        </div>

        <!-- Aviso entregado -->
        <div id="editWarningEntregado" style="display:none;background:rgba(251,191,36,.07);border:1px solid rgba(251,191,36,.25);border-radius:9px;padding:10px 14px;font-size:12px;color:#fbbf24;margin:10px 0;">
            ⚠️ Estás editando un pedido <strong>entregado</strong>. Usá esto para corregir la cuenta si salió un plato de más o uno dañado. El pedido se mantiene en estado <strong>entregado</strong>.
        </div>

        <!-- Tabla de ítems -->
        <table style="width:100%;border-collapse:collapse;font-size:13px;margin:14px 0 0;">
            <thead>
                <tr>
                    <th style="text-align:left;color:#64748b;font-size:11px;text-transform:uppercase;padding:0 6px 8px;width:40%">Producto</th>
                    <th style="text-align:center;color:#64748b;font-size:11px;text-transform:uppercase;padding:0 6px 8px;width:12%">Cant.</th>
                    <th style="text-align:left;color:#64748b;font-size:11px;text-transform:uppercase;padding:0 6px 8px;">Nota</th>
                    <th style="width:34px;"></th>
                </tr>
            </thead>
            <tbody id="editItemsBody"></tbody>
        </table>

        <!-- Agregar ítem -->
        <div style="display:grid;grid-template-columns:1fr 60px 1fr auto;gap:6px;align-items:center;margin-top:8px;padding-top:8px;border-top:1px solid #1a2640;">
            <select id="editNewProducto" style="background:#0f172a;border:1px solid #1f2d45;color:#e2e8f0;border-radius:8px;padding:7px 9px;font-size:13px;">
                <option value="">Agregar producto...</option>
            </select>
            <input type="number" id="editNewCantidad" value="1" min="1" style="background:#0f172a;border:1px solid #1f2d45;color:#e2e8f0;border-radius:8px;padding:7px 8px;font-size:13px;text-align:center;">
            <input type="text" id="editNewNota" placeholder="Nota..." style="background:#0f172a;border:1px solid #1f2d45;color:#e2e8f0;border-radius:8px;padding:7px 9px;font-size:13px;">
            <button onclick="editAddItem()" style="background:#1e3a5f;border:1px solid rgba(96,165,250,.3);color:#e2e8f0;border-radius:8px;padding:7px 12px;font-size:12px;cursor:pointer;white-space:nowrap;">+ Agregar</button>
        </div>

        <!-- Justificación -->
        <div style="background:rgba(251,191,36,.05);border:1px solid rgba(251,191,36,.25);border-radius:10px;padding:12px 14px;margin-top:16px;">
            <div style="font-size:12px;font-weight:600;color:#fbbf24;margin-bottom:6px;">
                📝 Justificación del cambio <span style="color:#f87171;font-weight:400;font-size:11px;">* obligatoria</span>
            </div>
            <textarea id="editJustificacion"
                style="width:100%;background:#0f172a;border:1px solid #1f2d45;color:#e2e8f0;border-radius:8px;padding:9px 11px;font-size:13px;resize:vertical;box-sizing:border-box;"
                rows="3" placeholder="Ej: El cliente solicitó cambiar el plato por alergia..." maxlength="500"></textarea>
            <div style="font-size:11px;color:#475569;margin-top:4px;">Mín. 5 caracteres · Queda registrado en auditoría.</div>
        </div>

        <!-- Error -->
        <div id="editError" style="display:none;color:#f87171;font-size:13px;margin-top:8px;padding:8px 12px;background:rgba(239,68,68,.1);border-radius:8px;"></div>

        <!-- Botones -->
        <div style="display:flex;gap:10px;margin-top:14px;">
            <button id="editSaveBtn" onclick="guardarEditPedido()"
                style="background:#2563eb;color:#fff;border:none;border-radius:10px;padding:9px 20px;font-weight:600;font-size:13px;cursor:pointer;">
                💾 Guardar cambios
            </button>
            <button onclick="cerrarEditModal()"
                style="background:transparent;border:1px solid #1f2d45;color:#94a3b8;border-radius:10px;padding:9px 16px;font-size:13px;cursor:pointer;">
                Cancelar
            </button>
        </div>
    </div>
</div>

{{-- ── Modal anular ítem de comprobante ── --}}
<div class="modal" id="ajusteOverlay">
    <div class="ajuste-modal">
        <h3>⚠️ Anular ítem del comprobante</h3>
        <p class="sub">Selecciona el ítem a anular. Esta acción queda registrada en el log de auditoría.</p>

        <ul class="ajuste-item-list" id="ajusteItemList"></ul>

        <div class="form-section" id="ajusteFormSection">
            <p style="font-size:13px;color:#94a3b8;margin:0 0 8px">
                Ítem seleccionado: <strong id="ajusteItemNombre" style="color:#f1f5f9"></strong>
                — Monto a anular: <strong id="ajusteItemMonto" style="color:#f87171"></strong>
            </p>
            <textarea
                class="ajuste-justificacion"
                id="ajusteJustificacion"
                placeholder="Escribe la justificación (mínimo 10 caracteres)..."
            ></textarea>
            <p class="ajuste-error" id="ajusteError"></p>
        </div>

        <div class="ajuste-btns">
            <button class="btn-cancelar-ajuste" onclick="cerrarAjusteModal()">Cancelar</button>
            <button class="btn-confirmar-ajuste" id="btnConfirmarAjuste" onclick="confirmarAnulacion()" disabled>
                Confirmar anulación
            </button>
        </div>
    </div>
</div>

<script>
/* ── Variables globales compartidas entre IIFE y funciones globales ── */
let pedidosModoExpandido  = false;
let todosPedidosCache     = [];
let menuItemsCache        = [];
let pedidosFiltroActual   = '';
const PEDIDOS_RECIENTES_N = 5;
let state                 = {};   // se inicializa dentro del IIFE

const fmt  = (v) => new Intl.NumberFormat('es-CO', { style:'currency', currency:'COP', maximumFractionDigits:0 }).format(Number(v||0));
const fmtN = (v) => new Intl.NumberFormat('es-CO').format(Number(v||0));
const elapsed = (iso) => {
    if (!iso) return '-';
    const min = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 60000));
    if (min < 60) return `${min} min`;
    return `${Math.floor(min/60)}h ${min%60}m`;
};

function statusClass(s) { return `s-${String(s||'').toLowerCase().replace(/\s+/g,'-')}`; }

function buildPedidoRow(o) {
    let editBtn;
    if (o.estado === 'entregado') {
        editBtn = `<button class="btn danger" style="font-size:11px;padding:4px 10px;" onclick="abrirEditModal(${o.id})">✏️ Editar</button>`;
    } else if (['facturado', 'cancelado', 'pendiente', 'preparando', 'listo', 'retenido'].includes(o.estado)) {
        editBtn = `<button class="btn" disabled title="Solo se pueden editar pedidos entregados"
            style="font-size:11px;padding:4px 10px;opacity:0.35;cursor:not-allowed;background:#1e293b;border:1px solid #1f2d45;color:#64748b;">
            ✏️ Editar
        </button>`;
    } else {
        editBtn = `<span style="color:#334155;font-size:11px;">—</span>`;
    }

    const anularBtn = (o.estado === 'facturado' && o.comprobante_token)
        ? `<button class="btn danger" style="font-size:11px;padding:4px 9px;"
               onclick="abrirAjusteModal('${o.comprobante_token}', ${JSON.stringify(JSON.stringify(o.comprobante_detalle || [])).replaceAll("'","&#39;")})">
               ⚠️ Anular ítem
           </button>`
        : '';

    return `<tr>
        <td>#${o.id}</td>
        <td>${o.mesa_numero ?? o.mesa_id ?? '-'}</td>
        <td>${o.cliente}</td>
        <td>${o.mesero ?? '—'}</td>
        <td>${fmt(o.total)}</td>
        <td><span class="status ${statusClass(o.estado)}">${o.estado}</span></td>
        <td>${elapsed(o.created_at)}</td>
        <td style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">${editBtn}${anularBtn}</td>
    </tr>`;
}

function filtrarPedidosTabla() {
    pedidosFiltroActual = document.getElementById('pedidosBuscar').value.trim().toLowerCase();
    const fuente = pedidosModoExpandido ? todosPedidosCache : (state.data?.recent_orders || []);
    renderPedidosEnTabla(fuente);
}

function renderPedidosEnTabla(lista) {
    const filtrada = pedidosFiltroActual
        ? lista.filter(o => [o.id, o.mesa_numero, o.cliente, o.mesero, o.estado].join(' ').toLowerCase().includes(pedidosFiltroActual))
        : lista;
    document.getElementById('recentOrders').innerHTML = filtrada.length
        ? filtrada.map(buildPedidoRow).join('')
        : `<tr><td colspan="8" style="text-align:center;color:#475569;padding:20px;">Sin pedidos para este filtro.</td></tr>`;
    document.getElementById('pedidosCount').textContent =
        pedidosModoExpandido ? `(${filtrada.length} pedidos)` : `(${filtrada.length} recientes)`;
}

(() => {
    /* ══════════════════════════════════════════
       DASHBOARD — estado y utilidades
    ══════════════════════════════════════════ */
    const initialData  = @json($initialData);
    const initialRange = @json($initialRange);

    state = {
        preset:    initialRange.preset || 'today',
        startDate: initialRange.start_date,
        endDate:   initialRange.end_date,
        data:      initialData,
        charts:    {},
    };

    function activatePreset() {
        document.querySelectorAll('.js-preset').forEach(btn =>
            btn.classList.toggle('active', btn.dataset.preset === state.preset)
        );
    }

    function renderKpis(kpis) {
        const cards = [
            ['💵 Ingresos totales',            fmt(kpis.revenue)],
            ['📦 Número de pedidos',            fmtN(kpis.orders)],
            ['🧾 Ticket promedio',              fmt(kpis.average_ticket)],
            ['🍽️ Mesas atendidas',             fmtN(kpis.tables_served)],
            ['⏱️ Tiempo promedio por pedido',  `${kpis.average_order_minutes} min`],
            ['❌ Cancelados / retenidos',       fmtN(kpis.cancelled_or_retained)],
        ];
        document.getElementById('kpis').innerHTML = cards.map(([label, value]) =>
            `<div class="kpi"><div class="label">${label}</div><div class="value">${value}</div></div>`
        ).join('');
    }

    function buildOrUpdateChart(id, config) {
        if (state.charts[id]) { state.charts[id].data = config.data; state.charts[id].update(); return; }
        state.charts[id] = new Chart(document.getElementById(id), config);
    }

    function renderCharts(c) {
        const tick = { color:'#8fa0c2' };
        const leg  = { labels:{ color:'#ccd5e6' } };

        buildOrUpdateChart('revenueChart', { type:'line', data:{ labels:c.days, datasets:[{ label:'Ingresos por día', data:c.daily_revenue, borderColor:'#7c8cff', backgroundColor:'rgba(124,140,255,.2)', tension:.35, fill:true }] }, options:{ plugins:{ legend:leg }, scales:{ x:{ ticks:tick }, y:{ ticks:tick } } } });
        buildOrUpdateChart('ordersChart',  { type:'bar',  data:{ labels:c.days, datasets:[{ label:'Pedidos por día',  data:c.daily_orders,   backgroundColor:'rgba(76,201,240,.6)' }] }, options:{ plugins:{ legend:leg }, scales:{ x:{ ticks:tick }, y:{ ticks:tick } } } });
        buildOrUpdateChart('productsChart',{ type:'bar',  data:{ labels:c.top_products.map(p=>p.nombre), datasets:[{ label:'Top productos', data:c.top_products.map(p=>p.cantidad), backgroundColor:'rgba(74,222,128,.6)' }] }, options:{ indexAxis:'y', plugins:{ legend:leg }, scales:{ x:{ ticks:tick }, y:{ ticks:tick } } } });
        buildOrUpdateChart('hoursChart',   { type:'bar',  data:{ labels:c.peak_hours.map(h=>`${String(h.hour).padStart(2,'0')}:00`), datasets:[{ label:'Horas pico', data:c.peak_hours.map(h=>h.orders), backgroundColor:'rgba(250,204,21,.62)' }] }, options:{ plugins:{ legend:leg }, scales:{ x:{ ticks:tick }, y:{ ticks:tick } } } });
    }

    function renderInsights(insights) {
        document.getElementById('insights').innerHTML =
            insights.map(i => `<div class="pill insight-${i.type}">${i.text}</div>`).join('');
    }

    function renderOperations(ops) {
        const li = (arr, fn) => arr.length ? arr.map(fn).join('') : '<li>Sin datos.</li>';

        document.getElementById('operations').innerHTML = `
            <div class="pill"><b>Mesas más utilizadas</b><ul>
                ${li(ops.most_used_tables,  t => `<li>Mesa ${t.mesa_numero ?? t.mesa_id ?? '-'}: ${t.pedidos} pedidos</li>`)}
            </ul></div>
            <div class="pill"><b>Mesas con más ingresos</b><ul>
                ${li(ops.top_revenue_tables, t => `<li>Mesa ${t.mesa_numero ?? t.mesa_id ?? '-'}: ${fmt(t.ingresos)}</li>`)}
            </ul></div>
            <div class="pill"><b>Meseros con más pedidos</b><ul>
                ${li(ops.top_waiters, w => `<li>${w.nombre}: ${w.pedidos ?? 'sin trazabilidad'}</li>`)}
            </ul></div>
            <div class="pill"><b>Tiempo promedio de cocina</b>
                <div style="margin-top:6px;font-size:1.05rem;">${ops.avg_kitchen_minutes} min</div>
            </div>`;
    }

    /* ── Pedidos: renderizado (funciones globales definidas antes del IIFE) ── */

    function renderRecentOrders(orders) {
        if (!pedidosModoExpandido) {
            renderPedidosEnTabla(orders.slice(0, PEDIDOS_RECIENTES_N));
            document.getElementById('verMenosWrap').style.display = 'none';
            document.getElementById('btnVerTodos').textContent = 'Ver todos';
        }
    }

    function renderAll(payload) {
        document.getElementById('rangeLabel').textContent = `${payload.meta.start_date} → ${payload.meta.end_date}`;
        renderKpis(payload.kpis);
        renderCharts(payload.charts);
        renderInsights(payload.insights);
        renderOperations(payload.operations);
        renderRecentOrders(payload.recent_orders);
    }

    async function fetchData() {
        const params = new URLSearchParams({ preset: state.preset });
        if (state.preset === 'custom') {
            params.set('start_date', state.startDate);
            params.set('end_date',   state.endDate);
        }
        const res   = await fetch(`{{ route('admin.dashboard.data') }}?${params}`, { headers:{ 'X-Requested-With':'XMLHttpRequest' } });
        state.data  = await res.json();
        renderAll(state.data);
    }

    document.querySelectorAll('.js-preset').forEach(btn => {
        btn.addEventListener('click', async () => { state.preset = btn.dataset.preset; activatePreset(); await fetchData(); });
    });
    document.getElementById('applyCustom').addEventListener('click', async () => {
        state.preset    = 'custom';
        state.startDate = document.getElementById('startDate').value;
        state.endDate   = document.getElementById('endDate').value;
        activatePreset();
        await fetchData();
    });
    document.getElementById('closeModal').addEventListener('click', () => document.getElementById('orderModal').classList.remove('open'));
    document.getElementById('orderModal').addEventListener('click', e => { if (e.target.id === 'orderModal') e.currentTarget.classList.remove('open'); });

    activatePreset();
    renderAll(state.data);
    setInterval(fetchData, 8000);
})();

async function toggleVerTodos() {
    if (pedidosModoExpandido) {
        pedidosModoExpandido = false;
        document.getElementById('btnVerTodos').textContent = 'Ver todos';
        document.getElementById('verMenosWrap').style.display = 'none';
        renderPedidosEnTabla((state.data?.recent_orders || []).slice(0, PEDIDOS_RECIENTES_N));
        return;
    }
    document.getElementById('btnVerTodos').textContent = 'Cargando...';
    try {
        const params = new URLSearchParams({ preset: state.preset });
        if (state.preset === 'custom') {
            params.set('start_date', state.startDate);
            params.set('end_date',   state.endDate);
        }
        const res  = await fetch(document.getElementById('urlPedidos').dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        todosPedidosCache = data.pedidos    || [];
        menuItemsCache    = data.menu_items || [];
        poblarSelectMenuItems();
        pedidosModoExpandido = true;
        document.getElementById('btnVerTodos').textContent = 'Ver menos';
        document.getElementById('verMenosWrap').style.display = 'block';
        renderPedidosEnTabla(todosPedidosCache);
    } catch(e) {
        document.getElementById('btnVerTodos').textContent = 'Ver todos';
        alert('Error cargando pedidos.');
    }
}


/* ══════════════════════════════════════════
   AJUSTES DE COMPROBANTE
══════════════════════════════════════════ */
let ajusteToken     = null;
let ajusteItemIndex = null;
let ajusteItemsData = [];

function abrirAjusteModal(token, detalle) {
    ajusteToken     = token;
    ajusteItemIndex = null;
    ajusteItemsData = typeof detalle === 'string' ? JSON.parse(detalle) : detalle;

    const list = document.getElementById('ajusteItemList');
    list.innerHTML = '';

    ajusteItemsData.forEach((item, idx) => {
        const li       = document.createElement('li');
        const anulado  = !!item.anulado;
        if (anulado) li.classList.add('anulado');

        const cantidad  = item.cantidad || 1;
        const precio    = item.precio_unitario || item.precio || 0;
        const subtotal  = cantidad * precio;

        li.innerHTML = `
            <span>${item.nombre || 'Ítem'} x${cantidad}</span>
            <span class="precio">$${Number(subtotal).toLocaleString('es-CO')}</span>
            ${anulado
                ? '<span style="font-size:11px;color:#64748b">Anulado</span>'
                : `<button class="btn danger" onclick="seleccionarItemAjuste(${idx})">Anular</button>`
            }`;
        list.appendChild(li);
    });

    document.getElementById('ajusteFormSection').style.display = 'none';
    document.getElementById('ajusteJustificacion').value        = '';
    document.getElementById('ajusteError').style.display        = 'none';
    document.getElementById('btnConfirmarAjuste').disabled      = true;
    document.getElementById('ajusteOverlay').classList.add('open');
}

function seleccionarItemAjuste(idx) {
    ajusteItemIndex = idx;

    Array.from(document.getElementById('ajusteItemList').children).forEach((li, i) => {
        li.style.border = i === idx ? '1px solid #3b82f6' : '1px solid #1e293b';
    });

    const item     = ajusteItemsData[idx];
    const cantidad = item.cantidad || 1;
    const precio   = item.precio_unitario || item.precio || 0;
    const monto    = cantidad * precio;

    document.getElementById('ajusteItemNombre').textContent = item.nombre || 'Ítem';
    document.getElementById('ajusteItemMonto').textContent  = `$${Number(monto).toLocaleString('es-CO')}`;
    document.getElementById('ajusteFormSection').style.display = 'block';
    document.getElementById('ajusteJustificacion').focus();

    document.getElementById('ajusteJustificacion').oninput = () => {
        const val = document.getElementById('ajusteJustificacion').value.trim();
        document.getElementById('btnConfirmarAjuste').disabled = val.length < 10;
    };
}

function cerrarAjusteModal() {
    document.getElementById('ajusteOverlay').classList.remove('open');
    ajusteToken = null; ajusteItemIndex = null; ajusteItemsData = [];
}

async function confirmarAnulacion() {
    const justificacion = document.getElementById('ajusteJustificacion').value.trim();
    const errorEl       = document.getElementById('ajusteError');
    const btn           = document.getElementById('btnConfirmarAjuste');

    if (justificacion.length < 10) {
        errorEl.textContent = 'La justificación debe tener al menos 10 caracteres.';
        errorEl.style.display = 'block';
        return;
    }

    btn.disabled    = true;
    btn.textContent = 'Procesando...';
    errorEl.style.display = 'none';

    try {
        const res = await fetch(`/admin/comprobantes/${ajusteToken}/anular-item`, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ item_index: ajusteItemIndex, justificacion }),
        });

        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Error al procesar la anulación.');

        cerrarAjusteModal();
        cargarHistorialAjustes();
        alert(`✅ Ítem anulado. Nuevo total: $${Number(data.total_nuevo).toLocaleString('es-CO')}`);

    } catch (err) {
        errorEl.textContent   = err.message;
        errorEl.style.display = 'block';
        btn.disabled    = false;
        btn.textContent = 'Confirmar anulación';
    }
}

async function cargarHistorialAjustes() {
    try {
        const res  = await fetch('/admin/comprobantes/ajustes/historial', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        const rows = data.data || [];

        document.getElementById('badgeAjustes').textContent = rows.length;

        const tbody = document.getElementById('tablaAjustesBody');
        if (!rows.length) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;color:#475569;padding:24px">No hay modificaciones registradas.</td></tr>`;
            return;
        }

        const fmt2 = v => Number(v).toLocaleString('es-CO');

        tbody.innerHTML = rows.map(a => {
            const esEdicion = a.tipo === 'edicion_pedido';
            const referencia = esEdicion
                ? `<span style="color:#94a3b8;">Pedido #${a.pedido_id ?? '—'}</span>`
                : `<a href="/comprobante/${a.comprobante?.token}" target="_blank"
                      style="color:#60a5fa;text-decoration:none">#${a.comprobante_id}</a>`;

            const mesa = esEdicion
                ? (a.pedido?.mesa_numero ?? '—')
                : (a.comprobante?.mesa_numero ?? '—');

            const tipoBadge = esEdicion
                ? `<span style="font-size:10px;background:rgba(96,165,250,.15);color:#60a5fa;border-radius:4px;padding:2px 6px;">edición</span>`
                : `<span style="font-size:10px;background:rgba(239,68,68,.12);color:#f87171;border-radius:4px;padding:2px 6px;">anulación</span>`;

            const montoColor = a.monto_anulado > 0 ? 'color:#f87171' : 'color:#34d399';

            return `<tr>
                <td>${new Date(a.created_at).toLocaleString('es-CO')}</td>
                <td>${referencia} ${tipoBadge}</td>
                <td>${mesa}</td>
                <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${a.item_nombre}">
                    ${a.item_nombre}
                </td>
                <td style="${montoColor}">-$${fmt2(a.monto_anulado)}</td>
                <td>$${fmt2(a.total_anterior)}</td>
                <td style="color:#34d399;font-weight:700">$${fmt2(a.total_nuevo)}</td>
                <td>${a.admin?.nombre ?? ''} ${a.admin?.apellido ?? ''}</td>
                <td class="justif-text" title="${a.justificacion}">${a.justificacion}</td>
            </tr>`;
        }).join('');

    } catch (err) {
        console.error('Error cargando ajustes:', err);
    }
}

// Cerrar modal ajuste al hacer clic fuera
document.getElementById('ajusteOverlay').addEventListener('click', e => {
    if (e.target.id === 'ajusteOverlay') cerrarAjusteModal();
});

// Cargar historial al iniciar
cargarHistorialAjustes();

/* ══════════════════════════════════════════
   MODAL BACKUP
══════════════════════════════════════════ */
let backupTipo = null;

function abrirModalBackup() {
    backupTipo = null;
    document.getElementById('backupOptBD').style.borderColor   = '#1f2d45';
    document.getElementById('backupOptTodo').style.borderColor = '#1f2d45';
    document.getElementById('backupConfirmBtn').style.opacity        = '.4';
    document.getElementById('backupConfirmBtn').style.pointerEvents  = 'none';
    document.getElementById('backupProgress').style.display = 'none';
    const modal = document.getElementById('backupModal');
    modal.style.display = 'flex';
}

function cerrarModalBackup() {
    document.getElementById('backupModal').style.display = 'none';
}

function seleccionarBackup(tipo) {
    backupTipo = tipo;
    document.getElementById('backupOptBD').style.borderColor   = tipo === 'bd'   ? '#34d399' : '#1f2d45';
    document.getElementById('backupOptTodo').style.borderColor = tipo === 'todo' ? '#34d399' : '#1f2d45';
    document.getElementById('backupConfirmBtn').style.opacity       = '1';
    document.getElementById('backupConfirmBtn').style.pointerEvents = 'auto';
}

async function ejecutarBackup() {
    if (!backupTipo) return;

    const btn      = document.getElementById('backupConfirmBtn');
    const progress = document.getElementById('backupProgress');
    const progText = document.getElementById('backupProgressText');

    btn.style.display = 'none';
    progress.style.display = 'block';
    progText.textContent = backupTipo === 'bd'
        ? 'Generando backup de la base de datos...'
        : 'Generando backup completo, esto puede tardar unos segundos...';

    const url = backupTipo === 'bd'
        ? '{{ route('admin.backup', ['tipo' => 'bd']) }}'
        : '{{ route('admin.backup', ['tipo' => 'todo']) }}';

    try {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });

        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw new Error(data.error || `Error ${res.status}`);
        }

        // Obtener nombre del archivo desde el header
        const disposition = res.headers.get('content-disposition') || '';
        const match = disposition.match(/filename="?([^"]+)"?/);
        const filename = match ? match[1] : `backup_${backupTipo}.${backupTipo === 'bd' ? 'sql' : 'zip'}`;

        // Descargar el blob
        const blob = await res.blob();
        const link = document.createElement('a');
        link.href  = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
        URL.revokeObjectURL(link.href);

        cerrarModalBackup();
    } catch (err) {
        progText.textContent = '❌ ' + err.message;
        btn.style.display = 'block';
    }
}

// Cerrar al hacer click fuera — esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    const backupModal = document.getElementById('backupModal');
    if (backupModal) {
        backupModal.addEventListener('click', e => {
            if (e.target.id === 'backupModal') cerrarModalBackup();
        });
    }

    const editModal = document.getElementById('editOrderModal');
    if (editModal) {
        editModal.addEventListener('click', e => {
            if (e.target.id === 'editOrderModal') cerrarEditModal();
        });
    }
});


let editPedidoId   = null;
let editPedidoData = null;

function poblarSelectMenuItems() {
    const sel = document.getElementById('editNewProducto');
    // Agrupar por categoría
    const grupos = {};
    menuItemsCache.forEach(m => {
        const cat = m.categoria || 'Sin categoría';
        if (!grupos[cat]) grupos[cat] = [];
        grupos[cat].push(m);
    });
    let html = '<option value="">Agregar producto...</option>';
    Object.entries(grupos).forEach(([cat, catItems]) => {
        html += `<optgroup label="${cat}">`;
        catItems.forEach(m => {
            html += `<option value="${m.id}">${m.nombre} — ${fmt(m.precio)}</option>`;
        });
        html += '</optgroup>';
    });
    sel.innerHTML = html;
}

function abrirEditModal(pedidoId) {
    // Buscar en caché expandido primero, luego en recientes
    const fromCache   = todosPedidosCache.find(p => p.id === pedidoId);
    const fromRecent  = (state.data?.recent_orders || []).find(p => p.id === pedidoId);
    const pedidoBase  = fromCache || fromRecent;

    if (!pedidoBase) { alert('Pedido no encontrado.'); return; }

    // Necesitamos items + menuItemsCache para abrir el editor
    const tieneItems  = Array.isArray(pedidoBase.items) && pedidoBase.items.length > 0;
    const tieneMenu   = menuItemsCache.length > 0;

    if (tieneItems && tieneMenu) {
        _abrirEditModalConDatos(pedidoBase);
        return;
    }

    // Faltan datos — cargamos el endpoint completo
    const params = new URLSearchParams({ preset: state.preset });
    if (state.preset === 'custom') {
        params.set('start_date', state.startDate);
        params.set('end_date',   state.endDate);
    }

    fetch(document.getElementById('urlPedidos').dataset.url + '?' + params, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        todosPedidosCache = data.pedidos    || [];
        menuItemsCache    = data.menu_items || [];
        poblarSelectMenuItems();
        const full = todosPedidosCache.find(p => p.id === pedidoId);
        if (full) _abrirEditModalConDatos(full);
        else alert('No se pudo cargar el pedido.');
    })
    .catch(() => alert('Error cargando el pedido.'));
}

function _abrirEditModalConDatos(pedido) {
    editPedidoId   = pedido.id;
    // Normalizar: algunos pedidos traen 'detalles' (de recent_orders), otros 'items'
    const itemsRaw = pedido.items || (pedido.detalles || []).map(d => ({
        menu_item_id: d.menu_item_id || null,
        nombre:       d.producto     || d.nombre || 'Ítem',
        cantidad:     d.cantidad     || 1,
        precio:       d.precio       || 0,
        importe:      d.importe      || 0,
        nota:         d.nota         || '',
    }));
    editPedidoData = JSON.parse(JSON.stringify({ ...pedido, items: itemsRaw }));

    document.getElementById('editModalTitle').textContent = `Editar pedido #${pedido.id}`;
    document.getElementById('editModalSub').textContent   =
        `Mesa ${pedido.mesa_numero} · ${pedido.cliente} · Estado: ${pedido.estado}`;

    document.getElementById('editWarningEntregado').style.display = 'block';

    document.getElementById('editJustificacion').value = '';
    document.getElementById('editError').style.display = 'none';

    renderEditItems();
    document.getElementById('editOrderModal').classList.add('open');
}

function renderEditItems() {
    const tbody = document.getElementById('editItemsBody');
    const items = editPedidoData?.items || [];

    if (!items.length) {
        tbody.innerHTML = `<tr><td colspan="4" style="color:#475569;padding:16px;text-align:center;">Sin ítems. Agrega productos abajo.</td></tr>`;
        return;
    }

    tbody.innerHTML = items.map((item, idx) => {
        // Build select options
        const grupos = {};
        menuItemsCache.forEach(m => {
            const cat = m.categoria || 'Sin categoría';
            if (!grupos[cat]) grupos[cat] = [];
            grupos[cat].push(m);
        });
        let opts = '';
        Object.entries(grupos).forEach(([cat, catItems]) => {
            opts += `<optgroup label="${cat}">`;
            catItems.forEach(m => {
                opts += `<option value="${m.id}" ${m.id == item.menu_item_id ? 'selected' : ''}>${m.nombre} — ${fmt(m.precio)}</option>`;
            });
            opts += '</optgroup>';
        });

        return `<tr>
            <td>
                <select onchange="editUpdateItem(${idx},'menu_item_id',this.value)"
                    style="width:100%;background:#0f172a;border:1px solid #1f2d45;color:#e2e8f0;border-radius:8px;padding:7px 9px;font-size:13px;">
                    ${opts}
                </select>
            </td>
            <td style="text-align:center;">
                <input type="number" value="${item.cantidad}" min="1"
                    onchange="editUpdateItem(${idx},'cantidad',this.value)"
                    style="width:56px;background:#0f172a;border:1px solid #1f2d45;color:#e2e8f0;border-radius:8px;padding:7px 6px;font-size:13px;text-align:center;">
            </td>
            <td>
                <input type="text" value="${item.nota || ''}" placeholder="Nota..."
                    onchange="editUpdateItem(${idx},'nota',this.value)"
                    style="width:100%;background:#0f172a;border:1px solid #1f2d45;color:#e2e8f0;border-radius:8px;padding:7px 9px;font-size:13px;">
            </td>
            <td>
                <button onclick="editRemoveItem(${idx})"
                    style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#f87171;border-radius:7px;padding:5px 9px;cursor:pointer;">✕</button>
            </td>
        </tr>`;
    }).join('');
}

function editUpdateItem(idx, field, value) {
    if (field === 'cantidad') editPedidoData.items[idx][field] = parseInt(value) || 1;
    else editPedidoData.items[idx][field] = value;
}

function editRemoveItem(idx) {
    if (editPedidoData.items.length <= 1) { alert('El pedido debe tener al menos 1 ítem.'); return; }
    editPedidoData.items.splice(idx, 1);
    renderEditItems();
}

function editAddItem() {
    const menuItemId = parseInt(document.getElementById('editNewProducto').value);
    if (!menuItemId) { alert('Selecciona un producto.'); return; }
    const menuItem = menuItemsCache.find(m => m.id === menuItemId);
    if (!menuItem) return;

    editPedidoData.items.push({
        menu_item_id: menuItem.id,
        nombre: menuItem.nombre,
        cantidad: parseInt(document.getElementById('editNewCantidad').value) || 1,
        precio:   parseFloat(menuItem.precio),
        nota:     document.getElementById('editNewNota').value,
    });

    document.getElementById('editNewProducto').value = '';
    document.getElementById('editNewCantidad').value = 1;
    document.getElementById('editNewNota').value     = '';
    renderEditItems();
}

async function guardarEditPedido() {
    const justificacion = document.getElementById('editJustificacion').value.trim();
    const errorEl = document.getElementById('editError');

    if (justificacion.length < 5) {
        errorEl.textContent = 'La justificación debe tener al menos 5 caracteres.';
        errorEl.style.display = 'block';
        return;
    }
    if (!editPedidoData.items.length) {
        errorEl.textContent = 'El pedido debe tener al menos 1 ítem.';
        errorEl.style.display = 'block';
        return;
    }

    const btn = document.getElementById('editSaveBtn');
    btn.disabled    = true;
    btn.textContent = 'Guardando...';
    errorEl.style.display = 'none';

    try {
        const res = await fetch(`/admin/dashboard/pedidos/${editPedidoId}/editar`, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                items: editPedidoData.items.map(i => ({
                    menu_item_id: parseInt(i.menu_item_id),
                    cantidad:     parseInt(i.cantidad) || 1,
                    nota:         i.nota || null,
                })),
                justificacion,
            }),
        });

        // Si no es JSON (ej: error 419 CSRF, 404) mostrar mensaje claro
        const contentType = res.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            throw new Error(`Error HTTP ${res.status} — recarga la página e intenta de nuevo.`);
        }

        const data = await res.json();
        if (!res.ok) throw new Error(data.error || data.message || `Error ${res.status}`);
        if (!res.ok) throw new Error(data.error || data.message || 'Error al guardar.');

        // Actualizar caché expandido
        const idx = todosPedidosCache.findIndex(p => p.id === editPedidoId);
        if (idx !== -1) {
            todosPedidosCache[idx].total  = data.pedido.total;
            todosPedidosCache[idx].estado = data.pedido.estado;
            todosPedidosCache[idx].items  = data.pedido.items;
        }

        // Actualizar también recent_orders si el pedido está ahí
        if (state.data?.recent_orders) {
            const ri = state.data.recent_orders.findIndex(p => p.id === editPedidoId);
            if (ri !== -1) {
                state.data.recent_orders[ri].total  = data.pedido.total;
                state.data.recent_orders[ri].estado = data.pedido.estado;
            }
        }

        cerrarEditModal();
        renderPedidosEnTabla(todosPedidosCache);
        await fetchData(); // Refrescar KPIs y charts

    } catch (err) {
        errorEl.textContent   = err.message;
        errorEl.style.display = 'block';
    } finally {
        btn.disabled    = false;
        btn.textContent = '💾 Guardar cambios';
    }
}

function cerrarEditModal() {
    document.getElementById('editOrderModal').classList.remove('open');
    editPedidoId   = null;
    editPedidoData = null;
}
</script>

{{-- ── Modal Backup ── --}}
<div id="backupModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:9999;display:none;align-items:center;justify-content:center;">
    <div style="background:#111827;border:1px solid #1f2d45;border-radius:16px;padding:28px 32px;width:min(480px,94vw);box-shadow:0 24px 60px rgba(0,0,0,.5);">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <h3 style="margin:0;font-size:1.1rem;">💾 Generar copia de seguridad</h3>
            <button onclick="cerrarModalBackup()" style="background:none;border:none;color:#64748b;font-size:1.3rem;cursor:pointer;">✕</button>
        </div>
        <p style="color:#64748b;font-size:13px;margin:0 0 20px;">Elige qué quieres incluir en la copia:</p>

        <!-- Opción 1: Solo BD -->
        <div id="backupOptBD" onclick="seleccionarBackup('bd')"
            style="border:2px solid #1f2d45;border-radius:12px;padding:16px 18px;cursor:pointer;margin-bottom:10px;transition:border-color 150ms;">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:1.6rem;">🗄️</span>
                <div>
                    <div style="font-weight:600;font-size:14px;color:#e2e8f0;">Solo base de datos</div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">Descarga un archivo <code style="color:#93c5fd;">.sql</code> con todos los datos: pedidos, clientes, menú, comprobantes, etc.</div>
                </div>
            </div>
        </div>

        <!-- Opción 2: Todo -->
        <div id="backupOptTodo" onclick="seleccionarBackup('todo')"
            style="border:2px solid #1f2d45;border-radius:12px;padding:16px 18px;cursor:pointer;margin-bottom:20px;transition:border-color 150ms;">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:1.6rem;">📦</span>
                <div>
                    <div style="font-weight:600;font-size:14px;color:#e2e8f0;">Proyecto completo</div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">Descarga un <code style="color:#93c5fd;">.zip</code> con el código fuente + la base de datos incluida. <span style="color:#fbbf24;">Puede tardar unos segundos.</span></div>
                </div>
            </div>
        </div>

        <!-- Estado de progreso -->
        <div id="backupProgress" style="display:none;text-align:center;padding:10px 0;color:#94a3b8;font-size:13px;">
            <span id="backupProgressText">Generando backup...</span>
        </div>

        <div style="display:flex;gap:10px;">
            <button id="backupConfirmBtn" onclick="ejecutarBackup()"
                style="flex:1;background:#065f46;border:1px solid rgba(52,211,153,.3);color:#6ee7b7;border-radius:10px;padding:10px;font-size:14px;font-weight:600;cursor:pointer;opacity:.4;pointer-events:none;">
                Descargar
            </button>
            <button onclick="cerrarModalBackup()"
                style="background:transparent;border:1px solid #1f2d45;color:#94a3b8;border-radius:10px;padding:10px 18px;font-size:14px;cursor:pointer;">
                Cancelar
            </button>
        </div>
    </div>
</div>
<script src="{{ asset('js/logout.js') }}"></script>
</body>
</html>