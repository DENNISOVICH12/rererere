<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo · Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                radial-gradient(circle at 0 0, rgba(156, 32, 48, 0.16), transparent 35%),
                radial-gradient(circle at 100% 0, rgba(255, 215, 170, 0.08), transparent 24%),
                linear-gradient(160deg, var(--bg-soft, #0f172a), var(--bg));
            color: var(--text);
        }
        .main { padding: 28px; }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .title h1 { margin: 0; font-size: 1.8rem; }
        .title p { margin: 6px 0 0; color: var(--muted); }
        .filters {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            background: var(--panel);
            padding: 10px;
            border-radius: 14px;
            border: 1px solid var(--border);
        }
        .btn, .date-input {
            border: 1px solid var(--border);
            background: #0d1320;
            color: var(--text);
            padding: 8px 10px;
            border-radius: 10px;
            font-size: .85rem;
        }
        .btn.active { background: rgba(156,32,48,.28); border-color: rgba(156,32,48,.65); }
        .btn.primary { background: linear-gradient(135deg, #b12a3b, #9c2030); border: 0; }
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .kpi {
            background: linear-gradient(140deg, var(--panel), var(--panel-soft));
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px;
        }
        .kpi .label { color: var(--muted); font-size: .82rem; }
        .kpi .value { font-size: 1.4rem; font-weight: 700; margin-top: 6px; }
        .layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 14px;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 14px;
        }
        .panel h3 { margin: 0 0 12px; font-size: 1rem; }
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .ops-list, .insight-list { display: grid; gap: 10px; }
        .pill {
            border-radius: 10px;
            padding: 10px;
            border: 1px solid var(--border);
            background: rgba(255,255,255,.02);
            font-size: .87rem;
        }
        .insight-danger { border-color: rgba(251,113,133,.45); }
        .insight-warning { border-color: rgba(250,204,21,.45); }
        .insight-success { border-color: rgba(74,222,128,.45); }
        table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        th, td { padding: 10px 8px; border-bottom: 1px solid rgba(255,255,255,.06); text-align: left; }
        th { color: var(--muted); font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; }
        .status { padding: 4px 8px; border-radius: 999px; font-size: .74rem; font-weight: 600; }
        .s-pendiente { background: rgba(251,113,133,.25); color: #fecdd3; }
        .s-preparando, .s-retenido { background: rgba(250,204,21,.22); color: #fef08a; }
        .s-listo, .s-entregado { background: rgba(74,222,128,.22); color: #bbf7d0; }
        .s-cancelado { background: rgba(248,113,113,.22); color: #fecaca; }
        .link-btn { color: #ffd7aa; cursor: pointer; text-decoration: underline; }
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(2,6,16,.8);
            display: none;
            place-items: center;
            z-index: 30;
            padding: 18px;
        }
        .modal.open { display: grid; }
        .modal-card {
            width: min(700px, 100%);
            background: #0f1523;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px;
        }
        @media (max-width: 1180px) {
            .layout, .charts-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
@include('layouts.partials.admin-sidebar')

<div class="main content" id="dashboardApp">
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
            <input class="date-input" type="date" id="endDate" value="{{ $initialRange['end_date'] }}">
            <button class="btn primary" id="applyCustom">Aplicar</button>
        </div>
    </div>

    <div class="kpi-grid" id="kpis"></div>

    <div class="layout">
        <div>
            <div class="panel" style="margin-bottom:12px;">
                <h3>Visual analítico</h3>
                <div class="charts-grid">
                    <div><canvas id="revenueChart" height="120"></canvas></div>
                    <div><canvas id="ordersChart" height="120"></canvas></div>
                    <div><canvas id="productsChart" height="120"></canvas></div>
                    <div><canvas id="hoursChart" height="120"></canvas></div>
                </div>
            </div>

            <div class="panel">
                <h3>Pedidos recientes</h3>
                <table>
                    <thead><tr><th>ID</th><th>Mesa</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Tiempo</th><th>Detalle</th></tr></thead>
                    <tbody id="recentOrders"></tbody>
                </table>
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

<div class="modal" id="orderModal">
    <div class="modal-card">
        <h3 id="modalTitle">Detalle pedido</h3>
        <div id="modalBody"></div>
        <button class="btn" id="closeModal" style="margin-top:10px;">Cerrar</button>
    </div>
</div>

<script>
(() => {
    const initialData = @json($initialData);
    const initialRange = @json($initialRange);
    const state = {
        preset: initialRange.preset || 'today',
        startDate: initialRange.start_date,
        endDate: initialRange.end_date,
        data: initialData,
        charts: {},
    };

    const formatCurrency = (v) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(v || 0));
    const formatNumber = (v) => new Intl.NumberFormat('es-CO').format(Number(v || 0));
    const elapsed = (iso) => {
        if (!iso) return '-';
        const min = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 60000));
        if (min < 60) return `${min} min`;
        const h = Math.floor(min / 60);
        const m = min % 60;
        return `${h}h ${m}m`;
    };

    function activatePreset() {
        document.querySelectorAll('.js-preset').forEach((btn) => btn.classList.toggle('active', btn.dataset.preset === state.preset));
    }

    function renderKpis(kpis) {
        const cards = [
            ['💵 Ingresos totales', formatCurrency(kpis.revenue)],
            ['📦 Número de pedidos', formatNumber(kpis.orders)],
            ['🧾 Ticket promedio', formatCurrency(kpis.average_ticket)],
            ['🍽️ Mesas atendidas', formatNumber(kpis.tables_served)],
            ['⏱️ Tiempo promedio por pedido', `${kpis.average_order_minutes} min`],
            ['❌ Cancelados / retenidos', formatNumber(kpis.cancelled_or_retained)],
        ];
        document.getElementById('kpis').innerHTML = cards.map(([label, value]) => `
            <div class="kpi"><div class="label">${label}</div><div class="value">${value}</div></div>
        `).join('');
    }

    function buildOrUpdateChart(id, config) {
        if (state.charts[id]) {
            state.charts[id].data = config.data;
            state.charts[id].update();
            return;
        }
        state.charts[id] = new Chart(document.getElementById(id), config);
    }

    function renderCharts(charts) {
        buildOrUpdateChart('revenueChart', {
            type: 'line',
            data: { labels: charts.days, datasets: [{ label: 'Ingresos por día', data: charts.daily_revenue, borderColor: '#7c8cff', backgroundColor: 'rgba(124,140,255,.2)', tension: .35, fill: true }] },
            options: { plugins: { legend: { labels: { color: '#ccd5e6' } } }, scales: { x: { ticks: { color: '#8fa0c2' } }, y: { ticks: { color: '#8fa0c2' } } } }
        });

        buildOrUpdateChart('ordersChart', {
            type: 'bar',
            data: { labels: charts.days, datasets: [{ label: 'Pedidos por día', data: charts.daily_orders, backgroundColor: 'rgba(76,201,240,.6)' }] },
            options: { plugins: { legend: { labels: { color: '#ccd5e6' } } }, scales: { x: { ticks: { color: '#8fa0c2' } }, y: { ticks: { color: '#8fa0c2' } } } }
        });

        buildOrUpdateChart('productsChart', {
            type: 'bar',
            data: { labels: charts.top_products.map(p => p.nombre), datasets: [{ label: 'Top productos', data: charts.top_products.map(p => p.cantidad), backgroundColor: 'rgba(74,222,128,.6)' }] },
            options: { indexAxis: 'y', plugins: { legend: { labels: { color: '#ccd5e6' } } }, scales: { x: { ticks: { color: '#8fa0c2' } }, y: { ticks: { color: '#8fa0c2' } } } }
        });

        buildOrUpdateChart('hoursChart', {
            type: 'bar',
            data: { labels: charts.peak_hours.map(h => `${String(h.hour).padStart(2, '0')}:00`), datasets: [{ label: 'Horas pico', data: charts.peak_hours.map(h => h.orders), backgroundColor: 'rgba(250,204,21,.62)' }] },
            options: { plugins: { legend: { labels: { color: '#ccd5e6' } } }, scales: { x: { ticks: { color: '#8fa0c2' } }, y: { ticks: { color: '#8fa0c2' } } } }
        });
    }

    function renderInsights(insights) {
        document.getElementById('insights').innerHTML = insights.map(i => `<div class="pill insight-${i.type}">${i.text}</div>`).join('');
    }

    function renderOperations(ops) {
        const waiters = ops.top_waiters.length
            ? ops.top_waiters.map(w => `<li>${w.nombre}: ${w.pedidos ?? 'sin trazabilidad de pedidos'}</li>`).join('')
            : '<li>Sin datos de meseros para el rango actual.</li>';

        const usedTables = ops.most_used_tables.length
            ? ops.most_used_tables.map(t => `<li>Mesa ${t.mesa_numero ?? t.mesa_id ?? '-'}: ${t.pedidos} pedidos</li>`).join('')
            : '<li>Sin mesas utilizadas.</li>';

        const revenueTables = ops.top_revenue_tables.length
            ? ops.top_revenue_tables.map(t => `<li>Mesa ${t.mesa_numero ?? t.mesa_id ?? '-'}: ${formatCurrency(t.ingresos)}</li>`).join('')
            : '<li>Sin ingresos por mesa.</li>';

        document.getElementById('operations').innerHTML = `
            <div class="pill"><b>Mesas más utilizadas</b><ul>${usedTables}</ul></div>
            <div class="pill"><b>Mesas con más ingresos</b><ul>${revenueTables}</ul></div>
            <div class="pill"><b>Meseros con más pedidos</b><ul>${waiters}</ul></div>
            <div class="pill"><b>Tiempo promedio de cocina</b><div style="margin-top:6px; font-size:1.05rem;">${ops.avg_kitchen_minutes} min</div></div>
        `;
    }

    function statusClass(status) {
        return `s-${String(status || '').toLowerCase().replace(/\s+/g, '-')}`;
    }

    function renderRecentOrders(orders) {
        document.getElementById('recentOrders').innerHTML = orders.map(o => `
            <tr>
                <td>#${o.id}</td>
                <td>${o.mesa_numero ?? o.mesa_id ?? '-'}</td>
                <td>${o.cliente}</td>
                <td>${formatCurrency(o.total)}</td>
                <td><span class="status ${statusClass(o.estado)}">${o.estado}</span></td>
                <td>${elapsed(o.created_at)}</td>
                <td><span class="link-btn" data-order='${JSON.stringify(o).replaceAll("'", "&#39;")}'>Ver</span></td>
            </tr>
        `).join('');

        document.querySelectorAll('.link-btn').forEach((el) => {
            el.addEventListener('click', () => {
                const order = JSON.parse(el.dataset.order.replaceAll('&#39;', "'"));
                document.getElementById('modalTitle').textContent = `Pedido #${order.id} · Mesa ${order.mesa_numero ?? order.mesa_id ?? '-'}`;
                document.getElementById('modalBody').innerHTML = order.detalles?.length
                    ? `<ul>${order.detalles.map(d => `<li>${d.cantidad}x ${d.producto} (${formatCurrency(d.importe)})</li>`).join('')}</ul>`
                    : '<p>Pedido sin detalle disponible.</p>';
                document.getElementById('orderModal').classList.add('open');
            });
        });
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
            params.set('end_date', state.endDate);
        }
        const response = await fetch(`{{ route('admin.dashboard.data') }}?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        state.data = await response.json();
        renderAll(state.data);
    }

    document.querySelectorAll('.js-preset').forEach((btn) => {
        btn.addEventListener('click', async () => {
            state.preset = btn.dataset.preset;
            activatePreset();
            await fetchData();
        });
    });

    document.getElementById('applyCustom').addEventListener('click', async () => {
        state.preset = 'custom';
        state.startDate = document.getElementById('startDate').value;
        state.endDate = document.getElementById('endDate').value;
        activatePreset();
        await fetchData();
    });

    document.getElementById('closeModal').addEventListener('click', () => document.getElementById('orderModal').classList.remove('open'));
    document.getElementById('orderModal').addEventListener('click', (e) => {
        if (e.target.id === 'orderModal') e.currentTarget.classList.remove('open');
    });

    activatePreset();
    renderAll(state.data);
    setInterval(fetchData, 60000);
})();
</script>
</body>
</html>
