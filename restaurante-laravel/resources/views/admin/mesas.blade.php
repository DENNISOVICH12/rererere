@extends('layouts.admin')

@section('content')
<div class="mesas-admin" id="mesasAdminApp">
    <header class="mesas-topbar">
        <div>
            <p class="eyebrow">Panel de operaciones</p>
            <h1>Gestión de Mesas</h1>
            <p class="subtitle">Visualiza estado, pedidos activos y administra tus mesas en tiempo real.</p>
        </div>

        <div class="toolbar-actions">
            <div class="search-wrap">
                <span aria-hidden="true">🔎</span>
                <input id="mesaSearch" type="search" placeholder="Buscar mesa por número..." autocomplete="off" />
            </div>
            <select id="mesaStateFilter" aria-label="Filtrar mesas por estado">
                <option value="all">Todas</option>
                <option value="libre">Libres</option>
                <option value="ocupada">Ocupadas</option>
                <option value="en_proceso">En proceso</option>
            </select>
        </div>
    </header>

    <section class="status-overview" id="statusOverview">
        <article class="stat-card">
            <p>Total</p>
            <strong id="statTotal">0</strong>
        </article>
        <article class="stat-card stat-libre">
            <p>Libres</p>
            <strong id="statLibres">0</strong>
        </article>
        <article class="stat-card stat-ocupada">
            <p>Ocupadas</p>
            <strong id="statOcupadas">0</strong>
        </article>
        <article class="stat-card stat-proceso">
            <p>En proceso</p>
            <strong id="statProceso">0</strong>
        </article>
    </section>

    <section class="mesa-grid" id="mesasGrid" aria-live="polite"></section>

    <section id="emptyState" class="empty-state" hidden>
        <div class="empty-icon">🪑</div>
        <h3>No hay mesas para mostrar</h3>
        <p>Prueba cambiando filtros, buscando por número o crea una nueva mesa.</p>
    </section>

    <div id="shimmerGrid" class="mesa-grid shimmer-grid" hidden>
        @for($i = 0; $i < 8; $i++)
            <article class="mesa-card shimmer-card" aria-hidden="true">
                <div class="shimmer-circle"></div>
                <div class="shimmer-line"></div>
                <div class="shimmer-line short"></div>
            </article>
        @endfor
    </div>

    <button class="fab" id="openCreateMesa" aria-label="Crear nueva mesa">＋ Nueva Mesa</button>
</div>

<div class="modal-overlay" id="createMesaModal" hidden>
    <div class="modal-card">
        <div class="modal-head">
            <h3>Nueva mesa</h3>
            <button class="icon-btn" id="closeCreateMesaModal" aria-label="Cerrar">✕</button>
        </div>
        <p class="modal-subtitle">Si no ingresas un número, se asignará automáticamente.</p>

        <form id="createMesaForm" class="modal-form">
            <label for="mesaNumero">Número de mesa</label>
            <input type="number" min="1" id="mesaNumero" name="numero" placeholder="Ej: 12" />

            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" id="cancelCreateMesa">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear mesa</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="mesaDetailModal" hidden>
    <div class="modal-card modal-detail-card">
        <div class="modal-head">
            <h3 id="detailTitle">Mesa</h3>
            <button class="icon-btn" id="closeDetailModal" aria-label="Cerrar">✕</button>
        </div>

        <div id="detailBody" class="mesa-detail-body"></div>

        <div class="modal-actions">
            <button type="button" class="btn btn-danger" id="deleteMesaAction">Eliminar mesa</button>
            <button type="button" class="btn btn-ghost" id="closeDetailAction">Cerrar</button>
        </div>
    </div>
</div>

<div class="toast-stack" id="toastStack" aria-live="assertive" aria-atomic="true"></div>

<style>
    .mesas-admin {
        --bg-primary: #0f172a;
        --bg-card: rgba(15, 23, 42, 0.72);
        --bg-card-hover: rgba(30, 41, 59, 0.92);
        --text-main: #e2e8f0;
        --text-soft: #94a3b8;
        --border-soft: rgba(148, 163, 184, 0.24);
        --accent: #60a5fa;

        color: var(--text-main);
        background:
            radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.22), transparent 35%),
            radial-gradient(circle at 90% 10%, rgba(14, 165, 233, 0.15), transparent 35%),
            var(--bg-primary);
        border-radius: 28px;
        padding: clamp(18px, 2.8vw, 36px);
        border: 1px solid var(--border-soft);
        box-shadow: 0 25px 70px rgba(2, 6, 23, 0.45);
    }

    .mesas-topbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 16px;
        flex-wrap: wrap;
    }

    .eyebrow {
        margin: 0 0 6px;
        text-transform: uppercase;
        letter-spacing: .12em;
        font-size: 11px;
        color: var(--accent);
        font-weight: 700;
    }

    .mesas-topbar h1 { margin: 0; font-size: clamp(24px, 3vw, 34px); }
    .subtitle { margin: 8px 0 0; color: var(--text-soft); }

    .toolbar-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .search-wrap,
    .toolbar-actions select {
        border: 1px solid var(--border-soft);
        background: rgba(15, 23, 42, 0.95);
        color: var(--text-main);
        border-radius: 14px;
        height: 44px;
    }

    .search-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 12px;
    }

    .search-wrap input {
        border: none;
        outline: none;
        background: transparent;
        color: inherit;
        min-width: 220px;
    }

    .toolbar-actions select { padding: 0 14px; }

    .status-overview {
        margin-top: 22px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
    }

    .stat-card {
        background: rgba(15, 23, 42, 0.82);
        border: 1px solid var(--border-soft);
        border-radius: 18px;
        padding: 14px;
    }
    .stat-card p { margin: 0; color: var(--text-soft); font-size: 13px; }
    .stat-card strong { font-size: 24px; margin-top: 6px; display: inline-block; }
    .stat-libre strong { color: #4ade80; }
    .stat-ocupada strong { color: #f87171; }
    .stat-proceso strong { color: #fbbf24; }

    .mesa-grid {
        margin-top: 24px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px;
        min-height: 170px;
    }

    .mesa-card {
        background: var(--bg-card);
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 22px;
        padding: 16px;
        cursor: pointer;
        transition: transform .24s ease, box-shadow .24s ease, background .24s ease;
        position: relative;
        overflow: hidden;
    }

    .mesa-card:hover {
        transform: translateY(-5px);
        background: var(--bg-card-hover);
        box-shadow: 0 18px 45px rgba(2, 6, 23, 0.45);
    }

    .mesa-status-dot {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 11px;
        height: 11px;
        border-radius: 50%;
        box-shadow: 0 0 0 7px rgba(255,255,255,0.04);
    }

    .mesa-circle {
        width: 92px;
        aspect-ratio: 1;
        margin: 8px auto 14px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        font-size: 19px;
        font-weight: 700;
        border: 1px solid rgba(255, 255, 255, 0.18);
        transition: all .3s ease;
    }

    .mesa-state-libre .mesa-circle,
    .mesa-state-libre .mesa-status-dot {
        background: rgba(34, 197, 94, .2);
        color: #86efac;
        box-shadow: 0 0 30px rgba(34, 197, 94, .35);
    }

    .mesa-state-ocupada .mesa-circle,
    .mesa-state-ocupada .mesa-status-dot {
        background: rgba(239, 68, 68, .24);
        color: #fca5a5;
        box-shadow: 0 0 28px rgba(248, 113, 113, .32);
    }

    .mesa-state-en_proceso .mesa-circle,
    .mesa-state-en_proceso .mesa-status-dot {
        background: rgba(250, 204, 21, .22);
        color: #fde68a;
        box-shadow: 0 0 28px rgba(234, 179, 8, .33);
    }

    .mesa-card h3 { margin: 0; text-align: center; font-size: 18px; }
    .mesa-meta { margin-top: 12px; display: grid; gap: 6px; color: var(--text-soft); font-size: 13px; }
    .mesa-badge {
        justify-self: center;
        margin-top: 10px;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .08em;
        border: 1px solid transparent;
    }

    .mesa-state-libre .mesa-badge { color: #86efac; border-color: rgba(34,197,94,.45); }
    .mesa-state-ocupada .mesa-badge { color: #fca5a5; border-color: rgba(248,113,113,.45); }
    .mesa-state-en_proceso .mesa-badge { color: #fde68a; border-color: rgba(234,179,8,.45); }

    .empty-state {
        margin-top: 18px;
        text-align: center;
        padding: 40px 20px;
        border-radius: 20px;
        border: 1px dashed var(--border-soft);
        color: var(--text-soft);
    }
    .empty-icon { font-size: 42px; margin-bottom: 10px; }

    .fab {
        position: fixed;
        bottom: 22px;
        right: 22px;
        border: none;
        border-radius: 16px;
        padding: 13px 18px;
        font-weight: 700;
        background: linear-gradient(120deg, #2563eb, #0ea5e9);
        color: #fff;
        box-shadow: 0 18px 45px rgba(37, 99, 235, .38);
        cursor: pointer;
        z-index: 40;
    }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(2, 6, 23, .65);
        display: grid;
        place-items: center;
        padding: 16px;
        z-index: 90;
    }

    .modal-card {
        width: min(500px, 100%);
        background: #0b1220;
        border-radius: 22px;
        border: 1px solid var(--border-soft);
        padding: 20px;
        box-shadow: 0 24px 60px rgba(2, 6, 23, .6);
        animation: pop .2s ease;
    }

    .modal-head { display: flex; justify-content: space-between; align-items: center; }
    .modal-head h3 { margin: 0; }
    .icon-btn {
        border: 1px solid var(--border-soft);
        border-radius: 10px;
        width: 34px;
        height: 34px;
        color: var(--text-main);
        background: transparent;
        cursor: pointer;
    }

    .modal-subtitle { color: var(--text-soft); margin-top: 8px; }
    .modal-form { display: grid; gap: 10px; margin-top: 12px; }
    .modal-form input {
        border-radius: 12px;
        border: 1px solid var(--border-soft);
        background: rgba(15, 23, 42, .8);
        padding: 10px;
        color: var(--text-main);
    }

    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; }

    .btn {
        border: none;
        border-radius: 12px;
        padding: 10px 14px;
        cursor: pointer;
        color: #fff;
    }
    .btn-primary { background: linear-gradient(120deg, #2563eb, #0ea5e9); }
    .btn-danger { background: linear-gradient(120deg, #dc2626, #ef4444); }
    .btn-ghost { background: rgba(148, 163, 184, .15); border: 1px solid var(--border-soft); }

    .mesa-detail-body {
        margin-top: 14px;
        display: grid;
        gap: 10px;
        color: var(--text-soft);
    }

    .detail-chip {
        display: inline-flex;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        border: 1px solid var(--border-soft);
    }

    .toast-stack {
        position: fixed;
        top: 18px;
        right: 18px;
        display: grid;
        gap: 8px;
        z-index: 120;
    }

    .toast {
        min-width: 240px;
        max-width: 320px;
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid var(--border-soft);
        background: rgba(15, 23, 42, .95);
        box-shadow: 0 16px 40px rgba(2, 6, 23, .5);
        color: var(--text-main);
        animation: pop .2s ease;
    }
    .toast.success { border-color: rgba(34,197,94,.6); }
    .toast.error { border-color: rgba(239,68,68,.6); }

    .shimmer-card { pointer-events: none; }
    .shimmer-circle,
    .shimmer-line {
        position: relative;
        overflow: hidden;
        background: rgba(148, 163, 184, .18);
        border-radius: 12px;
    }
    .shimmer-circle {
        width: 88px;
        aspect-ratio: 1;
        border-radius: 50%;
        margin: 8px auto 16px;
    }
    .shimmer-line { height: 14px; }
    .shimmer-line.short { width: 60%; margin-top: 8px; }

    .shimmer-circle::after,
    .shimmer-line::after {
        content: '';
        position: absolute;
        inset: 0;
        transform: translateX(-100%);
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.25), transparent);
        animation: shimmer 1.2s infinite;
    }

    @keyframes shimmer { to { transform: translateX(100%); } }
    @keyframes pop { from { opacity: .2; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 768px) {
        .fab { right: 14px; bottom: 14px; }
        .search-wrap input { min-width: 160px; }
    }
</style>

<script>
(() => {
    const state = {
        mesas: [],
        filtered: [],
        selectedMesa: null,
        loading: false,
        filter: 'all',
        search: '',
    };

    const el = {
        grid: document.getElementById('mesasGrid'),
        shimmer: document.getElementById('shimmerGrid'),
        empty: document.getElementById('emptyState'),
        search: document.getElementById('mesaSearch'),
        filter: document.getElementById('mesaStateFilter'),
        statTotal: document.getElementById('statTotal'),
        statLibres: document.getElementById('statLibres'),
        statOcupadas: document.getElementById('statOcupadas'),
        statProceso: document.getElementById('statProceso'),
        createModal: document.getElementById('createMesaModal'),
        openCreate: document.getElementById('openCreateMesa'),
        closeCreate: document.getElementById('closeCreateMesaModal'),
        cancelCreate: document.getElementById('cancelCreateMesa'),
        createForm: document.getElementById('createMesaForm'),
        mesaNumero: document.getElementById('mesaNumero'),
        detailModal: document.getElementById('mesaDetailModal'),
        detailTitle: document.getElementById('detailTitle'),
        detailBody: document.getElementById('detailBody'),
        closeDetail: document.getElementById('closeDetailModal'),
        closeDetailAction: document.getElementById('closeDetailAction'),
        deleteMesaAction: document.getElementById('deleteMesaAction'),
        toastStack: document.getElementById('toastStack'),
    };

    const statusLabel = {
        libre: 'Libre',
        ocupada: 'Ocupada',
        en_proceso: 'En proceso',
    };

    const getMesaStatus = (mesa) => {
        const raw = String(mesa?.estado || mesa?.status || 'libre').toLowerCase();
        if (['ocupada', 'occupied'].includes(raw)) return 'ocupada';
        if (['en_proceso', 'en proceso', 'proceso', 'pending'].includes(raw)) return 'en_proceso';
        return 'libre';
    };

    const getActiveOrdersCount = (mesa) => {
        if (typeof mesa.pedidos_activos_count === 'number') return mesa.pedidos_activos_count;
        if (typeof mesa.pedidos_count === 'number') return mesa.pedidos_count;
        if (Array.isArray(mesa.pedidos_activos)) return mesa.pedidos_activos.length;
        if (Array.isArray(mesa.pedidos)) {
            return mesa.pedidos.filter((pedido) => !['entregado', 'cancelado', 'cerrado'].includes((pedido.estado || '').toLowerCase())).length;
        }
        return 0;
    };

    const showToast = (message, type = 'success') => {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        el.toastStack.appendChild(toast);
        setTimeout(() => toast.remove(), 3200);
    };

    const setLoading = (isLoading) => {
        state.loading = isLoading;
        el.shimmer.hidden = !isLoading;
        el.grid.hidden = isLoading;
        if (isLoading) el.empty.hidden = true;
    };

    const applyFilters = () => {
        const query = state.search.trim().toLowerCase();

        state.filtered = [...state.mesas]
            .sort((a, b) => (Number(a.numero ?? a.id) - Number(b.numero ?? b.id)))
            .filter((mesa) => {
                const status = getMesaStatus(mesa);
                const mesaNumero = String(mesa.numero ?? mesa.id ?? '').toLowerCase();
                const byState = state.filter === 'all' || status === state.filter;
                const bySearch = !query || mesaNumero.includes(query);
                return byState && bySearch;
            });

        renderMesas();
        renderStats();
    };

    const mesaCardTemplate = (mesa) => {
        const status = getMesaStatus(mesa);
        const activeOrders = getActiveOrdersCount(mesa);
        const numero = mesa.numero ?? mesa.id ?? '-';

        return `
            <article class="mesa-card mesa-state-${status}" data-mesa-id="${mesa.id}">
                <span class="mesa-status-dot" aria-hidden="true"></span>
                <div class="mesa-circle">${numero}</div>
                <h3>Mesa ${numero}</h3>
                <div class="mesa-meta">
                    <span>Estado: <strong>${statusLabel[status]}</strong></span>
                    <span>Pedidos activos: <strong>${activeOrders}</strong></span>
                </div>
                <span class="mesa-badge">${statusLabel[status]}</span>
            </article>
        `;
    };

    const renderMesas = () => {
        el.grid.replaceChildren(); // limpia completamente para evitar duplicados visuales

        if (!state.filtered.length) {
            el.empty.hidden = false;
            return;
        }

        el.empty.hidden = true;
        const html = state.filtered.map(mesaCardTemplate).join('');
        el.grid.innerHTML = html;
    };

    const renderStats = () => {
        const totals = state.mesas.reduce((acc, mesa) => {
            const status = getMesaStatus(mesa);
            acc.total += 1;
            acc[status] += 1;
            return acc;
        }, { total: 0, libre: 0, ocupada: 0, en_proceso: 0 });

        el.statTotal.textContent = totals.total;
        el.statLibres.textContent = totals.libre;
        el.statOcupadas.textContent = totals.ocupada;
        el.statProceso.textContent = totals.en_proceso;
    };

    const openModal = (modal) => { modal.hidden = false; };
    const closeModal = (modal) => { modal.hidden = true; };

    const loadMesas = async () => {
        setLoading(true);

        try {
            const res = await fetch('/api/mesas', { headers: { Accept: 'application/json' } });
            const payload = await res.json();

            if (!res.ok) throw new Error(payload?.message || 'No se pudo cargar la data de mesas.');

            const mesas = Array.isArray(payload?.data) ? payload.data : [];
            const unique = new Map();
            mesas.forEach((mesa) => {
                if (mesa?.id != null) unique.set(mesa.id, mesa);
            });

            state.mesas = Array.from(unique.values());
            applyFilters();
        } catch (error) {
            el.grid.replaceChildren();
            el.empty.hidden = false;
            showToast(error.message || 'No fue posible cargar las mesas.', 'error');
        } finally {
            setLoading(false);
        }
    };

    const openMesaDetail = (mesaId) => {
        const mesa = state.mesas.find((item) => String(item.id) === String(mesaId));
        if (!mesa) return;

        state.selectedMesa = mesa;
        const status = getMesaStatus(mesa);
        const activeOrders = getActiveOrdersCount(mesa);
        const numero = mesa.numero ?? mesa.id;

        el.detailTitle.textContent = `Mesa ${numero}`;
        el.detailBody.innerHTML = `
            <div><span class="detail-chip">${statusLabel[status]}</span></div>
            <p><strong>Pedidos activos:</strong> ${activeOrders}</p>
            <p><strong>ID interno:</strong> ${mesa.id}</p>
            <p>Acciones rápidas: puedes eliminar la mesa o cerrar esta ventana.</p>
        `;

        openModal(el.detailModal);
    };

    const createMesa = async (event) => {
        event.preventDefault();
        const numero = el.mesaNumero.value ? Number(el.mesaNumero.value) : null;
        const body = numero ? { numero } : {};

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/api/mesas', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify(body),
            });

            const payload = await res.json();
            if (!res.ok) throw new Error(payload?.message || 'No se pudo crear la mesa.');

            closeModal(el.createModal);
            el.createForm.reset();
            showToast('Mesa creada correctamente.');
            await loadMesas();
        } catch (error) {
            showToast(error.message || 'No se pudo crear la mesa.', 'error');
        }
    };

    const deleteSelectedMesa = async () => {
        if (!state.selectedMesa) return;

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/api/mesas/${state.selectedMesa.id}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                },
            });

            const payload = await res.json();
            if (!res.ok) throw new Error(payload?.message || 'No se pudo eliminar la mesa.');

            closeModal(el.detailModal);
            state.selectedMesa = null;
            showToast('Mesa eliminada exitosamente.');
            await loadMesas();
        } catch (error) {
            showToast(error.message || 'No se pudo eliminar la mesa.', 'error');
        }
    };

    el.search.addEventListener('input', (event) => {
        state.search = event.target.value;
        applyFilters();
    });

    el.filter.addEventListener('change', (event) => {
        state.filter = event.target.value;
        applyFilters();
    });

    el.grid.addEventListener('click', (event) => {
        const card = event.target.closest('[data-mesa-id]');
        if (!card) return;
        openMesaDetail(card.dataset.mesaId);
    });

    el.createForm.addEventListener('submit', createMesa);

    el.openCreate.addEventListener('click', () => {
        openModal(el.createModal);
        el.mesaNumero.focus();
    });

    [el.closeCreate, el.cancelCreate].forEach((button) => {
        button.addEventListener('click', () => {
            closeModal(el.createModal);
            el.createForm.reset();
        });
    });

    [el.closeDetail, el.closeDetailAction].forEach((button) => {
        button.addEventListener('click', () => closeModal(el.detailModal));
    });

    el.createModal.addEventListener('click', (event) => {
        if (event.target === el.createModal) closeModal(el.createModal);
    });
    el.detailModal.addEventListener('click', (event) => {
        if (event.target === el.detailModal) closeModal(el.detailModal);
    });

    el.deleteMesaAction.addEventListener('click', deleteSelectedMesa);

    loadMesas();
})();
</script>
@endsection
